<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\Laravel;

use Sigmie\AgentTools\Contracts\RetrievalSource;
use Sigmie\AgentTools\Elasticsearch\AgentConversationsElasticsearchIndex;
use Sigmie\AgentTools\Elasticsearch\AgentKnowledgeElasticsearchIndex;
use Sigmie\AgentTools\Elasticsearch\AgentUserMemoryElasticsearchIndex;

/**
 * Resolves {@see config('agent-tools.unified_search_indices')} into FQCNs and container instances.
 */
final class UnifiedSearchIndices
{
    /**
     * @return list<class-string>
     */
    public static function classNames(): array
    {
        $configured = config('agent-tools.unified_search_indices');
        if (! is_array($configured) || $configured === []) {
            return self::defaultClassNames();
        }

        $out = [];
        foreach ($configured as $fqcn) {
            if (is_string($fqcn) && $fqcn !== '' && class_exists($fqcn)) {
                $out[] = $fqcn;
            }
        }

        return $out !== [] ? array_values(array_unique($out)) : self::defaultClassNames();
    }

    /**
     * @return list<class-string>
     */
    public static function defaultClassNames(): array
    {
        return [
            AgentUserMemoryElasticsearchIndex::class,
            AgentConversationsElasticsearchIndex::class,
            AgentKnowledgeElasticsearchIndex::class,
        ];
    }

    /**
     * @return list<RetrievalSource>
     */
    public static function resolvedSources(): array
    {
        $sources = [];
        foreach (self::classNames() as $class) {
            if (! is_a($class, RetrievalSource::class, true)) {
                continue;
            }
            $sources[] = app($class);
        }

        return $sources;
    }
}
