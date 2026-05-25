<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\Knowledge;

use Sigmie\Document\Document;

/**
 * One logical knowledge row from any upstream source (CSV, crawl, PDF text, etc.).
 * The pipeline chunks `content` into one {@see self::forChunk()} per Elasticsearch document.
 */
class KnowledgeDocument extends Document
{
    /**
     * @param  array<string, mixed>  $meta  Arbitrary key-value pairs stored under the index `meta` object and exposed via {@see \Sigmie\AgentTools\Elasticsearch\AgentKnowledgeElasticsearchIndex::metaFieldNames()}.
     */
    public function __construct(
        public readonly string $content,
        public readonly ?string $sourceId = null,
        public readonly array $meta = [],
    ) {
        parent::__construct();
    }

    /**
     * One indexed chunk ready for {@see \Sigmie\AgentTools\Elasticsearch\AgentKnowledgeElasticsearchIndex::merge()}.
     *
     * @param  array<string, mixed>  $meta
     */
    public static function forChunk(
        string $sourceId,
        int $position,
        string $content,
        array $meta,
    ): self {
        $id = hash('sha256', $sourceId.'|'.$position);
        $doc = new self($content, $sourceId, $meta);
        $doc->_source = [
            'source_id' => $sourceId,
            'position' => $position,
            'content' => $content,
            'meta' => $meta,
        ];
        $doc->id($id);

        return $doc;
    }
}
