<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Collection;
use Laravel\Ai\Contracts\Tool as AiTool;
use Laravel\Ai\Tools\Request;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Sigmie\AgentTools\Agents\RetrievalPlan;
use Sigmie\AgentTools\Contracts\RetrievalSource;
use Sigmie\AgentTools\Laravel\AgentTurnDebugCollector;
use Sigmie\AgentTools\Laravel\RecordsAgentToolDebug;
use Sigmie\AgentTools\Laravel\RetrievalPlanning;
use Sigmie\AI\Contracts\RerankApi;
use Sigmie\Document\Hit;
use Sigmie\Document\RerankedHit;
use Sigmie\Search\NewRerank;
use Sigmie\Sigmie;

/**
 * Retrieves from knowledge, memory, and past conversation turns with per-source queries and relevance,
 * merges candidates, reranks only sources where {@see RetrievalSource::shouldRerank()} is true (Cohere via
 * {@see \Sigmie\Search\NewRerank}), and appends pass-through hits with semantic scores.
 * The history source searches across all past conversations for the current user (not only the active thread).
 *
 * Per-source queries, relevance (high/low/skip), and rerank phrase are produced by {@see RetrievalPlannerAgent}.
 * Source retrieval uses {@see Sigmie::newMultiSearch()} (one `_msearch` round-trip when any source is queried).
 */
class UnifiedSearchTool implements AiTool, LoggerAwareInterface
{
    use LoggerAwareTrait;
    use RecordsAgentToolDebug;

    /**
     * @param  list<RetrievalSource>  $sources
     * @param  \Closure(): (\Illuminate\Contracts\Auth\Authenticatable|object{id: mixed}|null)  $user
     */
    public function __construct(
        private array $sources,
        private ?RerankApi $rerankApi,
        private Sigmie $sigmie,
        private \Closure $user,
        private int $topK = 5,
        private float $scoreThreshold = 0.1,
    ) {}

    public function name(): string
    {
        return 'unified_search';
    }

