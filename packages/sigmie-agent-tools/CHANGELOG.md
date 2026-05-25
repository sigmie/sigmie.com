# Changelog

## [Unreleased] - Code Quality & Plug-and-Play Improvements

### Breaking changes

- **`AgentKnowledgeElasticsearchIndex` mapping**: removed `title`, `link`, and `language`; added dynamic `meta` object. Recreate the knowledge index (`sigmie:agent-tools:indices-delete` / `indices-create`) and re-ingest. `metaFieldNames()` now includes `meta` instead of `link`.
- **`RetrievalSource`**: implement `shouldRerank(): bool` and `metaFieldNames()` (built-in `AgentIndex` defaults `shouldRerank` to `true` and `metaFieldNames` to `[]`; memory and conversation indices return `false` for `shouldRerank` so hits use semantic scores only in `unified_search`).
- **`RetrievalPlannerAgent` / `RetrievalPlan`**: structured output now includes `{source}_relevance` (`high` | `low` | `skip`) per source. `RetrievalPlan` carries `sourceRelevances` and `maxResultsFor()`; `skip` omits a source from `_msearch`. Config `unified_relevance_high_limit` / `unified_relevance_low_limit` replace `unified_retrieve_per_source` for per-source retrieve sizes.
- **`UnifiedSearchTool`**: removed `unified_max_results` / `maxResults` final slice and `unified_retrieve_per_source` / `retrievePerSource` constructor argument. Result size is driven by planner relevance limits + rerank threshold; memory/history hits are pass-through (no global rerank). Removed rerank threshold fallback (empty reranked pool when all scores are below threshold). `unified_search` JSON may include `sources_skipped`.
- **`UnifiedSearchDebugData`**: `thresholdFallback` removed.
- **`SigmieAgent` constructor** now accepts only `RerankApi`, `Sigmie`, and optional `LoggerInterface`. Elasticsearch index instances are no longer injected; resolve them from the container or from `unified_search_indices` / `SigmieAgent::extraSources()` as needed.
- Removed publishable agent stub (`agent-tools-stub` / `stubs/ExampleAgent.php`). Create your own `app/Agent/*` class extending `SigmieAgent` and register it with `AgentTools::defaultAgent(...)`.
- Removed `ConversationsIndex`, `UserMemoryIndex`, and `KnowledgeIndex` contracts. Resolve `AgentConversationsElasticsearchIndex`, `AgentUserMemoryElasticsearchIndex`, and `AgentKnowledgeElasticsearchIndex` from the container instead.
- Replaced `GenerateKnowledgeTagsJob`, `GenerateMemoryTagsJob`, and `GenerateConversationTagsJob` with a single `GenerateTagsJob` that accepts the index FQCN and document id: `new GenerateTagsJob(AgentKnowledgeElasticsearchIndex::class, $documentId)`.

### Added
- **Documentation:** [docs/multi-agent-and-costs.md](docs/multi-agent-and-costs.md) — data scoping per user, multi-agent isolation patterns (tools, indices, session keys), Sentry `sentry_insights` vs hard cost limits, app-level token budget pattern. README sections “Multi-agent applications and LLM costs” and Configuration note.
- **Knowledge ingestion**: `KnowledgeDocument`, `KnowledgeSource`, `KnowledgePipeline` (chunking + stable `_id` / `source_id` / `position`), `IndexKnowledgeChunkJob` (3 tries, exponential backoff, `Batchable`), Artisan `sigmie:agent-tools:kb-populate` (streams sources into a `Bus::batch` with `allowFailures()`). Config: `knowledge_sources`, `kb_populate_queue`, `kb_populate_chunk_size`. Optional `sourceId` defaults to `sha256(content)`; explicit `sourceId` runs positions continuously across logical rows. `positionCursorsAfter()` + job `positionStartBySourceId` keep positions correct across batched jobs for the same source.
- **`unified_search_indices`**: Config array of `RetrievalSource` / `AgentIndex` FQCNs (default: memory, conversations, knowledge). Drives `SigmieAgent` unified search, Artisan index create/delete, and container singleton registration via `UnifiedSearchIndices`.

