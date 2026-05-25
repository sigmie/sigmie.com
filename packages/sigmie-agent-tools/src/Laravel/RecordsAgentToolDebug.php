<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\Laravel;

/**
 * Records tool name, arguments, and structured result into {@see AgentTurnDebugCollector}.
 */
trait RecordsAgentToolDebug
{
    /**
     * @param  array<string, mixed>  $arguments
     */
    protected function recordAgentToolDebug(string $name, array $arguments, mixed $result): void
    {
        try {
            app(AgentTurnDebugCollector::class)->addToolCall($name, $arguments, $result);
        } catch (\Throwable) {
            // Collector optional outside full HTTP stack (e.g. isolated tests).
        }
    }
}
