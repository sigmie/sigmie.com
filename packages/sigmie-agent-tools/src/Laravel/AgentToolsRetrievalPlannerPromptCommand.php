<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\Laravel;

use Illuminate\Console\Command;

/**
 * Runs only the retrieval planner (same code path as {@see \Sigmie\AgentTools\Tools\UnifiedSearchTool}).
 */
class AgentToolsRetrievalPlannerPromptCommand extends Command
{
    protected $signature = 'sigmie:agent-tools:retrieval-planner:prompt
                            {query : Search intent / user query to plan retrieval for}
                            {--model= : Override config retrieval_planner_model}
                            {--provider= : Override config retrieval_planner_provider (Lab value, e.g. openai)}
                            {--json : Print only JSON (plan fields + fallback)}';

    protected $description = 'Run the retrieval planner LLM in isolation (no unified_search, no Elasticsearch).';

    public function handle(): int
    {
        $query = trim((string) $this->argument('query'));
        if ($query === '') {
            $this->error('query must be non-empty.');

            return self::FAILURE;
        }

        $modelOpt = $this->option('model');
        if (is_string($modelOpt) && trim($modelOpt) !== '') {
            config(['agent-tools.retrieval_planner_model' => trim($modelOpt)]);
        }

        $providerOpt = $this->option('provider');
        if (is_string($providerOpt) && trim($providerOpt) !== '') {
            config(['agent-tools.retrieval_planner_provider' => trim($providerOpt)]);
        }

        $sources = UnifiedSearchIndices::resolvedSources();
        $logger = new ConsoleLogger($this->output, false);

        $start = microtime(true);
        $result = RetrievalPlanning::resolve($query, $sources, $logger);
        $ms = (int) ((microtime(true) - $start) * 1000);

        $plan = $result['plan'];
        $fallback = (bool) $result['fallback'];

        $payload = [
            'fallback' => $fallback,
            'ms' => $ms,
            'source_queries' => $plan->sourceQueries,
            'source_relevances' => $plan->sourceRelevances,
            'rerank_query' => $plan->rerankQuery,
        ];

        if ($this->option('json')) {
            $this->line(json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $this->info('Retrieval planner');
        $this->line(sprintf('<fg=gray>provider:</> %s  <fg=gray>model:</> %s  <fg=gray>%dms</>',
            (string) config('agent-tools.retrieval_planner_provider', 'openai'),
            (string) config('agent-tools.retrieval_planner_model', 'gpt-4o-mini'),
            $ms,
        ));
        $this->newLine();
        $this->line('<fg=cyan>Input</> '.$query);
        $this->newLine();
        $fb = $fallback ? '<fg=yellow>yes (used fallback plan)</>' : '<fg=green>no</>';
        $this->line("<fg=white>fallback:</> {$fb}");
        foreach ($plan->sourceQueries as $sourceKey => $sourceQuery) {
            $this->line("<fg=white>{$sourceKey}_query:</>  {$sourceQuery}");
            $rel = $plan->relevanceFor($sourceKey);
            $this->line("<fg=white>{$sourceKey}_relevance:</>  {$rel}");
        }
        $this->line('<fg=white>rerank_query:</>    '.$plan->rerankQuery);

        return self::SUCCESS;
    }
}
