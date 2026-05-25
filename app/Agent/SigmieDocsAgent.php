<?php

declare(strict_types=1);

namespace App\Agent;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Sigmie\AgentTools\Contracts\RetrievalSource;
use Sigmie\AgentTools\Elasticsearch\AgentKnowledgeElasticsearchIndex;
use Sigmie\AgentTools\Laravel\SigmieAgent;
use Sigmie\AgentTools\Tools\UnifiedSearchTool;
use Sigmie\AI\Contracts\RerankApi;
use Sigmie\Sigmie;

class SigmieDocsAgent extends SigmieAgent
{
    public function __construct(
        Sigmie $sigmie,
        ?LoggerInterface $logger = null,
    ) {
        parent::__construct(
            rerankApi: new NullRerankApi,
            sigmie: $sigmie,
            logger: $logger,
        );
    }

    public static function defaultModel(): string
    {
        return 'claude-haiku-4-5';
    }

    public function maxSteps(): int
    {
        return 5;
    }

    public function maxTokens(): int
    {
        return (int) config('agent.chat.max_output_tokens', 600);
    }

    protected function maxConversationMessages(): int
    {
        return (int) config('agent.chat.max_history_messages', 6);
    }

    protected function systemPrompt(): string
    {
        return <<<'PROMPT'
You are Sigmie's documentation assistant. Speak as "we" / "Sigmie" — you are the team behind the library, not a third party.

About Sigmie:
- A modern PHP and Laravel library for Elasticsearch and OpenSearch.
- Focus areas: fluent search builders, semantic and hybrid retrieval, AI-ready pipelines, no boilerplate.
- The knowledge base contains the official documentation at https://sigmie.com/docs/v2/*. Cite the page URL when relevant.

Answering rules:
- Use unified_search on every factual question, before answering.
- Only answer from retrieved content. If a method, option, or field is not in the retrieved chunks, say you don't have it documented — never invent API signatures.
- For code examples: prefer copying snippets from retrieved chunks verbatim, then explain. Use ```php fences.
- When the answer spans multiple pages, group by page and link each.
- For off-topic questions (weather, general PHP help, competitor products), politely steer back to Sigmie.
- Keep answers tight. Long preambles like "Based on the documentation…" are forbidden — lead with the answer.
PROMPT;
    }

    /**
     * @return list<RetrievalSource>
     */
    protected function defaultSources(): array
    {
        return [app(AgentKnowledgeElasticsearchIndex::class)];
    }

    /**
     * @return iterable<int, \Laravel\Ai\Contracts\Tool>
     */
    protected function defaultTools(): iterable
    {
        $log = $this->logger ?? new NullLogger;

        $unified = new UnifiedSearchTool(
            $this->mergedRetrievalSources(),
            null,
            $this->sigmie,
            fn (): mixed => $this->conversationUser,
            topK: (int) config('agent-tools.rerank_top_k', 8),
            scoreThreshold: 0.0,
        );
        $unified->setLogger($log);

        return [$unified];
    }
}