    public function description(): string
    {
        $keys = array_map(static fn (RetrievalSource $s): string => $s->sourceKey(), $this->sources);
        $label = $keys === [] ? 'configured sources' : implode(', ', $keys);
        $historyHint = $this->historyConversationIdHint();

        return 'Search '.$label.' in ONE single call. '
            .'Sources marked skip by the internal planner are omitted. If not sure, call this tool multiple times per turn. '
            .'Pass a single `query`: the user intent or question. The tool rewrites it into optimized queries per source internally.'
            .($historyHint !== '' ? ' '.$historyHint : '');
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()
                ->description('User intent or question to search across: '.$this->sourcesSummaryForPrompt().'.')
                ->required(),
        ];
    }

    /**
     * Hint when the history source is present (stable key `history`).
     */
    private function historyConversationIdHint(): string
    {
        foreach ($this->sources as $source) {
            if ($source->sourceKey() === 'history') {
                return 'History hits include `conversation_id` in metadata so you can tell which past thread a turn came from.';
            }
        }

        return '';
    }

    /**
     * Short list of source keys for tool / schema descriptions.
     */
    private function sourcesSummaryForPrompt(): string
    {
        $keys = array_map(static fn (RetrievalSource $s): string => $s->sourceKey(), $this->sources);

        return $keys === [] ? '(no sources configured)' : implode(', ', $keys);
    }

    /**
     * @return array<string, RetrievalSource>
     */
    private function sourcesByKey(): array
    {
        $map = [];
        foreach ($this->sources as $source) {
            $map[$source->sourceKey()] = $source;
        }

        return $map;
    }

    private function sourceForKey(string $sourceKey): ?RetrievalSource
    {
        return $this->sourcesByKey()[$sourceKey] ?? null;
    }

    public function handle(Request $request): string
    {
        $args = $request->toArray();
        $result = $this->execute($args);
        $this->recordAgentToolDebug($this->name(), $args, $result);

        return json_encode($result, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    public function execute(array $arguments): array
    {
        $start = microtime(true);
        $userQuery = $this->extractUserQuery($arguments);
        if ($userQuery === '') {
            $this->pushUnifiedSearchDebug([
                'error' => 'query is required and must be non-empty',
                'arguments' => $arguments,
            ]);

            return ['error' => 'query is required and must be non-empty'];
        }

        $plannerStart = microtime(true);
        $plannerResult = RetrievalPlanning::resolve($userQuery, $this->sources, $this->logger);
        $plannerMs = (int) ((microtime(true) - $plannerStart) * 1000);

        $plan = $plannerResult['plan'];
        $plannerFallback = $plannerResult['fallback'];

        $queries = $plan->sourceQueries;
        $rerankQuery = $plan->rerankQuery;
        $sourceRelevances = $plan->sourceRelevances;
        $highLimit = max(1, min(50, (int) config('agent-tools.unified_relevance_high_limit', 10)));
        $lowLimit = max(0, min(50, (int) config('agent-tools.unified_relevance_low_limit', 3)));

        ($this->logger ?? new NullLogger)->info('unified_search.start', [
            'queries' => $queries,
            'source_relevances' => $sourceRelevances,
            'rerank_query' => $rerankQuery,
            'user_query' => $userQuery,
            'planner_fallback' => $plannerFallback,
            'planner_ms' => $plannerMs,
            'top_k' => $this->topK,
            'relevance_high_limit' => $highLimit,
            'relevance_low_limit' => $lowLimit,
            'score_threshold' => $this->scoreThreshold,
        ]);

        $retrieval = null;
        try {
            $retrieval = $this->retrieveMergeAndDedup($plan, $highLimit, $lowLimit, $start);
        } catch (\Throwable $e) {
            ($this->logger ?? new NullLogger)->error('unified_search.error', [
                'message' => $e->getMessage(),
            ]);
            $sourcesOnError = is_array($retrieval) ? ($retrieval['sources_queried'] ?? []) : [];
            $this->pushUnifiedSearchDebug([
                'error' => $e->getMessage(),
                'arguments' => $arguments,
                'queries' => $queries,
                'user_query' => $userQuery,
                'sources_queried' => $sourcesOnError,
            ]);

            return [
                'results' => [],
                'sources_queried' => $sourcesOnError,
                'sources_skipped' => [],
                'total_candidates' => 0,
                'error' => 'search temporarily unavailable',
            ];
        }

        $merged = $retrieval['merged'];
        $perSourceHits = $retrieval['per_source_hits'];
        $sourcesQueried = $retrieval['sources_queried'];
        $sourcesSkipped = $retrieval['sources_skipped'];
        $searchMs = $retrieval['search_ms'];
        $totalBeforeDedup = $retrieval['merged_before_dedup'];
        $dedupRemoved = $retrieval['dedup_removed'];
        $rawHits = $retrieval['raw_hits'];
        $dedupRemovals = $retrieval['dedup_removals'];
        $totalCandidates = count($merged);

        if ($merged === []) {
            $emptyMs = (int) ((microtime(true) - $start) * 1000);
            $this->logDoneEmpty($userQuery, $queries, $rerankQuery, $plannerFallback, $plannerMs, $perSourceHits, $sourcesQueried, $sourcesSkipped, $totalBeforeDedup, $dedupRemoved, $emptyMs);
            $this->pushUnifiedSearchDebug($this->buildDebugPayloadEmpty(
                $arguments,
                $queries,
                $rerankQuery,
                $userQuery,
                $plannerFallback,
                $plannerMs,
                $perSourceHits,
                $sourcesQueried,
                $sourcesSkipped,
                $totalBeforeDedup,
                $dedupRemoved,
                $emptyMs,
            ));

            return [
                'results' => [],
                'sources_queried' => $sourcesQueried,
                'sources_skipped' => $sourcesSkipped,
                'total_candidates' => 0,
            ];
        }

        $split = $this->splitMergedByRerank($merged);
        $rerankCandidates = $split['rerank'];
        $passThrough = $this->sortHitsByScoreDesc($split['pass_through']);
        $rerankCandidateCount = count($rerankCandidates);
        $passThroughCount = count($passThrough);

        if ($rerankCandidates === []) {
            $final = $this->rerankApi === null
                ? array_slice($passThrough, 0, $this->topK)
                : $passThrough;
            $rerankFinal = [];
            $reranked = [];
            $rerankedIds = [];
            $rerankMs = 0;
            $topK = 0;
            $rerankOutputCount = 0;
            $droppedBelowThreshold = [];
            $rerankAllScored = [];
        } else {
            $rerankOutcome = $this->rerankWithThreshold($rerankCandidates, $rerankQuery);
            $rerankFinal = $rerankOutcome['final'];
            $final = array_merge($rerankFinal, $passThrough);
            $reranked = $rerankOutcome['reranked'];
            $rerankedIds = $rerankOutcome['reranked_ids'];
            $rerankMs = $rerankOutcome['rerank_ms'];
            $topK = $rerankOutcome['top_k'];
            $rerankOutputCount = $rerankOutcome['output_count'];
            $droppedBelowThreshold = $rerankOutcome['dropped_below_threshold'];
            $rerankAllScored = $rerankOutcome['all_scored'];
        }

        $results = $this->mapRerankedToResults($final);
        $keptForLog = $this->buildKeptForLog($final, $results);
        [$keptMin, $keptMax] = $this->scoreRangeFromKept($keptForLog);

        $finalIds = array_map(static fn (Hit|RerankedHit $h): string => $h->_id, $final);

        $omittedTopK = [];
        foreach ($merged as $hit) {
            if (in_array($hit->_id, $finalIds, true)) {
                continue;
            }
            if (in_array($hit->_id, $rerankedIds, true)) {
                continue;
            }
            $omittedTopK[] = [
                '_id' => $hit->_id,
                'source' => (string) ($hit->_source['retrieval_source'] ?? 'unknown'),
                'text' => (string) ($hit->_source['text'] ?? ''),
            ];
        }

        $totalMs = (int) ((microtime(true) - $start) * 1000);

        $stageCounts = $this->buildStageCounts(
            $rawHits,
            $dedupRemovals,
            $split['rerank'],
            $split['pass_through'],
            $rerankFinal,
            $droppedBelowThreshold,
            $sourcesQueried,
            $sourcesSkipped,
        );

        ($this->logger ?? new NullLogger)->info('unified_search.done', [
            'ms' => $totalMs,
            'search_ms' => $searchMs,
            'rerank_ms' => $rerankMs,
            'rerank_query' => $rerankQuery,
            'queries' => $queries,
            'source_relevances' => $sourceRelevances,
            'user_query' => $userQuery,
            'planner_fallback' => $plannerFallback,
            'planner_ms' => $plannerMs,
            'per_source_hits' => $perSourceHits,
            'sources_skipped' => $sourcesSkipped,
            'rerank_candidate_count' => $rerankCandidateCount,
            'pass_through_count' => $passThroughCount,
            'merged_before_dedup' => $totalBeforeDedup,
            'dedup_removed' => $dedupRemoved,
            'total_candidates' => $totalCandidates,
            'rerank_input_count' => $rerankCandidateCount,
            'rerank_top_k_cap' => $topK,
            'rerank_output_count' => $rerankOutputCount,
            'returned' => count($results),
            'dropped_below_threshold_count' => count($droppedBelowThreshold),
            'omitted_top_k_count' => count($omittedTopK),
            'sources_queried' => $sourcesQueried,
            'kept' => $keptForLog,
            'omitted' => $omittedTopK,
            'dropped_below_threshold' => $droppedBelowThreshold,
            'score_threshold' => $this->scoreThreshold,
            'kept_score_min' => $keptMin,
            'kept_score_max' => $keptMax,
            'raw_hits' => $rawHits,
            'dedup_removals' => $dedupRemovals,
            'rerank_all_scored' => $rerankAllScored,
            'stage_counts' => $stageCounts,
        ]);

        $debugData = new UnifiedSearchDebugData(
            arguments: $arguments,
            queries: $queries,
            rerankQuery: $rerankQuery,
            userQuery: $userQuery,
            plannerFallback: $plannerFallback,
            plannerMs: $plannerMs,
            perSourceHits: $perSourceHits,
            sourcesQueried: $sourcesQueried,
            totalBeforeDedup: $totalBeforeDedup,
            dedupRemoved: $dedupRemoved,
            totalCandidates: $totalCandidates,
            topK: $topK,
            rerankOutputCount: $rerankOutputCount,
            searchMs: $searchMs,
            rerankMs: $rerankMs,
            keptMin: $keptMin,
            keptMax: $keptMax,
            totalMs: $totalMs,
            keptForLog: $keptForLog,
            omittedTopK: $omittedTopK,
            droppedBelowThreshold: $droppedBelowThreshold,
            results: $results,
            rawHits: $rawHits,
            dedupRemovals: $dedupRemovals,
            rerankAllScored: $rerankAllScored,
            stageCounts: $stageCounts,
            sourceRelevances: $sourceRelevances,
        );

        $this->pushUnifiedSearchDebug($this->buildDebugPayloadSuccess($debugData));

        return [
            'results' => $results,
            'sources_queried' => $sourcesQueried,
            'sources_skipped' => $sourcesSkipped,
            'total_candidates' => $totalCandidates,
        ];
    }

    /**
     * @return array{
     *     merged: list<Hit>,
     *     per_source_hits: array<string, int>,
     *     sources_queried: list<string>,
     *     sources_skipped: list<string>,
     *     search_ms: int,
     *     merged_before_dedup: int,
     *     dedup_removed: int,
     *     raw_hits: list<array{source: string, _id: string, _score: float|null, text: string}>,
     *     dedup_removals: list<array{_id: string, source: string, kept_by_id: string, text: string}>,
     * }
     */
    private function retrieveMergeAndDedup(RetrievalPlan $plan, int $highLimit, int $lowLimit, float $retrieveStart): array
    {
        $sourcesQueried = [];
        $sourcesSkipped = [];
        $multi = $this->sigmie->newMultiSearch();
        $user = ($this->user)();

        foreach ($this->sources as $source) {
            $key = $source->sourceKey();
            $lim = $plan->maxResultsFor($key, $highLimit, $lowLimit);
            if ($lim === 0) {
                $sourcesSkipped[] = $key;

                continue;
            }
            $q = trim((string) ($plan->sourceQueries[$key] ?? ''));
            $multi->add($source->prepareSearch($q, $lim, $user), $key);
            $sourcesQueried[] = $key;
        }

        $perSourceHits = [];

        if ($sourcesQueried === []) {
            foreach ($this->sources as $source) {
                $perSourceHits[$source->sourceKey()] = 0;
            }

            return [
                'merged' => [],
                'per_source_hits' => $perSourceHits,
                'sources_queried' => [],
                'sources_skipped' => $sourcesSkipped,
                'search_ms' => 0,
                'merged_before_dedup' => 0,
                'dedup_removed' => 0,
                'raw_hits' => [],
                'dedup_removals' => [],
            ];
        }

        $grouped = $multi->groupedHits();
        $searchMs = (int) ((microtime(true) - $retrieveStart) * 1000);

        $merged = [];
        $rawHitsLog = [];

        foreach ($this->sources as $source) {
            $key = $source->sourceKey();
            if (! in_array($key, $sourcesQueried, true)) {
                $perSourceHits[$key] = 0;

                continue;
            }
            $raw = $grouped[$key] ?? [];
            $rawHits = collect($raw)->filter(
                static fn (mixed $h): bool => $h instanceof Hit || $h instanceof RerankedHit
            );
            $rows = $source->mapHits($rawHits);
            $perSourceHits[$key] = $rows->count();
            $hits = $this->rowsToHits($source, $rows);
            foreach ($hits as $hit) {
                $rawHitsLog[] = [
                    'source' => $key,
                    '_id' => $hit->_id,
                    '_score' => $hit->_score,
                    'text' => $this->snippet((string) ($hit->_source['text'] ?? ''), 160),
                ];
            }
            $merged = array_merge($merged, $hits);
        }

        $totalBeforeDedup = count($merged);
        [$merged, $dedupRemovals] = $this->deduplicateByTextWithLog($merged);
        $dedupRemoved = $totalBeforeDedup - count($merged);

        return [
            'merged' => $merged,
            'per_source_hits' => $perSourceHits,
            'sources_queried' => $sourcesQueried,
            'sources_skipped' => $sourcesSkipped,
            'search_ms' => $searchMs,
            'merged_before_dedup' => $totalBeforeDedup,
            'dedup_removed' => $dedupRemoved,
            'raw_hits' => $rawHitsLog,
            'dedup_removals' => $dedupRemovals,
        ];
    }

    /**
     * @param  list<Hit>  $merged
     * @return array{rerank: list<Hit>, pass_through: list<Hit>}
     */
    private function splitMergedByRerank(array $merged): array
    {
        if ($this->rerankApi === null) {
            return ['rerank' => [], 'pass_through' => $merged];
        }

        $rerank = [];
        $passThrough = [];
        foreach ($merged as $hit) {
            $key = (string) ($hit->_source['retrieval_source'] ?? '');
            $src = $this->sourceForKey($key);
            if ($src !== null && $src->shouldRerank()) {
                $rerank[] = $hit;
            } else {
                $passThrough[] = $hit;
            }
        }

        return ['rerank' => $rerank, 'pass_through' => $passThrough];
    }

    /**
     * @param  list<Hit>  $hits
     * @return list<Hit>
     */
    private function sortHitsByScoreDesc(array $hits): array
    {
        usort(
            $hits,
            static function (Hit $a, Hit $b): int {
                $sa = $a->_score;
                $sb = $b->_score;
                if ($sa === null && $sb === null) {
                    return 0;
                }
                if ($sa === null) {
                    return 1;
                }
                if ($sb === null) {
                    return -1;
                }

                return $sb <=> $sa;
            }
        );

        return $hits;
    }

    /**
     * @param  Collection<int, Collection<string, mixed>>  $rows
     * @return list<Hit>
     */
    private function rowsToHits(RetrievalSource $source, Collection $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $arr = $row->all();
            $id = (string) ($arr['_id'] ?? '');
            if ($id === '') {
                $id = $source->sourceKey().'_'.md5(json_encode($arr, JSON_THROW_ON_ERROR));
            }
            $text = $source->toText($arr);
            $sourceArr = array_merge($arr, [
                'text' => $text,
                'retrieval_source' => $source->sourceKey(),
            ]);
            $score = isset($arr['_score']) ? (float) $arr['_score'] : null;
            $out[] = new Hit($sourceArr, $id, $score, '');
        }

        return $out;
    }

    /**
     * @param  list<Hit>  $merged
     * @return array{
     *     final: list<RerankedHit>,
     *     reranked: list<Hit|RerankedHit>,
     *     reranked_ids: list<string>,
     *     rerank_ms: int,
     *     top_k: int,
     *     output_count: int,
     *     dropped_below_threshold: list<array<string, mixed>>,
     *     all_scored: list<array{_id: string, source: string, score: float, text: string}>,
     * }
     */
    private function rerankWithThreshold(array $merged, string $rerankQuery): array
    {
        $topK = min($this->topK, count($merged));
        $reranker = (new NewRerank($this->rerankApi))
            ->query($rerankQuery)
            ->fields(['text'])
            ->topK($topK);

        $rrStart = microtime(true);
        $reranked = $reranker->rerank($merged);
        $rerankMs = (int) ((microtime(true) - $rrStart) * 1000);
        $rerankedIds = array_map(static fn (Hit|RerankedHit $h): string => $h->_id, $reranked);

        $final = [];
        $droppedBelowThreshold = [];
        $allScored = [];
        foreach ($reranked as $h) {
            if (! $h instanceof RerankedHit) {
                continue;
            }
            $allScored[] = [
                '_id' => $h->_id,
                'source' => (string) ($h->_source['retrieval_source'] ?? 'unknown'),
                'score' => $h->_rerank_score,
                'text' => $this->snippet((string) ($h->_source['text'] ?? ''), 160),
            ];
            if ($h->_rerank_score >= $this->scoreThreshold) {
                $final[] = $h;
            } else {
                $droppedBelowThreshold[] = [
                    '_id' => $h->_id,
                    'source' => (string) ($h->_source['retrieval_source'] ?? 'unknown'),
                    'score' => $h->_rerank_score,
                    'text' => (string) ($h->_source['text'] ?? ''),
                ];
            }
        }

        return [
            'final' => $final,
            'reranked' => $reranked,
            'reranked_ids' => $rerankedIds,
            'rerank_ms' => $rerankMs,
            'top_k' => $topK,
            'output_count' => count($reranked),
            'dropped_below_threshold' => $droppedBelowThreshold,
            'all_scored' => $allScored,
        ];
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    private function extractUserQuery(array $arguments): string
    {
        return trim((string) ($arguments['query'] ?? ''));
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @param  array{memory: string, history: string, knowledge: string}  $queries
     * @param  array<string, int>  $perSourceHits
     * @param  list<string>  $sourcesQueried
     */
    private function buildDebugPayloadEmpty(
        array $arguments,
        array $queries,
        string $rerankQuery,
        string $userQuery,
        bool $plannerFallback,
        int $plannerMs,
        array $perSourceHits,
        array $sourcesQueried,
        array $sourcesSkipped,
        int $totalBeforeDedup,
        int $dedupRemoved,
        int $emptyMs,
    ): array {
        return [
            'arguments' => $arguments,
            'queries' => $queries,
            'rerank_query' => $rerankQuery,
            'user_query' => $userQuery,
            'planner_fallback' => $plannerFallback,
            'planner_ms' => $plannerMs,
            'per_source_hits' => $perSourceHits,
            'sources_queried' => $sourcesQueried,
            'sources_skipped' => $sourcesSkipped,
            'merged_before_dedup' => $totalBeforeDedup,
            'dedup_removed' => $dedupRemoved,
            'total_candidates' => 0,
            'rerank_skipped' => true,
            'reason' => 'no_candidates_after_merge',
            'timing_ms' => $emptyMs,
            'results_returned' => [],
        ];
    }

    /**
     * Build debug payload from structured data object.
     */
    private function buildDebugPayloadSuccess(UnifiedSearchDebugData $data): array
    {
        return [
            'arguments' => $data->arguments,
            'queries' => $data->queries,
            'rerank_query' => $data->rerankQuery,
            'user_query' => $data->userQuery,
            'planner_fallback' => $data->plannerFallback,
            'planner_ms' => $data->plannerMs,
            'per_source_hits' => $data->perSourceHits,
            'sources_queried' => $data->sourcesQueried,
            'merged_before_dedup' => $data->totalBeforeDedup,
            'dedup_removed' => $data->dedupRemoved,
            'total_candidates' => $data->totalCandidates,
            'rerank' => [
                'input_count' => $data->totalCandidates,
                'top_k_cap' => $data->topK,
                'output_count' => $data->rerankOutputCount,
                'search_ms' => $data->searchMs,
                'rerank_ms' => $data->rerankMs,
                'score_threshold' => $this->scoreThreshold,
                'kept_score_min' => $data->keptMin,
                'kept_score_max' => $data->keptMax,
            ],
            'timing_ms' => $data->totalMs,
            'kept' => $this->truncateForDebug($data->keptForLog),
            'omitted_top_k_sample' => $this->truncateForDebug(array_slice($data->omittedTopK, 0, 8)),
            'dropped_below_threshold_sample' => $this->truncateForDebug(array_slice($data->droppedBelowThreshold, 0, 8)),
            'raw_hits' => $this->truncateForDebug($data->rawHits),
            'dedup_removals' => $this->truncateForDebug($data->dedupRemovals),
            'rerank_all_scored' => $this->truncateForDebug($data->rerankAllScored),
            'stage_counts' => $data->stageCounts,
            'source_relevances' => $data->sourceRelevances,
            'results_returned' => $this->truncateForDebug($data->results),
        ];
    }

    /**
     * @param  array{memory: string, history: string, knowledge: string}  $queries
     * @param  array<string, int>  $perSourceHits
     * @param  list<string>  $sourcesQueried
     */
    private function logDoneEmpty(
        string $userQuery,
        array $queries,
        string $rerankQuery,
        bool $plannerFallback,
        int $plannerMs,
        array $perSourceHits,
        array $sourcesQueried,
        array $sourcesSkipped,
        int $totalBeforeDedup,
        int $dedupRemoved,
        int $emptyMs,
    ): void {
        ($this->logger ?? new NullLogger)->info('unified_search.done', [
            'ms' => $emptyMs,
            'search_ms' => $emptyMs,
            'rerank_ms' => 0,
            'rerank_query' => $rerankQuery,
            'queries' => $queries,
            'user_query' => $userQuery,
            'planner_fallback' => $plannerFallback,
            'planner_ms' => $plannerMs,
            'per_source_hits' => $perSourceHits,
            'sources_skipped' => $sourcesSkipped,
            'merged_before_dedup' => $totalBeforeDedup,
            'dedup_removed' => $dedupRemoved,
            'total_candidates' => 0,
            'rerank_input_count' => 0,
            'rerank_top_k_cap' => 0,
            'rerank_output_count' => 0,
            'returned' => 0,
            'dropped_below_threshold_count' => 0,
            'omitted_top_k_count' => 0,
            'sources_queried' => $sourcesQueried,
            'kept' => [],
            'omitted' => [],
            'dropped_below_threshold' => [],
            'score_threshold' => $this->scoreThreshold,
            'kept_score_min' => null,
            'kept_score_max' => null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function pushUnifiedSearchDebug(array $payload): void
    {
        try {
            app(AgentTurnDebugCollector::class)->addUnifiedSearchRun($payload);
        } catch (\Throwable) {
        }
    }

    /**
     * @param  mixed  $data
     * @return ($data is array ? array : mixed)
     */
    private function truncateForDebug(mixed $data, int $maxLen = 480): mixed
    {
        if (is_string($data)) {
            if (strlen($data) <= $maxLen) {
                return $data;
            }

            return substr($data, 0, $maxLen).'…';
        }
        if (is_array($data)) {
            $out = [];
            foreach ($data as $k => $v) {
                $out[$k] = $this->truncateForDebug($v, $maxLen);
            }

            return $out;
        }

        return $data;
    }

    /**
     * @param  list<Hit|RerankedHit>  $final
     * @param  list<array<string, mixed>>  $results
     * @return list<array<string, mixed>>
     */
    private function buildKeptForLog(array $final, array $results): array
    {
        $out = [];
        foreach ($final as $i => $hit) {
            $r = $results[$i] ?? null;
            if (! is_array($r)) {
                continue;
            }
            $row = [
                'source' => $r['source'],
                'text' => $r['text'],
                'score' => $r['score'],
            ];
            if ($r['source'] === 'history') {
                $row['user_message'] = (string) ($hit->_source['user_message'] ?? '');
                $row['assistant_message'] = (string) ($hit->_source['assistant_message'] ?? '');
                $row['conversation_id'] = (string) ($hit->_source['conversation_id'] ?? '');
            }
            $out[] = $row;
        }

        return $out;
    }

    /**
     * @param  list<array<string, mixed>>  $kept
     * @return array{0: ?float, 1: ?float}
     */
    private function scoreRangeFromKept(array $kept): array
    {
        $scores = [];
        foreach ($kept as $row) {
            $s = $row['score'] ?? null;
            if (is_float($s) || is_int($s)) {
                $scores[] = (float) $s;
            }
        }
        if ($scores === []) {
            return [null, null];
        }

        return [min($scores), max($scores)];
    }

    /**
     * Deduplicate by text AND return a log of which hits were removed (and which keeper won).
     *
     * @param  list<Hit>  $hits
     * @return array{0: list<Hit>, 1: list<array{_id: string, source: string, kept_by_id: string, text: string}>}
     */
    private function deduplicateByTextWithLog(array $hits): array
    {
        $keeperByHash = [];
        $out = [];
        $removals = [];
        foreach ($hits as $hit) {
            $text = (string) ($hit->_source['text'] ?? '');
            $key = md5($text);
            if (isset($keeperByHash[$key])) {
                $removals[] = [
                    '_id' => $hit->_id,
                    'source' => (string) ($hit->_source['retrieval_source'] ?? 'unknown'),
                    'kept_by_id' => $keeperByHash[$key],
                    'text' => $this->snippet($text, 160),
                ];

                continue;
            }
            $keeperByHash[$key] = $hit->_id;
            $out[] = $hit;
        }

        return [$out, $removals];
    }

    /**
     * Per-source counts at each stage so you can see the funnel at a glance.
     *
     * Stages:
     *  - raw:                 hits returned by ES (pre-dedup)
     *  - post_dedup:          after text-equality dedup
     *  - rerank_input:        went into Cohere rerank (shouldRerank() === true)
     *  - pass_through:        bypassed rerank (shouldRerank() === false)
     *  - rerank_kept:         survived threshold
     *  - dropped_threshold:   scored but below scoreThreshold
     *
     * @param  list<array{source: string, _id: string, _score: float|null, text: string}>  $rawHits
     * @param  list<array{_id: string, source: string, kept_by_id: string, text: string}>  $dedupRemovals
     * @param  list<Hit>  $rerankInput
     * @param  list<Hit>  $passThrough
     * @param  list<RerankedHit>  $rerankKept
     * @param  list<array<string, mixed>>  $droppedBelowThreshold
     * @param  list<string>  $sourcesQueried
     * @param  list<string>  $sourcesSkipped
     * @return array<string, array<string, int>>
     */
    private function buildStageCounts(
        array $rawHits,
        array $dedupRemovals,
        array $rerankInput,
        array $passThrough,
        array $rerankKept,
        array $droppedBelowThreshold,
        array $sourcesQueried,
        array $sourcesSkipped,
    ): array {
        $keys = array_values(array_unique(array_merge(
            array_map(static fn (RetrievalSource $s): string => $s->sourceKey(), $this->sources),
            $sourcesQueried,
            $sourcesSkipped,
        )));

        $init = array_fill_keys($keys, 0);

        $raw = $init;
        foreach ($rawHits as $row) {
            $s = (string) ($row['source'] ?? '');
            $raw[$s] = ($raw[$s] ?? 0) + 1;
        }

        $removed = $init;
        foreach ($dedupRemovals as $row) {
            $s = (string) ($row['source'] ?? '');
            $removed[$s] = ($removed[$s] ?? 0) + 1;
        }
        $postDedup = $init;
        foreach ($keys as $k) {
            $postDedup[$k] = ($raw[$k] ?? 0) - ($removed[$k] ?? 0);
        }

        $rerankInputCounts = $init;
        foreach ($rerankInput as $h) {
            $s = (string) ($h->_source['retrieval_source'] ?? '');
            $rerankInputCounts[$s] = ($rerankInputCounts[$s] ?? 0) + 1;
        }

        $passThroughCounts = $init;
        foreach ($passThrough as $h) {
            $s = (string) ($h->_source['retrieval_source'] ?? '');
            $passThroughCounts[$s] = ($passThroughCounts[$s] ?? 0) + 1;
        }

        $keptCounts = $init;
        foreach ($rerankKept as $h) {
            $s = (string) ($h->_source['retrieval_source'] ?? '');
            $keptCounts[$s] = ($keptCounts[$s] ?? 0) + 1;
        }

        $droppedCounts = $init;
        foreach ($droppedBelowThreshold as $row) {
            $s = (string) ($row['source'] ?? '');
            $droppedCounts[$s] = ($droppedCounts[$s] ?? 0) + 1;
        }

        return [
            'raw' => $raw,
            'dedup_removed' => $removed,
            'post_dedup' => $postDedup,
            'rerank_input' => $rerankInputCounts,
            'pass_through' => $passThroughCounts,
            'rerank_kept' => $keptCounts,
            'dropped_threshold' => $droppedCounts,
        ];
    }

    private function snippet(string $text, int $len): string
    {
        $text = preg_replace('/\s+/', ' ', trim($text)) ?? '';
        if ($text === '' || mb_strlen($text) <= $len) {
            return $text;
        }

        return mb_substr($text, 0, $len - 1).'…';
    }

    /**
     * @param  array<int, Hit|RerankedHit>  $hits
     * @return list<array<string, mixed>>
     */
    private function mapRerankedToResults(array $hits): array
    {
        $out = [];
        foreach ($hits as $h) {
            $src = $h->_source;
            $sourceTag = (string) ($src['retrieval_source'] ?? 'unknown');
            $text = (string) ($src['text'] ?? '');
            $score = $h instanceof RerankedHit ? $h->_rerank_score : (float) ($h->_score ?? 0.0);
            $meta = $this->buildMeta($src, $h->_id, $this->sourceForKey($sourceTag));

            $out[] = [
                'source' => $sourceTag,
                'text' => $text,
                'score' => $score,
                'meta' => $meta,
            ];
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $src
     * @return array<string, mixed>
     */
    private function buildMeta(array $src, string $id, ?RetrievalSource $retrievalSource): array
    {
        $meta = ['_id' => $id];
        $names = $retrievalSource?->metaFieldNames() ?? [];
        if ($names !== []) {
            return $meta + array_intersect_key($src, array_flip($names));
        }

        return $meta + array_intersect_key($src, array_flip([
            'source_id', 'position', 'link', 'topic', 'conversation_id', 'created_at',
        ]));
    }
}
