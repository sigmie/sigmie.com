<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\MagicTags;

use Sigmie\AgentTools\MagicTags\Sidecar\Index as MagicTagsSidecarIndex;
use Sigmie\Base\Http\Responses\Search as SearchResponse;
use Sigmie\Document\Contracts\CollectionHook;
use Sigmie\Document\Document;
use Sigmie\Mappings\Properties;
use Sigmie\Mappings\Types\Text;
use Sigmie\Query\Aggs;
use Sigmie\Query\Queries\MatchAll;
use Sigmie\Sigmie;

/**
 * Fills {@see MagicTagsFieldType} fields and syncs the sidecar registry index.
 */
class MagicTagsCollectionHook implements CollectionHook
{
    /**
     * When true, {@see processBatch} skips LLM/classify tagging so documents index without `topic`;
     * the app should dispatch tag-generation jobs after indexing.
     */
    private static bool $asyncIndexing = false;

    /** @var array<string, true> */
    private static array $sidecarsEnsured = [];

    public static function setAsyncIndexing(bool $async): void
    {
        self::$asyncIndexing = $async;
    }

    public static function asyncIndexing(): bool
    {
        return self::$asyncIndexing;
    }

    private ?string $mainIndexName = null;

    private ?Sigmie $sigmieForBatch = null;

    public function shouldRun(Properties $properties): bool
    {
        return $properties->fieldsOfType(MagicTagsFieldType::class)->isNotEmpty();
    }

    public function beforeBatch(
        string $indexName,
        Sigmie $sigmie,
        Properties $properties,
        array $apis
    ): void {
        $this->mainIndexName = $indexName;
        $this->sigmieForBatch = $sigmie;

        if (! $this->shouldRun($properties)) {
            return;
        }

        $this->ensureMagicTagsSidecarIndexExists($sigmie, $properties);
    }

    /**
     * @param  array<int, Document>  $documents
     * @return array<int, Document>
     */
    public function processBatch(
        array $documents,
        Properties $properties,
        array $apis
    ): array {
        if (self::$asyncIndexing) {
            return $documents;
        }

        if (! $this->shouldRun($properties) || $documents === [] || $this->mainIndexName === null || $this->sigmieForBatch === null) {
            return $documents;
        }

        $sigmie = $this->sigmieForBatch;
        $sidecars = $this->buildSidecarsMap($properties, $this->mainIndexName, $sigmie);
        $processor = new MagicTagsProcessor($properties, $sidecars);

        $existing = $this->fetchExistingMagicTags($properties, $this->mainIndexName, $sigmie);

        return $processor->populateMagicTagsForDocuments($documents, $existing);
    }

    public function afterBatch(
        array $documents,
        string $indexName,
        Sigmie $sigmie,
        Properties $properties,
        array $apis
    ): void {
        if (! $this->shouldRun($properties) || $documents === []) {
            $this->mainIndexName = null;
            $this->sigmieForBatch = null;

            return;
        }

        $this->writeMagicTagsToSidecar($documents, $indexName, $sigmie, $properties, $apis);
        $this->mainIndexName = null;
        $this->sigmieForBatch = null;
    }

    /**
     * Run the same tagging pipeline as {@see processBatch} (existing tags + LLM/classify) without indexing.
     * Use before {@see \Sigmie\SigmieIndex::merge} so the document has `topic` pre-filled; the collection hook
     * then skips re-generation and {@see afterBatch} still writes the sidecar.
     */
    public function populateDocument(
        Document $document,
        string $indexName,
        Sigmie $sigmie,
        Properties $properties,
    ): Document {
        if (! $this->shouldRun($properties)) {
            return $document;
        }

        $this->mainIndexName = $indexName;
        $this->sigmieForBatch = $sigmie;
        $this->ensureMagicTagsSidecarIndexExists($sigmie, $properties);

        $sidecars = $this->buildSidecarsMap($properties, $indexName, $sigmie);
        $processor = new MagicTagsProcessor($properties, $sidecars);
        $existing = $this->fetchExistingMagicTags($properties, $indexName, $sigmie);

        $results = $processor->populateMagicTagsForDocuments([$document], $existing);

        $this->mainIndexName = null;
        $this->sigmieForBatch = null;

        return $results[0] ?? $document;
    }

    private function searchWithAggs(Sigmie $sigmie, string $indexName, Aggs $aggs): SearchResponse
    {
        return $sigmie->query($indexName, new MatchAll, $aggs)
            ->size(0)
            ->get();
    }

    private function firstMagicTagsField(Properties $properties): ?MagicTagsFieldType
    {
        foreach ($properties->fieldsOfType(MagicTagsFieldType::class) as $field) {
            if ($field instanceof MagicTagsFieldType) {
                return $field;
            }
        }

        return null;
    }

    /**
     * @return array{api: string, dimensions: int}|null
     */
    private function getSidecarEmbeddingsConfig(Properties $properties): ?array
    {
        $magicField = $this->firstMagicTagsField($properties);

        if ($magicField === null) {
            return null;
        }

        $sourceField = $properties->get($magicField->fromField());

        if (! $sourceField instanceof Text || ! $sourceField->isSemantic()) {
            return null;
        }

        $vectorField = $sourceField->vectorFields()->first();

        if ($vectorField === null) {
            return null;
        }

        return [
            'api' => $vectorField->apiName ?? 'default',
            'dimensions' => $vectorField->dims ?? 256,
        ];
    }

