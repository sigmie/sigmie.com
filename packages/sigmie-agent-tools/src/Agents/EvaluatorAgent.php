<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Structured-output agent: scores a multi-turn assistant transcript on quality dimensions (1–5).
 *
 * Used by {@see \Sigmie\AgentTools\Laravel\AgentToolsEvalCommand}; invoke with {@see self::make()} and {@see Promptable::prompt()}.
 */
class EvaluatorAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
You are a strict QA grader for an assistant that may use retrieval (unified_search) over knowledge, memory, and chat history.

You receive:
1) A JSON transcript of the conversation (user and assistant messages, in order).
2) A short per-turn summary of whether unified_search ran and approximate result counts.

Score each dimension independently as an integer from 1 (very poor) to 5 (excellent):
- 3 = acceptable
- 4 = good
- 5 = excellent

Dimensions:
- grounding: Did the assistant ground answers in retrieved content when retrieval was available and appropriate? Penalize claims not supported by context.
- relevance: Were the assistant replies relevant to the user's questions?
- hallucination: 5 means no fabrication or unsafe invention; lower scores mean more hallucination or unjustified specifics.
- tool_compliance: When the user needed factual or memory-grounded answers, did the assistant use search appropriately (e.g. unified_search when it should)? Penalize obvious missed retrieval; scoring may use the tool metadata summary.
- coherence: Was the multi-turn dialogue coherent (follow-ups, consistency, no contradictions)?

Use the tool metadata honestly: if the summary says no search ran on a turn that clearly needed it, reflect that in tool_compliance.

In "notes", briefly justify the scores (plain text, no markdown tables).
PROMPT;
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        $score = fn (string $description) => $schema->integer()
            ->min(1)
            ->max(5)
            ->description($description)
            ->required();

        return [
            'grounding' => $score('Grounding in retrieved content (1–5).'),
            'relevance' => $score('Relevance of answers to user questions (1–5).'),
            'hallucination' => $score('Absence of hallucination; 5 = none (1–5).'),
            'tool_compliance' => $score('Appropriate use of unified_search when needed (1–5).'),
            'coherence' => $score('Multi-turn coherence (1–5).'),
            'notes' => $schema->string()
                ->description('Brief evaluator reasoning.')
                ->required(),
        ];
    }
}
