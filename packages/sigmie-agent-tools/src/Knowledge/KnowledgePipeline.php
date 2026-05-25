<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\Knowledge;

use Sigmie\AgentTools\Elasticsearch\AgentKnowledgeElasticsearchIndex;

class KnowledgePipeline
{
    public function __construct(
        private readonly AgentKnowledgeElasticsearchIndex $knowledgeIndex,
    ) {}

    /**
     * Chunk each document, build ES documents with stable ids, and merge into the knowledge index.
     *
     * When {@see KnowledgeDocument::$sourceId} is null, a deterministic id is derived from full `content`
     * (`sha256`). Set `sourceId` explicitly to group many logical rows (e.g. PDF paragraphs) under one
     * source for neighbor expansion.
     *
     * @param  iterable<KnowledgeDocument>  $documents
     * @param  array<string, int>  $positionStartBySourceId  Next position per `source_id` when continuing a prior job batch
     * @return int Number of Elasticsearch documents written (chunks).
     */
    public function ingest(iterable $documents, array $positionStartBySourceId = []): int
    {
        $batch = [];
        $written = 0;
        $positionBySource = $positionStartBySourceId;
        $seenContent = [];

        foreach ($documents as $kd) {
            if (! $kd instanceof KnowledgeDocument) {
                continue;
            }

            $contentHash = hash('sha256', trim($kd->content));
            if (isset($seenContent[$contentHash])) {
                continue;
            }
            $seenContent[$contentHash] = true;

            $sourceId = $this->resolveSourceId($kd);
            $chunks = $this->chunkContent($kd->content);
            $position = $positionBySource[$sourceId] ?? 0;

            foreach ($chunks as $chunkText) {
                $batch[] = KnowledgeDocument::forChunk($sourceId, $position, $chunkText, $kd->meta);
                $position++;
                $written++;
            }

            $positionBySource[$sourceId] = $position;
        }

        if ($batch !== []) {
            $this->knowledgeIndex->merge($batch, true);
        }

        return $written;
    }

    /**
     * Next position index per `source_id` after pipeline chunking, without writing to Elasticsearch.
     * Used to chain {@see \Sigmie\AgentTools\Laravel\Jobs\IndexKnowledgeChunkJob} batches for the same `source_id`.
     *
     * @param  iterable<KnowledgeDocument>  $documents
     * @param  array<string, int>  $positionStartBySourceId
     * @return array<string, int>
     */
    public function positionCursorsAfter(iterable $documents, array $positionStartBySourceId = []): array
    {
        $positionBySource = $positionStartBySourceId;
        $seenContent = [];

        foreach ($documents as $kd) {
            if (! $kd instanceof KnowledgeDocument) {
                continue;
            }

            $contentHash = hash('sha256', trim($kd->content));
            if (isset($seenContent[$contentHash])) {
                continue;
            }
            $seenContent[$contentHash] = true;

            $sourceId = $this->resolveSourceId($kd);
            $chunks = $this->chunkContent($kd->content);
            $position = $positionBySource[$sourceId] ?? 0;
            $position += count($chunks);
            $positionBySource[$sourceId] = $position;
        }

        return $positionBySource;
    }

    private function resolveSourceId(KnowledgeDocument $kd): string
    {
        $id = $kd->sourceId;

        return ($id !== null && $id !== '') ? $id : hash('sha256', $kd->content);
    }

    /**
     * @return list<string>
     */
    private function chunkContent(string $content): array
    {
        $content = trim($content);
        if ($content === '') {
            return [];
        }

        $paragraphs = array_values(array_filter(
            array_map('trim', preg_split('/\n\s*\n/u', $content) ?: []),
            fn (string $p) => $p !== '',
        ));

        if ($paragraphs === []) {
            return $this->hardSplit($content);
        }

        $chunks = [];
        $current = '';

        foreach ($paragraphs as $p) {
            if ($current === '') {
                $current = $p;

                continue;
            }

            $candidate = $current."\n\n".$p;
            if (mb_strlen($candidate) <= 1500) {
                $current = $candidate;

                continue;
            }

            $chunks = array_merge($chunks, $this->finalizeChunkSegment($current));
            $current = $p;
        }

        $chunks = array_merge($chunks, $this->finalizeChunkSegment($current));

        return $chunks;
    }

    /**
     * @return list<string>
     */
    private function finalizeChunkSegment(string $segment): array
    {
        $segment = trim($segment);
        if ($segment === '') {
            return [];
        }

        if (mb_strlen($segment) <= 1500) {
            return [$segment];
        }

        return $this->hardSplit($segment);
    }

    /**
     * @return list<string>
     */
    private function hardSplit(string $text, int $maxLen = 1500): array
    {
        if ($text === '') {
            return [];
        }

        $out = [];
        $len = mb_strlen($text);
        for ($i = 0; $i < $len; $i += $maxLen) {
            $piece = mb_substr($text, $i, $maxLen);
            $piece = trim($piece);
            if ($piece !== '') {
                $out[] = $piece;
            }
        }

        return $out;
    }
}