    private function sidecarLogicalNameForField(MagicTagsFieldType $field, string $mainIndexName): string
    {
        $name = $field->tagIndexName();

        return $name !== '' ? $name : $mainIndexName;
    }

    private function ensureMagicTagsSidecarIndexExists(Sigmie $sigmie, Properties $properties): void
    {
        $config = $this->getSidecarEmbeddingsConfig($properties);

        if ($config === null) {
            return;
        }

        foreach ($properties->fieldsOfType(MagicTagsFieldType::class) as $field) {
            if (! $field instanceof MagicTagsFieldType) {
                continue;
            }

            $logical = $this->sidecarLogicalNameForField($field, $this->mainIndexName ?? '');

            if ($logical === '') {
                continue;
            }

            $ensureKey = $logical.'@'.$field->getSidecarSuffix();

            if (isset(self::$sidecarsEnsured[$ensureKey])) {
                continue;
            }

            (new MagicTagsSidecarIndex(
                $logical,
                $sigmie,
                $config['api'],
                $config['dimensions'],
                $field->getSidecarSuffix(),
            ))->ensureExists();
            self::$sidecarsEnsured[$ensureKey] = true;
        }
    }

    /**
     * @param  array<int, Document>  $documents
     */
    private function writeMagicTagsToSidecar(
        array $documents,
        string $mainIndexName,
        Sigmie $sigmie,
        Properties $properties,
        array $apis
    ): void {
        $config = $this->getSidecarEmbeddingsConfig($properties);

        if ($config === null) {
            return;
        }

        /** @var array<string, array<string, array<int, Document>>> $tagDocsBySidecar */
        $tagDocsBySidecar = [];

        foreach ($documents as $document) {
            foreach ($properties->fieldsOfType(MagicTagsFieldType::class) as $path => $magicField) {
                if (! $magicField instanceof MagicTagsFieldType) {
                    continue;
                }

                $tags = $document->get($path);

                if (! is_array($tags)) {
                    continue;
                }

                $logical = $this->sidecarLogicalNameForField($magicField, $mainIndexName);
                $suffix = $magicField->getSidecarSuffix();

                foreach ($tags as $tag) {
                    if (! is_string($tag)) {
                        continue;
                    }
                    if ($tag === '') {
                        continue;
                    }
                    $tagDocsBySidecar[$logical][$suffix][] = new Document([
                        'magic_field_path' => $path,
                        'tag' => $tag,
                    ], md5($path.'::'.$tag));
                }
            }
        }

        foreach ($tagDocsBySidecar as $logical => $bySuffix) {
            foreach ($bySuffix as $suffix => $tagDocs) {
                if ($tagDocs === []) {
                    continue;
                }

                (new MagicTagsSidecarIndex(
                    $logical,
                    $sigmie,
                    $config['api'],
                    $config['dimensions'],
                    $suffix,
                ))->collect(false)
                    ->apis($apis)
                    ->withoutHooks()
                    ->merge($tagDocs);
            }
        }
    }

    /**
     * @return array<string, MagicTagsSidecarIndex>
     */
    private function buildSidecarsMap(Properties $properties, string $mainIndexName, Sigmie $sigmie): array
    {
        $config = $this->getSidecarEmbeddingsConfig($properties);

        if ($config === null) {
            return [];
        }

        $sidecars = [];

        foreach ($properties->fieldsOfType(MagicTagsFieldType::class) as $path => $field) {
            if (! $field instanceof MagicTagsFieldType) {
                continue;
            }

            $logical = $this->sidecarLogicalNameForField($field, $mainIndexName);

            if ($logical === '') {
                continue;
            }

            $sidecars[$path] = new MagicTagsSidecarIndex(
                $logical,
                $sigmie,
                $config['api'],
                $config['dimensions'],
                $field->getSidecarSuffix(),
            );
        }

        return $sidecars;
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function fetchExistingMagicTags(Properties $properties, string $indexName, Sigmie $sigmie): array
    {
        $magicFields = $properties->fieldsOfType(MagicTagsFieldType::class);

        if ($magicFields->isEmpty()) {
            return [];
        }

        $this->ensureMagicTagsSidecarIndexExists($sigmie, $properties);

        $aggs = new Aggs;

        foreach ($magicFields as $path => $field) {
            if (! $field instanceof MagicTagsFieldType) {
                continue;
            }

            $aggs->terms($path, $path)->size($field->getAggregationSize());
        }

        $response = $this->searchWithAggs($sigmie, $indexName, $aggs);

        $aggregations = $response->json('aggregations') ?? [];
        $result = [];

        foreach ($magicFields as $path => $field) {
            $buckets = $aggregations[$path]['buckets'] ?? [];
            $result[$path] = array_column($buckets, 'key');
        }

        return $result;
    }

}
