<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\Agents;

/**
 * Structured retrieval plan produced by {@see RetrievalPlannerAgent} for {@see \Sigmie\AgentTools\Tools\UnifiedSearchTool}.
 */
final class RetrievalPlan
{
    /**
     * @param  array<string, string>  $sourceQueries  keyed by {@see \Sigmie\AgentTools\Contracts\RetrievalSource::sourceKey()}
     * @param  array<string, string>  $sourceRelevances  keyed by source key: high|low|skip
     */
    public function __construct(
        public readonly array $sourceQueries,
        public readonly string $rerankQuery,
        public readonly array $sourceRelevances = [],
    ) {}

    public function queryFor(string $sourceKey): string
    {
        return trim((string) ($this->sourceQueries[$sourceKey] ?? ''));
    }

    /**
     * @return 'high'|'low'|'skip'
     */
    public function relevanceFor(string $sourceKey): string
    {
        $r = trim((string) ($this->sourceRelevances[$sourceKey] ?? 'high'));

        return in_array($r, ['high', 'low', 'skip'], true) ? $r : 'high';
    }

    /**
     * Max hits to retrieve for a source given planner relevance (high / low / skip).
     */
    public function maxResultsFor(string $sourceKey, int $highLimit, int $lowLimit): int
    {
        return match ($this->relevanceFor($sourceKey)) {
            'skip' => 0,
            'low' => $lowLimit,
            default => $highLimit,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $sourceKeys
     */
    public static function fromStructuredResponse(array $data, array $sourceKeys): self
    {
        $queries = [];
        $relevances = [];
        foreach ($sourceKeys as $key) {
            $queries[$key] = trim((string) ($data[$key.'_query'] ?? ''));
            $rel = trim((string) ($data[$key.'_relevance'] ?? ''));
            $relevances[$key] = in_array($rel, ['high', 'low', 'skip'], true) ? $rel : 'high';
        }
        $rerank = trim((string) ($data['rerank_query'] ?? ''));

        return new self($queries, $rerank, $relevances);
    }

    /**
     * @param  list<string>  $sourceKeys
     */
    public static function fallbackFromUserQuery(string $query, array $sourceKeys): self
    {
        $q = trim($query);
        $queries = [];
        $relevances = [];
        foreach ($sourceKeys as $key) {
            $queries[$key] = $q;
            $relevances[$key] = 'high';
        }

        return new self($queries, $q, $relevances);
    }
}
