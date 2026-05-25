<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\Laravel;

use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Sigmie\AgentTools\Contracts\RetrievalSource;
use Sigmie\AgentTools\Elasticsearch\AgentKnowledgeElasticsearchIndex;
use Sigmie\AgentTools\Elasticsearch\AgentUserMemoryElasticsearchIndex;
use Sigmie\AgentTools\Tools\ClarificationTool;
use Sigmie\AgentTools\Tools\MemoryDeleteTool;
use Sigmie\AgentTools\Tools\MemorySaveTool;
use Sigmie\AgentTools\Tools\UnifiedSearchTool;
use Sigmie\AI\Contracts\RerankApi;
use Sigmie\Sigmie;
use Stringable;

/**
 * Base agent: Laravel AI SDK orchestration + Elasticsearch-backed conversations, memory, and knowledge.
 *
 * The LLM context window (recent messages with full tool calls) is handled by the SDK's
 * {@see RemembersConversations} trait reading from the DB via {@see \Laravel\Ai\Contracts\ConversationStore}.
 * Elasticsearch is used only for semantic search via {@see UnifiedSearchTool}.
 *
 * Extend and override {@see self::systemPrompt()} (and optionally {@see self::defaultTools()}) per app.
 */
class SigmieAgent implements Agent, Conversational, HasTools
{
    use Promptable;
    use RemembersConversations;

    public function __construct(
        protected RerankApi $rerankApi,
        protected Sigmie $sigmie,
        protected ?LoggerInterface $logger = null,
    ) {}

    /** CLI / artisan defaults; override in app subclasses (e.g. env-backed). */
    public static function defaultUserToken(): string
    {
        return 'demo-user';
    }

    public static function defaultConversationId(): string
    {
        return 'demo-conversation';
    }

    public static function defaultModel(): string
    {
        return '';
    }

    public static function defaultDemoMessage(): string
    {
        return 'Hello';
    }

    /**
     * Domain-specific system instructions (override in app subclasses).
     */
    protected function systemPrompt(): string
    {
        return trim((string) config('agent.system_prompt', 'You are a helpful assistant.'));
    }

    /**
     * Universal RAG grounding rules prepended to every agent's instructions.
     *
     * These prevent the most common retrieval-augmented generation failures:
     * hallucination, stale-turn reuse, cross-source synthesis, and arithmetic
     * on retrieved content. Override to replace or extend.
     */
    protected function groundingRules(): string
    {
        return <<<'RULES'
Retrieval — MANDATORY:
- You MUST call unified_search as your FIRST action on EVERY turn that involves a factual question, product, service, pricing, user-specific memory, or any topic your knowledge base covers — including follow-up turns and requests to quote or reference specific documents.
- Call it ONCE per turn. Pass a single `query` string capturing the user's intent. The tool searches knowledge, memory, and past conversations internally.
- The ONLY turns where you may skip unified_search:
  1. Pure greetings with no question ("hi", "hello", "good morning").
  2. Pure acknowledgments with no question ("ok", "thanks", "got it").
  3. Turns where you are calling memory_save or memory_delete (and nothing else).
  4. Requests clearly unrelated to your domain.
- CRITICAL: Each turn requires a FRESH unified_search call. Do NOT reuse information from a previous turn's search results. Even if you believe you recall relevant content from earlier in this conversation, call unified_search again before answering.
- When in doubt whether to search — search. Querying unnecessarily is cheap; answering without retrieval is wrong.

Grounding Rules:
The unified_search tool applies a rerank score threshold. Documents below this threshold are filtered out and NOT included in the results.
- If unified_search returns results → answer using ONLY the information in those results. Do NOT supplement with training data.
- If unified_search returns NO results on a relevant topic → say you don't have that information. Do NOT guess or fall back to training data.
- If the question is clearly outside your domain → deflect politely and offer to help with your area of expertise.
- **Direct answer first:** Lead with the answer (Yes, No, or the key fact). Do not begin with filler such as "Based on the documentation" or "The documentation I can access right now".
- **Anti-synthesis:** Do NOT combine or derive figures from multiple retrieved sources. If two sources give different numbers, report the primary source's phrasing — never compute a derived value. Never merge facts from different documents into a single claim.
- **Anti-arithmetic:** Never perform arithmetic on retrieved content. If a document lists items plus "N others", do NOT compute a total. Report only numbers explicitly stated as totals in the source.
- **Partial results:** If results are on-topic but incomplete, answer what the docs support, then name the missing detail. Do not fill gaps with training data.
- **Cite sources:** When retrieved results include metadata (ref_id, title, category), reference the source in your answer for traceability.
- **Paraphrase, don't blockquote:** Always paraphrase retrieved content in your own words with attribution. Never present retrieved text inside markdown blockquotes (>).
- **Stay within retrieved content.** If a detail is not in the results, say so. Do not guess dates, numbers, or process steps.
- **No weak framing.** Do not say "based on the search results" or "the documentation I can access." State the answer plainly.
- The knowledge base is the single source of truth. The reranker is the authority on relevance. NEVER answer factual questions using training data alone.

Memory:
- ALWAYS call unified_search before answering any question that could involve the user's personal information (name, preferences, past requests, etc.). Never assume you don't have the information — check first.
- Use memory_save when the user states facts that should persist (name, preferences, context). Store as clear facts (e.g. "The user's name is ...").
- Before calling memory_save, ALWAYS call unified_search to check if the same fact already exists. Do NOT save duplicates.
- When the user corrects or updates a saved fact: (1) search for the existing fact, (2) memory_delete the outdated entry, (3) memory_save the corrected fact. Never leave stale or contradicting facts in memory.
- After a clarification resolves ambiguity: persist the resolution to memory in the same turn — do not only reply in chat.
RULES;
    }

