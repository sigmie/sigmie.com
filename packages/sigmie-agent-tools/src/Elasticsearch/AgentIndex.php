<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\Elasticsearch;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Sigmie\AgentTools\Contracts\RetrievalSource;
use Sigmie\AgentTools\Elasticsearch\Concerns\ExtractsTopicTags;
use Sigmie\AgentTools\Elasticsearch\Concerns\HasTopicTagFilter;
use Sigmie\AgentTools\Laravel\Jobs\GenerateTagsJob;
use Sigmie\AgentTools\MagicTags\MagicTagsCollectionHook;
use Sigmie\Document\AliveCollection;
use Sigmie\Document\Document;
use Sigmie\Document\Hit;
use Sigmie\Document\RerankedHit;
use Sigmie\Mappings\NewProperties;
use Sigmie\Search\NewSearch;
use Sigmie\Sigmie;
use Sigmie\SigmieIndex;

abstract class AgentIndex extends SigmieIndex implements RetrievalSource
{
    use ExtractsTopicTags;
    use HasTopicTagFilter;

    public function __construct()
    {
        parent::__construct(app(Sigmie::class));
    }

    abstract public function sourceKey(): string;

    abstract public function retrievalPlannerHint(): string;

    abstract public function properties(): NewProperties;

    public function embeddingsDocApi(): string
    {
        return (string) config('agent-tools.embeddings_doc_api');
    }

    public function embeddingsQueryApi(): string
    {
        return (string) config('agent-tools.embeddings_query_api');
    }

    public function magicTagIndex(): string
    {
        return (string) config('agent-tools.memory_history_magic_tag_index');
    }

    public function prepareSearch(string $query, int $limit, mixed $user = null): NewSearch
    {
        $q = trim($query);
        $lim = max(1, min(100, $limit));

        $search = $this->newSearch()
            ->semantic($q !== '')
            ->properties($this->properties())
            ->queryString($q)
            ->size($lim);

        return $this->buildSearch($search, $user);
    }

    /**
     * @param  Authenticatable|object{id: int|string}|null  $user
     */
    protected function buildSearch(NewSearch $search, mixed $user): NewSearch
    {
        return $search;
    }

    /**
     * Resolve a user id for `user_id` filters on memory and conversation indices.
     */
    protected function userIdForFilters(mixed $user): ?string
    {
        if ($user === null) {
            return null;
        }
        if ($user instanceof Authenticatable) {
            return (string) $user->getAuthIdentifier();
        }
        if (is_object($user) && property_exists($user, 'id')) {
            return (string) $user->id;
        }

        return null;
    }

    /**
     * @param  Collection<int, Hit|RerankedHit>  $hits
     * @return Collection<int, Collection<string, mixed>>
     */
    public function mapHits(Collection $hits): Collection
    {
        return $hits->map(function (Hit|RerankedHit $hit) {
            $row = collect($hit->_source)->put('_id', $hit->_id);
            if ($hit instanceof RerankedHit) {
                $row->put('_rerank_score', $hit->_rerank_score);
            } elseif (isset($hit->_score)) {
                $row->put('_score', $hit->_score);
            }

            return $row;
        });
    }

    public function toText(array $source): string
    {
        return json_encode($source, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return list<string>
     */
    public function metaFieldNames(): array
    {
        return [];
    }

    public function shouldRerank(): bool
    {
        return true;
    }

    public function search(string $query, int $limit, ?Authenticatable $user = null): Collection
    {
        $hits = $this->prepareSearch($query, $limit, $user)->get()->hits();

        return $this->mapHits(collect($hits));
    }

    public function merge(array $documents, bool $refresh = false): AliveCollection
    {
        return $this->sigmie()
            ->collect($this->name(), refresh: $refresh)
            ->populateEmbeddings()
            ->properties($this->properties())
            ->merge($documents);
    }

    public function generateAndApplyTags(string $documentId): array
    {
        $collection = $this->collect(refresh: true);
        $doc = $collection->get($documentId);
        if ($doc === null) {
            return [];
        }

        $properties = ($this->properties())();
        $doc = (new MagicTagsCollectionHook)->populateDocument($doc, $this->name(), $this->sigmie(), $properties);
        $tags = $this->topicTagsFromDocument($doc);
        $this->merge([$doc], true);

        return $tags;
    }

    public function indexDocument(Document $doc): void
    {
        MagicTagsCollectionHook::setAsyncIndexing(true);
        try {
            $this->merge([$doc], true);
        } finally {
            MagicTagsCollectionHook::setAsyncIndexing(false);
        }

        dispatch(
            (new GenerateTagsJob(static::class, $doc->_id))
                ->onQueue((string) config('agent-tools.magic_tags_queue', 'default'))
        );
    }

    public function getDocument(string $id): ?Collection
    {
        $doc = $this->collect(refresh: true)->get($id);
        if ($doc === null) {
            return null;
        }

        return collect($doc->_source)->put('_id', $id);
    }

    public function removeDocument(string $id): bool
    {
        return $this->collect(refresh: true)->remove($id);
    }

    public function countDocuments(string $filters = ''): int
    {
        return (int) $this->newSearch()
            ->semantic(false)
            ->properties($this->properties())
            ->filters($filters)
            ->queryString('')
            ->size(0)
            ->get()
            ->total();
    }

    /**
     * @param  Collection<int, string>|array<int, string>  $ids
     * @return Collection<int, Collection<string, mixed>>
     */
    public function fetchByIds(Collection|array $ids, ?string $sort = null): Collection
    {
        $ids = collect($ids)
            ->map(fn ($id) => trim((string) $id))
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        $search = $this->newSearch()
            ->semantic(false)
            ->properties($this->properties())
            ->filters('_id:['.$ids->implode(',').']')
            ->queryString('')
            ->size(min(10000, $ids->count()));

        if ($sort !== null) {
            $search->sort($sort);
        }

        return collect($search->get()->hits())
            ->map(fn (Hit $hit) => collect($hit->_source)->put('_id', $hit->_id));
    }
}
