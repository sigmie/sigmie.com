<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\MagicTags\Sidecar;

use Sigmie\Index\AliasedIndex;
use Sigmie\Mappings\NewProperties;
use Sigmie\Sigmie;
use Sigmie\SigmieIndex;

/**
 * Sidecar index for magic-tag registry rows, tied to a main index by name.
 *
 * The physical index name is the logical main index name plus {@see $sidecarSuffix}.
 */
class Index extends SigmieIndex
{
    public function __construct(
        public readonly string $mainIndexName,
        Sigmie $sigmie,
        public readonly string $embeddingsApiName,
        public readonly int $embeddingDimensions,
        public readonly string $sidecarSuffix = '__sigmie_magic_tags',
    ) {
        parent::__construct($sigmie);
    }

    public function name(): string
    {
        return $this->mainIndexName.$this->sidecarSuffix;
    }

    public function properties(): NewProperties
    {
        $properties = new NewProperties;

        $properties->keyword('magic_field_path');
        $properties->shortText('tag')->semantic(
            api: $this->embeddingsApiName,
            accuracy: 1,
            dimensions: $this->embeddingDimensions,
        );

        return $properties;
    }

    public function ensureExists(): AliasedIndex
    {
        return $this->newIndex()->createIfNotExists();
    }

    /**
     * @return array<int, array{tag: string, score: float}>
     */
    public function searchSimilarTags(string $text, string $fieldPath, int $size = 10): array
    {
        $formatted = $this->newSearch()
            ->semantic()
            ->disableKeywordSearch()
            ->queryString($text, fields: ['tag'])
            ->filters("magic_field_path:'{$fieldPath}'")
            ->size($size)
            ->get()
            ->format();

        $rawHits = $formatted['hits'] ?? [];

        return array_map(fn (array $hit): array => [
            'tag' => $hit['_source']['tag'] ?? '',
            'score' => (float) ($hit['_score'] ?? 0.0),
        ], $rawHits);
    }
}
