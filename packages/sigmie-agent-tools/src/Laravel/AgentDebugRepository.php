<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\Laravel;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Persists agent debug turns outside the session so data survives streamed responses
 * (session is often saved before the stream completes, so session()->put in stream callbacks is dropped).
 *
 * Turns are keyed by authenticated user + conversation id so they survive full page reloads
 * (same cache as before; only the key is stable across sessions). Legacy session-only keys
 * are still read when no conversation id is available.
 *
 * When a turn is stored under user+conversation, it is also mirrored to the legacy session
 * cache key so GET /api/agent-debug (no query, session not yet carrying active conversation)
 * still returns data immediately after a streamed chat response.
 */
class AgentDebugRepository
{
    private const TTL_HOURS = 24;

    public function __construct(
        private readonly CacheRepository $cache,
    ) {}

    /**
     * @deprecated Use {@see appendTurn()} with conversation id; kept for backwards compatibility.
     */
    public function legacySessionCacheKey(Request $request): string
    {
        return 'agent_debug_turns_'.$request->session()->getId();
    }

    /**
     * @param  array<string, mixed>  $turn
     */
    public function appendTurn(Request $request, array $turn, ?string $conversationId = null): void
    {
        $key = $this->resolveWriteKey($request, $conversationId);
        $this->pushTurn($key, $turn);

        $user = $request->user();
        if (
            $user instanceof Authenticatable
            && is_string($conversationId) && $conversationId !== ''
            && $key === $this->userConversationKey($user, $conversationId)
        ) {
            $this->pushTurn($this->legacySessionCacheKey($request), $turn);
        }
    }

    /**
     * @param  array<string, mixed>  $turn
     */
    private function pushTurn(string $cacheKey, array $turn): void
    {
        $turns = $this->cache->get($cacheKey, []);
        if (! is_array($turns)) {
            $turns = [];
        }
        $turns[] = $turn;
        $this->cache->put($cacheKey, array_slice($turns, -30), now()->addHours(self::TTL_HOURS));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function all(Request $request): array
    {
        $user = $request->user();
        if (! $user instanceof Authenticatable) {
            return [];
        }

        $queryId = $request->query('conversation_id');
        $queryId = is_string($queryId) && $queryId !== '' ? $queryId : null;

        if ($queryId !== null) {
            if (! $this->userOwnsConversation($user, $queryId)) {
                return [];
            }
            $key = $this->userConversationKey($user, $queryId);
            $turns = $this->cache->get($key, []);

            return is_array($turns) ? $turns : [];
        }

        $sessionKey = (string) config('agent-tools.session_conversation_key', 'agent_conversation_id');
        $fromSession = $request->session()->get($sessionKey);
        $sessionConversationId = is_string($fromSession) && $fromSession !== '' ? $fromSession : null;

        if ($sessionConversationId !== null && $this->userOwnsConversation($user, $sessionConversationId)) {
            $key = $this->userConversationKey($user, $sessionConversationId);
            $turns = $this->cache->get($key, []);

            return is_array($turns) ? $turns : [];
        }

        $legacy = $this->cache->get($this->legacySessionCacheKey($request), []);

        return is_array($legacy) ? $legacy : [];
    }

    public function clear(Request $request): void
    {
        $this->cache->forget($this->legacySessionCacheKey($request));
    }

    /**
     * @param  non-empty-string  $conversationId
     */
    private function userOwnsConversation(Authenticatable $user, string $conversationId): bool
    {
        return DB::table('agent_conversations')
            ->where('id', $conversationId)
            ->where('user_id', $user->getAuthIdentifier())
            ->exists();
    }

    private function resolveWriteKey(Request $request, ?string $conversationId): string
    {
        $user = $request->user();
        if ($user instanceof Authenticatable && is_string($conversationId) && $conversationId !== '') {
            return $this->userConversationKey($user, $conversationId);
        }

        return $this->legacySessionCacheKey($request);
    }

    private function userConversationKey(Authenticatable $user, string $conversationId): string
    {
        return 'agent_debug_turns_u'.(string) ($user->getAuthIdentifier()).'_c'.$conversationId;
    }
}
