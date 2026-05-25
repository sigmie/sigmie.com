<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\MagicTags;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Embeddings;
use Sigmie\AgentTools\MagicTags\Sidecar\Index as MagicTagsSidecarIndex;
use Sigmie\Document\Document;
use Sigmie\Mappings\Properties;
use Sigmie\Support\VectorMath;

/**
 * Magic-tag generation (LLM, sidecar classification, dedup) for documents.
 *
 * Expects the using class to define {@see Properties} $properties
 * and optionally {@see array} $sidecars (field path -> MagicTagsSidecarIndex).
 *
 * @property Properties $properties
 * @property array<string, MagicTagsSidecarIndex> $sidecars
 */
trait ProcessesMagicTags
{
    public function populateMagicTags(Document $document, array $existingTags = []): Document
    {
        return $this->populateMagicTagsForDocuments([$document], $existingTags)[0];
    }

    /**
     * @param  array<int, Document>  $documents
     * @param  array<string, array<int, string>>  $existingTags  keyed by magic field path
     * @return array<int, Document>
     */
    public function populateMagicTagsForDocuments(array $documents, array $existingTags = []): array
    {
        $magicFields = $this->properties->fieldsOfType(MagicTagsFieldType::class);

        if ($magicFields->isEmpty() || $documents === []) {
            return $documents;
        }

        $sidecars = $this->sidecars ?? [];

        foreach ($magicFields as $path => $field) {
            if (! $field instanceof MagicTagsFieldType) {
                continue;
            }

            $existing = $existingTags[$path] ?? [];

            $needIndices = [];

            foreach ($documents as $i => $document) {
                if ($this->magicTagsFieldNeedsGeneration($document, $field, $path)) {
                    $needIndices[] = $i;
                }
            }

            if ($needIndices === []) {
                continue;
            }

            $sidecar = $sidecars[$path] ?? null;
            $canClassify = $field->isClassifyFirst()
                && $sidecar !== null
                && count($existing) >= $field->getMinTagsForClassification();

            $llmIndices = [];

            foreach ($needIndices as $idx) {
                if ($canClassify) {
                    $tags = $this->classifyViaSidecar($documents[$idx], $field, $path, $sidecar);

                    if ($tags !== []) {
                        $this->applyMagicTagsToDocument($documents[$idx], $path, $tags);

                        continue;
                    }
                }

                $llmIndices[] = $idx;
            }

            if ($llmIndices === []) {
                continue;
            }

            $chunks = array_chunk($llmIndices, $field->getBatchSize());

            foreach ($chunks as $chunkIndices) {
                if (count($chunkIndices) === 1) {
                    $idx = $chunkIndices[0];
                    $this->fillMagicTagsSingleDocument(
                        $documents[$idx],
                        $field,
                        $path,
                        $existing,
                    );
                } else {
                    $this->fillMagicTagsBatchChunk(
                        $documents,
                        $chunkIndices,
                        $field,
                        $path,
                        $existing,
                    );
                }
            }
        }

        return $documents;
    }

    /**
     * Classify a document's content against the sidecar tag registry using Sigmie semantic search.
     *
     * @return array<int, string>
     */
    protected function classifyViaSidecar(
        Document $document,
        MagicTagsFieldType $field,
        string $path,
        MagicTagsSidecarIndex $sidecar,
    ): array {
        $rawContent = dot($document->_source)->get($field->fromField());
        $content = $this->normalizeContentForMagicTags($rawContent);

        if ($content === '') {
            return [];
        }

        $maxTags = $field->getMaxTags();
        $minConfidence = $field->getClassifyConfidence();

        $hits = $sidecar->searchSimilarTags($content, $path, $maxTags * 2);

        $out = [];

        foreach ($hits as $hit) {
            if ($hit['score'] < $minConfidence) {
                break;
            }

            $tag = $hit['tag'];

            if ($tag !== '' && ! in_array($tag, $out, true)) {
                $out[] = $tag;

                if (count($out) >= $maxTags) {
                    break;
                }
            }
        }

        return $out;
    }

