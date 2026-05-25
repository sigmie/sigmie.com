# Multi-agent setups and AI cost controls

This document explains how per-user data is scoped today, how to run more than one agent in one application without cross-talk, and how to monitor or cap LLM cost. It applies to `sigmie/agent-tools` as it ships: there is no built-in `agent_type` column or token budget in the package yet; use the patterns below until or unless a future release adds them.

## How data is scoped by default

| Store | Scope | Notes |
|--------|--------|--------|
| **Laravel AI conversations** | `user_id` + `conversation_id` (DB) | `AgentChatController` only continues conversations the user owns. |
| **User memory** | `user_id` only (DB + ES) | `MemorySaveTool` writes to `agent_user_memory`; the memory index filters search by `user_id`. All agent classes share the same memory namespace for a given user. |
| **Conversation history (ES)** | `user_id` only in search | The history source searches past turns for the current user, across threads; metadata includes `conversation_id`. There is no separate “agent” dimension on those documents. |
| **Knowledge** | Global (not per user) | Same index for all users; scope content with `source_id` / `meta` if you need logical separation. |

So: two different `SigmieAgent` subclasses (e.g. a chat agent and a “rename my PDFs” agent) for the same user will **share** stored memory facts and **share** the pool of past turns in Elasticsearch, unless you isolate them (below).

## Isolating multiple agents

### 1. Tools and retrieval sources (no DB migration)

Override `defaultTools()` and/or `defaultSources()` + `extraSources()` on a per-agent class:

- **Omit the memory source** and omit `MemorySaveTool` (memory is only registered when the merged sources include `AgentUserMemoryElasticsearchIndex` — see `SigmieAgent::defaultTools()`). Use this for a narrow, stateless tool agent (e.g. file naming) so it does not read or write long-lived user memory.
- **Omit the conversations/history** source for an agent that should not see other chats. Remove `AgentConversationsElasticsearchIndex` from that agent’s merged `RetrievalSource` list only.
- **Keep only knowledge** for a FAQ-only or doc-grounded agent: merged sources = `AgentKnowledgeElasticsearchIndex` (and optional custom `AgentIndex` implementations).

`unified_search_indices` in `config/agent-tools.php` is the default list; subclasses override with explicit sources when they need a different mix.

### 2. Elasticsearch index names (strong isolation)

Point different “products” or tenants at different indices using env:

- `SIGMIE_AGENT_MEMORY_INDEX`, `SIGMIE_AGENT_CONVERSATIONS_INDEX`, `SIGMIE_AGENT_KNOWLEDGE_INDEX`

Set these **before** the request resolves the agent, or use separate config per route group. Recreate indices (`sigmie:agent-tools:indices-delete` / `indices-create`) and reindex after a rename.

### 3. Session and active conversation (HTTP UI)

`session_conversation_key` (default `agent_conversation_id`) is global to the app. If two UIs use the same session and the same key, they can clobber the active conversation. For two agents behind different URL prefixes, use **different** session keys in config (e.g. `AGENT_SESSION_CONVERSATION_KEY=agent_chat_id` for one mount and a second key for the other) so each agent’s UI keeps an independent “active” thread.

## Shared global memory vs per-agent memory

There is a product tradeoff, not a single “correct” setting:

- **Shared memory** helps when the same user’s preferences (language, date format, naming style) should apply in every surface.
- **Isolated memory** helps when one surface must not recall facts from another (compliance, minimising noise, or special-purpose tools).

With the current schema, the practical split is: **one agent** uses memory save/delete; **the other** drops the memory source and only reads knowledge (or a separate index) so it cannot persist cross-surface facts.

## Sentry: observability, not a hard cap

`agent-tools.sentry_insights` (env: `SIGMIE_AGENT_SENTRY_INSIGHTS`) registers listeners that map Laravel AI events to Sentry `gen_ai.*` spans (model, tool calls, `gen_ai.usage.*` on responses when the SDK provides usage). It requires `sentry/sentry` and tracing (e.g. `SENTRY_TRACES_SAMPLE_RATE` in the app).

Sentry is for **observability and alerts** (dashboards, budgets in Sentry, notifications). It does not reject HTTP requests over a token budget.

## Enforcing cost or token limits (application layer)

The package does not ship quota middleware. A typical pattern in the **host application**:

1. **Listen to** `Laravel\Ai\Events\AgentPrompted` and read `response->usage` (input/output tokens) plus the authenticated user.
2. **Increment** a counter in Redis/DB (per user, per day, or per team).
3. **Guard** the chat route with middleware that checks the counter *before* calling the agent, or with a pre-stream check, and return `429` when over limit.
4. Optionally **attribute cost** to model and provider (from the same event or your billing integration).

`AgentChatController` is the main HTTP entry; ensure your middleware runs in the same middleware group as the agent route.

## Related config keys (reference)

- `sentry_insights` — Sentry `gen_ai.*` spans; see [Configuration](../README.md#configuration) in the README.
- `unified_search_indices` — ordered `RetrievalSource` classes for `unified_search`.
- `conversations_index`, `memory_index`, `knowledge_index` — Elasticsearch index names.

## Future package direction

A long-term design may add an explicit `agent_type` (or similar) to memory and conversation history documents, per-agent session key defaults, and optional token-usage recording in the package. This file will be updated when those exist.
