<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\Contracts;

use Illuminate\Support\Collection;
use Sigmie\Document\Hit;
use Sigmie\Search\NewSearch;

/**
 * Search source for {@see \Sigmie\AgentTools\Tools\UnifiedSearchTool}: prepare query, map hits, rerank text.
 */
interface RetrievalSource
{
    /**
     * Stable key for multi-search grouping and retrieval planner (e.g. memory, history, knowledge).
     */
    public function sourceKey(): string;

    public function retrievalPlannerHint(): string;

    /**
     * @param  \Illuminate\Contracts\Auth\Authenticatable|object{id: int|string}|null  $user  User-scoped indices use Authenticatable or an object with `id` (e.g. CLI demo user).
     */
    public function prepareSearch(string $query, int $limit, mixed $user = null): NewSearch;

    /**
     * @param  Collection<int, Hit>  $hits
     * @return Collection<int, \Illuminate\Support\Collection<string, mixed>>
     */
    public function mapHits(Collection $hits): Collection;

    /**
     * @param  array<string, mixed>  $source
     */
    public function toText(array $source): string;

    /**
     * Keys from {@see mapHits()} row / hit `_source` to expose as `meta` on unified_search results (besides `_id`).
     *
     * @return list<string>
     */
    public function metaFieldNames(): array;

    /**
     * Whether hits from this source participate in global Cohere rerank in {@see \Sigmie\AgentTools\Tools\UnifiedSearchTool}.
     * Return false for short-text or personal sources (memory, history); semantic scores are used as-is.
     */
    public function shouldRerank(): bool;
}
