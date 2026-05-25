<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\Laravel;

use Laravel\Ai\Contracts\Providers\TextProvider;
use Laravel\Ai\Events\AgentPrompted;
use Laravel\Ai\Events\AgentStreamed;
use Laravel\Ai\Events\InvokingTool;
use Laravel\Ai\Events\PromptingAgent;
use Laravel\Ai\Events\StreamingAgent;
use Laravel\Ai\Events\ToolInvoked;
use Sentry\SentrySdk;
use Sentry\Tracing\Span;
use Sentry\Tracing\SpanContext;

/**
 * Maps Laravel AI events to Sentry OpenTelemetry gen_ai.* spans (AI Agent Insights, Models, Tools).
 *
 * Enabled when the service provider registers listeners and config agent-tools.sentry_insights
 * is true. Requires sentry/sentry in the consuming application and an active Sentry transaction span.
 */
class SentryAiMonitoringSubscriber
{
    /** @var array<string, Span> */
    private static array $agentSpans = [];

    /** @var array<string, Span> */
    private static array $agentRequestSpans = [];

    /** @var array<string, Span> */
    private static array $agentParentSpans = [];

    /** @var array<string, Span> */
    private static array $toolSpans = [];

    /** @var array<string, Span> */
    private static array $toolParentSpans = [];

    public function handleStreamingAgent(StreamingAgent $event): void
    {
        $this->handlePromptingAgent($event);
    }

    public function handleAgentStreamed(AgentStreamed $event): void
    {
        $this->handleAgentPrompted($event);
    }

    public function handlePromptingAgent(PromptingAgent $event): void
    {
        $hub = SentrySdk::getCurrentHub();
        $parentSpan = $hub->getSpan();

        if ($parentSpan === null || ! $parentSpan->getSampled()) {
            return;
        }

        $system = self::resolveSystem($event->prompt->provider);
        $agentName = class_basename($event->prompt->agent);

        $agentSpan = $parentSpan->startChild(
            SpanContext::make()
                ->setOp('gen_ai.invoke_agent')
                ->setDescription($agentName)
                ->setData([
                    'gen_ai.system' => $system,
                    'gen_ai.request.model' => $event->prompt->model,
                    'gen_ai.agent.name' => $agentName,
                ])
        );

        $requestSpan = $agentSpan->startChild(
            SpanContext::make()
                ->setOp('gen_ai.request')
                ->setDescription('LLM request '.$event->prompt->model)
                ->setData([
                    'gen_ai.system' => $system,
                    'gen_ai.request.model' => $event->prompt->model,
                    'gen_ai.request.messages' => json_encode([
                        ['role' => 'user', 'content' => $event->prompt->prompt],
                    ]),
                ])
        );

        self::$agentSpans[$event->invocationId] = $agentSpan;
        self::$agentRequestSpans[$event->invocationId] = $requestSpan;
        self::$agentParentSpans[$event->invocationId] = $parentSpan;

        $hub->setSpan($agentSpan);
    }

    public function handleAgentPrompted(AgentPrompted $event): void
    {
        $agentSpan = self::$agentSpans[$event->invocationId] ?? null;
        $requestSpan = self::$agentRequestSpans[$event->invocationId] ?? null;
        $parent = self::$agentParentSpans[$event->invocationId] ?? null;

        if ($agentSpan === null) {
            return;
        }

        $usage = $event->response->usage;

        if ($requestSpan !== null) {
            $requestSpan->setData(array_merge($requestSpan->getData() ?? [], [
                'gen_ai.usage.input_tokens' => $usage->promptTokens,
                'gen_ai.usage.output_tokens' => $usage->completionTokens,
                'gen_ai.response.text' => $event->response->text,
            ]));
            $requestSpan->finish();
        }

        $agentSpan->finish();

        $hub = SentrySdk::getCurrentHub();

        if ($parent !== null) {
            $hub->setSpan($parent);
        }

        unset(
            self::$agentSpans[$event->invocationId],
            self::$agentRequestSpans[$event->invocationId],
            self::$agentParentSpans[$event->invocationId],
        );
    }

    public function handleInvokingTool(InvokingTool $event): void
    {
        $hub = SentrySdk::getCurrentHub();
        $parentSpan = $hub->getSpan();

        if ($parentSpan === null || ! $parentSpan->getSampled()) {
            return;
        }

        $toolName = method_exists($event->tool, 'name') ? $event->tool->name() : class_basename($event->tool);

        $span = $parentSpan->startChild(
            SpanContext::make()
                ->setOp('gen_ai.execute_tool')
                ->setDescription($toolName)
                ->setData([
                    'gen_ai.tool.name' => $toolName,
                    'gen_ai.tool.call.id' => $event->toolInvocationId,
                ])
        );

        // Key by toolInvocationId only — laravel/ai overwrites the gateway onToolInvocation callback
        // when a sub-agent runs inside a tool, so ToolInvoked fires with a different invocationId
        // than InvokingTool. toolInvocationId is unique and stays consistent across both events.
        self::$toolSpans[$event->toolInvocationId] = $span;
        self::$toolParentSpans[$event->toolInvocationId] = $parentSpan;

        $hub->setSpan($span);
    }

    public function handleToolInvoked(ToolInvoked $event): void
    {
        $span = self::$toolSpans[$event->toolInvocationId] ?? null;
        $parent = self::$toolParentSpans[$event->toolInvocationId] ?? null;

        if ($span === null) {
            return;
        }

        $span->finish();

        $hub = SentrySdk::getCurrentHub();

        if ($parent !== null) {
            $hub->setSpan($parent);
        }

        unset(self::$toolSpans[$event->toolInvocationId], self::$toolParentSpans[$event->toolInvocationId]);
    }

    private static function resolveSystem(TextProvider $provider): string
    {
        return match (class_basename($provider)) {
            'AnthropicProvider' => 'anthropic',
            'AzureOpenAiProvider' => 'azure',
            'GeminiProvider' => 'google_ai_studio',
            'GroqProvider' => 'groq',
            'MistralProvider' => 'mistral',
            'OllamaProvider' => 'ollama',
            default => 'openai',
        };
    }
}
