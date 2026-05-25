<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\MagicTags;

use Closure;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Enums\Lab;
use Sigmie\AgentTools\AgentTools;
use Sigmie\Mappings\Types\Keyword;

class MagicTagsFieldType extends Keyword
{
    protected Lab|string|null $provider = null;

    protected int $maxTags = 5;

    protected Lab|string|null $embeddingsProvider = null;

    protected int $embeddingDimensions = 1024;

    protected float $similarityThreshold = 0.85;

    protected bool $classifyFirst = true;

    protected float $classifyConfidence = 0.3;

    protected int $minTagsForClassification = 10;

    protected string $prompt = '';

    /**
     * When non-empty, magic-tag sidecar index name is derived from this value instead of the main collection name,
     * so multiple indices can share one tag registry.
     */
    protected string $tagIndexName = '';

    protected int $batchSize = 15;

    protected int $aggregationSize = 500;

    protected string $sidecarSuffix = '__sigmie_magic_tags';

    protected bool $deduplicateTags = true;

    /**
     * @var (Closure(string, array): string)|null
     */
    protected ?Closure $userPromptBuilder = null;

    /**
     * @var (Closure(int, array<int, string>, array): string)|null
     */
    protected ?Closure $batchUserPromptBuilder = null;

    /**
     * @var class-string<Agent>|null
     */
    protected ?string $agentClass = null;

    /**
     * @var class-string<Agent>|null
     */
    protected ?string $batchAgentClass = null;

    public function __construct(
        string $name,
        protected string $fromField,
    ) {
        parent::__construct($name);
        $this->applyConfigDefaultsFromAgentTools();
    }

    private function applyConfigDefaultsFromAgentTools(): void
    {
        if (! function_exists('config')) {
            return;
        }

        $m = config('agent-tools.magic_tags');

        if (! is_array($m)) {
            return;
        }

        if (isset($m['provider']) && $m['provider'] !== null && $m['provider'] !== '') {
            $this->provider = is_string($m['provider']) || $m['provider'] instanceof Lab
                ? $m['provider']
                : $this->provider;
        }

        if (isset($m['embeddings_provider']) && $m['embeddings_provider'] !== null && $m['embeddings_provider'] !== '') {
            $this->embeddingsProvider = is_string($m['embeddings_provider']) || $m['embeddings_provider'] instanceof Lab
                ? $m['embeddings_provider']
                : $this->embeddingsProvider;
        }

        if (isset($m['max_tags'])) {
            $this->maxTags = max(1, (int) $m['max_tags']);
        }

        if (isset($m['batch_size'])) {
            $this->batchSize = max(1, (int) $m['batch_size']);
        }

        if (isset($m['aggregation_size'])) {
            $this->aggregationSize = max(1, (int) $m['aggregation_size']);
        }

        if (isset($m['sidecar_suffix']) && is_string($m['sidecar_suffix']) && $m['sidecar_suffix'] !== '') {
            $this->sidecarSuffix = $m['sidecar_suffix'];
        }
    }

    public function provider(Lab|string $provider): self
    {
        $this->provider = $provider;

        return $this;
    }

    public function embeddingsProvider(Lab|string $provider): self
    {
        $this->embeddingsProvider = $provider;

        return $this;
    }

    public function embeddingDimensions(int $dimensions): self
    {
        $this->embeddingDimensions = $dimensions;

        return $this;
    }

    public function similarityThreshold(float $threshold): self
    {
        $this->similarityThreshold = $threshold;

        return $this;
    }

    public function classifyFirst(bool $value = true): self
    {
        $this->classifyFirst = $value;

        return $this;
    }

    public function classifyConfidence(float $confidence): self
    {
        $this->classifyConfidence = $confidence;

        return $this;
    }

    public function minTagsForClassification(int $min): self
    {
        $this->minTagsForClassification = $min;

        return $this;
    }

    public function maxTags(int $max): self
    {
        $this->maxTags = $max;

        return $this;
    }

    public function prompt(string $prompt): self
    {
        $this->prompt = $prompt;

        return $this;
    }

    public function tagIndex(string $name): self
    {
        $this->tagIndexName = $name;

        return $this;
    }