    /**
     * @param  array<int, string>  $tags
     * @param  array<int, string>  $existingTagStrings
     * @return array<int, string>
     */
    protected function deduplicateMagicTagStrings(
        array $tags,
        array $existingTagStrings,
        MagicTagsFieldType $field,
    ): array {
        if (! $field->shouldDeduplicateTags() || $field->getEmbeddingsProvider() === null || $tags === []) {
            return $tags;
        }

        $dims = $field->getEmbeddingDimensions();
        $threshold = $field->getSimilarityThreshold();

        $existingUnique = array_values(array_unique(array_filter($existingTagStrings, fn ($t): bool => is_string($t) && $t !== '')));

        $toEmbed = [...$tags, ...$existingUnique];

        $response = Embeddings::for($toEmbed)
            ->dimensions($dims)
            ->generate(provider: $field->getEmbeddingsProvider());

        $vectors = $response->embeddings;
        $tagCount = count($tags);
        $candidateVectors = array_slice($vectors, 0, $tagCount);
        $existingVectors = array_slice($vectors, $tagCount);

        $result = [];

        foreach ($tags as $i => $candidate) {
            $cVec = $candidateVectors[$i] ?? [];

            if ($cVec === []) {
                $result[] = $candidate;

                continue;
            }

            $bestTag = $candidate;
            $bestSim = $threshold;

            foreach ($existingUnique as $j => $existingTag) {
                $eVec = $existingVectors[$j] ?? [];

                if ($eVec === []) {
                    continue;
                }

                $sim = VectorMath::cosineSimilarity($cVec, $eVec);

                if ($sim > $bestSim) {
                    $bestSim = $sim;
                    $bestTag = $existingTag;
                }
            }

            $result[] = $bestTag;
        }

        return array_values(array_unique($result));
    }

    protected function magicTagsFieldNeedsGeneration(Document $document, MagicTagsFieldType $field, string $path): bool
    {
        $current = dot($document->_source)->get($path);

        if (is_array($current) && $current !== []) {
            return false;
        }

        if (is_string($current) && $current !== '') {
            return false;
        }

        $rawContent = dot($document->_source)->get($field->fromField());
        $content = $this->normalizeContentForMagicTags($rawContent);

        return $content !== '';
    }

    protected function fillMagicTagsSingleDocument(
        Document $document,
        MagicTagsFieldType $field,
        string $path,
        array $existing,
    ): void {
        $rawContent = dot($document->_source)->get($field->fromField());
        $content = $this->normalizeContentForMagicTags($rawContent);

        if ($content === '') {
            return;
        }

        $maxTags = $field->getMaxTags();

        $builder = $field->getUserPromptBuilder();
        $userPrompt = $builder !== null
            ? $builder($content, $existing)
            : $this->defaultMagicTagsUserPrompt($content, $existing);

        $agentClass = $field->getAgentClass();
        $agent = $this->makeSingleTaggingAgent($agentClass, $field);

        $response = $agent->prompt(
            $userPrompt,
            provider: $field->getProvider(),
        );

        $tags = $response['tags'] ?? [];

        if (! is_array($tags)) {
            return;
        }

        $tags = $this->normalizeMagicTagsList($tags, $maxTags);
        if ($field->shouldDeduplicateTags()) {
            $tags = $this->deduplicateMagicTagStrings($tags, $existing, $field);
        }
        $this->applyMagicTagsToDocument($document, $path, $tags);
    }

