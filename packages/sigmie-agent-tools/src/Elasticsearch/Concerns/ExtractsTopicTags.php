<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\Elasticsearch\Concerns;

use Sigmie\Document\Document;

/**
 * Shared methods for extracting topic tags from documents and querying available tags via facets.
 */
trait ExtractsTopicTags
{
    /**
     * Extract topic tags from a document's _source.
     *
     * @return list<string>
     */
    protected function topicTagsFromDocument(Document $document): array
    {
        $topic = $document->_source['topic'] ?? null;
        if (! is_array($topic)) {
            return [];
        }

        return array_values(array_filter(
            array_map(static fn (mixed $t): string => is_string($t) ? trim($t) : '', $topic),
            static fn (string $t): bool => $t !== ''
        ));
    }

    /**
     * Get top topic facet values from the index.
     *
     * @return list<string>
     */
    public function availableTags(int $limit = 20): array
    {
        $lim = max(1, min(500, $limit));

        $response = $this->newSearch()
            ->semantic(false)
            ->properties($this->properties())
            ->filters('')
            ->queryString('')
            ->size(0)
            ->facets('topic:'.$lim)
            ->get();

        $formatted = $response->format();
        $facets = $formatted['facets'] ?? new \stdClass;
        $topic = $facets->topic ?? null;
        if (! is_object($topic)) {
            return [];
        }

        /** @var array<string, int|float> $counts */
        $counts = (array) $topic;
        arsort($counts, SORT_NUMERIC);

        return array_keys(array_slice($counts, 0, $lim, true));
    }
}
