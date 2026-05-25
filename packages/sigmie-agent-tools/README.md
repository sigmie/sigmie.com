# Sigmie Agent Tools

## Introduction

`sigmie/agent-tools` wires the Laravel AI SDK to Elasticsearch through Sigmie: a base agent (`SigmieAgent`) with tools for unified semantic search across knowledge, per-user memory, and conversation turns, plus memory save/delete and clarification. The LLM context window (recent messages, including tool calls) comes from the database via Laravel AI’s `ConversationStore`. Elasticsearch holds a search sidecar for conversations and memory; `SigmieConversationStore` syncs each assistant turn to the conversations index after the reply is stored. The package ships Artisan commands to create or delete configured indices and to run a prompt against your registered agent class from the CLI.

## Core Concepts

The agent resolves embeddings and reranking through your Sigmie instance. Topic facets on the `topic` field are produced when you index documents through Sigmie’s pipeline (`sigmie/sigmie`); this package wires mappings and search only. `unified_search` takes a single `query` from the main agent; a tool-internal `RetrievalPlannerAgent` (Laravel AI structured output) expands it into per-source queries, then results are merged and globally reranked.

```
Laravel AI (Agent) ──► SigmieAgent (tools)
        │                      │
        │                      ├── UnifiedSearchTool ──► Knowledge / Memory / Conversations indices
        │                      ├── MemorySaveTool / MemoryDeleteTool
        │                      └── ClarificationTool
        │
        └── SigmieConversationStore ──► AgentConversationsElasticsearchIndex (sync turn after assistant reply)
```

The container registers singletons for the three Elasticsearch index classes:

```php
use Sigmie\AgentTools\Elasticsearch\AgentConversationsElasticsearchIndex;
use Sigmie\AgentTools\Elasticsearch\AgentKnowledgeElasticsearchIndex;
use Sigmie\AgentTools\Elasticsearch\AgentUserMemoryElasticsearchIndex;

app(AgentConversationsElasticsearchIndex::class);
app(AgentUserMemoryElasticsearchIndex::class);
app(AgentKnowledgeElasticsearchIndex::class);
```

## Installation

### 1. Install the package

```bash
composer require sigmie/agent-tools
```

### 2. Install Laravel AI (if not already installed)

This package extends Laravel AI's conversation system:

```bash
composer require laravel/ai
php artisan vendor:publish --tag=ai-migrations
php artisan migrate
```

### 3. Register Sigmie singleton with Elasticsearch connection

In your `AppServiceProvider::register()` (or a dedicated provider), bind `Sigmie` with your Elasticsearch connection only:

```php
use Sigmie\Sigmie;
use Sigmie\Base\Http\ElasticsearchConnection;
use Sigmie\Http\JSONClient;

$this->app->singleton(Sigmie::class, function ($app) {
    $json = JSONClient::createWithBasic(
        [config('services.sigmie.url')],
        username: config('services.sigmie.auth.username'),
        password: config('services.sigmie.auth.password'),
    );

    return new Sigmie(new ElasticsearchConnection($json));
});
```

### 3b. Embeddings + rerank APIs (auto-registration)

By default (`SIGMIE_AGENT_AUTO_REGISTER_APIS=true`), the package registers **Cohere** document embeddings, query embeddings, and rerank on your `Sigmie` instance during `boot()`, using names from `config/agent-tools.php` (defaults: `cohere-doc`, `cohere-query`, `cohere-rerank`). It only registers a name if it is not already present (`hasApi`).

Set one of:

- `SIGMIE_AGENT_EMBEDDINGS_API_KEY` — preferred key for this package, or
- `COHERE_API_KEY` — fallback, or
- `services.cohere.api_key` / `services.cohere.key` in `config/services.php`

Optional:

```env
SIGMIE_AGENT_EMBEDDINGS_PROVIDER=cohere
SIGMIE_AGENT_COHERE_EMBEDDINGS_MODEL=embed-english-v3.0
SIGMIE_AGENT_COHERE_RERANK_MODEL=rerank-v3.5
```