    /**
     * @param  array<int, Document>  $documents
     * @param  array<int, int>  $chunkIndices  Indices into $documents
     */
    protected function fillMagicTagsBatchChunk(
        array $documents,
        array $chunkIndices,
        MagicTagsFieldType $field,
        string $path,
        array $existing,
    ): void {
        $maxTags = $field->getMaxTags();
        $contents = [];

        foreach ($chunkIndices as $localPos => $docIndex) {
            $raw = dot($documents[$docIndex]->_source)->get($field->fromField());
            $contents[$localPos] = $this->normalizeContentForMagicTags($raw);
        }

        $count = count($chunkIndices);
        $blocks = [];

        foreach ($contents as $localPos => $text) {
            $blocks[] = "--- Document {$localPos} ---\n".$text;
        }

        $batchBuilder = $field->getBatchUserPromptBuilder();
        $documentTexts = array_values($contents);

        if ($batchBuilder !== null) {
            $userPrompt = $batchBuilder($count, $documentTexts, $existing);
        } else {
            $userParts = [];

            if ($existing !== []) {
                $userParts[] = 'Existing tags already in the index (reuse these when they fit):'."\n".implode(', ', $existing);
            }

            $userParts[] = "Tag each block below. The `results` array MUST have {$count} entries, one per document, in the same order.\n\n"
                .implode("\n\n", $blocks);

            $userPrompt = implode("\n\n", $userParts);
        }

        $batchAgentClass = $field->getBatchAgentClass();
        $agent = $this->makeBatchTaggingAgent($batchAgentClass, $field, $count);

        $response = $agent->prompt(
            $userPrompt,
            provider: $field->getProvider(),
        );

        $results = $response['results'] ?? [];

        if (! is_array($results)) {
            return;
        }

        foreach ($chunkIndices as $localPos => $docIndex) {
            $item = $results[$localPos] ?? null;
            $tags = is_array($item) ? ($item['tags'] ?? []) : [];

            if (! is_array($tags)) {
                $tags = [];
            }

            $tags = $this->normalizeMagicTagsList($tags, $maxTags);
            if ($field->shouldDeduplicateTags()) {
                $tags = $this->deduplicateMagicTagStrings($tags, $existing, $field);
            }
            $this->applyMagicTagsToDocument($documents[$docIndex], $path, $tags);
        }
    }

    /**
     * @param  array<int, string>  $existing
     */
    protected function defaultMagicTagsUserPrompt(string $content, array $existing): string
    {
        $userParts = [];

        if ($existing !== []) {
            $userParts[] = 'Existing tags already in the index (reuse these when they fit):'."\n".implode(', ', $existing);
        }

        $userParts[] = "Content to tag:\n\n".$content;

        return implode("\n\n", $userParts);
    }

    /**
     * @param  class-string<Agent>  $class
     */
    protected function makeSingleTaggingAgent(string $class, MagicTagsFieldType $field): Agent
    {
        $params = ['maxTags' => $field->getMaxTags()];
        if ($field->getPrompt() !== '') {
            $params['instructionsOverride'] = $field->getPrompt();
        }

        return app()->makeWith($class, $params);
    }

    /**
     * @param  class-string<Agent>  $class
     */
    protected function makeBatchTaggingAgent(string $class, MagicTagsFieldType $field, int $expectedResultCount): Agent
    {
        $params = [
            'maxTags' => $field->getMaxTags(),
            'expectedResultCount' => $expectedResultCount,
        ];
        if ($field->getPrompt() !== '') {
            $params['instructionsOverride'] = $field->getPrompt();
        }

        return app()->makeWith($class, $params);
    }

    /**
     * @param  array<int, mixed>  $tags
     * @return array<int, string>
     */
    protected function normalizeMagicTagsList(array $tags, int $maxTags): array
    {
        $tags = array_values(array_filter(
            array_map(static fn (mixed $t): string => is_string($t) ? trim($t) : '', $tags),
            fn (string $t): bool => $t !== ''
        ));

        return array_slice($tags, 0, $maxTags);
    }

    protected function applyMagicTagsToDocument(Document $document, string $path, array $tags): void
    {
        $dotHelper = dot($document->_source);
        $dotHelper->set($path, $tags);

        $document->_source = $dotHelper->all();
    }

    protected function normalizeContentForMagicTags(mixed $raw): string
    {
        if (is_string($raw)) {
            return trim($raw);
        }

        if (is_array($raw)) {
            $parts = array_map(
                fn ($v): string => is_scalar($v) ? (string) $v : '',
                $raw
            );

            return trim(implode("\n", array_filter($parts, fn (string $p): bool => $p !== '')));
        }

        return '';
    }
}
