<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\Tools;

/**
 * DTO for UnifiedSearchTool debug payload to avoid 22-parameter method.
 */
final readonly class UnifiedSearchDebugData
{
    /**
     * @param  array<string, mixed>  $arguments
     * @param  array{memory: string, history: string, knowledge: string}  $queries
     * @param  array<string, int>  $perSourceHits
     * @param  list<string>  $sourcesQueried
     * @param  list<array<string, mixed>>  $keptForLog
     * @param  list<array<string, mixed>>  $omittedTopK
     * @param  list<array<string, mixed>>  $droppedBelowThreshold
     * @param  list<array<string, mixed>>  $results
     * @param  list<array{source: string, _id: string, _score: float|null, text: string}>  $rawHits
     * @param  list<array{_id: string, source: string, kept_by_id: string, text: string}>  $dedupRemovals
     * @param  list<array{_id: string, source: string, score: float, text: string}>  $rerankAllScored
     * @param  array<string, array<string, int>>  $stageCounts
     * @param  array<string, string>  $sourceRelevances
     */
    public function __construct(
        public array $arguments,
        public array $queries,
        public string $rerankQuery,
        public string $userQuery,
        public bool $plannerFallback,
        public int $plannerMs,
        public array $perSourceHits,
        public array $sourcesQueried,
        public int $totalBeforeDedup,
        public int $dedupRemoved,
        public int $totalCandidates,
        public int $topK,
        public int $rerankOutputCount,
        public int $searchMs,
        public int $rerankMs,
        public ?float $keptMin,
        public ?float $keptMax,
        public int $totalMs,
        public array $keptForLog,
        public array $omittedTopK,
        public array $droppedBelowThreshold,
        public array $results,
        public array $rawHits = [],
        public array $dedupRemovals = [],
        public array $rerankAllScored = [],
        public array $stageCounts = [],
        public array $sourceRelevances = [],
    ) {}
}