To register APIs yourself (custom models or non-Cohere), set `SIGMIE_AGENT_AUTO_REGISTER_APIS=false` and call `$sigmie->registerApi(...)` in your provider with names matching `SIGMIE_AGENT_EMBEDDINGS_*_API` and `SIGMIE_AGENT_RERANK_API`.

**Important:** The API names must match the values in your `.env` or published `config/agent-tools.php`. Mismatched names will cause runtime errors.

### 4. Run package migrations

```bash
php artisan migrate
```

This creates the `agent_user_memory` table and adds `topic` columns to Laravel AI's conversation tables.

### 5. Configure environment variables

Add to your `.env`:

```env
# Cohere (embeddings + rerank auto-registration; or use COHERE_API_KEY)
SIGMIE_AGENT_EMBEDDINGS_API_KEY=

# Elasticsearch indices (defaults shown)
SIGMIE_AGENT_CONVERSATIONS_INDEX=sigmie_agent_tools_conversations
SIGMIE_AGENT_MEMORY_INDEX=sigmie_agent_tools_memory
SIGMIE_AGENT_KNOWLEDGE_INDEX=sigmie_agent_tools_knowledge

# Sigmie API logical names (must match auto-registration or manual registerApi())
SIGMIE_AGENT_EMBEDDINGS_DOC_API=cohere-doc
SIGMIE_AGENT_EMBEDDINGS_QUERY_API=cohere-query
SIGMIE_AGENT_RERANK_API=cohere-rerank

# Retrieval settings (optional, defaults shown)
SIGMIE_AGENT_RERANK_TOP_K=30
SIGMIE_AGENT_RERANK_SCORE_THRESHOLD=0.1
SIGMIE_AGENT_UNIFIED_RETRIEVE_PER_SOURCE=10
SIGMIE_AGENT_KNOWLEDGE_EXPAND=1

# Queue connection for async jobs
QUEUE_CONNECTION=database  # or redis, etc.
```

### 6. Create Elasticsearch indices

```bash
php artisan sigmie:agent-tools:indices-create
```

This creates three indices with proper mappings for semantic search and topic tags.

### 7. (Optional) Register your custom agent class

Create a class under `app/Agent/` that extends `Sigmie\AgentTools\Laravel\SigmieAgent` and override `systemPrompt()` (and optionally `defaultTools()`).

Register it in `AppServiceProvider::register()`:

```php
use Sigmie\AgentTools\AgentTools;
use App\Agent\MyAgent;

AgentTools::defaultAgent(MyAgent::class);
```

### 8. (Optional) Set up the headless UI

If you want the included Inertia.js chat interface:

**a) Install frontend dependencies:**

```bash
npm install @ai-sdk/vue ai dompurify marked
```

**b) Publish the Vue component:**

```bash
php artisan vendor:publish --tag=agent-tools-assets
```

This copies `SigmieAgent.vue` and `agentMarkdown.js` to `resources/js/`.

**c) Register the Inertia page in your routes or link to it:**

The package automatically registers routes at `/sigmie/agent` (configurable via `AGENT_ROUTE_PREFIX`).

**d) Add CSRF exemption for agent API routes:**

In `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->validateCsrfTokens(except: [
        '/sigmie/api/agent-*',  // Agent streaming endpoints
    ]);
})
```

**To disable routes entirely** (API-only usage), set in `.env`:

```env
SIGMIE_AGENT_ENABLE_ROUTES=false
```

### 9. Index your knowledge base

1. Implement `KnowledgeSource` and yield `KnowledgeDocument` rows (`content`, optional `sourceId`, optional `meta` array).
2. Register the class in `config/agent-tools.php` under `knowledge_sources`.
3. Ensure the knowledge index mapping matches the package (after upgrades, run `sigmie:agent-tools:indices-delete` then `indices-create` if the schema changed).
4. Run the populate command, then keep a queue worker running.