    public function batchSize(int $size): self
    {
        $this->batchSize = max(1, $size);

        return $this;
    }

    public function aggregationSize(int $size): self
    {
        $this->aggregationSize = max(1, $size);

        return $this;
    }

    public function sidecarSuffix(string $suffix): self
    {
        $this->sidecarSuffix = $suffix;

        return $this;
    }

    public function deduplicateTags(bool $value = true): self
    {
        $this->deduplicateTags = $value;

        return $this;
    }

    /**
     * Override the default user message for single-document LLM tagging.
     *
     * @param  Closure(string $content, array<int, string> $existingTags): string  $builder
     */
    public function userPrompt(Closure $builder): self
    {
        $this->userPromptBuilder = $builder;

        return $this;
    }

    /**
     * Override the default user message for batch LLM tagging.
     *
     * @param  Closure(int $documentCount, array<int, string> $documentTexts, array<int, string> $existingTags): string  $builder
     */
    public function batchUserPrompt(Closure $builder): self
    {
        $this->batchUserPromptBuilder = $builder;

        return $this;
    }

    /**
     * Per-field single-document tagging agent (defaults to {@see AgentTools::resolvedTaggingAgentClass()}).
     *
     * @param  class-string<Agent>  $class
     */
    public function agent(string $class): self
    {
        $this->agentClass = $class;

        return $this;
    }

    /**
     * Per-field batch tagging agent (defaults to {@see AgentTools::resolvedBatchTaggingAgentClass()}).
     *
     * @param  class-string<Agent>  $class
     */
    public function batchAgent(string $class): self
    {
        $this->batchAgentClass = $class;

        return $this;
    }

    public function getPrompt(): string
    {
        return $this->prompt;
    }

    public function getMaxTags(): int
    {
        return $this->maxTags;
    }

    public function tagIndexName(): string
    {
        return $this->tagIndexName;
    }

    public function getEmbeddingsProvider(): Lab|string|null
    {
        return $this->embeddingsProvider;
    }

    public function getEmbeddingDimensions(): int
    {
        return $this->embeddingDimensions;
    }

    public function getSimilarityThreshold(): float
    {
        return $this->similarityThreshold;
    }

    public function isClassifyFirst(): bool
    {
        return $this->classifyFirst;
    }

    public function getClassifyConfidence(): float
    {
        return $this->classifyConfidence;
    }

    public function getMinTagsForClassification(): int
    {
        return $this->minTagsForClassification;
    }

    public function fromField(): string
    {
        return $this->fromField;
    }

    public function getProvider(): Lab|string|null
    {
        return $this->provider;
    }

    public function getBatchSize(): int
    {
        return $this->batchSize;
    }

    public function getAggregationSize(): int
    {
        return $this->aggregationSize;
    }

    public function getSidecarSuffix(): string
    {
        return $this->sidecarSuffix;
    }

    public function shouldDeduplicateTags(): bool
    {
        return $this->deduplicateTags;
    }

    /**
     * @return (Closure(string, array): string)|null
     */
    public function getUserPromptBuilder(): ?Closure
    {
        return $this->userPromptBuilder;
    }

    /**
     * @return (Closure(int, array<int, string>, array): string)|null
     */
    public function getBatchUserPromptBuilder(): ?Closure
    {
        return $this->batchUserPromptBuilder;
    }

    /**
     * @return class-string<Agent>
     */
    public function getAgentClass(): string
    {
        return $this->agentClass ?? AgentTools::resolvedTaggingAgentClass();
    }

    /**
     * @return class-string<Agent>
     */
    public function getBatchAgentClass(): string
    {
        return $this->batchAgentClass ?? AgentTools::resolvedBatchTaggingAgentClass();
    }

    public function validate(string $key, mixed $value): array
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                if (! is_string($item)) {
                    return [false, sprintf('The field %s mapped as %s must contain only strings', $key, $this->typeName())];
                }
            }

            return [true, ''];
        }

        if (! is_string($value)) {
            return [false, sprintf('The field %s mapped as %s must be a string or array of strings', $key, $this->typeName())];
        }

        return [true, ''];
    }
}
