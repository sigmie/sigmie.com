<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\Elasticsearch;

use Laravel\Ai\Enums\Lab;
use Sigmie\Document\Hit;
use Sigmie\Document\RerankedHit;
use Sigmie\Mappings\NewProperties;
use Sigmie\Search\NewSearch;
use Illuminate\Support\Collection;

/**
 * Knowledge chunks: semantic search on `content`, `topic` labels from the MagicTags index pipeline.
 *
 * @see \Sigmie\AgentTools\MagicTags\MagicTagsPackage (macro `magicTags` on {@see NewProperties})
 */
class AgentKnowledgeElasticsearchIndex extends AgentIndex
{
    public function name(): string
    {
        return (string) config('agent-tools.knowledge_index');
    }

    public function sourceKey(): string
    {
        return 'knowledge';
    }

    public function retrievalPlannerHint(): string
    {
        return 'Documentation and knowledge-base chunks (content, topics, optional meta).';
    }

    public function properties(): NewProperties
    {
        $p = new NewProperties;
        $p->keyword('source_id');
        $p->number('position')->long();
        $p->text('content')
            ->semantic($this->embeddingsDocApi(), 5, 384)
            ->searchApi($this->embeddingsQueryApi());

        // Some Sigmie versions require a non-null callable for object(); empty body = dynamic meta subfields.
        $p->object('meta', function (NewProperties $props): void {});

        $p->magicTags('topic', 'content')->provider(
            Lab::tryFrom((string) config('agent-tools.knowledge_magic_tags_provider', config('ai.default', 'openai'))) ?? Lab::OpenAI
        );

        return $p;
    }

    /**
     * @param  list<string>  $tags
     */
    public function buildSearchWithTags(string $query, int $size, array $tags = []): NewSearch
    {
        $q = trim($query);
        $lim = max(1, min(100, $size));
        $filters = $this->buildTagFilter($tags);

        if ($q !== '') {
            return $this->newSearch()
                ->semantic()
                ->properties($this->properties())
                ->filters($filters)
                ->queryString($q)
                ->fields(['content'])
                ->size($lim);
        }

        return $this->newSearch()
            ->semantic(false)
            ->properties($this->properties())
            ->filters($filters)
            ->queryString('')
            ->size($lim);
    }

    /**
     * @param  Collection<int, Hit|RerankedHit>  $hits
     */
    public function mapHits(Collection $hits): Collection
    {
        $rows = parent::mapHits($hits);

        $expand = (int) config('agent-tools.knowledge_expand_neighbors', 1);
        if ($expand <= 0) {
            return $rows;
        }

        $allIds = $rows->flatMap(function (Collection $row) use ($expand) {
            $sourceId = (string) $row->get('source_id', '');
            $position = (int) $row->get('position', 0);
            if ($sourceId === '') {
                return collect();
            }

            return collect(range(max(0, $position - $expand), $position + $expand))
                ->map(fn (int $p) => hash('sha256', $sourceId.'|'.$p));
        })->unique()->values();

        if ($allIds->isEmpty()) {
            return $rows;
        }

        $byId = $this->fetchByIds($allIds, 'position:asc')
            ->keyBy(fn (Collection $r) => (string) $r->get('_id'));

        return $rows->map(function (Collection $row) use ($expand, $byId) {
            $sourceId = (string) $row->get('source_id', '');
            $position = (int) $row->get('position', 0);
            if ($sourceId === '') {
                return $row;
            }

            $merged = collect(range(max(0, $position - $expand), $position + $expand))
                ->map(fn (int $p) => hash('sha256', $sourceId.'|'.$p))
                ->map(fn (string $id) => $byId->get($id))
                ->filter()
                ->map(fn (Collection $neighbor) => trim((string) $neighbor->get('content', '')))
                ->filter()
                ->implode("\n\n");

            $content = $merged !== '' ? $merged : (string) $row->get('content', '');

            return $row->put('content', $content);
        });
    }

    public function toText(array $source): string
    {
        foreach (['text', 'content', 'body'] as $k) {
            if (! empty($source[$k])) {
                return trim((string) $source[$k]);
            }
        }

        return trim(json_encode($source, JSON_UNESCAPED_UNICODE));
    }

    /**
     * @return list<string>
     */
    public function metaFieldNames(): array
    {
        return ['source_id', 'position', 'topic', 'meta'];
    }
}