```php
use Sigmie\AgentTools\Knowledge\KnowledgeDocument;
use Sigmie\AgentTools\Knowledge\KnowledgeSource;

class FaqSource implements KnowledgeSource
{
    public function documents(): iterable
    {
        // Each document is chunked; set sourceId to group related chunks for neighbor expansion
        yield new KnowledgeDocument(
            content: 'Answer text…',
            sourceId: 'faq:1',  // optional; auto-generated if omitted
            meta: ['question' => '…', 'url' => 'https://example.com/a'],
        );
    }
}
```

```bash
php artisan sigmie:agent-tools:kb-populate
# or one class:
php artisan sigmie:agent-tools:kb-populate --class="App\\Knowledge\\FaqSource"
```

**sourceId behavior:** If you set `sourceId` explicitly, multiple logical rows and their pipeline chunks share one `source_id` and neighbor-expansion (±1 chunk by default) can span them. If omitted, the pipeline uses `sha256(content)` so identical text collides on purpose (dedupe); use an explicit `sourceId` when several rows should chain positions (e.g. PDF paragraphs).

Each batch job retries up to 3 times; `allowFailures()` means one failed chunk does not cancel the rest. Tune `kb_populate_chunk_size` and `kb_populate_queue` in config or via `--chunk` / `--queue`.

### 10. Run queue workers (for async tag generation)

```bash
php artisan queue:work
```

Magic tags (topic labels) are generated asynchronously after indexing conversations, memory, and knowledge.

---

## Quick Start Checklist

- [ ] `composer require sigmie/agent-tools laravel/ai`
- [ ] Publish and run Laravel AI migrations
- [ ] Register `Sigmie` singleton with ES connection + APIs
- [ ] Run `php artisan migrate` (agent-tools migrations)
- [ ] Configure `.env` with index names and API names
- [ ] Run `php artisan sigmie:agent-tools:indices-create`
- [ ] (Optional) Register a custom agent subclass with `AgentTools::defaultAgent(...)`
- [ ] (Optional) Publish UI assets and add CSRF exemption
- [ ] Register a `KnowledgeSource` and run `sigmie:agent-tools:kb-populate`
- [ ] Start queue worker
- [ ] **Verify installation** (see below)

---

## Verifying Your Installation

After completing the installation steps, verify everything works:

### 1. Check Indices Were Created

```bash
# List all indices (should see your 3 agent indices)
curl -u username:password "http://your-elasticsearch:9200/_cat/indices?v" | grep sigmie_agent_tools
```

Or via Sigmie in tinker:

```bash
php artisan tinker
>>> app(\Sigmie\AgentTools\Elasticsearch\AgentKnowledgeElasticsearchIndex::class)->name()
=> "sigmie_agent_tools_knowledge"
```

### 2. Test Agent via CLI

Run a simple prompt to verify the agent responds:

```bash
php artisan sigmie:agent-tools:prompt "Hello, can you help me?"
```

**Expected output:**
- Agent responds with a greeting
- No errors about missing Sigmie setup or API registration
- Command completes successfully

### 3. Test Knowledge Search (After Indexing)

Once you've indexed some knowledge documents:

```bash
php artisan sigmie:agent-tools:prompt "What do you know about [your domain topic]?"
```

**Expected behavior:**
- Agent uses `unified_search` tool
- Returns relevant information from your knowledge base
- Check `storage/logs/laravel.log` for `unified_search.done` entries

### 4. Test Memory Persistence

```bash
# Save a fact
php artisan sigmie:agent-tools:prompt "My favorite color is blue"

# In a new conversation, ask to recall it
php artisan sigmie:agent-tools:prompt "What's my favorite color?"
```

**Expected behavior:**
- First prompt triggers `memory_save` tool
- Second prompt uses `unified_search` to find the memory
- Agent recalls "blue"

### 5. Verify Queue Processing

Check that async tag generation jobs are running:

```bash
# Start queue worker in one terminal
php artisan queue:work

# In another terminal, trigger a memory save
php artisan sigmie:agent-tools:prompt "Remember that I live in Brussels"

# Check queue worker output for:
# - SyncMemoryToElasticsearchJob
# - GenerateTagsJob (memory / knowledge / conversations via index class)
```

### 6. (Optional) Test UI

If you set up the headless UI, visit:

```
http://your-app.test/sigmie/agent
```

**Expected behavior:**
- Chat interface loads
- You can send messages
- Agent responds with streaming output
- No console errors about missing CSRF token

### Common Issues

**"No Sigmie singleton bound"**
- You forgot step 3 (register Sigmie in AppServiceProvider)

**"Embeddings API 'cohere-doc' not registered"**
- API names in your `registerApi()` calls don't match `.env` values
- Check `SIGMIE_AGENT_KNOWLEDGE_EMBEDDINGS_DOC_API` matches your registered name

**"unified_search returns empty results"**
- Your knowledge index is empty (expected if you haven't indexed yet)
- Run your knowledge indexer first

**"Queue jobs not processing"**
- Queue worker not running: `php artisan queue:work`
- Wrong queue driver: check `QUEUE_CONNECTION` in `.env`

---

## Publishing Config

To customize all settings locally:

```bash
php artisan vendor:publish --tag=agent-tools-config
```

This copies `config/agent-tools.php` to your app's `config/` directory.

## Registering Your Agent Class

The CLI command `sigmie:agent-tools:prompt` resolves an agent class from `AgentTools::defaultAgent(...)` first, then `config('agent-tools.agent_class')`, then `SigmieAgent` as a fallback. Register your subclass from a service provider:

```php
use Sigmie\AgentTools\AgentTools;

public function register(): void
{
    AgentTools::defaultAgent(\App\Agent\PublicDomainDemoAgent::class);
}
```

Alternatively, set the class name in config (publish `config/agent-tools.php` first):

```php
// config/agent-tools.php
'agent_class' => \App\Agent\PublicDomainDemoAgent::class,
```

### Structured-output agents (retrieval planner, MagicTags)

`UnifiedSearchTool` resolves the retrieval planner with `AgentTools::resolvedRetrievalPlannerAgentClass()` (default `RetrievalPlannerAgent`). MagicTags resolves single- and batch-tagging agents with `resolvedTaggingAgentClass()` and `resolvedBatchTaggingAgentClass()`.

Register replacements from a service provider (wins over `config('agent-tools.*_agent_class')`):

```php
use Sigmie\AgentTools\AgentTools;

AgentTools::retrievalPlannerAgent(\App\Agent\MyRetrievalPlanner::class);
AgentTools::taggingAgent(\App\MagicTags\MyTaggingAgent::class);
AgentTools::batchTaggingAgent(\App\MagicTags\MyBatchTaggingAgent::class);
```

Default package agents keep **instructions inside the agent** (`instructions()`; tagging agents also expose `defaultInstructions()`). Subclasses override those methods. Custom tagging agents should accept the same optional constructor parameters as `TaggingAgent` / `BatchTaggingAgent` (`maxTags`, `instructionsOverride`, and for batch `expectedResultCount`) because the indexing pipeline instantiates them via `app()->makeWith(...)`. A non-empty `prompt()` on `MagicTagsFieldType` still sets `instructionsOverride` for that field.

Extend `Sigmie\AgentTools\Laravel\SigmieAgent`, override `systemPrompt()` and optionally `defaultTools()` to add app-specific tools:

```php
namespace App\Agent;

use Sigmie\AgentTools\Laravel\SigmieAgent;

class PublicDomainDemoAgent extends SigmieAgent
{
    protected function systemPrompt(): string
    {
        return 'You help with public-domain stories and films (e.g. Grimms\' fairy tales, Steamboat Willie).';
    }
}
```

> **Note:** Examples in this README use **public-domain** works only (e.g. the 1928 film *Steamboat Willie*, the Grimms’ *Snow White* tale)—not later Disney adaptations still under copyright.

## Creating and Deleting Elasticsearch Indices

Create conversations, memory, and knowledge indices (mappings + Sigmie setup):

```bash
php artisan sigmie:agent-tools:indices-create
```

Delete those indices if they exist (use before a clean reindex):

```bash
php artisan sigmie:agent-tools:indices-delete
```

> **Note:** Index names come from `config('agent-tools.conversations_index')`, `memory_index`, and `knowledge_index` (see [Configuration](#configuration)).

## Testing the retrieval planner only

The planner runs inside `unified_search` when the **main LLM chooses that tool**. A `dd()` in `UnifiedSearchTool` only runs if the model actually calls `unified_search` (and you must edit the **path package** `packages/sigmie-agent-tools`, which is symlinked as `vendor/sigmie/agent-tools` when using Composer path repos).

To call the planner directly (no agent, no Elasticsearch):

```bash
php artisan sigmie:agent-tools:retrieval-planner:prompt "My wife told me she need bigger bobs?"
php artisan sigmie:agent-tools:retrieval-planner:prompt "..." --model=claude-haiku-4-5-20251001 --provider=anthropic --json
```

## Running the Agent from Artisan

Send a message to the resolved agent with tools and DB-backed conversation context plus Elasticsearch search:

```bash
php artisan sigmie:agent-tools:prompt "In Steamboat Willie, what animal does Mickey use as a musical instrument?"
```

Use options to pin identity and model:

```bash
php artisan sigmie:agent-tools:prompt "In the Grimms' fairy tale Snow White, how does the queen learn she is not the fairest?" \
  --user-token=grimm-reader \
  --conversation-id=steamboat-willie-demo \
  --model=gpt-4o-mini \
  --debug
```

`--clear` deletes and recreates the **conversations and memory** indices (not knowledge), then runs when a message is present—useful for a clean local demo:

```bash
php artisan sigmie:agent-tools:prompt "What vessel does Mickey pilot in Steamboat Willie?" --clear
```

## Configuring Queue Persistence

Memory saves and conversation turns dispatch jobs when queueing is enabled (see your app’s queue config).

Run a worker for async persistence, or use the sync driver for immediate writes:

```env
QUEUE_CONNECTION=sync
```

## Configuring Sigmie APIs and Indices

Point the package at your Elasticsearch index names and the API names registered on your `Sigmie` instance (embeddings, rerank). Topic labels at index time use the Laravel AI SDK (`config/ai.php` credentials; providers per `config/agent-tools.php`).

```env
SIGMIE_AGENT_CONVERSATIONS_INDEX=sigmie_agent_tools_conversations
SIGMIE_AGENT_MEMORY_INDEX=sigmie_agent_tools_memory
SIGMIE_AGENT_KNOWLEDGE_INDEX=sigmie_agent_tools_knowledge

SIGMIE_AGENT_EMBEDDINGS_DOC_API=cohere-doc
SIGMIE_AGENT_EMBEDDINGS_QUERY_API=cohere-query
SIGMIE_AGENT_RERANK_API=cohere-rerank
```

Tune retrieval and reranking with:

```env
SIGMIE_AGENT_RERANK_TOP_K=30
SIGMIE_AGENT_RERANK_SCORE_THRESHOLD=0.1
SIGMIE_AGENT_UNIFIED_RETRIEVE_PER_SOURCE=10
SIGMIE_AGENT_KNOWLEDGE_EXPAND=1
SIGMIE_AGENT_KNOWLEDGE_TAGS_LIMIT=20

## Indexing Knowledge in Your Application

The package defines the knowledge index shape and search behavior; it does not ship a content importer. Your application indexes documents into `AgentKnowledgeElasticsearchIndex` using your own Artisan command, jobs, or admin flow:

```php
use Sigmie\AgentTools\Elasticsearch\AgentKnowledgeElasticsearchIndex;

$knowledge = app(AgentKnowledgeElasticsearchIndex::class);
// e.g. index PD text (Grimms' tales) or notes on Steamboat Willie via your importer
```

> **Note:** Keep document `_id` stable across reindexes if you rely on deduplication or neighbor expansion keyed by id.

## How It Works Under the Hood

`SigmieAgentToolsServiceProvider` registers a container singleton for each entry in `unified_search_indices` (defaults: memory, conversations, knowledge indices), and replaces Laravel AI’s `ConversationStore` with `SigmieConversationStore`. After each assistant reply, the store persists messages in the database (full tool calls) and dispatches a job to append one turn to the conversations index (queued or synchronous). `SigmieAgent` uses the SDK’s `RemembersConversations` for the sliding window from the DB (`max_conversation_messages` in config). `SigmieAgent` is constructed with `RerankApi`, `Sigmie`, and optional `LoggerInterface` only. `SigmieAgent::defaultTools()` builds `UnifiedSearchTool` with your rerank API and config-driven limits, and wires `MemorySaveTool` / `MemoryDeleteTool` when `AgentUserMemoryElasticsearchIndex` is among the merged retrieval sources (`unified_search_indices` plus `extraSources()`). Inside `unified_search`, `RetrievalPlannerAgent` runs a structured-output call (model/provider from `agent-tools` config) to split the user intent into one query per source plus `rerank_query`; on failure the tool falls back to using the raw `query` for all sources.

In `unified_search`, the history branch searches the conversations index for **all** past turns for the current user (`user_id` only, not the active `conversation_id`). Result metadata includes `conversation_id` per hit so the model can tell which thread a turn came from.

Laravel AI resolves the store from the container:

```php
use Laravel\Ai\Contracts\ConversationStore;

app(ConversationStore::class); // Sigmie\AgentTools\Laravel\SigmieConversationStore
```

## Multi-agent applications and LLM costs

By default, user memory and Elasticsearch conversation history are **per user**, not per agent class: two different agents for the same user see the same saved facts and the same retrievable past turns. To keep agents separate (e.g. a document assistant vs a general chat), you override `defaultTools()` / retrieval sources, use different index names, or use different `session_conversation_key` values per mount.

For **Sentry** (`SIGMIE_AGENT_SENTRY_INSIGHTS=true`), the package maps Laravel AI events to `gen_ai.*` spans with token usage where available. That is observability only; it does not enforce a budget. To **cap** spend or tokens, listen to `Laravel\Ai\Events\AgentPrompted` in the host app, accumulate usage per user, and use middleware to reject over-limit requests.

See [docs/multi-agent-and-costs.md](docs/multi-agent-and-costs.md) for the full scoping table, isolation patterns, and a concrete cost-guard pattern.

## Adding extra search sources

Publish `config/agent-tools.php` and add your index FQCN to **`unified_search_indices`** (an ordered list). The package registers each class as a container singleton, feeds them to `UnifiedSearchTool`, and includes them in `sigmie:agent-tools:indices-create` / `indices-delete`. The retrieval planner uses each source’s `sourceKey()` and `retrievalPlannerHint()` for structured per-source queries.

Alternatively, append sources at runtime with `SigmieAgent::extraSources()` (merged after the config list).

```php
// config/agent-tools.php
'unified_search_indices' => [
    \Sigmie\AgentTools\Elasticsearch\AgentUserMemoryElasticsearchIndex::class,
    \Sigmie\AgentTools\Elasticsearch\AgentConversationsElasticsearchIndex::class,
    \Sigmie\AgentTools\Elasticsearch\AgentKnowledgeElasticsearchIndex::class,
    \App\Elasticsearch\MyDocsElasticsearchIndex::class,
],
```

```php
use Sigmie\AgentTools\Elasticsearch\AgentIndex;

final class MyDocsElasticsearchIndex extends AgentIndex
{
    public function sourceKey(): string
    {
        return 'my_docs';
    }

    public function retrievalPlannerHint(): string
    {
        return 'Internal wiki pages and PDF excerpts.';
    }

    // properties(), name(), buildSearch/mapHits/toText as needed
    // Override metaFieldNames() to control which fields appear in unified_search result meta (default: none beyond _id).
}
```

```
User message
    │
    ▼
RetrievalPlannerAgent (structured) ──► RetrievalPlan (source_key → sub-query)
    │
    ▼
UnifiedSearchTool: for each RetrievalSource
    prepareSearch(query, limit, user) → ES hit → mapHits() → rows
    │
    ▼
Merge, dedupe, rerank → tool result
```

## Using agents as tools (sub-agents)

With `laravel/ai ^0.6`, you can return agent instances from `tools()` to delegate specialized tasks. The parent agent sends a task description; the sub-agent runs in isolation with its own instructions, tools, model, and provider.

Return any `Agent` instance from your `tools()` method:

```php
namespace App\Agent;

use Sigmie\AgentTools\Laravel\SigmieAgent;

class MyAgent extends SigmieAgent
{
    public function tools(): iterable
    {
        return [
            ...parent::defaultTools(),
            new InventoryLookupAgent,
        ];
    }
}
```

For control over the tool name and description, implement `CanActAsTool` on the sub-agent:

```php
namespace App\Agent;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\CanActAsTool;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;

class InventoryLookupAgent implements Agent, CanActAsTool, HasTools
{
    use Promptable;

    public function name(): string
    {
        return 'inventory_lookup';
    }

    public function description(): string
    {
        return 'Check product availability and pricing in the inventory system.';
    }

    public function instructions(): string
    {
        return 'You look up products in the inventory. Return availability and pricing.';
    }

    public function tools(): iterable
    {
        return [new SearchInventoryTool];
    }
}
```

Without `CanActAsTool`, the SDK uses the class basename as the tool name and a generic description. Each sub-agent invocation runs in isolation — it does not receive the parent agent's conversation history.

## Configuration

Every key lives in `config/agent-tools.php` with `SIGMIE_AGENT_*` env overrides. Typical `.env` entries:

```env
SIGMIE_AGENT_CONVERSATIONS_INDEX=sigmie_agent_tools_conversations
SIGMIE_AGENT_MEMORY_INDEX=sigmie_agent_tools_memory
SIGMIE_AGENT_KNOWLEDGE_INDEX=sigmie_agent_tools_knowledge

SIGMIE_AGENT_MAX_CONVERSATION_MESSAGES=100

SIGMIE_AGENT_EMBEDDINGS_DOC_API=cohere-doc
SIGMIE_AGENT_EMBEDDINGS_QUERY_API=cohere-query
SIGMIE_AGENT_RERANK_API=cohere-rerank
SIGMIE_AGENT_RERANK_TOP_K=30
SIGMIE_AGENT_RERANK_SCORE_THRESHOLD=0.1

SIGMIE_AGENT_KNOWLEDGE_TAGS_LIMIT=20
SIGMIE_AGENT_KNOWLEDGE_EXPAND=1

SIGMIE_AGENT_MEMORY_HISTORY_MAGIC_TAG_INDEX=sigmie_agent_tools_memory_history_magic_tags
```

`SIGMIE_AGENT_*_MAGIC_TAGS_PROVIDER` values must match a `Laravel\Ai\Enums\Lab` case (e.g. `anthropic`, `openai`). When not set, they fall back to `config('ai.default')`. Topic tagging uses structured-output agents and `Embeddings` from the Laravel AI SDK (`src/MagicTags`).

Sentry AI Agent Insights (token spans, tool spans): set `SIGMIE_AGENT_SENTRY_INSIGHTS=true` and use `sentry/sentry` with tracing enabled. Details in [docs/multi-agent-and-costs.md](docs/multi-agent-and-costs.md).

Read the published file for the full list, clamped defaults, and comments:

```php
config('agent-tools.conversations_index');
```

## Future Improvements: Retrieval Quality

Ideas inspired by [PageIndex](https://github.com/VectifyAI/PageIndex) — a vectorless, reasoning-based RAG system that replaces similarity search with LLM-driven navigation over hierarchical document trees. Their core insight: **similarity ≠ relevance**. Finding text that "looks like" the query is not the same as finding text that *answers* it.

### 1. Natural section boundaries (foundation)

`KnowledgePipeline` currently splits on blank lines with a 1500-char hard limit. This cuts sentences mid-thought and bleeds content across topics.

**Change:** Split at heading/section markers first. Only sub-split sections that exceed the char limit. Keep the section title as a prefix on every sub-chunk so neighbor expansion stays within the same section.

**Impact:** Each chunk becomes self-contained and topically coherent. The LLM no longer gets chunks that start mid-sentence about one topic and end mid-sentence about another.

### 2. Document-level descriptions (foundation)

Generate a one-sentence LLM summary per source document during `kb-populate`. Store as a `document_description` field on each chunk (or a separate lightweight index).

**Impact:** The `RetrievalPlannerAgent` can pre-filter at the document level — "only search documents about X" — before diving into chunks. Fewer candidates for Cohere reranking = faster, cheaper, more precise results.

### 3. Hierarchical chunking with `section_path`

Add `section_path` (e.g. `"Terms > Cancellation > Refund Policy"`), `section_level`, and `source_title` fields to the Knowledge index. Parse document structure during indexing so each chunk knows where it lives in the document hierarchy.

**Impact:** When the LLM gets 10 search results, knowing *where* each chunk lives helps it pick the right one. A chunk from "FAQ > Returns" is more trustworthy for a returns question than one from "Blog > Summer Sale" that happens to mention returns. Also enables section-level filtering in queries.

### 4. Section summaries as a retrieval layer

During indexing, generate a 1-2 sentence LLM summary per source document or per section. Index these in a `section_summary` field.

**Impact:** Summaries bridge the vocabulary gap between how users ask ("how much does it cost?") and how documents are written ("subscription pricing tiers"). Enables two-stage retrieval: search summaries first (fast, cheap) to identify which documents/sections matter, then search chunks within those.

### 5. Structure-aware `RetrievalPlannerAgent`

Inject available section paths (from improvement #3) into the planner prompt alongside topic tags. The planner outputs a `section_filter` alongside its per-source queries. Elasticsearch filters chunks by `section_path` before scoring.

**Impact:** This is PageIndex's tree navigation within our existing architecture. The LLM *reasons* about where the answer lives ("cancellation questions belong in Terms > Cancellation"), then ES fetches precisely from there instead of searching everything.

### 6. Cross-reference linking

During indexing, detect reference patterns ("see our pricing page", "refer to Section 4.2", "as described in the terms"). Store as `related_source_ids` on the chunk. When a chunk is retrieved, also pull its linked chunks — similar to neighbor expansion but semantic instead of positional.

**Impact:** Without this, the LLM gets a partial answer ("Refunds are processed within 5 days. For eligibility, see Terms Section 4.2") and either hallucinates the rest or says "I don't know" — even though the answer exists in the knowledge base.

### Build order

```
Foundation                     Enhancement                Advanced
(do first)                     (builds on foundation)     (when ready)

┌─────────────┐               ┌──────────────────┐       ┌─────────────┐
│ #1 Natural   │──────────────▶│ #3 Hierarchical  │──────▶│ #5 Structure│
│ boundaries   │               │ section_path     │       │ -aware      │
└─────────────┘               └──────────────────┘       │ planner     │
                                       │                  └─────────────┘
┌─────────────┐                        │
│ #2 Document  │───────────────────────┘                ┌─────────────┐
│ descriptions │                                        │ #6 Cross-ref│
└─────────────┘               ┌──────────────────┐     │ linking     │
                              │ #4 Section        │     └─────────────┘
                              │ summaries         │
                              └──────────────────┘
```
