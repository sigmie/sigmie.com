<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\Laravel;

use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Sigmie\AgentTools\AgentTools;
use Sigmie\AgentTools\Agents\RetrievalPlan;
use Sigmie\AgentTools\Agents\RetrievalPlannerAgent;
use Sigmie\AgentTools\Contracts\RetrievalSource;

/**
 * Runs the retrieval planner (structured-output LLM) used by {@see \Sigmie\AgentTools\Tools\UnifiedSearchTool}.
 */
final class RetrievalPlanning
{
    /**
     * @param  list<RetrievalSource>  $sources
     * @return array{plan: RetrievalPlan, fallback: bool}
     */
    public static function resolve(string $userQuery, array $sources, ?LoggerInterface $logger = null): array
    {
        $log = $logger ?? new NullLogger;
        $providerStr = (string) config('agent-tools.retrieval_planner_provider', config('ai.default', 'openai'));
        $lab = Lab::tryFrom($providerStr) ?? Lab::OpenAI;
        $model = trim((string) config('agent-tools.retrieval_planner_model', ''));

        $sourceKeys = array_map(
            static fn (RetrievalSource $s): string => $s->sourceKey(),
            $sources
        );

        try {
            RetrievalPlannerAgent::useSourcesForNextPrompt($sources);

            $plannerClass = AgentTools::resolvedRetrievalPlannerAgentClass();
            $agent = app()->make($plannerClass);
            $response = $agent->prompt(
                "User search intent:\n\n".$userQuery,
                provider: $lab,
                model: $model !== '' ? $model : null,
            );

            RetrievalPlannerAgent::clearSourcesForNextPrompt();

            if (! $response instanceof StructuredAgentResponse) {
                return ['plan' => RetrievalPlan::fallbackFromUserQuery($userQuery, $sourceKeys), 'fallback' => true];
            }

            $data = $response->toArray();
            if (! is_array($data) || $data === []) {
                return ['plan' => RetrievalPlan::fallbackFromUserQuery($userQuery, $sourceKeys), 'fallback' => true];
            }

            $plan = RetrievalPlan::fromStructuredResponse($data, $sourceKeys);
            $normalized = self::coalescePlan($plan, $userQuery, $sourceKeys);

            if ($normalized->rerankQuery === '') {
                return ['plan' => RetrievalPlan::fallbackFromUserQuery($userQuery, $sourceKeys), 'fallback' => true];
            }

            return ['plan' => $normalized, 'fallback' => false];
        } catch (\Throwable $e) {
            RetrievalPlannerAgent::clearSourcesForNextPrompt();
            $log->warning('unified_search.planner_failed', [
                'message' => $e->getMessage(),
            ]);

            return ['plan' => RetrievalPlan::fallbackFromUserQuery($userQuery, $sourceKeys), 'fallback' => true];
        }
    }

    /**
     * @param  list<string>  $sourceKeys
     */
    private static function coalescePlan(RetrievalPlan $plan, string $userQuery, array $sourceKeys): RetrievalPlan
    {
        $fallback = trim($userQuery);
        $rerank = $plan->rerankQuery !== '' ? $plan->rerankQuery : $fallback;
        $rr = $rerank !== '' ? $rerank : $fallback;

        $queries = [];
        foreach ($sourceKeys as $key) {
            $q = $plan->queryFor($key);
            $queries[$key] = $q !== '' ? $q : $rr;
        }

        $relevances = [];
        foreach ($sourceKeys as $key) {
            $relevances[$key] = $plan->relevanceFor($key);
        }

        return new RetrievalPlan($queries, $rr !== '' ? $rr : $fallback, $relevances);
    }
}
