<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\Laravel;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * PSR-3 logger for the Artisan console.
 *
 * Default: friendly human-readable lines only.
 * With $debug = true: also dumps raw context after each friendly line.
 */
class ConsoleLogger extends AbstractLogger
{
    /**
     * @var array<string, string> log message key => friendly template (use {key} for context values)
     */
    private const FRIENDLY = [
        'history_search.start' => '  <fg=blue>→</> Searching conversation history...',
        'history_search.done' => '  <fg=green>✓</> Found <fg=yellow>{count}</> turn(s) in history',
        'memory_search.start' => '  <fg=blue>→</> Searching memory...',
        'memory_search.done' => '  <fg=green>✓</> Found <fg=yellow>{count}</> fact(s) in memory',
        'memory_save.start' => null,
        'memory_save.done' => null,
        'memory_delete.start' => '  <fg=red>→</> Deleting from memory: <fg=white>"{fact}"</>',
        'memory_delete.done' => null,
        'clarification.pending' => '  <fg=yellow>?</> Needs clarification',
        'agent.turn_start' => null,
        'agent.prompt_included_turns' => null,
        'agent.llm_turn' => null,
        'agent.tool_invocation' => null,
        'agent.tool_result' => null,
        'openai.chat.request' => null,
        'openai.chat.response' => null,
        'knowledge_search.start' => '  <fg=blue>→</> Searching knowledge base...',
        'knowledge_search.done' => '  <fg=green>✓</> Found <fg=yellow>{count}</> result(s)',
        'unified_search.start' => null,
        'unified_search.done' => null,
    ];

    /** @var array<string, int> */
    private const LEVEL_PRIORITY = [
        LogLevel::DEBUG => 100,
        LogLevel::INFO => 200,
        LogLevel::NOTICE => 250,
        LogLevel::WARNING => 300,
        LogLevel::ERROR => 400,
        LogLevel::CRITICAL => 500,
        LogLevel::ALERT => 550,
        LogLevel::EMERGENCY => 600,
    ];

    private bool $memorySavedThisTurn = false;

    /** @var list<string> */
    private array $savedMemoryFactsThisTurn = [];

    private ?int $lastUnifiedReturned = null;

    public function __construct(
        private readonly OutputInterface $output,
        private readonly bool $debug = false,
    ) {}

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $message = (string) $message;
        $levelStr = is_string($level) ? $level : LogLevel::INFO;

        if ($this->priority($levelStr) < $this->priority(LogLevel::INFO)) {
            return;
        }

        $friendlyLine = $this->friendlyLine($message, $context);

        if ($friendlyLine !== null) {
            $this->output->writeln($friendlyLine);
        }

        $this->writeExtras($message, $context);

        if ($this->debug) {
            $this->writeTechnical($levelStr, $message, $context);
        }

