<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\Elasticsearch\Concerns;

/**
 * Shared topic tag filter string for MagicTags `topic` field (OR across tags).
 */
trait HasTopicTagFilter
{
    /**
     * @param  list<string>  $tags
     */
    protected function buildTagFilter(array $tags): string
    {
        $tags = array_values(array_filter(array_map('trim', $tags), fn (string $t): bool => $t !== ''));
        if ($tags === []) {
            return '';
        }

        $parts = array_map(fn (string $t) => "topic:'".$this->escapeFilter($t)."'", $tags);

        return implode(' OR ', $parts);
    }

    protected function escapeFilter(string $value): string
    {
        return str_replace("'", "\\'", $value);
    }
}
