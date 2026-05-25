# sigmie.com

Marketing site and documentation hub for Sigmie, a PHP library for Elasticsearch/OpenSearch.

## Stack

- **Backend:** Laravel 9, PHP 8.3, Inertia.js
- **Frontend:** Vue 3, Vite, Tailwind CSS, SSR via `inertia:start-ssr`
- **Search:** Elasticsearch (localhost:9200), Infinity Embeddings (localhost:7997, 384-dim vectors)
- **Server:** Ubuntu 24.04, nginx, PHP-FPM, Supervisor, deployed at `/home/forge/sigmie.com`

## Deployment

Run `envoy run deploy` from the project root. The full flow:

1. **sync_docs** (local) — sparse-clones `sigmie/sigmie` repo, copies `docs/*` into `docs/v2/`, auto-commits if changed
2. **push_to_repository** (local) — pushes to GitHub
3. **enable_maintenance_mode** (server) — `artisan down`
4. **pull_changes** (server) — `git pull origin master`
5. **run_composer** (server) — `composer install --no-dev`
6. **run_migrations** (server) — `artisan migrate --force`
7. **build_assets** (server) — `npm install && npm run build` (Vite + SSR)
8. **install_mcp_deps** (server) — `npm install` in `mcp-server/`
9. **reindex_docs** (server) — `artisan docs:index --fresh` (parses markdown by h2/h3 sections, indexes into Elasticsearch with vector embeddings)
10. **optimize_laravel** (server) — config/route/view cache
11. **restart_ssr** (server) — supervisor restart `daemon-523660`
12. **restart_mcp** (server) — supervisor restart `sigmie-mcp`
13. **reload_php_fpm** (server)
14. **disable_maintenance_mode** (server) — `artisan up`

All remote commands run as `sudo -u forge` (files owned by `forge` user). SSH as `nico@35.242.239.121` (alias: `sigmie`).

## Documentation Flow

Docs originate in `sigmie/sigmie` repo (`docs/*.md`) and are synced into this repo at `docs/v2/` during deploy. The `artisan docs:index` command:

- Reads all `docs/*/*.md` files
- Parses YAML frontmatter (title, description, category, keywords)
- Splits each page into sections by h2/h3 headings
- Indexes each section as a separate Elasticsearch document (649 total)
- Generates 384-dim vector embeddings via Infinity Embeddings for semantic search

## MCP Server

A Node.js MCP server at `mcp-server/` serves Sigmie documentation to AI agents.

**Two entrypoints:**
- `index.mjs` — stdio transport (local use)
- `http.mjs` — Streamable HTTP transport on port 3100 (remote use via `sigmie.com/mcp`)

**Three tools:**
- `search_docs(query)` — calls `POST /api/search/docs` (hybrid keyword + vector search via Elasticsearch)
- `read_doc(page, version)` — reads markdown file from disk
- `list_docs(version)` — lists available doc pages

**Infrastructure:**
- Supervisor: `/etc/supervisor/conf.d/sigmie-mcp.conf`
- Nginx proxy: `/etc/nginx/forge-conf/sigmie.com/server/mcp-proxy.conf` (`/mcp` → `127.0.0.1:3100`)

## Key Paths

- `app/Http/Controllers/DocsSearchController.php` — search API endpoint
- `app/Http/Controllers/SearchController.php` — RAG and standard search
- `app/Console/Commands/IndexDocs.php` — `docs:index` command
- `app/Indices/Docs.php` — Elasticsearch index definition (semantic fields: title, content, headings)
- `config/docs.php` — doc versions and navigation config
- `Envoy.blade.php` — deployment script
- `mcp-server/server.mjs` — shared MCP tool definitions
- `mcp-server/http.mjs` — HTTP transport server
- `mcp-server/test.mjs` — MCP server tests (5 tests, `node --test test.mjs`)

## Sigmie Docs Chat Agent

A Claude Haiku 4.5 agent answers documentation questions, powered by `sigmie/agent-tools` and the existing Infinity embeddings stack. Sources:

