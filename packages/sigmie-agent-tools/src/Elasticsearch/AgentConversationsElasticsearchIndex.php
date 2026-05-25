<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\Elasticsearch;

use Laravel\Ai\Enums\Lab;
use Sigmie\Mappings\NewProperties;
use Sigmie\Search\NewSearch;

/**
 * Conversation turns: semantic search on messages, `topic` on `assistant_message` via Sigmie (shared sidecar with memory).
 *
 * @see \Sigmie\AgentTools\MagicTags\MagicTagsPackage
 */
class AgentConversationsElasticsearchIndex extends AgentIndex
{
    public function name(): string
    {
        return (string) config('agent-tools.conversations_index');
    }

    public function sourceKey(): string
    {
        return 'history';
    }

    public function retrievalPlannerHint(): string
    {
        return 'Past chat turns for this user (user and assistant messages).';
    }

    public function properties(): NewProperties
    {
        $p = new NewProperties;
        $p->keyword('user_id');
        $p->keyword('conversation_id');
        $p->text('user_message')->semantic($this->embeddingsDocApi(), 5, 384)->searchApi($this->embeddingsQueryApi());
        $p->text('assistant_message')->semantic($this->embeddingsDocApi(), 5, 384)->searchApi($this->embeddingsQueryApi());
        $p->text('tool_calls');
        $p->text('tool_results');
        $p->datetime('created_at');
        $p->datetime('updated_at');
        $p->magicTags('topic', 'assistant_message')
            ->tagIndex($this->magicTagIndex())
            ->provider(
                Lab::tryFrom((string) config('agent-tools.memory_history_magic_tags_provider', config('ai.default', 'openai'))) ?? Lab::OpenAI
            );

        return $p;
    }

    protected function buildSearch(NewSearch $search, mixed $user): NewSearch
    {
        $uid = $this->userIdForFilters($user);
        if ($uid !== null) {
            $search->filters("user_id:'".$this->escapeFilter($uid)."'");
        }

        return $search;
    }

    public function toText(array $source): string
    {
        return collect(['user_message', 'assistant_message'])
            ->map(fn (string $key) => trim((string) ($source[$key] ?? '')))
            ->filter()
            ->implode("\n");
    }

    /**
     * @return list<string>
     */
    public function metaFieldNames(): array
    {
        return ['conversation_id', 'created_at', 'topic', 'user_message', 'assistant_message'];
    }

    public function shouldRerank(): bool
    {
        return false;
    }

    public function countTurnsForUserConversation(string $userId, string $conversationId): int
    {
        $filters = "user_id:'".$this->escapeFilter($userId)."' AND conversation_id:'".$this->escapeFilter($conversationId)."'";

        return $this->countDocuments($filters);
    }
}
