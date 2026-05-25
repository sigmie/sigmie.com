<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\Http\Controllers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Files\Base64Audio;
use Laravel\Ai\Files\Base64Document;
use Laravel\Ai\Files\Base64Image;
use Laravel\Ai\Responses\StreamedAgentResponse;
use Laravel\Ai\Responses\StreamableAgentResponse;
use Sigmie\AgentTools\Laravel\ActiveConversationStore;
use Sigmie\AgentTools\Laravel\AgentDebugRepository;
use Sigmie\AgentTools\Laravel\AgentTurnDebugCollector;
use Sigmie\AgentTools\Laravel\SigmieAgent;

/**
 * Streams agent output using the Vercel AI UI message stream protocol (Laravel AI SDK).
 */
class AgentChatController extends Controller
{
    public function __construct(
        private readonly AgentDebugRepository $debugRepo,
        private readonly ActiveConversationStore $activeStore,
    ) {}

    public function __invoke(Request $request): StreamableAgentResponse
    {
        $user = $request->user();
        if (! $user instanceof Authenticatable) {
            abort(401, 'Unauthenticated.');
        }

        /** @var AgentTurnDebugCollector $debug */
        $debug = app(AgentTurnDebugCollector::class);

        $agentClass = $request->boolean('stateless')
            ? (string) config('agent-tools.onboarding_agent_class', config('agent-tools.agent_class', SigmieAgent::class))
            : (string) config('agent-tools.agent_class', SigmieAgent::class);
        /** @var Agent $agent */
        $agent = app($agentClass);

        $sessionKey = (string) config('agent-tools.session_conversation_key', 'agent_conversation_id');

        // Prefer explicit conversation id from the client body. Streamed responses often persist session
        // after the long-lived connection ends; the next POST can run before the session cookie updates,
        // so session-only continuation creates a new conversation every message.
        $resolvedId = $this->resolveAgentConversationId($request, $user, $sessionKey);

        if ($resolvedId !== null) {
            $agent->continue($resolvedId, $user);
            $this->activeStore->set($user, $resolvedId);
            $request->session()->put($sessionKey, $resolvedId);
        } else {
            $agent->forUser($user);
        }

        $debug->setSystemPrompt((string) $agent->instructions());

        $messages = $request->input('messages', []);
        if (! is_array($messages)) {
            abort(422, 'Invalid messages payload.');
        }

        $prompt = $this->lastUserTextFromMessages($messages);
        $attachments = $this->attachmentsFromLastUserMessage($messages);

        $defaultModel = is_string($agentClass) && is_callable([$agentClass, 'defaultModel'])
            ? $agentClass::defaultModel()
            : (string) config('agent-tools.agent_default_model', '');

        $model = $request->input('model', $defaultModel);
        if (! is_string($model) || $model === '') {
            $model = null;
        }

        $defaultProvider = (string) config('ai.default', 'anthropic');
        $provider = Lab::tryFrom((string) $request->input('provider', $defaultProvider)) ?? Lab::tryFrom($defaultProvider) ?? Lab::Anthropic;

        if ($prompt === '' && $attachments === []) {
            abort(422, 'No user message or attachments found.');
        }

        if ($prompt === '' && $attachments !== []) {
            $prompt = '(User attached file(s) without a text message.)';
        }

        return $agent->stream($prompt, $attachments, provider: $provider, model: $model)
            ->usingVercelDataProtocol()
            ->then(function (StreamedAgentResponse $response) use ($request, $user, $debug, $prompt, $model, $sessionKey) {
                rescue(function () use ($response, $request, $user, $debug, $prompt, $model, $sessionKey) {
                    if ($response->conversationId !== null && $response->conversationId !== '' && ! $request->boolean('stateless')) {
                        $this->activeStore->set($user, $response->conversationId);
                        $request->session()->put($sessionKey, $response->conversationId);
                        $request->session()->save();
                    }

                    $turn = array_merge([
                        'at' => now()->toIso8601String(),
                        'user_prompt' => $prompt,
                        'model' => $model,
                    ], $debug->toArray());

                    $this->debugRepo->appendTurn($request, $turn, $response->conversationId);
                });
            });
    }