- `app/Agent/SigmieDocsAgent.php` — agent class (Anthropic Haiku, no rerank, single knowledge source)
- `app/Agent/NullRerankApi.php` — placeholder satisfying `RerankApi` contract
- `app/Knowledge/DocsKnowledgeSource.php` — yields one `KnowledgeDocument` per docs/*/*.md h2/h3 section
- `app/Http/Controllers/DocsChatController.php` — POST `/api/agent/chat` (Vercel data protocol stream) + POST `/api/agent/clear`. Conversations persist server-side **keyed by client IP** via `$agent->continue($conversationId, $user)` (`docs-<sha256(ip)[:24]>` / `ip-<sha256(ip)[:24]>`), giving real multi-turn memory across requests/reloads. `clear` wipes the IP's rows in `agent_conversation_messages` / `agent_conversations`.
- `resources/js/components/ChatWidget.vue` — chat column with a **Clear** button (resets UI + server history)
- `resources/js/composables/useChatPanel.js` — shared singleton (`open`, `openChat()`); the Navbar **"Ask AI"** button opens/focuses the panel
- `packages/sigmie-agent-tools/` — path-copied fork of the package, with three patches:
  - `Tools/UnifiedSearchTool.php` — `RerankApi` made nullable, pass-through respects `topK` when null
  - `Elasticsearch/Agent{Knowledge,Conversations,UserMemory}ElasticsearchIndex.php` — semantic dim 256 → 384 to match `infinity-embeddings`
  - `Laravel/AgentToolsPromptCommand.php` — removed required Cohere rerank API lookup

Indices:
- `sigmie_agent_knowledge` — chunks (~400) with 384-dim embeddings + magic-tags topic labels
- `sigmie_agent_tools_conversations` / `sigmie_agent_tools_memory` — auto-created sidecars (not queried by this agent but kept for `SyncConversationTurnJob` writes)

Env: `SIGMIE_AGENT_AUTO_REGISTER_APIS=false`, `SIGMIE_AGENT_RERANK_API=` (empty), `SIGMIE_AGENT_EMBEDDINGS_DOC_API=infinity-embeddings`, `AI_DEFAULT=anthropic`.

### Opt-in & disabled by default

The chat is **off by default**. Set `AGENT_CHAT_ENABLED=true` to expose it.
- Server: `config('agent.chat.enabled')`; `DocsChatController` 404s both routes when off; flag shared to the frontend via `HandleInertiaRequests` → `agentChat.enabled`.
- UI: when enabled, the column is still **opt-in** — closed by default, opened via the topbar **"Ask AI"** button or the floating launcher, remembered in `localStorage` (`sigmie-chat-open`). Close button opts out. Layouts/Navbar reserve the 400px column only when `useChatPanel().showColumn` is true.

### Streaming behind nginx (502 fix)

Streamed responses 502 behind nginx when the proxy buffers them. `App\Http\Middleware\DisableResponseBuffering` (on the `/api/agent/chat` route) sets `X-Accel-Buffering: no` + `Cache-Control: no-transform`. If a 502 persists on Forge, also set in the site's nginx server block:

```nginx
location /api/agent/ {
    fastcgi_buffering off;
    fastcgi_read_timeout 300s;
    try_files $uri $uri/ /index.php?$query_string;
}
```

### Public abuse protection (`config/agent.php`)

The chat endpoint is public, so cost is bounded on three axes:
- **Per-IP throttle** — `RateLimiter::for('agent-chat')` in `AppServiceProvider` (`AGENT_CHAT_PER_MINUTE`=6, `AGENT_CHAT_PER_HOUR`=30), applied as `throttle:agent-chat` on the route.
- **Global daily budget** — `DocsChatController::enforceDailyBudget()` increments a Redis day-keyed counter (`AGENT_CHAT_DAILY_BUDGET`=2000), 429s when exhausted, counts only valid LLM-bound requests.
- **Per-request caps** — `SigmieDocsAgent::maxTokens()` (600) + `maxConversationMessages()` (6), and `AGENT_CHAT_MAX_PROMPT_CHARS` (2000) prompt-length guard.

### Chat UI (`ChatWidget.vue`)

Permanent full-height right column (`xl:w-[400px]`), slide-over below `xl`. Mounted in `AppLayout`, `DocsLayout`, and `Pages/Document.vue` (which reserves `xl:pr-[400px]` + `Navbar` `xl:right-[400px]`). Uses `@ai-sdk/vue` `Chat`, `marked` + `marked-highlight` + `highlight.js` (github-dark) for syntax-highlighted snippets, animated "Thinking…" indicator, and input focus retained across turns.

## Commands

- `envoy run deploy` — full deploy with doc sync + reindex (now also runs `sigmie:agent-tools:kb-populate`)
- `php artisan docs:index --fresh` — reindex documentation into Elasticsearch
- `php artisan sigmie:agent-tools:indices-create` — create agent ES indices (idempotent on alias conflicts; safe to ignore)
- `php artisan sigmie:agent-tools:kb-populate` — dispatch batched jobs to (re)populate the agent knowledge index
- `php artisan sigmie:agent-tools:prompt "question"` — CLI smoke test against the agent
- `cd mcp-server && node --test test.mjs` — run MCP server tests
