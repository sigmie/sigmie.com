<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\Laravel;

use Illuminate\Support\Str;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Sigmie\AgentTools\Agents\EvaluatorAgent;
use Symfony\Component\Yaml\Yaml;

/**
 * Runs configured multi-turn / multi-session scenarios against the real agent, then scores
 * transcripts with {@see EvaluatorAgent}. Supports hard tool-call assertions via `assert_tools`.
 *
 * YAML scenario format:
 *
 *   user_token: "1"            # optional; overrides --user-token for this scenario
 *
 *   # Option A – single session (backward compat)
 *   turns:
 *     - First message
 *     - Follow-up
 *
 *   # Option B – multi-session (each gets its own conversation_id)
 *   sessions:
 *     - name: save fact
 *       turns: [...]
 *       assert_tools: [memory_save]   # hard assert: tool must be called
 *     - name: recall fact
 *       turns: [...]
 *       assert_tools: [unified_search]
 *
 *   thresholds:
 *     grounding: 3
 *     relevance: 4
 *     hallucination: 4
 *     tool_compliance: 4
 *     coherence: 4
 */
class AgentToolsEvalCommand extends AgentToolsBaseCommand
{
    private const DIMENSIONS = ['grounding', 'relevance', 'hallucination', 'tool_compliance', 'coherence'];

    protected $signature = 'sigmie:agent-tools:eval
                            {--scenario= : Run a single scenario by key (default: all)}
                            {--user-token=1 : User id (users.id) for conversations and memory}
                            {--model= : Agent model (default: agent class default)}
                            {--eval-model=gpt-4o : Evaluator model}
                            {--debug : Print full transcript, tool metadata, and raw scores}';

    protected $description = 'Run agent eval scenarios (real LLM + ES), score transcripts with a structured evaluator.';

