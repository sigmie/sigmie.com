<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Headless agent UI (HTTP + Inertia)
    |--------------------------------------------------------------------------
    */

    /** Enable HTTP routes and Inertia UI (set false for API-only or custom UI). */
    'enable_routes' => env('SIGMIE_AGENT_ENABLE_ROUTES', true),

    /** FQCN implementing Laravel AI Agent + stream (e.g. app-specific subclass of SigmieAgent). */
    'agent_class' => env('AGENT_CLASS', \Sigmie\AgentTools\Laravel\SigmieAgent::class),

    /** URL prefix for agent routes (e.g. `sigmie` → `/sigmie/agent`, `/sigmie/api/agent-chat`). */
    'route_prefix' => env('AGENT_ROUTE_PREFIX', 'sigmie'),

    /** Inertia page heading (defaults to app name). */
    'agent_ui_title' => env('AGENT_UI_TITLE'),

    /** Optional subtitle under the main heading. */
    'agent_ui_subtitle' => env('AGENT_UI_SUBTITLE', 'Vercel AI UI stream · tools + memory + past conversations'),

    /** Middleware for agent routes (must include `web` for session + CSRF). */
    'route_middleware' => ['web', 'auth'],

    /** Session key for the active agent conversation id. */
    'session_conversation_key' => env('AGENT_SESSION_CONVERSATION_KEY', 'agent_conversation_id'),

    /** Default chat model when the agent class has no `defaultModel()` static method. Empty = provider default. */
    'agent_default_model' => env('AGENT_DEFAULT_MODEL', ''),

    /*
    |--------------------------------------------------------------------------
    | Magic tags (async queue)
    |--------------------------------------------------------------------------
    */

    'magic_tags_queue' => env('SIGMIE_AGENT_MAGIC_TAGS_QUEUE', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Elasticsearch index names (Sigmie)
    |--------------------------------------------------------------------------
    */

    'conversations_index' => env('SIGMIE_AGENT_CONVERSATIONS_INDEX', 'sigmie_agent_tools_conversations'),

    'memory_index' => env('SIGMIE_AGENT_MEMORY_INDEX', 'sigmie_agent_tools_memory'),

    'knowledge_index' => env('SIGMIE_AGENT_KNOWLEDGE_INDEX', 'sigmie_agent_tools_knowledge'),

    /*
    |--------------------------------------------------------------------------
    | Knowledge base ingestion (queue batches)
    |--------------------------------------------------------------------------
    |
    | Register FQCNs implementing {@see \Sigmie\AgentTools\Knowledge\KnowledgeSource}.
    | Run `php artisan sigmie:agent-tools:kb-populate` to dispatch chunked jobs.
    | Requires `php artisan queue:work` and a batch driver (e.g. database job_batches).
    |
    */

    'knowledge_sources' => [
        // \App\Knowledge\FaqSource::class,
    ],

    'kb_populate_queue' => env('SIGMIE_AGENT_KB_POPULATE_QUEUE', 'default'),

    'kb_populate_chunk_size' => max(1, (int) env('SIGMIE_AGENT_KB_POPULATE_CHUNK_SIZE', '10')),

    /** Max {@see \Sigmie\AgentTools\Laravel\Jobs\IndexKnowledgeChunkJob} executions per minute (Cohere embed rate limit). */
    'kb_populate_jobs_per_minute' => max(1, (int) env('SIGMIE_AGENT_KB_POPULATE_JOBS_PER_MINUTE', '60')),

    /** Seconds before a throttled job is released back to the queue. */
    'kb_populate_rate_limit_release' => max(1, (int) env('SIGMIE_AGENT_KB_POPULATE_RATE_LIMIT_RELEASE', '10')),

    /**
     * Seconds to wait for a Redis throttle slot before releasing the job.
     * Use 0 to fail immediately when busy (fast Horizon "DONE" with no ingest — avoid for production).
     */
    'kb_populate_throttle_block_seconds' => max(0, (int) env('SIGMIE_AGENT_KB_POPULATE_THROTTLE_BLOCK_SECONDS', '3')),

    /** Minutes from dispatch before a chunk job is abandoned (time-based retry window). */
    'kb_populate_retry_for_minutes' => max(1, (int) env('SIGMIE_AGENT_KB_POPULATE_RETRY_FOR_MINUTES', '120')),

    /*
    |--------------------------------------------------------------------------
    | Conversation context window (Laravel AI SDK)
    |--------------------------------------------------------------------------
    |
    | Maximum number of recent messages (including tool calls) passed to the LLM.
    | Messages are read from the DB via the SDK's ConversationStore (full fidelity).
    |
    */

    'max_conversation_messages' => max(1, (int) env('SIGMIE_AGENT_MAX_CONVERSATION_MESSAGES', '100')),

    /*
    |--------------------------------------------------------------------------
    | Queue persistence (memory + conversations)
    |--------------------------------------------------------------------------
    |
    | Memory saves and conversation turns are always dispatched to the queue.
    | Set QUEUE_CONNECTION=sync for immediate execution without a worker.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Registered Sigmie embedding API names
    |--------------------------------------------------------------------------
    |
    | Must match APIs registered on your Sigmie instance (e.g. Cohere document
    | and query embeddings).
    |
    */

    'embeddings_doc_api' => env('SIGMIE_AGENT_EMBEDDINGS_DOC_API', 'cohere-doc'),

    'embeddings_query_api' => env('SIGMIE_AGENT_EMBEDDINGS_QUERY_API', 'cohere-query'),

    /*
    |--------------------------------------------------------------------------
    | Auto-register Sigmie embeddings + rerank APIs (plug and play)
    |--------------------------------------------------------------------------
    |
    | When true, the package registers doc/query embeddings and rerank on the
    | bound {@see \Sigmie\Sigmie} instance if an API key is set and the name
    | is not already registered. Set false to register APIs only in your own
    | service provider.
    |
    */

    'auto_register_sigmie_apis' => env('SIGMIE_AGENT_AUTO_REGISTER_APIS', true),

    /**
     * Provider for embeddings + rerank auto-registration. Supported: cohere.
     */
    'embeddings_provider' => env('SIGMIE_AGENT_EMBEDDINGS_PROVIDER', 'cohere'),

    /**
     * API key for the embeddings provider (Cohere: used for embeddings + rerank).
     * Falls back to COHERE_API_KEY, then config services.cohere.api_key / services.cohere.key.
     */
    'embeddings_api_key' => env('SIGMIE_AGENT_EMBEDDINGS_API_KEY', env('COHERE_API_KEY', '')),

    /** Cohere embedding model for doc + query APIs (when provider is cohere). */
    'cohere_embeddings_model' => env('SIGMIE_AGENT_COHERE_EMBEDDINGS_MODEL', 'embed-english-v3.0'),

    /** Cohere rerank model (when provider is cohere). */
    'cohere_rerank_model' => env('SIGMIE_AGENT_COHERE_RERANK_MODEL', 'rerank-v3.5'),

    /*
    |--------------------------------------------------------------------------
    | Knowledge index — topic field (Laravel AI SDK)
    |--------------------------------------------------------------------------
    |
    | Provider for index-time `topic` labels (structured-output agent in this package).
    | Must match a {@see \Laravel\Ai\Enums\Lab} value (e.g. openai, anthropic).
    |
    */

    'knowledge_magic_tags_provider' => env('SIGMIE_AGENT_KNOWLEDGE_MAGIC_TAGS_PROVIDER', ''),

    /** How many topic tags to inject into the agent system prompt (facets on `topic`). */
    'knowledge_available_tags_limit' => max(1, min(100, (int) env('SIGMIE_AGENT_KNOWLEDGE_TAGS_LIMIT', '20'))),

    /** Expand knowledge hits with ±N neighboring paragraphs for better context (0 = no expansion). */
    'knowledge_expand_neighbors' => max(0, min(5, (int) env('SIGMIE_AGENT_KNOWLEDGE_EXPAND', '1'))),

    /*
    |--------------------------------------------------------------------------
    | Memory + conversations indices — shared topic sidecar (Sigmie)
    |--------------------------------------------------------------------------
    |
    | Logical name for Sigmie’s shared tagIndex() so memory and conversations share one
    | sidecar; knowledge uses its own. Naming follows Sigmie conventions.
    |
    */

    'memory_history_magic_tag_index' => env('SIGMIE_AGENT_MEMORY_HISTORY_MAGIC_TAG_INDEX', 'sigmie_agent_tools_memory_history_magic_tags'),

    'memory_history_magic_tags_provider' => env('SIGMIE_AGENT_MEMORY_HISTORY_MAGIC_TAGS_PROVIDER', ''),

    /*
    |--------------------------------------------------------------------------
    | Reranking (Cohere via Sigmie)
    |--------------------------------------------------------------------------
    |
    | API name must match a RerankApi registered on Sigmie (e.g. cohere-rerank).
    | The rerank API scores at most rerank_top_k merged candidates (raise this if many
    | good chunks never receive a score). After rerank, hits below rerank_score_threshold
    | are dropped unless that would remove every hit — then the best reranked hits are kept
    | anyway (Cohere scores are often below a strict floor while still being the best match).
    |
    */

    'rerank_api' => env('SIGMIE_AGENT_RERANK_API', 'cohere-rerank'),

    'rerank_top_k' => max(1, min(100, (int) env('SIGMIE_AGENT_RERANK_TOP_K', '30'))),

    /*
    |--------------------------------------------------------------------------
    | Rerank score floor (Cohere rerank relevance 0–1)
    |--------------------------------------------------------------------------
    |
    | After global rerank, hits with score strictly below this floor are dropped.
    | Cohere-style relevance is typically ~0–1. Lower the value to keep more borderline
    | matches; raise it to be stricter.
    |
    */

    'rerank_score_threshold' => max(0.0, min(1.0, (float) env('SIGMIE_AGENT_RERANK_SCORE_THRESHOLD', '0.1'))),

    /*
    |--------------------------------------------------------------------------
    | Unified search (per-source recall before global rerank)
    |--------------------------------------------------------------------------
    |
    | FQCNs of {@see \Sigmie\AgentTools\Contracts\RetrievalSource} implementations
    | (typically {@see \Sigmie\AgentTools\Elasticsearch\AgentIndex} subclasses).
    | Order is preserved for the retrieval planner and multi-search. Each class must be
    | registered as a singleton (see service provider) or bound in your application.
    | Publish this config to add custom indices or change order.
    |
    */

    'unified_search_indices' => \Sigmie\AgentTools\Laravel\UnifiedSearchIndices::defaultClassNames(),

    /**
     * Per-source retrieve size when the retrieval planner marks a source as high relevance.
     */
    'unified_relevance_high_limit' => max(1, min(50, (int) env('SIGMIE_AGENT_UNIFIED_RELEVANCE_HIGH', '10'))),

    /**
     * Per-source retrieve size when the planner marks a source as low relevance.
     */
    'unified_relevance_low_limit' => max(0, min(50, (int) env('SIGMIE_AGENT_UNIFIED_RELEVANCE_LOW', '3'))),

    /*
    |--------------------------------------------------------------------------
    | Unified search — retrieval planner (structured output, tool-internal)
    |--------------------------------------------------------------------------
    |
    | The planner decomposes the single `query` argument into per-source ES queries
    | and a rerank phrase. Must match a {@see \Laravel\Ai\Enums\Lab} value.
    |
    */

    'retrieval_planner_model' => env('SIGMIE_AGENT_RETRIEVAL_PLANNER_MODEL', ''),

    'retrieval_planner_provider' => env('SIGMIE_AGENT_RETRIEVAL_PLANNER_PROVIDER', ''),

    /*
    |--------------------------------------------------------------------------
    | Structured-output agent classes (Laravel AI)
    |--------------------------------------------------------------------------
    |
    | Global defaults for tool-internal and MagicTags agents. Override in code with
    | {@see \Sigmie\AgentTools\AgentTools::retrievalPlannerAgent()} etc. (registration
    | wins over config). Classes must implement Agent + HasStructuredOutput.
    |
    | Tagging agents: constructor should accept optional maxTags, instructionsOverride.
    | Batch tagging agents: also expectedResultCount when batching.
    |
    */

    'retrieval_planner_agent_class' => env(
        'SIGMIE_AGENT_RETRIEVAL_PLANNER_AGENT_CLASS',
        \Sigmie\AgentTools\Agents\RetrievalPlannerAgent::class
    ),

    'tagging_agent_class' => env(
        'SIGMIE_AGENT_TAGGING_AGENT_CLASS',
        \Sigmie\AgentTools\Agents\TaggingAgent::class
    ),

    'batch_tagging_agent_class' => env(
        'SIGMIE_AGENT_BATCH_TAGGING_AGENT_CLASS',
        \Sigmie\AgentTools\Agents\BatchTaggingAgent::class
    ),

    /*
    |--------------------------------------------------------------------------
    | Magic tags (Sigmie index-time field type)
    |--------------------------------------------------------------------------
    |
    | Defaults for MagicTagsFieldType when declared in a SigmieIndex properties()
    | blueprint. Override per field with fluent methods on the field type.
    |
    */

    'magic_tags' => [
        'provider' => env('SIGMIE_AGENT_MAGIC_TAGS_PROVIDER'),
        'embeddings_provider' => env('SIGMIE_AGENT_MAGIC_TAGS_EMBEDDINGS_PROVIDER'),
        'max_tags' => max(1, (int) env('SIGMIE_AGENT_MAGIC_TAGS_MAX', '5')),
        'batch_size' => max(1, (int) env('SIGMIE_AGENT_MAGIC_TAGS_BATCH_SIZE', '15')),
        'aggregation_size' => max(1, (int) env('SIGMIE_AGENT_MAGIC_TAGS_AGG_SIZE', '500')),
        'sidecar_suffix' => env('SIGMIE_AGENT_MAGIC_TAGS_SIDECAR_SUFFIX', '__sigmie_magic_tags'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Agentic evaluation scenarios (sigmie:agent-tools:eval)
    |--------------------------------------------------------------------------
    |
    | Default: one YAML file per scenario under eval_scenarios_path (relative to
    | base_path), filename stem = scenario key (e.g. tests/scenarios/habit_building.yaml).
    |
    | If that directory is missing or has no .yaml/.yml files, eval_scenarios below
    | is used as a PHP fallback.
    |
    */

    'eval_scenarios_path' => env('SIGMIE_AGENT_EVAL_SCENARIOS_PATH', 'tests/scenarios'),

    'eval_scenarios' => [],

    /*
    |--------------------------------------------------------------------------
    | Sentry AI Agent Insights (gen_ai.* spans)
    |--------------------------------------------------------------------------
    |
    | When true, registers event listeners that map Laravel AI events to Sentry
    | OpenTelemetry-style spans (gen_ai.invoke_agent, gen_ai.request, gen_ai.execute_tool).
    | Requires sentry/sentry (e.g. via sentry/sentry-laravel) and tracing enabled
    | (SENTRY_TRACES_SAMPLE_RATE) on the consuming application.
    |
    */

    'sentry_insights' => env('SIGMIE_AGENT_SENTRY_INSIGHTS', false),

];