        if ($friendlyLine === null && ! $this->debug) {
            if ($levelStr === LogLevel::WARNING || $this->priority($levelStr) >= $this->priority(LogLevel::WARNING)) {
                $this->writeTechnical($levelStr, $message, $context);
            }
        }
    }

    private function writeExtras(string $message, array $context): void
    {
        if ($message === 'agent.turn_start') {
            $this->memorySavedThisTurn = false;
            $this->savedMemoryFactsThisTurn = [];
            $this->lastUnifiedReturned = null;

            return;
        }

        if ($message === 'agent.prompt_included_turns') {
            $count = (int) ($context['turn_count'] ?? 0);
            $mem = $context['memory_fact_count'] ?? null;
            $line = sprintf('  <fg=cyan>[context]</>  history=%d', $count);
            if ($mem !== null && (is_int($mem) || is_float($mem))) {
                $line .= sprintf('  memory=%d', (int) $mem);
            }
            $this->output->writeln($line);

            $tagsErr = $context['topic_tags_error'] ?? null;
            $topicTags = $context['topic_tags'] ?? [];
            if (is_string($tagsErr) && $tagsErr !== '') {
                $this->output->writeln(sprintf(
                    '  <fg=gray>topic tags:</> <fg=yellow>unavailable</> (%s)',
                    $this->escape($tagsErr)
                ));
            } elseif (is_array($topicTags) && $topicTags !== []) {
                $this->output->writeln(sprintf(
                    '  <fg=gray>topic tags:</> %s',
                    $this->escape(json_encode(array_values($topicTags), JSON_UNESCAPED_UNICODE))
                ));
            } else {
                $this->output->writeln('  <fg=gray>topic tags:</> []');
            }

            if ($this->debug) {
                $limit = (int) ($context['included_turns_limit'] ?? 0);
                $this->output->writeln(sprintf(
                    '     <fg=gray>(limit %d prior turns in system prompt, not from history_search tool)</>',
                    $limit,
                ));
                $turns = $context['turns'] ?? [];
                if (! is_array($turns) || $turns === []) {
                    $this->output->writeln('     <fg=gray>·</> none — empty history or first message in this conversation');
                } else {
                    $n = 0;
                    foreach ($turns as $t) {
                        if (! is_array($t)) {
                            continue;
                        }
                        $n++;
                        $u = (string) ($t['user'] ?? '');
                        $a = (string) ($t['assistant'] ?? '');
                        $this->output->writeln(sprintf('     <fg=gray>%d.</> <fg=white>user:</> %s', $n, $this->escape($u)));
                        $this->output->writeln(sprintf('        <fg=white>assistant:</> %s', $this->escape($a)));
                    }
                }
            }

            return;
        }

        if ($message === 'openai.chat.response') {
            $this->writePipelineSynthAndMemory($context);

            return;
        }

        if ($message === 'memory_save.done') {
            $this->memorySavedThisTurn = true;
            $fact = trim((string) ($context['fact'] ?? ''));
            if ($fact !== '') {
                $this->savedMemoryFactsThisTurn[] = $fact;
            }
        }

        if ($message === 'memory_search.start' || $message === 'history_search.start') {
            $this->writeSearchRerankQueries($context);
        }

        if ($message === 'memory_search.done') {
            $kept = $context['kept'] ?? [];
            if (is_array($kept) && $kept !== []) {
                foreach ($kept as $item) {
                    if (! is_array($item)) {
                        continue;
                    }
                    $text = (string) ($item['text'] ?? '');
                    $score = $item['score'] ?? null;
                    $scorePart = is_float($score) || is_int($score)
                        ? sprintf(' <fg=gray>(%.4f)</>', (float) $score)
                        : '';
                    $this->output->writeln('     <fg=green>·</> '.$this->escape($text).$scorePart);
                }
            }
            $this->writeRerankOmittedFromToolResult($context);
        }

        if ($message === 'history_search.done') {
            $kept = $context['kept'] ?? [];
            if (is_array($kept) && $kept !== []) {
                foreach ($kept as $item) {
                    if (! is_array($item)) {
                        continue;
                    }
                    $text = (string) ($item['text'] ?? '');
                    $score = $item['score'] ?? null;
                    $scorePart = is_float($score) || is_int($score)
                        ? sprintf(' <fg=gray>(%.4f)</>', (float) $score)
                        : '';
                    $this->output->writeln('     <fg=green>·</> '.$this->escape($text).$scorePart);
                }
            }
            $this->writeRerankOmittedFromToolResult($context);
        }

        if ($message === 'unified_search.done') {
            $this->writeUnifiedSearchDone($context);
        }

        if ($message === 'memory_delete.done') {
            $deleted = (bool) ($context['deleted'] ?? false);
            $fact = trim((string) ($context['fact'] ?? ''));
            $label = $fact !== '' ? '"'.$this->escape($fact).'"' : 'id '.$this->escape((string) ($context['id'] ?? ''));
            if ($deleted) {
                $this->output->writeln('  <fg=green>✓</> Deleted '.$label);
            } else {
                $this->output->writeln('  <fg=red>✗</> Not deleted: '.$label);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function writeSearchRerankQueries(array $context): void
    {
        $sq = (string) ($context['search_query'] ?? '');
        $rq = (string) ($context['rerank_query'] ?? $sq);
        $searchLabel = $sq !== '' ? $this->escape($sq) : '<fg=gray>(empty — broad recall)</>';
        $this->output->writeln('     <fg=gray>search:</> '.$searchLabel);
        if ($rq !== $sq) {
            $this->output->writeln('     <fg=gray>rerank:</> '.$this->escape($rq));
        } elseif ($sq !== '') {
            $this->output->writeln('     <fg=gray>rerank:</> <fg=gray>(same as search)</>');
        } else {
            $this->output->writeln('     <fg=gray>rerank:</> '.$this->escape($rq));
        }
    }

    /**
     * Extra ES hits that rerank ranked below top-K; not part of the tool JSON returned to the LLM.
     *
     * @param  array<string, mixed>  $context
     */
    private function writeRerankOmittedFromToolResult(array $context): void
    {
        $filtered = $context['filtered_out'] ?? [];
        if (! is_array($filtered) || $filtered === []) {
            return;
        }

        $k = (int) ($context['rerank_top_k'] ?? 3);
        $this->output->writeln(sprintf(
            '     <fg=gray>Omitted from tool result (not in top %d after rerank; still valid matches):</>',
            $k,
        ));
        foreach ($filtered as $item) {
            if (! is_array($item)) {
                continue;
            }
            $text = (string) ($item['text'] ?? '');
            $score = $item['score'] ?? null;
            $scorePart = is_float($score) || is_int($score)
                ? sprintf(' <fg=gray>(rerank score: %.4f)</>', (float) $score)
                : '';
            $this->output->writeln('     <fg=gray>·</> '.$this->escape($text).$scorePart);
        }
    }

    /**
     * Per-index hit counts, merge/dedup, rerank I/O, and dismissal counts (after unified_search completes).
     *
     * @param  array<string, mixed>  $context
     */
    private function writeUnifiedSearchPipelineSummary(array $context): void
    {
        $userQ = trim((string) ($context['user_query'] ?? ''));
        if ($userQ !== '') {
            $this->output->writeln(sprintf('     <fg=gray>User query:</> %s', $this->escape($userQ)));
        }
        $fallback = $context['planner_fallback'] ?? null;
        if ($fallback !== null) {
            $this->output->writeln(sprintf(
                '     <fg=gray>Planner:</> %s%s',
                $fallback ? '<fg=yellow>fallback</>' : '<fg=green>ok</>',
                isset($context['planner_ms']) ? sprintf(' (%dms)', (int) $context['planner_ms']) : '',
            ));
        }

        $queries = $context['queries'] ?? [];
        if (is_array($queries) && $queries !== []) {
            $parts = [];
            foreach ($queries as $src => $q) {
                $parts[] = sprintf('%s: %s', (string) $src, $this->escape((string) $q));
            }
            $this->output->writeln('     <fg=gray>Queries:</> '.implode(' <fg=gray>|</> ', $parts));
        }

        $per = $context['per_source_hits'] ?? [];
        if (is_array($per) && $per !== []) {
            $order = ['memory', 'history', 'knowledge'];
            $bits = [];
            foreach ($order as $k) {
                if (! array_key_exists($k, $per)) {
                    continue;
                }
                $bits[] = sprintf('%s <fg=yellow>%d</>', $k, (int) $per[$k]);
            }
            foreach ($per as $k => $n) {
                if (in_array($k, $order, true)) {
                    continue;
                }
                $bits[] = sprintf('%s <fg=yellow>%d</>', (string) $k, (int) $n);
            }
            if ($bits !== []) {
                $this->output->writeln('     <fg=gray>Hits per index (raw):</> '.implode(' <fg=gray>·</> ', $bits).'  <fg=gray>(knowledge = after neighbor expansion)</>');
            }
        }

        $beforeDedup = (int) ($context['merged_before_dedup'] ?? 0);
        $dedupRm = (int) ($context['dedup_removed'] ?? 0);
        $afterDedup = (int) ($context['total_candidates'] ?? 0);
        $this->output->writeln(sprintf(
            '     <fg=gray>Merged:</> %d before dedup → <fg=white>%d</> after dedup',
            $beforeDedup,
            $afterDedup,
        ));
        if ($dedupRm > 0) {
            $this->output->writeln(sprintf('     <fg=gray>Dedup removed:</> %d duplicate text(s)', $dedupRm));
        }

        $rerankIn = (int) ($context['rerank_input_count'] ?? $afterDedup);
        $cap = (int) ($context['rerank_top_k_cap'] ?? 0);
        $rerankOut = (int) ($context['rerank_output_count'] ?? 0);
        if ($afterDedup > 0) {
            $this->output->writeln(sprintf(
                '     <fg=gray>Rerank:</> <fg=white>%d</> doc(s) sent to rerank API · top-K cap <fg=white>%d</> · API returned <fg=white>%d</> scored hit(s)',
                $rerankIn,
                $cap,
                $rerankOut,
            ));
        }

        $droppedArr = $context['dropped_below_threshold'] ?? [];
        $droppedN = array_key_exists('dropped_below_threshold_count', $context)
            ? (int) $context['dropped_below_threshold_count']
            : (is_array($droppedArr) ? count($droppedArr) : 0);
        $omittedArr = $context['omitted'] ?? [];
        $omittedN = array_key_exists('omitted_top_k_count', $context)
            ? (int) $context['omitted_top_k_count']
            : (is_array($omittedArr) ? count($omittedArr) : 0);
        if ($afterDedup > 0) {
            $this->output->writeln(sprintf(
                '     <fg=gray>Dismissed:</> <fg=white>%d</> below score floor · <fg=white>%d</> not in rerank top-K shortlist',
                $droppedN,
                $omittedN,
            ));
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function writeUnifiedSearchDone(array $context): void
    {
        $this->lastUnifiedReturned = (int) ($context['returned'] ?? 0);

        $this->writePipelineUnifiedSearch($context);

        if (! $this->debug) {
            return;
        }

        $kept = $context['kept'] ?? [];
        $omitted = $context['omitted'] ?? [];
        $droppedBelow = $context['dropped_below_threshold'] ?? [];
        $total = (int) ($context['total_candidates'] ?? 0);
        $returned = (int) ($context['returned'] ?? 0);
        $ms = (int) ($context['ms'] ?? 0);
        $dedupRemoved = (int) ($context['dedup_removed'] ?? 0);

        $this->writeUnifiedSearchPipelineSummary($context);

        if ($total === 0) {
            $this->output->writeln('  <fg=red>✗</> unified_search: no candidates found');
            if ($dedupRemoved > 0) {
                $this->output->writeln(sprintf('     <fg=gray>(%d duplicate text(s) removed before rerank)</>', $dedupRemoved));
            }

            return;
        }

        $dedupNote = $dedupRemoved > 0 ? sprintf(', dedup removed %d', $dedupRemoved) : '';
        $this->output->writeln(sprintf(
            '  <fg=green>✓</> unified_search: <fg=yellow>%d</> result(s) returned to agent (of %d candidates%s, %dms)',
            $returned,
            $total,
            $dedupNote,
            $ms,
        ));

        if (is_array($kept) && $kept !== []) {
            foreach ($kept as $i => $item) {
                if (! is_array($item)) {
                    continue;
                }
                $n = $i + 1;
                $source = (string) ($item['source'] ?? 'unknown');
                $text = (string) ($item['text'] ?? '');
                $score = $item['score'] ?? null;
                $sourceColor = match ($source) {
                    'memory' => 'magenta',
                    'history' => 'cyan',
                    'knowledge' => 'yellow',
                    default => 'white',
                };
                $scorePart = is_float($score) || is_int($score)
                    ? sprintf(' <fg=gray>%.4f</>', (float) $score)
                    : '';
                if ($source === 'history' && (isset($item['user_message']) || isset($item['assistant_message']))) {
                    $u = (string) ($item['user_message'] ?? '');
                    $a = (string) ($item['assistant_message'] ?? '');
                    $this->output->writeln(sprintf(
                        '     <fg=white>%d.</> <fg=%s>[%s]</>%s',
                        $n,
                        $sourceColor,
                        $source,
                        $scorePart,
                    ));
                    $this->output->writeln('        <fg=white>user:</> '.$this->escape(mb_strlen($u) > 100 ? mb_substr($u, 0, 97).'…' : $u));
                    $this->output->writeln('        <fg=white>assistant:</> '.$this->escape(mb_strlen($a) > 120 ? mb_substr($a, 0, 117).'…' : $a));

                    continue;
                }
                $shortText = mb_strlen($text) > 120 ? mb_substr($text, 0, 117).'…' : $text;
                $this->output->writeln(sprintf(
                    '     <fg=white>%d.</> <fg=%s>[%s]</>%s %s',
                    $n,
                    $sourceColor,
                    $source,
                    $scorePart,
                    $this->escape($shortText),
                ));
            }
        }

        if (is_array($droppedBelow) && $droppedBelow !== []) {
            $floor = $context['score_threshold'] ?? null;
            $floorLabel = is_float($floor) || is_int($floor)
                ? sprintf(' (threshold %.4f)', (float) $floor)
                : '';
            $this->output->writeln(sprintf(
                '     <fg=gray>Below rerank score threshold%s (%d):</>',
                $floorLabel,
                count($droppedBelow),
            ));
            foreach ($droppedBelow as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $source = (string) ($item['source'] ?? 'unknown');
                $id = (string) ($item['_id'] ?? '');
                $text = (string) ($item['text'] ?? '');
                $shortText = mb_strlen($text) > 80 ? mb_substr($text, 0, 77).'…' : $text;
                $sc = $item['score'] ?? null;
                $scPart = is_float($sc) || is_int($sc) ? sprintf(' %.4f', (float) $sc) : '';
                $this->output->writeln(sprintf(
                    '     <fg=gray>·</> [%s] id=%s%s %s',
                    $source,
                    $this->escape($id),
                    $scPart,
                    $this->escape($shortText),
                ));
            }
        }

        if (is_array($omitted) && $omitted !== []) {
            $this->output->writeln(sprintf(
                '     <fg=gray>Omitted by top-K only (%d, not in rerank shortlist):</>',
                count($omitted),
            ));
            foreach ($omitted as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $source = (string) ($item['source'] ?? 'unknown');
                $id = (string) ($item['_id'] ?? '');
                $text = (string) ($item['text'] ?? '');
                $shortText = mb_strlen($text) > 100 ? mb_substr($text, 0, 97).'…' : $text;
                $this->output->writeln(sprintf(
                    '     <fg=gray>·</> [%s] id=%s %s',
                    $source,
                    $this->escape($id),
                    $this->escape($shortText),
                ));
            }
        }

        $this->writeUnifiedSearchStageMatrix($context);
        $this->writeUnifiedSearchRawHits($context);
        $this->writeUnifiedSearchDedupRemovals($context);
        $this->writeUnifiedSearchRerankAllScored($context);

        $this->output->writeln('');
    }

    /**
     * Funnel table: raw → post-dedup → rerank input → rerank kept → pass-through → dropped (per source).
     *
     * @param  array<string, mixed>  $context
     */
    private function writeUnifiedSearchStageMatrix(array $context): void
    {
        $stages = $context['stage_counts'] ?? null;
        if (! is_array($stages) || $stages === []) {
            return;
        }

        $stageOrder = ['raw', 'dedup_removed', 'post_dedup', 'rerank_input', 'pass_through', 'rerank_kept', 'dropped_threshold'];
        $sources = [];
        foreach ($stageOrder as $stage) {
            if (! isset($stages[$stage]) || ! is_array($stages[$stage])) {
                continue;
            }
            foreach (array_keys($stages[$stage]) as $src) {
                $sources[(string) $src] = true;
            }
        }
        $sources = array_keys($sources);
        if ($sources === []) {
            return;
        }
        $preferred = ['knowledge', 'eval_faq', 'history', 'memory'];
        usort($sources, static function (string $a, string $b) use ($preferred): int {
            $ia = array_search($a, $preferred, true);
            $ib = array_search($b, $preferred, true);
            $ia = $ia === false ? PHP_INT_MAX : $ia;
            $ib = $ib === false ? PHP_INT_MAX : $ib;
            if ($ia === $ib) {
                return strcmp($a, $b);
            }

            return $ia <=> $ib;
        });

        $this->output->writeln('');
        $this->output->writeln('<fg=cyan>[funnel]</>  per-source counts at each stage');

        $labelWidth = 18;
        $colWidth = max(array_map('strlen', $sources));
        $colWidth = max($colWidth, 6);

        $header = str_pad('stage', $labelWidth);
        foreach ($sources as $src) {
            $header .= str_pad($src, $colWidth + 2, ' ', STR_PAD_LEFT);
        }
        $this->output->writeln('  <fg=gray>'.$this->escape($header).'</>');

        foreach ($stageOrder as $stage) {
            if (! isset($stages[$stage]) || ! is_array($stages[$stage])) {
                continue;
            }
            $row = str_pad($stage, $labelWidth);
            foreach ($sources as $src) {
                $val = (int) ($stages[$stage][$src] ?? 0);
                $row .= str_pad((string) $val, $colWidth + 2, ' ', STR_PAD_LEFT);
            }
            $color = $stage === 'rerank_kept' ? 'green' : ($stage === 'dropped_threshold' ? 'yellow' : 'white');
            $this->output->writeln(sprintf('  <fg=%s>%s</>', $color, $this->escape($row)));
        }
    }

    /**
     * Every raw ES hit pre-dedup with id + ES score + snippet.
     *
     * @param  array<string, mixed>  $context
     */
    private function writeUnifiedSearchRawHits(array $context): void
    {
        $raw = $context['raw_hits'] ?? null;
        if (! is_array($raw) || $raw === []) {
            return;
        }

        $grouped = [];
        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }
            $src = (string) ($row['source'] ?? 'unknown');
            $grouped[$src][] = $row;
        }

        $this->output->writeln('');
        $this->output->writeln('<fg=cyan>[raw hits]</>  pre-dedup, pre-rerank (ES scores)');
        foreach ($grouped as $src => $rows) {
            $this->output->writeln(sprintf('  <fg=white>%s</> <fg=gray>(%d)</>', $this->escape($src), count($rows)));
            foreach ($rows as $row) {
                $id = (string) ($row['_id'] ?? '');
                $sc = $row['_score'] ?? null;
                $scPart = is_float($sc) || is_int($sc) ? sprintf('%.4f', (float) $sc) : '—';
                $text = (string) ($row['text'] ?? '');
                $this->output->writeln(sprintf(
                    '     <fg=gray>·</> id=%s es=%s  %s',
                    $this->escape($id),
                    $scPart,
                    $this->escape($text),
                ));
            }
        }
    }

    /**
     * Which hits were removed by text-equality dedup and who kept the slot.
     *
     * @param  array<string, mixed>  $context
     */
    private function writeUnifiedSearchDedupRemovals(array $context): void
    {
        $removals = $context['dedup_removals'] ?? null;
        if (! is_array($removals) || $removals === []) {
            return;
        }

        $this->output->writeln('');
        $this->output->writeln(sprintf('<fg=cyan>[dedup]</>  %d hit(s) collapsed into earlier duplicates', count($removals)));
        foreach ($removals as $row) {
            if (! is_array($row)) {
                continue;
            }
            $id = (string) ($row['_id'] ?? '');
            $src = (string) ($row['source'] ?? 'unknown');
            $keptBy = (string) ($row['kept_by_id'] ?? '');
            $text = (string) ($row['text'] ?? '');
            $this->output->writeln(sprintf(
                '     <fg=gray>·</> [%s] id=%s  kept_by=%s  %s',
                $this->escape($src),
                $this->escape($id),
                $this->escape($keptBy),
                $this->escape($text),
            ));
        }
    }

    /**
     * Full Cohere rerank scored list — includes items that later fell below threshold.
     *
     * @param  array<string, mixed>  $context
     */
    private function writeUnifiedSearchRerankAllScored(array $context): void
    {
        $scored = $context['rerank_all_scored'] ?? null;
        if (! is_array($scored) || $scored === []) {
            return;
        }

        $threshold = $context['score_threshold'] ?? null;
        $thresholdLabel = is_float($threshold) || is_int($threshold)
            ? sprintf(' (threshold %.4f)', (float) $threshold)
            : '';

        $this->output->writeln('');
        $this->output->writeln(sprintf(
            '<fg=cyan>[rerank scores]</>  %d scored by Cohere%s',
            count($scored),
            $thresholdLabel,
        ));
        foreach ($scored as $row) {
            if (! is_array($row)) {
                continue;
            }
            $score = $row['score'] ?? null;
            $scPart = is_float($score) || is_int($score) ? sprintf('%.4f', (float) $score) : '—';
            $id = (string) ($row['_id'] ?? '');
            $src = (string) ($row['source'] ?? 'unknown');
            $text = (string) ($row['text'] ?? '');
            $above = is_float($threshold) || is_int($threshold)
                ? (is_numeric($score) && (float) $score >= (float) $threshold)
                : true;
            $mark = $above ? '<fg=green>✓</>' : '<fg=yellow>✗</>';
            $this->output->writeln(sprintf(
                '     %s %s  [%s] id=%s  %s',
                $mark,
                $scPart,
                $this->escape($src),
                $this->escape($id),
                $this->escape($text),
            ));
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function writePipelineUnifiedSearch(array $context): void
    {
        $queries = $context['queries'] ?? [];
        if (! is_array($queries)) {
            $queries = [];
        }
        $per = $context['per_source_hits'] ?? [];
        if (! is_array($per)) {
            $per = [];
        }

        $this->output->writeln('');
        $this->output->writeln('<fg=cyan>[search]</>');

        $kbQ = trim((string) ($queries['knowledge'] ?? ''));
        $memQ = trim((string) ($queries['memory'] ?? ''));
        $histQ = trim((string) ($queries['history'] ?? ''));

        $kbHits = (int) ($per['knowledge'] ?? 0);
        $memHits = (int) ($per['memory'] ?? 0);
        $histHits = (int) ($per['history'] ?? 0);

        $this->output->writeln(sprintf(
            '  <fg=white>kb:</>   q="%s"  → %d',
            $this->escape($kbQ !== '' ? $kbQ : '—'),
            $kbHits
        ));
        $kbTagged = $per['knowledge_tagged'] ?? null;
        $kbUntagged = $per['knowledge_untagged'] ?? null;
        $kbDedup = $per['knowledge_after_dedup'] ?? null;
        $kbExpand = $per['knowledge_after_expand'] ?? null;
        if ($kbTagged !== null && $kbUntagged !== null) {
            $this->output->writeln(sprintf(
                '  <fg=gray>  (tagged: %d + untagged: %d → %d after dedup → %d after expand)</>',
                (int) $kbTagged,
                (int) $kbUntagged,
                (int) ($kbDedup ?? 0),
                (int) ($kbExpand ?? $kbHits)
            ));
        }
        $this->output->writeln(sprintf(
            '  <fg=white>mem:</>  q="%s"  → %d',
            $this->escape($memQ !== '' ? $memQ : '—'),
            $memHits
        ));
        $this->output->writeln(sprintf(
            '  <fg=white>hist:</> q="%s"  → %d',
            $this->escape($histQ !== '' ? $histQ : '—'),
            $histHits
        ));

        $merged = (int) ($context['total_candidates'] ?? 0);
        $searchMs = (int) ($context['search_ms'] ?? $context['ms'] ?? 0);
        $this->output->writeln(sprintf('  merged=%d  %dms', $merged, $searchMs));

        $signals = $this->pipelineSignalsLine($context);
        $this->output->writeln('  <fg=gray>signals:</> '.$this->escape($signals));

        $rerankIn = (int) ($context['rerank_input_count'] ?? 0);
        $keep = (int) ($context['returned'] ?? 0);
        $drop = (int) ($context['dropped_below_threshold_count'] ?? 0) + (int) ($context['omitted_top_k_count'] ?? 0);
        $rerankMs = (int) ($context['rerank_ms'] ?? 0);
        $minS = $context['kept_score_min'] ?? null;
        $maxS = $context['kept_score_max'] ?? null;
        $scorePart = ($minS !== null && $maxS !== null && $keep > 0)
            ? sprintf('scores=%.2f–%.2f', (float) $minS, (float) $maxS)
            : 'scores=—';

        $rerankQ = trim((string) ($context['rerank_query'] ?? ''));

        $this->output->writeln('');
        $this->output->writeln('<fg=cyan>[rerank]</>');
        $this->output->writeln(sprintf(
            '  q="%s"',
            $this->escape($rerankQ !== '' ? $rerankQ : '—')
        ));
        $this->output->writeln(sprintf(
            '  in=%d  keep=%d  drop=%d  %s  %dms',
            $rerankIn,
            $keep,
            $drop,
            $scorePart,
            $rerankMs
        ));
        if (! empty($context['rerank_threshold_fallback'])) {
            $this->output->writeln('  <fg=yellow>note:</> score floor would have removed all reranked hits; kept best reranked results anyway');
        }
        $this->output->writeln('');
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function pipelineSignalsLine(array $context): string
    {
        $kept = $context['kept'] ?? [];
        if (! is_array($kept) || $kept === []) {
            return '—';
        }

        $text = '';
        foreach ($kept as $item) {
            if (! is_array($item)) {
                continue;
            }
            if (($item['source'] ?? '') === 'knowledge') {
                $text = (string) ($item['text'] ?? '');
                break;
            }
        }
        if ($text === '') {
            $text = (string) ($kept[0]['text'] ?? '');
        }

        $text = preg_replace('/\s+/', ' ', trim($text)) ?? '';
        if ($text === '') {
            return '—';
        }
        if (mb_strlen($text) > 90) {
            return mb_substr($text, 0, 87).'…';
        }

        return $text;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function writePipelineSynthAndMemory(array $context): void
    {
        $toolCalls = $context['tool_calls'] ?? null;
        if (is_array($toolCalls) && $toolCalls !== []) {
            return;
        }

        $ms = (int) ($context['ms'] ?? 0);
        $chunks = $this->lastUnifiedReturned ?? 0;

        $this->output->writeln('');
        $this->output->writeln(sprintf(
            '<fg=cyan>[synth]</>   chunks=%d  %dms',
            $chunks,
            $ms
        ));

        if ($this->memorySavedThisTurn && $this->savedMemoryFactsThisTurn !== []) {
            $this->output->writeln('<fg=cyan>[memory]</>  saved');
            foreach ($this->savedMemoryFactsThisTurn as $fact) {
                $short = mb_strlen($fact) > 200 ? mb_substr($fact, 0, 197).'…' : $fact;
                $this->output->writeln('     <fg=gray>·</> '.$this->escape($short));
            }
        } elseif ($this->memorySavedThisTurn) {
            $this->output->writeln('<fg=cyan>[memory]</>  saved');
        } else {
            $this->output->writeln('<fg=cyan>[memory]</>  no change');
        }
    }

    private function friendlyLine(string $message, array $context): ?string
    {
        if (! array_key_exists($message, self::FRIENDLY)) {
            return '<fg=gray>  '.$this->escape($message).'</>';
        }

        $template = self::FRIENDLY[$message];

        if ($template === null) {
            return null;
        }

        return preg_replace_callback('/\{(\w+)\}/', static function (array $m) use ($context): string {
            $val = $context[$m[1]] ?? '';

            return (string) $val;
        }, $template) ?? $template;
    }

    private function writeTechnical(string $level, string $message, array $context): void
    {
        $head = sprintf(
            '<fg=gray>[%s] %s</>',
            strtoupper($level),
            $this->escape($message),
        );
        $this->output->writeln($head);

        if ($context !== []) {
            $json = json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            foreach (explode("\n", $json) as $row) {
                $this->output->writeln('<fg=white>  '.$this->escape($row).'</>');
            }
        }
    }

    private function priority(string $level): int
    {
        return self::LEVEL_PRIORITY[$level] ?? 200;
    }

    private function escape(string $s): string
    {
        return str_replace('<', '\\<', $s);
    }
}