    public function handle(): int
    {
        $agentClass = $this->resolveAgentClass();
        if ($agentClass === null) {
            return self::FAILURE;
        }

        $scenarios = $this->resolveScenarios();
        if ($scenarios === []) {
            $this->error('No eval scenarios to run (add YAML files under '.base_path((string) config('agent-tools.eval_scenarios_path', 'tests/scenarios')).' or set agent-tools.eval_scenarios).');

            return self::FAILURE;
        }

        $defaultUserToken = (string) $this->option('user-token');
        $model = (string) ($this->option('model') ?: $agentClass::defaultModel());
        $evalModel = (string) $this->option('eval-model');
        $debug = (bool) $this->option('debug');

        $this->ensureAgentElasticsearchIndices();

        $logger = new ConsoleLogger($this->output, $debug);

        /** @var SigmieAgent $agent */
        $agent = app()->make($agentClass, ['logger' => $logger]);

        /** @var AgentTurnDebugCollector $collector */
        $collector = app(AgentTurnDebugCollector::class);

        $allPassed = true;
        $ran = 0;

        foreach ($scenarios as $key => $scenario) {
            $ran++;

            $scenarioUserToken = (string) ($scenario['user_token'] ?? $defaultUserToken);
            $user = (object) ['id' => ctype_digit($scenarioUserToken) ? (int) $scenarioUserToken : $scenarioUserToken];

            $sessions = $scenario['sessions'];
            $totalTurns = (int) array_sum(array_map(fn (array $s): int => count($s['turns']), $sessions));
            $multiSession = count($sessions) > 1;

            $this->newLine();
            $this->line(sprintf(
                '<fg=cyan>Scenario:</> %s (%d session(s), %d turn(s))',
                $key,
                count($sessions),
                $totalTurns
            ));

            /** @var list<string> $transcriptParts plain-text sections for the evaluator */
            $transcriptParts = [];
            /** @var list<string> $toolSummaries per-turn summary lines */
            $toolSummaries = [];

            $scenarioAborted = false;

            foreach ($sessions as $sessionIndex => $session) {
                $sessionNum = $sessionIndex + 1;
                $sessionName = (string) ($session['name'] ?? "Session {$sessionNum}");
                /** @var list<string> $assertTools */
                $assertTools = is_array($session['assert_tools'] ?? null) ? array_values($session['assert_tools']) : [];

                $conversationId = Str::uuid()->toString();
                $agent->continue($conversationId, $user);

                $sessionUnifiedBaseline = count($collector->toArray()['unified_search']);
                $unifiedStart = $sessionUnifiedBaseline;
                $toolsStart = count($collector->toArray()['tools']);

                if ($multiSession) {
                    $this->line("  <fg=yellow>[session {$sessionNum}]</> {$sessionName}");
                }

                $sessionTranscriptLines = [];

                foreach ($session['turns'] as $turnIndex => $userMessage) {
                    $turnNum = $turnIndex + 1;
                    $turnLabel = $multiSession
                        ? "Session {$sessionNum} ({$sessionName}) Turn {$turnNum}"
                        : "Turn {$turnNum}";

                    $sessionTranscriptLines[] = "user: {$userMessage}";

                    try {
                        $response = $agent->prompt($userMessage, provider: Lab::tryFrom((string) config('ai.default', 'anthropic')) ?? Lab::Anthropic, model: $model);
                    } catch (\Throwable $e) {
                        $this->error("{$turnLabel} failed: ".$e->getMessage());
                        $allPassed = false;
                        $scenarioAborted = true;
                        break 2;
                    }

                    $assistantText = (string) $response->text;
                    $sessionTranscriptLines[] = "assistant: {$assistantText}";

                    $debugAfter = $collector->toArray();
                    $newUnified = array_slice($debugAfter['unified_search'], $unifiedStart);
                    $unifiedStart += count($newUnified);
                    $toolSummaries[] = "{$turnLabel}: ".$this->summarizeUnifiedRuns($newUnified);

                    if ($debug) {
                        $this->line("<fg=gray>  {$turnLabel} search:</> {$toolSummaries[array_key_last($toolSummaries)]}");
                    }
                }

                // Hard-assert tool calls for this session.
                $sessionToolCalls = array_slice($collector->toArray()['tools'], $toolsStart);
                $calledTools = array_values(array_unique(array_column($sessionToolCalls, 'name')));
                $sessionUnifiedRunCount = count($collector->toArray()['unified_search']) - $sessionUnifiedBaseline;

                if ($assertTools !== []) {
                    $toolSummaries[] = "{$sessionName} tools called: ".(implode(', ', $calledTools) ?: 'none');
                }

                foreach ($assertTools as $required) {
                    // unified_search is recorded via {@see RecordsAgentToolDebug}; also accept collector
                    // unified_search runs so asserts stay correct if instrumentation diverges.
                    $passed = match ($required) {
                        'unified_search' => in_array('unified_search', $calledTools, true) || $sessionUnifiedRunCount > 0,
                        default => in_array($required, $calledTools, true),
                    };
                    if (! $passed) {
                        $allPassed = false;
                    }
                    $this->line(sprintf(
                        '  %-20s %s %s',
                        'assert_tools:',
                        $passed ? '<fg=green>PASS</>' : '<fg=red>FAIL</>',
                        "{$required} ".($passed ? 'called' : 'NOT called')." in \"{$sessionName}\""
                    ));
                }

                // Append this session to the evaluator transcript.
                if ($multiSession) {
                    $header = "--- {$sessionName} (conversation: {$conversationId}) ---";
                    $transcriptParts[] = $header."\n".implode("\n", $sessionTranscriptLines);
                } else {
                    $transcriptParts[] = implode("\n", $sessionTranscriptLines);
                }
            }

            if ($scenarioAborted) {
                continue;
            }

            $transcriptBlock = implode("\n\n", $transcriptParts);
            $toolBlock = implode("\n", $toolSummaries);
            $evalPrompt = <<<TXT
## Transcript
{$transcriptBlock}

## Per-turn tool metadata
{$toolBlock}
TXT;

            if ($debug) {
                $this->newLine();
                $this->line('<fg=magenta>── Evaluator input ──</>');
                $this->line($evalPrompt);
            }

            try {
                $evalResponse = EvaluatorAgent::make()->prompt($evalPrompt, provider: Lab::tryFrom((string) config('ai.default', 'anthropic')) ?? Lab::Anthropic, model: $evalModel);
            } catch (\Throwable $e) {
                $this->error('Evaluator failed: '.$e->getMessage());
                $allPassed = false;

                continue;
            }

            if (! $evalResponse instanceof StructuredAgentResponse) {
                $this->error('Evaluator did not return structured output.');
                $allPassed = false;

                continue;
            }

            /** @var array<string, mixed> $structured */
            $structured = $evalResponse->structured;

            if ($debug) {
                $this->line('<fg=magenta>── Evaluator structured ──</>');
                $this->line(json_encode($structured, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }

            $thresholds = $scenario['thresholds'];

            foreach (self::DIMENSIONS as $dim) {
                $threshold = (int) ($thresholds[$dim] ?? 0);
                $raw = $structured[$dim] ?? 0;
                $score = is_numeric($raw) ? (int) $raw : 0;
                $pass = $score >= $threshold;
                if (! $pass) {
                    $allPassed = false;
                }
                $this->line(sprintf(
                    '  %-18s %d/5 %s (threshold: %d)',
                    $dim.':',
                    $score,
                    $pass ? 'PASS' : 'FAIL',
                    $threshold
                ));
            }

            $notes = trim((string) ($structured['notes'] ?? ''));
            if ($notes !== '') {
                $this->line('  notes: '.$notes);
            }
        }

        $this->newLine();
        $total = count($scenarios);
        if ($allPassed) {
            $this->info("Results: {$ran}/{$total} scenario(s) passed.");

            return self::SUCCESS;
        }

        $this->error("Results: at least one scenario failed (ran {$ran}/{$total}).");

        return self::FAILURE;
    }

    /**
     * @param  list<array<string, mixed>>  $runs
     */
    private function summarizeUnifiedRuns(array $runs): string
    {
        if ($runs === []) {
            return 'no unified_search call';
        }

        $parts = [];
        foreach ($runs as $run) {
            $n = 0;
            if (isset($run['returned']) && is_numeric($run['returned'])) {
                $n = (int) $run['returned'];
            } elseif (isset($run['results_returned']) && is_array($run['results_returned'])) {
                $n = count($run['results_returned']);
            }
            $parts[] = 'unified_search returned '.$n.' result(s)';
        }

        return implode('; ', $parts);
    }

    /**
     * @return array<string, array{
     *   user_token: string|null,
     *   sessions: list<array{name: string, turns: list<string>, assert_tools: list<string>}>,
     *   thresholds: array<string, int>
     * }>
     */
    private function resolveScenarios(): array
    {
        $fromFiles = $this->loadScenariosFromDirectory();
        /** @var mixed $configRaw */
        $configRaw = config('agent-tools.eval_scenarios', []);
        $fromConfig = is_array($configRaw) ? $configRaw : [];

        /** @var array<string, mixed> $raw */
        $raw = $fromFiles !== [] ? $fromFiles : $fromConfig;

        return $this->filterAndNormalizeScenarios($raw);
    }

    /**
     * @return array<string, mixed>
     */
    private function loadScenariosFromDirectory(): array
    {
        $relative = (string) config('agent-tools.eval_scenarios_path', 'tests/scenarios');
        $dir = base_path($relative);
        if (! is_dir($dir)) {
            return [];
        }

        $files = array_merge(
            glob($dir.'/*.yaml') ?: [],
            glob($dir.'/*.yml') ?: [],
        );
        sort($files);

        $out = [];
        foreach ($files as $file) {
            $key = pathinfo($file, PATHINFO_FILENAME);
            if (! is_string($key) || $key === '') {
                continue;
            }
            try {
                $parsed = Yaml::parseFile($file);
            } catch (\Throwable $e) {
                $this->warn("Eval scenario file \"{$file}\" skipped: ".$e->getMessage());

                continue;
            }
            if (! is_array($parsed)) {
                $this->warn("Eval scenario file \"{$file}\" skipped: root must be a mapping.");

                continue;
            }
            $out[$key] = $parsed;
        }

        return $out;
    }

    /**
     * Normalize raw YAML/config into typed scenario structures.
     * Supports both `turns` (single session, backward compat) and `sessions` (multi-session).
     *
     * @param  array<string, mixed>  $raw
     * @return array<string, array{
     *   user_token: string|null,
     *   sessions: list<array{name: string, turns: list<string>, assert_tools: list<string>}>,
     *   thresholds: array<string, int>
     * }>
     */
    private function filterAndNormalizeScenarios(array $raw): array
    {
        $filter = $this->option('scenario');
        $filterKey = is_string($filter) && $filter !== '' ? $filter : null;

        $out = [];
        foreach ($raw as $key => $item) {
            if (! is_string($key) || ! is_array($item)) {
                continue;
            }
            if ($filterKey !== null && $key !== $filterKey) {
                continue;
            }

            $thresholds = $item['thresholds'] ?? null;
            if (! is_array($thresholds)) {
                $this->warn("Scenario \"{$key}\" skipped: missing thresholds.");

                continue;
            }

            $thresh = [];
            foreach (self::DIMENSIONS as $dim) {
                if (isset($thresholds[$dim]) && is_numeric($thresholds[$dim])) {
                    $thresh[$dim] = (int) $thresholds[$dim];
                }
            }
            if (count($thresh) !== count(self::DIMENSIONS)) {
                $this->warn("Scenario \"{$key}\" skipped: thresholds must define all dimensions: ".implode(', ', self::DIMENSIONS));

                continue;
            }

            $userToken = isset($item['user_token']) ? (string) $item['user_token'] : null;

            // Build sessions list.
            $sessions = [];

            if (isset($item['sessions']) && is_array($item['sessions'])) {
                // Multi-session format.
                foreach ($item['sessions'] as $si => $sessionRaw) {
                    if (! is_array($sessionRaw)) {
                        continue;
                    }
                    $turns = $this->parseTurnList($sessionRaw['turns'] ?? null);
                    if ($turns === []) {
                        continue;
                    }
                    $assertTools = $this->parseStringList($sessionRaw['assert_tools'] ?? null);
                    $sessionName = isset($sessionRaw['name']) ? (string) $sessionRaw['name'] : 'Session '.($si + 1);
                    $sessions[] = ['name' => $sessionName, 'turns' => $turns, 'assert_tools' => $assertTools];
                }
            } elseif (isset($item['turns'])) {
                // Single-session backward-compat format.
                $turns = $this->parseTurnList($item['turns']);
                if ($turns !== []) {
                    $sessions[] = ['name' => 'Session 1', 'turns' => $turns, 'assert_tools' => []];
                }
            }

            if ($sessions === []) {
                $this->warn("Scenario \"{$key}\" skipped: no valid sessions or turns found.");

                continue;
            }

            $out[$key] = [
                'user_token' => $userToken,
                'sessions' => $sessions,
                'thresholds' => $thresh,
            ];
        }

        if ($filterKey !== null && $out === []) {
            $this->error("No scenario found for key \"{$filterKey}\".");
        }

        return $out;
    }

    /**
     * @param  mixed  $raw
     * @return list<string>
     */
    private function parseTurnList(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $t) {
            if (is_string($t) && $t !== '') {
                $out[] = $t;
            }
        }

        return $out;
    }

    /**
     * @param  mixed  $raw
     * @return list<string>
     */
    private function parseStringList(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $v) {
            if (is_string($v) && $v !== '') {
                $out[] = $v;
            }
        }

        return $out;
    }

}
