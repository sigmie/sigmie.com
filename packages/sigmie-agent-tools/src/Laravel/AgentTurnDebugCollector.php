<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\Laravel;

/**
 * Request-scoped accumulator for agent debug (system prompt, unified search, tool calls).
 * Bind as scoped in the service provider; resolve once per HTTP request.
 */
class AgentTurnDebugCollector
{
    private ?string $systemPrompt = null;

    /** @var list<array<string, mixed>> */
    private array $unifiedSearchRuns = [];

    /** @var list<array{name: string, arguments: array<string, mixed>, result: mixed}> */
    private array $toolCalls = [];

    public function setSystemPrompt(string $prompt): void
    {
        $this->systemPrompt = $prompt;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function addUnifiedSearchRun(array $payload): void
    {
        $this->unifiedSearchRuns[] = $payload;
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    public function addToolCall(string $name, array $arguments, mixed $result): void
    {
        $this->toolCalls[] = [
            'name' => $name,
            'arguments' => $arguments,
            'result' => $result,
        ];
    }

    /**
     * @return array{
     *     system_prompt: ?string,
     *     unified_search: list<array<string, mixed>>,
     *     tools: list<array{name: string, arguments: array<string, mixed>, result: mixed}>
     * }
     */
    public function toArray(): array
    {
        return [
            'system_prompt' => $this->systemPrompt,
            'unified_search' => $this->unifiedSearchRuns,
            'tools' => $this->toolCalls,
        ];
    }
}