- **Auto-register Sigmie APIs**: With `SIGMIE_AGENT_EMBEDDINGS_API_KEY` or `COHERE_API_KEY` (or `services.cohere.*`), the package registers Cohere doc/query embeddings and rerank on the bound `Sigmie` instance (`SIGMIE_AGENT_AUTO_REGISTER_APIS`, default true). Opt out with `SIGMIE_AGENT_AUTO_REGISTER_APIS=false` for manual `registerApi()` calls.
- **Comprehensive Installation Guide**: 10-step installation with checklist in README
- **Optional Routes**: `enable_routes` config flag to disable HTTP/Inertia UI for API-only usage
- **Fail-Fast Validation**: Boot-time checks for Sigmie singleton and API registration with clear error messages
- **Publishable Config**: `php artisan vendor:publish --tag=agent-tools-config`
- **Missing Asset**: `agentMarkdown.js` now ships with the package and publishes correctly

### Improved
- **Grounding prompt** (example in package README): strengthened system prompt with five **Answer Requirements** (quote exactly, cite `meta.ref_id`/`category`/`title`, stay within retrieved content, verify before inventing, use meta for traceability). Includes bad vs good examples for FAQ quotes. E2E turns show verbatim citations after this change (tested FAQ-001, FAQ-002, FAQ-003 with exact wording + source citations).

### Changed
- **Generic Naming**: All defaults now use `sigmie_agent_tools_*` prefix instead of project-specific names
- **Optional Inertia**: Moved from `require` to `suggest` in composer.json
- **Vue Component**: Updated to use correct import path for markdown helper
- **Error Messages**: Clear instructions for missing Sigmie setup or API registrations

### Fixed
- **CLI / `conversationUser` + `unified_search`**: `prepareSearch()` accepts a non-`Authenticatable` object with an `id` property (same shape as `sigmie:agent-tools:prompt` uses). `SigmieAgent` passes `mixed` into `UnifiedSearchTool`’s user closure so PHP does not enforce `?Authenticatable` on `stdClass`.
- **`AgentTools::resolvedAgentClass()`**: reads `config('agent-tools.agent_class')` (same as HTTP chat), not `agent.agent_class`.
- **`sigmie:agent-tools:retrieval-planner:prompt`**: passes resolved retrieval sources and logger to `RetrievalPlanning::resolve()`; JSON output uses `source_queries` keyed by `sourceKey()`.
- **Broken Import**: `SigmieAgent.vue` import of `agentMarkdown.js` now works after publish
- **Inertia Dependency**: Routes only load when enabled and Inertia is installed
- **Static Method Call**: `Sigmie::extend()` now called on instance in `boot()` instead of statically in `register()`
- **MagicTagsPackage Signature**: Fixed `register()` method to match `Package` interface requirement
- **Protected Property Access**: Removed invalid access to protected `$collectionHooks` property
- **Plug-and-Play**: Package now works correctly when installed in fresh Laravel apps

### Code Quality Improvements

#### Reduced Duplication
- **ExtractsTopicTags trait**: Extracted `topicTagsFromDocument()` and `availableTags()` from 3 index classes
- **MapsHitsToRows / FiltersCreatedAtRange traits** (later removed when search hit mapping moved onto `AgentIndex::mapHits`)

#### Improved Maintainability
- **UnifiedSearchDebugData DTO**: Replaced 22-parameter `buildDebugPayloadSuccess()` method with structured data object
- **Instance-Based Repository**: Converted `AgentDebugRepository` from static methods to dependency injection pattern

#### Impact
- **Lines of duplicated code removed**: ~150 lines
- **Trait reusability**: 3 new traits shared across index classes
- **Type safety**: Structured DTOs replace long parameter lists
- **Testability**: Instance-based dependencies enable easier mocking

## Migration Notes

### For Existing Installations

If you're upgrading and have the old index names in production:

1. **Keep your existing `.env` values** - they override the new defaults:
   ```env
   SIGMIE_AGENT_CONVERSATIONS_INDEX=your_existing_name
   SIGMIE_AGENT_MEMORY_INDEX=your_existing_name
   SIGMIE_AGENT_KNOWLEDGE_INDEX=your_existing_name
   ```

2. **Or migrate to new names**:
   - Export data from old indices
   - Update `.env` to use new `sigmie_agent_tools_*` names
   - Run `php artisan sigmie:agent-tools:indices-create`
   - Re-index your data

### For New Installations

Follow the [Installation Guide](README.md#installation) in the README.

### API-Only Usage

To use only the backend (no UI):

```env
SIGMIE_AGENT_ENABLE_ROUTES=false
```

Then remove `inertiajs/inertia-laravel` from your composer.json if not needed elsewhere.
