<?php

declare(strict_types=1);

namespace Sigmie\AgentTools;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Sigmie\AgentTools\Agents\BatchTaggingAgent;
use Sigmie\AgentTools\Agents\RetrievalPlannerAgent;
use Sigmie\AgentTools\Agents\TaggingAgent;
use Sigmie\AgentTools\Laravel\SigmieAgent;

/**
 * Register default agent classes from a service provider.
 *
 * <code>
 * use Sigmie\AgentTools\AgentTools;
 * use App\Agent\MyApplicationAgent;
 *
 * AgentTools::defaultAgent(MyApplicationAgent::class);
 * AgentTools::retrievalPlannerAgent(App\Agent\MyRetrievalPlanner::class);
 * AgentTools::taggingAgent(App\MagicTags\MyTaggingAgent::class);
 * AgentTools::batchTaggingAgent(App\MagicTags\MyBatchTaggingAgent::class);
 * </code>
 */
class AgentTools
{
    private static ?string $defaultAgentClass = null;

    /** @var class-string<Agent&HasStructuredOutput>|null */
    private static ?string $retrievalPlannerAgentClass = null;

    /** @var class-string<Agent&HasStructuredOutput>|null */
    private static ?string $taggingAgentClass = null;

    /** @var class-string<Agent&HasStructuredOutput>|null */
    private static ?string $batchTaggingAgentClass = null;

    /**
     * @param  class-string<SigmieAgent>  $class
     */
    public static function defaultAgent(string $class): void
    {
        self::$defaultAgentClass = $class;
    }

    /**
     * @param  class-string<Agent&HasStructuredOutput>  $class
     */
    public static function retrievalPlannerAgent(string $class): void
    {
        self::$retrievalPlannerAgentClass = $class;
    }

    /**
     * @param  class-string<Agent&HasStructuredOutput>  $class
     */
    public static function taggingAgent(string $class): void
    {
        self::$taggingAgentClass = $class;
    }

    /**
     * @param  class-string<Agent&HasStructuredOutput>  $class
     */
    public static function batchTaggingAgent(string $class): void
    {
        self::$batchTaggingAgentClass = $class;
    }

    /**
     * @return class-string<SigmieAgent>
     */
    public static function resolvedAgentClass(): string
    {
        if (self::$defaultAgentClass !== null) {
            return self::$defaultAgentClass;
        }

        $fromConfig = config('agent-tools.agent_class');

        return is_string($fromConfig) && $fromConfig !== ''
            ? $fromConfig
            : SigmieAgent::class;
    }

    /**
     * @return class-string<Agent&HasStructuredOutput>
     */
    public static function resolvedRetrievalPlannerAgentClass(): string
    {
        if (self::$retrievalPlannerAgentClass !== null) {
            return self::$retrievalPlannerAgentClass;
        }

        $fromConfig = config('agent-tools.retrieval_planner_agent_class');

        return is_string($fromConfig) && $fromConfig !== '' && class_exists($fromConfig)
            ? $fromConfig
            : RetrievalPlannerAgent::class;
    }

    /**
     * @return class-string<Agent&HasStructuredOutput>
     */
    public static function resolvedTaggingAgentClass(): string
    {
        if (self::$taggingAgentClass !== null) {
            return self::$taggingAgentClass;
        }

        $fromConfig = config('agent-tools.tagging_agent_class');

        return is_string($fromConfig) && $fromConfig !== '' && class_exists($fromConfig)
            ? $fromConfig
            : TaggingAgent::class;
    }

    /**
     * @return class-string<Agent&HasStructuredOutput>
     */
    public static function resolvedBatchTaggingAgentClass(): string
    {
        if (self::$batchTaggingAgentClass !== null) {
            return self::$batchTaggingAgentClass;
        }

        $fromConfig = config('agent-tools.batch_tagging_agent_class');

        return is_string($fromConfig) && $fromConfig !== '' && class_exists($fromConfig)
            ? $fromConfig
            : BatchTaggingAgent::class;
    }
}