    protected function maxConversationMessages(): int
    {
        return (int) config('agent-tools.max_conversation_messages', 100);
    }

    /**
     * @return list<RetrievalSource>
     */
    protected function defaultSources(): array
    {
        return UnifiedSearchIndices::resolvedSources();
    }

    /**
     * Extra retrieval sources (override in app subclasses).
     *
     * @return list<RetrievalSource>
     */
    protected function extraSources(): array
    {
        return [];
    }

    /**
     * Sources passed to {@see UnifiedSearchTool} (config list plus optional extras).
     *
     * @return list<RetrievalSource>
     */
    protected function mergedRetrievalSources(): array
    {
        return array_merge($this->defaultSources(), $this->extraSources());
    }

    /**
     * User memory index when that implementation is part of merged retrieval sources.
     */
    protected function memoryIndex(): ?AgentUserMemoryElasticsearchIndex
    {
        foreach ($this->mergedRetrievalSources() as $source) {
            if ($source instanceof AgentUserMemoryElasticsearchIndex) {
                return $source;
            }
        }

        return null;
    }

    /**
     * Knowledge index when that implementation is part of merged retrieval sources.
     */
    protected function knowledgeIndex(): ?AgentKnowledgeElasticsearchIndex
    {
        foreach ($this->mergedRetrievalSources() as $source) {
            if ($source instanceof AgentKnowledgeElasticsearchIndex) {
                return $source;
            }
        }

        return null;
    }

    /**
     * Tools for this agent (override to append app-specific tools).
     *
     * @return iterable<int, \Laravel\Ai\Contracts\Tool>
     */
    protected function defaultTools(): iterable
    {
        $log = $this->logger ?? new NullLogger;

        $tools = [];

        $sources = $this->mergedRetrievalSources();

        $unified = new UnifiedSearchTool(
            $sources,
            $this->rerankApi,
            $this->sigmie,
            fn (): mixed => $this->conversationUser,
            topK: (int) config('agent-tools.rerank_top_k', 30),
            scoreThreshold: (float) config('agent-tools.rerank_score_threshold', 0.1),
        );
        $unified->setLogger($log);
        $tools[] = $unified;

        if ($this->memoryIndex() !== null) {
            $memorySave = new MemorySaveTool(fn (): string => $this->resolveUserIdString());
            $memorySave->setLogger($log);
            $tools[] = $memorySave;

            $memoryDelete = new MemoryDeleteTool(fn (): string => $this->resolveUserIdString());
            $memoryDelete->setLogger($log);
            $tools[] = $memoryDelete;
        }

        $clarification = new ClarificationTool;
        $clarification->setLogger($log);
        $tools[] = $clarification;

        return $tools;
    }

    public function instructions(): Stringable|string
    {
        $userId = $this->resolveUserIdString();
        $conversationId = $this->resolveConversationIdString();
        $today = date('Y-m-d');

        $context = "context:\nuser_id: {$userId}\nconversation_id: {$conversationId}\ntoday: {$today}\n";

        $tagsLine = $this->buildKnowledgeTopicsLine();
        if ($tagsLine !== '') {
            $context .= $tagsLine."\n";
        }

        $grounding = trim($this->groundingRules());

        return $context.($grounding !== '' ? $grounding."\n\n" : '').$this->systemPrompt();
    }

    /**
     * Returns a single-line hint of top knowledge topics, e.g.:
     * "knowledge_base_topics: habits, atomic habits, morning routines, ..."
     * Returns an empty string when no knowledge index is configured or tags are unavailable.
     */
    protected function buildKnowledgeTopicsLine(): string
    {
        $knowledge = $this->knowledgeIndex();
        if ($knowledge === null) {
            return '';
        }

        $limit = max(1, min(100, (int) config('agent-tools.knowledge_available_tags_limit', 20)));

        try {
            $tags = $knowledge->availableTags($limit);
        } catch (\Throwable) {
            return '';
        }

        if ($tags === []) {
            return '';
        }

        return 'knowledge_base_topics: '.implode(', ', $tags);
    }

    public function tools(): iterable
    {
        return $this->defaultTools();
    }

    protected function resolveUserIdString(): string
    {
        return (string) ($this->conversationUser?->id ?? '');
    }

    protected function resolveConversationIdString(): string
    {
        return (string) ($this->conversationId ?? '');
    }
}
