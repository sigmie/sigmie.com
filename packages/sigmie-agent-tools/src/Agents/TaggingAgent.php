<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Structured-output agent: single-document topic tagging for MagicTags indexing.
 *
 * Register a replacement via {@see \Sigmie\AgentTools\AgentTools::taggingAgent()}.
 * Per-field {@see \Sigmie\AgentTools\MagicTags\MagicTagsFieldType::prompt()} still overrides instructions when non-empty.
 */
class TaggingAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function __construct(
        protected int $maxTags = 5,
        protected ?string $instructionsOverride = null,
    ) {}

    public function instructions(): Stringable|string
    {
        if ($this->instructionsOverride !== null && $this->instructionsOverride !== '') {
            return $this->instructionsOverride;
        }

        return self::defaultInstructions($this->maxTags);
    }

    /**
     * Default taxonomy instructions for a given max tag count.
     */
    public static function defaultInstructions(int $maxTags): string
    {
        return "You are a taxonomy tagger for search indexing.\n"
            ."Return up to {$maxTags} concise tags as lowercase kebab-case tokens.";
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'tags' => $schema->array()->items($schema->string())->required(),
        ];
    }
}
