<?php

declare(strict_types=1);

namespace Sigmie\AgentTools;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Laravel\Ai\Contracts\ConversationStore;
use Laravel\Ai\Events\AgentPrompted;
use Laravel\Ai\Events\AgentStreamed;
use Laravel\Ai\Events\InvokingTool;
use Laravel\Ai\Events\PromptingAgent;
use Laravel\Ai\Events\StreamingAgent;
use Laravel\Ai\Events\ToolInvoked;
use Sentry\SentrySdk;
use Sigmie\AgentTools\Elasticsearch\AgentKnowledgeElasticsearchIndex;
use Sigmie\AgentTools\Knowledge\KnowledgePipeline;
use Sigmie\AgentTools\Laravel\AgentToolsConversationsPopulateCommand;
use Sigmie\AgentTools\Laravel\AgentToolsEvalCommand;
use Sigmie\AgentTools\Laravel\AgentToolsIndicesCreateCommand;
use Sigmie\AgentTools\Laravel\AgentToolsIndicesDeleteCommand;
use Sigmie\AgentTools\Laravel\AgentToolsKbPopulateCommand;
use Sigmie\AgentTools\Laravel\AgentToolsMemoryPopulateCommand;
use Sigmie\AgentTools\Laravel\AgentToolsPromptCommand;
use Sigmie\AgentTools\Laravel\AgentToolsRetrievalPlannerPromptCommand;
use Sigmie\AgentTools\Laravel\AgentTurnDebugCollector;
use Sigmie\AgentTools\Laravel\SentryAiMonitoringSubscriber;
use Sigmie\AgentTools\Laravel\SigmieApiAutoRegistrar;
use Sigmie\AgentTools\Laravel\SigmieConversationStore;
use Sigmie\AgentTools\Laravel\UnifiedSearchIndices;
use Sigmie\AgentTools\MagicTags\MagicTagsPackage;
use Sigmie\AI\Contracts\RerankApi;
use Sigmie\Sigmie;

/**
 * Laravel integration for Sigmie agent tools: index bindings, Laravel AI conversation store, Artisan commands.
 * Registers {@see MagicTagsPackage} (topic tags via Sigmie {@see \Sigmie\Document\Contracts\CollectionHook}).
 */
class SigmieAgentToolsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            dirname(__DIR__).'/config/agent-tools.php',
            'agent-tools'
        );

        foreach (UnifiedSearchIndices::classNames() as $indexClass) {
            $this->app->singleton($indexClass);
        }

        $this->app->singleton(RerankApi::class, function ($app) {
            return $app->make(Sigmie::class)->api((string) config('agent-tools.rerank_api', 'cohere-rerank'));
        });

        $this->app->scoped(AgentTurnDebugCollector::class, fn () => new AgentTurnDebugCollector);

        $this->app->singleton(KnowledgePipeline::class, function ($app) {
            return new KnowledgePipeline($app->make(AgentKnowledgeElasticsearchIndex::class));
        });
    }

    /**
     * Override Laravel AI's database conversation store with Sigmie-backed ES sync.
     */
    public function boot(): void
    {
        if ($this->app->bound(Sigmie::class)) {
            $sigmie = $this->app->make(Sigmie::class);
            SigmieApiAutoRegistrar::register($sigmie);
            $sigmie->extend(new MagicTagsPackage);
        }

        $this->validateSigmieSetup();

        $this->app->singleton(ConversationStore::class, SigmieConversationStore::class);

        $this->loadMigrationsFrom(dirname(__DIR__).'/database/migrations');

        $this->publishes([
            dirname(__DIR__).'/config/agent-tools.php' => config_path('agent-tools.php'),
        ], 'agent-tools-config');

        // Only load routes and UI assets when enabled (requires Inertia)
        if (config('agent-tools.enable_routes', true)) {
            if (! class_exists(\Inertia\Inertia::class)) {
                throw new \RuntimeException(
                    'Inertia.js is required when agent-tools routes are enabled. '.
                    'Install it with: composer require inertiajs/inertia-laravel, '.
                    'or disable routes by setting SIGMIE_AGENT_ENABLE_ROUTES=false in .env'
                );
            }

            $this->loadRoutesFrom(dirname(__DIR__).'/routes/agent.php');

            $this->publishes([
                dirname(__DIR__).'/resources/js/Pages/SigmieAgent.vue' => resource_path('js/Pages/SigmieAgent.vue'),
                dirname(__DIR__).'/resources/js/agentMarkdown.js' => resource_path('js/agentMarkdown.js'),
            ], 'agent-tools-assets');
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                AgentToolsIndicesCreateCommand::class,
                AgentToolsIndicesDeleteCommand::class,
                AgentToolsMemoryPopulateCommand::class,
                AgentToolsConversationsPopulateCommand::class,
                AgentToolsKbPopulateCommand::class,
                AgentToolsPromptCommand::class,
                AgentToolsRetrievalPlannerPromptCommand::class,
                AgentToolsEvalCommand::class,
            ]);
        }

        $this->registerSentryAiMonitoringListeners();
    }

    /**
     * Map Laravel AI events to Sentry gen_ai.* spans when agent-tools.sentry_insights is true
     * and the Sentry SDK is present.
     */
    protected function registerSentryAiMonitoringListeners(): void
    {
        if (! config('agent-tools.sentry_insights', false)) {
            return;
        }

        if (! class_exists(SentrySdk::class)) {
            return;
        }

        Event::listen(StreamingAgent::class, [SentryAiMonitoringSubscriber::class, 'handleStreamingAgent']);
        Event::listen(AgentStreamed::class, [SentryAiMonitoringSubscriber::class, 'handleAgentStreamed']);
        Event::listen(PromptingAgent::class, [SentryAiMonitoringSubscriber::class, 'handlePromptingAgent']);
        Event::listen(AgentPrompted::class, [SentryAiMonitoringSubscriber::class, 'handleAgentPrompted']);
        Event::listen(InvokingTool::class, [SentryAiMonitoringSubscriber::class, 'handleInvokingTool']);
        Event::listen(ToolInvoked::class, [SentryAiMonitoringSubscriber::class, 'handleToolInvoked']);
    }

    /**
     * Validate that Sigmie is properly configured with required APIs.
     */
    protected function validateSigmieSetup(): void
    {
        // Skip validation in console (migrations, etc.) or when explicitly disabled
        if ($this->app->runningInConsole() || config('agent-tools.skip_validation', false)) {
            return;
        }

        try {
            $sigmie = $this->app->make(Sigmie::class);
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                'Sigmie instance not bound in container. Register a Sigmie singleton in your AppServiceProvider with Elasticsearch connection and embedding/rerank APIs. '.
                'See package README for installation instructions.'
            );
        }

        $requiredApis = [
            'embeddings_doc_api' => config('agent-tools.embeddings_doc_api'),
            'embeddings_query_api' => config('agent-tools.embeddings_query_api'),
            'rerank_api' => config('agent-tools.rerank_api'),
        ];

        $missing = [];
        foreach ($requiredApis as $configKey => $apiName) {
            if (! is_string($apiName) || $apiName === '') {
                continue;
            }

            // Check if API is registered on Sigmie instance
            try {
                $api = $sigmie->api($apiName);
                if ($api === null) {
                    $missing[] = $apiName;
                }
            } catch (\Throwable) {
                $missing[] = $apiName;
            }
        }

        if ($missing !== []) {
            $hint = '';
            if (config('agent-tools.auto_register_sigmie_apis', true) && SigmieApiAutoRegistrar::resolveApiKey() === '') {
                $hint = 'Set SIGMIE_AGENT_EMBEDDINGS_API_KEY or COHERE_API_KEY (or services.cohere.api_key) so auto-registration can run, '.
                    'or register the APIs manually and set SIGMIE_AGENT_AUTO_REGISTER_APIS=false. ';
            }

            throw new \RuntimeException(
                'Missing Sigmie API registrations: '.implode(', ', $missing).'. '.$hint.
                'Register these APIs on your Sigmie singleton using $sigmie->registerApi(), or rely on auto-registration when an embeddings API key is set. '.
                'See package README Installation section for complete setup.'
            );
        }
    }
}
