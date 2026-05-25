<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\Elasticsearch;

use Laravel\Ai\Enums\Lab;
use Sigmie\Mappings\NewProperties;
use Sigmie\Search\NewSearch;

/**
 * User memory facts: semantic search on `fact`, `topic` from Sigmie (shared sidecar with history).
 *
 * @see \Sigmie\AgentTools\MagicTags\MagicTagsPackage
 */
class AgentUserMemoryElasticsearchIndex extends AgentIndex
{
    public function name(): string
    {
        return (string) config('agent-tools.memory_index');
    }

    public function sourceKey(): string
    {
        return 'memory';
    }

    public function retrievalPlannerHint(): string
    {
        return 'Stored user facts and preferences.';
    }

    public function properties(): NewProperties
    {
        $p = new NewProperties;
        $p->keyword('user_id');
        $p->keyword('category');
        $p->text('fact')->semantic($this->embeddingsDocApi(), 5, 384)->searchApi($this->embeddingsQueryApi());
        $p->datetime('created_at');
        $p->magicTags('topic', 'fact')
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
        return trim((string) ($source['fact'] ?? ''));
    }

    /**
     * @return list<string>
     */
    public function metaFieldNames(): array
    {
        return ['category', 'created_at', 'topic'];
    }

    public function shouldRerank(): bool
    {
        return false;
    }

    public function factCount(string $userId): int
    {
        return $this->countDocuments("user_id:'".$this->escapeFilter($userId)."'");
    }
}
