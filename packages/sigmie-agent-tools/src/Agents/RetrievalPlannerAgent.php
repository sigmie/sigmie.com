<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Sigmie\AgentTools\Contracts\RetrievalSource;
use Stringable;

/**
 * Structured-output agent: decomposes a user search intent into per-source queries and a rerank phrase.
 *
 * Invoked inside {@see \Sigmie\AgentTools\Tools\UnifiedSearchTool}; not registered on the main agent.
 * Override {@see self::instructions()} or register a subclass via {@see \Sigmie\AgentTools\AgentTools::retrievalPlannerAgent()}.
 */
class RetrievalPlannerAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    /** @var list<RetrievalSource>|null */
    protected static ?array $activeSourcesForNextPrompt = null;

    /**
     * @param  list<RetrievalSource>  $sources
     */
    public static function useSourcesForNextPrompt(array $sources): void
    {
        self::$activeSourcesForNextPrompt = $sources;
    }

    public static function clearSourcesForNextPrompt(): void
    {
        self::$activeSourcesForNextPrompt = null;
    }

    /**
     * @return list<RetrievalSource>
     */
    protected static function sourcesForSchema(): array
    {
        return self::$activeSourcesForNextPrompt ?? [];
    }

    public function instructions(): Stringable|string
    {
        $sources = self::sourcesForSchema();

        $lines = [];
        foreach ($sources as $source) {
            $key = $source->sourceKey();
            $lines[] = '- '.$key.'_query: optimized for '.$source->retrievalPlannerHint();
            $lines[] = '- '.$key.'_relevance: one of high, low, or skip — how relevant this source is for the user intent';
        }
        $lines[] = '- rerank_query: one short phrase capturing what matters for ranking merged passages from all sources';

        return "You are a retrieval planner for a RAG assistant. The user message is a single search intent.\n\n"
            ."Produce these strings:\n".implode("\n", $lines)."\n\n"
            ."Rules:\n"
            ."- Keep each query concise; no markdown; no JSON in the values.\n"
            ."- If the intent applies equally to all sources, you may repeat similar wording.\n"
            ."- Use \"skip\" only when you are confident the source has no useful information for this intent.\n"
            ."- rerank_query should be short (roughly a line) and suitable as a reranker instruction.\n";
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        $sources = self::sourcesForSchema();

        $out = [];
        foreach ($sources as $source) {
            $key = $source->sourceKey();
            $out[$key.'_query'] = $schema->string()
                ->description('Semantic search query for: '.$source->retrievalPlannerHint())
                ->required();
            $out[$key.'_relevance'] = $schema->string()
                ->enum(['high', 'low', 'skip'])
                ->description('Relevance of '.$source->retrievalPlannerHint().' for this query.')
                ->required();
        }
        $out['rerank_query'] = $schema->string()
            ->description('Short phrase for globally reranking merged passages from all sources.')
            ->required();

        return $out;
    }
}
