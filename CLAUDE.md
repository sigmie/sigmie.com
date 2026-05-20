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

## Commands

- `envoy run deploy` — full deploy with doc sync + reindex
- `php artisan docs:index --fresh` — reindex documentation into Elasticsearch
- `cd mcp-server && node --test test.mjs` — run MCP server tests