    /**
     * @param  array<int, mixed>  $messages
     * @return list<Base64Image|Base64Audio|Base64Document>
     */
    private function attachmentsFromLastUserMessage(array $messages): array
    {
        $lastUser = null;
        foreach (array_reverse($messages) as $message) {
            if (! is_array($message)) {
                continue;
            }
            if (($message['role'] ?? '') === 'user') {
                $lastUser = $message;
                break;
            }
        }
        if ($lastUser === null || ! isset($lastUser['parts']) || ! is_array($lastUser['parts'])) {
            return [];
        }

        $out = [];
        foreach ($lastUser['parts'] as $part) {
            if (! is_array($part) || ($part['type'] ?? '') !== 'file') {
                continue;
            }
            $url = isset($part['url']) ? (string) $part['url'] : '';
            if ($url === '' || ! str_starts_with($url, 'data:')) {
                continue;
            }
            $parsed = $this->parseDataUrl($url);
            if ($parsed === null) {
                continue;
            }
            $mime = isset($part['mediaType']) ? (string) $part['mediaType'] : $parsed['mime'];
            $filename = isset($part['filename']) ? (string) $part['filename'] : 'attachment';

            $rawLen = strlen(base64_decode($parsed['base64'], true) ?: '');
            if ($rawLen > 10 * 1024 * 1024) {
                abort(413, 'An attachment exceeds the maximum size (10MB).');
            }

            $file = $this->makeAttachmentFromBase64($parsed['base64'], $mime, $filename);
            if ($file !== null) {
                $out[] = $file;
            }
        }

        if (count($out) > 10) {
            abort(422, 'A maximum of 10 attachments is allowed per message.');
        }

        return $out;
    }

    /**
     * @return array{mime: string, base64: string}|null
     */
    private function parseDataUrl(string $url): ?array
    {
        if (! preg_match('#^data:([^;]+);base64,(.+)$#s', $url, $m)) {
            return null;
        }

        return ['mime' => trim($m[1]), 'base64' => $m[2]];
    }

    private function makeAttachmentFromBase64(string $base64, string $mime, string $filename): Base64Image|Base64Audio|Base64Document|null
    {
        $mime = strtolower($mime);

        if (str_starts_with($mime, 'image/') && in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)) {
            $img = new Base64Image($base64, $mime);

            return $img->as($filename);
        }

        if (str_starts_with($mime, 'audio/') && in_array($mime, [
            'audio/mpeg', 'audio/wav', 'audio/x-wav', 'audio/aac', 'audio/opus',
        ], true)) {
            $audio = new Base64Audio($base64, $mime);

            return $audio->as($filename);
        }

        if (in_array($mime, [
            'application/pdf',
            'text/plain',
            'text/csv',
            'text/markdown',
            'application/json',
        ], true) || str_starts_with($mime, 'text/')) {
            $doc = new Base64Document($base64, $mime);

            return $doc->as($filename);
        }

        $doc = new Base64Document($base64, $mime);

        return $doc->as($filename);
    }

    /**
     * @param  array<int, mixed>  $messages
     */
    private function lastUserTextFromMessages(array $messages): string
    {
        $lastUser = null;
        foreach (array_reverse($messages) as $message) {
            if (! is_array($message)) {
                continue;
            }
            if (($message['role'] ?? '') === 'user') {
                $lastUser = $message;
                break;
            }
        }
        if ($lastUser === null) {
            return '';
        }

        if (isset($lastUser['parts']) && is_array($lastUser['parts'])) {
            $chunks = [];
            foreach ($lastUser['parts'] as $part) {
                if (! is_array($part)) {
                    continue;
                }
                if (($part['type'] ?? '') === 'text' && isset($part['text'])) {
                    $chunks[] = (string) $part['text'];
                }
            }

            return trim(implode("\n", $chunks));
        }

        if (isset($lastUser['content']) && is_string($lastUser['content'])) {
            return trim($lastUser['content']);
        }

        return '';
    }

    /**
     * Body `conversation_id` (client UI selection) wins, then cache, then session. Validates ownership.
     */
    private function resolveAgentConversationId(Request $request, Authenticatable $user, string $sessionKey): ?string
    {
        // Stateless mode: always start a fresh conversation (no session/cache fallback).
        if ($request->boolean('stateless')) {
            return null;
        }

        $bodyId = $request->input('conversation_id');
        if (is_string($bodyId) && $bodyId !== '' && $this->userOwnsConversation($user, $bodyId)) {
            return $bodyId;
        }

        $cachedId = $this->activeStore->get($user);
        if ($cachedId !== null && $this->userOwnsConversation($user, $cachedId)) {
            return $cachedId;
        }

        $sessionId = $request->session()->get($sessionKey);
        if (is_string($sessionId) && $sessionId !== '' && $this->userOwnsConversation($user, $sessionId)) {
            return $sessionId;
        }

        return null;
    }

    private function userOwnsConversation(Authenticatable $user, string $conversationId): bool
    {
        return DB::table('agent_conversations')
            ->where('id', $conversationId)
            ->where('user_id', $user->getAuthIdentifier())
            ->exists();
    }
}
