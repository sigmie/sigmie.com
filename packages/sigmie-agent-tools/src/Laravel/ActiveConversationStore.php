<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\Laravel;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\DB;

/**
 * Persists the user's currently-active agent conversation id outside the HTTP session.
 *
 * Laravel's StartSession middleware saves the session in terminate(), which is racy for
 * streamed responses: put/save inside the stream callback is not reliably visible to the
 * very next request. We store the id in cache (driver-backed, e.g. database), so
 * continuity survives across streamed responses and page reloads.
 */
class ActiveConversationStore
{
    private const TTL_DAYS = 30;

    public function __construct(
        private readonly CacheRepository $cache,
    ) {}

    public function get(Authenticatable $user): ?string
    {
        $value = $this->cache->get($this->key($user));

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function set(Authenticatable $user, string $conversationId): void
    {
        $this->cache->put(
            $this->key($user),
            $conversationId,
            now()->addDays(self::TTL_DAYS),
        );
    }

    public function clear(Authenticatable $user): void
    {
        $this->cache->forget($this->key($user));
    }

    /**
     * Most recently updated conversation for the user, used as a safe fallback
     * when no explicit active id is known (session lost, first load, etc.).
     */
    public function mostRecentFor(Authenticatable $user): ?string
    {
        $row = DB::table('agent_conversations')
            ->where('user_id', $user->getAuthIdentifier())
            ->orderByDesc('updated_at')
            ->limit(1)
            ->first(['id']);

        if ($row === null) {
            return null;
        }

        return (string) $row->id;
    }

    private function key(Authenticatable $user): string
    {
        return 'agent_active_conversation_u'.(string) $user->getAuthIdentifier();
    }
}
