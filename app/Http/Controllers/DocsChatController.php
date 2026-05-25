<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Agent\SigmieDocsAgent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Responses\StreamableAgentResponse;

class DocsChatController extends Controller
{
    public function __invoke(Request $request): StreamableAgentResponse
    {
        abort_unless((bool) config('agent.chat.enabled'), 404);

        $messages = $request->input('messages', []);
        $prompt = $this->lastUserText(is_array($messages) ? $messages : []);

        $prompt !== '' ?: abort(422, 'No user message found.');

        $maxChars = (int) config('agent.chat.max_prompt_chars', 2000);
        mb_strlen($prompt) <= $maxChars ?: abort(422, 'Message too long.');

        // Count only valid, LLM-bound requests against the global budget.
        $this->enforceDailyBudget();

        [$conversationId, $user] = $this->identity($request);

        $agent = app(SigmieDocsAgent::class)->continue($conversationId, $user);

        return $agent->stream(
            $prompt,
            provider: Lab::Anthropic,
            model: SigmieDocsAgent::defaultModel(),
        )->usingVercelDataProtocol();
    }

    /**
     * Wipe the IP-scoped conversation history (DB-backed Laravel AI store).
     */
    public function clear(Request $request): JsonResponse
    {
        abort_unless((bool) config('agent.chat.enabled'), 404);

        [$conversationId] = $this->identity($request);

        DB::table('agent_conversation_messages')->where('conversation_id', $conversationId)->delete();
        DB::table('agent_conversations')->where('id', $conversationId)->delete();

        return response()->json(['ok' => true]);
    }

    /**
     * Stable per-IP conversation + user identity. Keys stay well under the
     * conversation_id varchar(36) limit. Same IP → same ongoing conversation.
     *
     * @return array{0: string, 1: object}
     */
    private function identity(Request $request): array
    {
        $hash = substr(hash('sha256', (string) $request->ip()), 0, 24);

        return ['docs-'.$hash, (object) ['id' => 'ip-'.$hash]];
    }

    /**
     * Global daily message budget across all users — protects the Anthropic
     * bill even under distributed abuse. Aborts with 429 once exhausted.
     */
    private function enforceDailyBudget(): void
    {
        $budget = (int) config('agent.chat.daily_budget', 2000);

        if ($budget <= 0) {
            return;
        }

        $key = 'agent-chat:daily:'.now()->format('Y-m-d');

        (int) Cache::get($key, 0) < $budget ?: abort(429, 'The assistant is busy right now. Please try again later.');

        Cache::add($key, 0, now()->endOfDay());
        Cache::increment($key);
    }

    /**
     * @param  array<int, mixed>  $messages
     */
    private function lastUserText(array $messages): string
    {
        foreach (array_reverse($messages) as $message) {
            if (! is_array($message) || ($message['role'] ?? '') !== 'user') {
                continue;
            }

            if (isset($message['parts']) && is_array($message['parts'])) {
                $chunks = [];
                foreach ($message['parts'] as $part) {
                    if (is_array($part) && ($part['type'] ?? '') === 'text' && isset($part['text'])) {
                        $chunks[] = (string) $part['text'];
                    }
                }

                return trim(implode("\n", $chunks));
            }

            if (isset($message['content']) && is_string($message['content'])) {
                return trim($message['content']);
            }
        }

        return '';
    }
}
