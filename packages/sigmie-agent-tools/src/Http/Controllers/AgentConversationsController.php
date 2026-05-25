<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\Http\Controllers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Sigmie\AgentTools\Laravel\ActiveConversationStore;

/**
 * Lists and switches Laravel AI agent conversations (DB-backed), for the agent UI.
 */
class AgentConversationsController extends Controller
{
    public function __construct(
        private readonly ActiveConversationStore $activeStore,
    ) {}

    private function resolveUser(Request $request): Authenticatable
    {
        $user = $request->user();
        if (! $user instanceof Authenticatable) {
            abort(401, 'Unauthenticated.');
        }

        return $user;
    }

    private function sessionConversationKey(): string
    {
        return (string) config('agent-tools.session_conversation_key', 'agent_conversation_id');
    }

    /**
     * GET …/api/agent-conversations
     *
     * @return JsonResponse{data: list<array{id: string, title: string, updated_at: string}>}
     */
    public function index(Request $request): JsonResponse
    {
        $user = $this->resolveUser($request);

        $rows = DB::table('agent_conversations')
            ->where('user_id', $user->getAuthIdentifier())
            ->orderByDesc('updated_at')
            ->limit(100)
            ->get(['id', 'title', 'updated_at']);

        $data = $rows->map(static function ($row): array {
            return [
                'id' => (string) $row->id,
                'title' => (string) $row->title,
                'updated_at' => (string) $row->updated_at,
            ];
        })->values()->all();

        $activeId = $this->activeStore->get($user);
        if ($activeId === null) {
            $fromSession = $request->session()->get($this->sessionConversationKey());
            if (is_string($fromSession) && $fromSession !== '') {
                $activeId = $fromSession;
            }
        }

        return response()->json([
            'data' => $data,
            'active_conversation_id' => $activeId,
        ]);
    }

    /**
     * GET …/api/agent-conversations/{conversationId}/messages
     *
     * @return JsonResponse{messages: list<array<string, mixed>>}
     */
    public function messages(Request $request, string $conversationId): JsonResponse
    {
        $user = $this->resolveUser($request);

        $exists = DB::table('agent_conversations')
            ->where('id', $conversationId)
            ->where('user_id', $user->getAuthIdentifier())
            ->exists();

        if (! $exists) {
            abort(404, 'Conversation not found.');
        }

        $records = DB::table('agent_conversation_messages')
            ->where('conversation_id', $conversationId)
            ->where('user_id', $user->getAuthIdentifier())
            ->orderBy('id')
            ->get(['id', 'role', 'content']);

        $messages = [];
        foreach ($records as $record) {
            $role = (string) $record->role;
            if ($role !== 'user' && $role !== 'assistant') {
                continue;
            }
            $text = trim((string) $record->content);
            $messages[] = [
                'id' => (string) $record->id,
                'role' => $role,
                'parts' => [
                    ['type' => 'text', 'text' => $text !== '' ? $text : ($role === 'assistant' ? '…' : '')],
                ],
            ];
        }

        return response()->json(['messages' => $messages]);
    }

    /**
     * POST …/api/agent-conversations/active
     */
    public function setActive(Request $request): JsonResponse
    {
        $user = $this->resolveUser($request);

        $sessionKey = $this->sessionConversationKey();
        $conversationId = $request->input('conversation_id');

        if ($conversationId === null || $conversationId === '') {
            $this->activeStore->clear($user);
            $request->session()->forget($sessionKey);
        } else {
            if (! is_string($conversationId)) {
                abort(422, 'conversation_id must be a string or null.');
            }
            $this->activeStore->set($user, $conversationId);
            $request->session()->put($sessionKey, $conversationId);
        }
        $request->session()->save();

        return response()->json(['ok' => true]);
    }
}
