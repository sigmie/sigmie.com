<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Structured-output agent: batch topic tagging for MagicTags indexing (multiple documents per request).
 *
 * Register a replacement via {@see \Sigmie\AgentTools\AgentTools::batchTaggingAgent()}.
 * {@see $expectedResultCount} is set per chunk by the indexing pipeline.
 */
class BatchTaggingAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function __construct(
        protected int $maxTags = 5,
        protected ?int $expectedResultCount = null,
        protected ?string $instructionsOverride = null,
    ) {}

    public function instructions(): Stringable|string
    {
        if ($this->instructionsOverride !== null && $this->instructionsOverride !== '') {
            return $this->instructionsOverride;
        }

        $base = self::defaultInstructions($this->maxTags);
        if ($this->expectedResultCount !== null && $this->expectedResultCount > 0) {
            $n = $this->expectedResultCount;

            return $base."\n\n"
                ."You are tagging multiple documents in one response. Return exactly {$n} items in `results`, in order: "
                .'results[0] tags Document 0, results[1] tags Document 1, etc.';
        }

        return $base;
    }

    /**
     * Base instructions shared with single-document tagging (without batch ordering rules).
     */
    public static function defaultInstructions(int $maxTags): string
    {
        return TaggingAgent::defaultInstructions($maxTags);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'results' => $schema->array()->items(
                $schema->object([
                    'tags' => $schema->array()->items($schema->string())->required(),
                ])->withoutAdditionalProperties()
            )->required(),
        ];
    }
}
