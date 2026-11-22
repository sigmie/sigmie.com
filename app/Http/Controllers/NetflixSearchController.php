<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Indices\NetflixTitles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Sigmie\Document\Hit;

class NetflixSearchController extends Controller
{
    public const DEFAULT_QUERIES = [
        'secret agent undercover spy mission',
        'astronaut stranded in deep space',
        'corrupt politicians behind closed doors',
        'family road trip goes horribly wrong',
    ];

    public const DEFAULT_MODES = ['keyword', 'hybrid', 'vector'];

    public const DEFAULT_RETRIEVE = ['title', 'description', 'type', 'release_year', 'director'];

    public const DEFAULT_FILTERS = 'type:"TV Show" OR type:"Movie"';

    public const DEFAULT_SIZE = 5;

    private const CUSTOM_TTL = 600;

    public function search(Request $request)
    {
        $request->validate([
            'query' => 'required|string|max:500',
            'retrieve' => 'nullable|array',
            'filters' => 'nullable|string',
            'mode' => 'nullable|string|in:keyword,hybrid,vector',
            'size' => 'nullable|integer|min:1|max:10',
        ]);

        $query = trim((string) $request->input('query'));
        $retrieve = $request->input('retrieve', self::DEFAULT_RETRIEVE);
        $filters = (string) $request->input('filters', self::DEFAULT_FILTERS);
        $mode = (string) $request->input('mode', 'hybrid');
        $size = (int) $request->input('size', self::DEFAULT_SIZE);

        $cacheKey = 'netflix:search:v2:' . md5(json_encode([
            'q' => $query,
            'r' => $retrieve,
            'f' => $filters,
            'm' => $mode,
            's' => $size,
        ]));

        $isDefault = in_array($query, self::DEFAULT_QUERIES, true)
            && $retrieve === self::DEFAULT_RETRIEVE
            && in_array($filters, [self::DEFAULT_FILTERS, 'type:"Movie"', 'type:"TV Show"'], true)
            && $size === self::DEFAULT_SIZE;

        $payload = $isDefault
            ? Cache::rememberForever($cacheKey, fn () => $this->runSearch($query, $retrieve, $filters, $mode, $size))
            : Cache::remember($cacheKey, self::CUSTOM_TTL, fn () => $this->runSearch($query, $retrieve, $filters, $mode, $size));

        return response()->json($payload);
    }

    private function runSearch(string $query, array $retrieve, string $filters, string $mode, int $size): array
    {
        $netflixIndex = app(NetflixTitles::class);
        $blueprint = $netflixIndex->properties();

        $search = $netflixIndex
            ->newSearch()
            ->properties($blueprint)
            ->noResultsOnEmptySearch()
            ->queryString($query)
            ->filters($filters)
            ->facets('type')
            ->fields([
                'type',
                'title',
                'description',
            ])
            ->retrieve($retrieve)
            ->size($size);

        if ($mode === 'hybrid') {
            $search->semantic()->textScoreMultiplier(0.05);
        } elseif ($mode === 'vector') {
            $search->semantic()->disableKeywordSearch();
        }

        $response = $search->get();
        $rawDsl = $this->collapseVectors($search->makeSearch()->toRaw());

        $allFields = ['_id', 'type', 'title', 'director', 'cast', 'country', 'date_added', 'release_year', 'description'];

        $formattedResults = collect($response->hits())->map(function (Hit $doc) use ($retrieve, $allFields) {
            $result = [
                '_id' => $doc->_id,
                '_score' => round((float) ($doc->_score ?? 0), 3),
            ];

            foreach ($allFields as $field) {
                if ($field !== '_id' && in_array($field, $retrieve, true)) {
                    $result[$field] = $doc[$field] ?? '';
                }
            }

            return $result;
        })->values()->toArray();

        return [
            'query' => $query,
            'mode' => $mode,
            'filters' => $filters,
            'engine' => 'Elasticsearch',
            'results' => $formattedResults,
            'total' => $response->total(),
            'dsl' => $rawDsl,
        ];
    }

    private function collapseVectors(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value) && count($value) > 16 && is_numeric($value[0])) {
            return '[' . count($value) . '-dim float vector]';
        }

        foreach ($value as $key => $item) {
            $value[$key] = $this->collapseVectors($item);
        }

        return $value;
    }

    public function recommend(Request $request)
    {
        $request->validate([
            'seed_ids' => 'required|array|min:1',
            'seed_ids.*' => 'string',
            'mmr' => 'nullable|numeric|min:0|max:1',
        ]);

        $seedIds = $request->input('seed_ids');
        $mmr = $request->input('mmr', null);

        $netflixIndex = app(NetflixTitles::class);

        $recommend = $netflixIndex
            ->newRecommend()
            ->rrf(rrfRankConstant: 60, rankWindowSize: 10)
            ->topK(10)
            ->seedIds($seedIds)
            ->field(fieldName: 'title', weight: 1)
            ->field(fieldName: 'cast', weight: 1)
            ->field(fieldName: 'director', weight: 1);

        if ($mmr !== null) {
            $recommend->mmr($mmr);
        }

        $results = $recommend->hits();

        $formattedResults = collect($results)->map(fn (Hit $doc) => [
            '_id' => $doc->_id,
            'type' => $doc['type'] ?? '',
            'title' => $doc['title'] ?? '',
            'director' => $doc['director'] ?? '',
            'cast' => $doc['cast'] ?? '',
            'country' => $doc['country'] ?? '',
            'date_added' => $doc['date_added'] ?? '',
            'release_year' => $doc['release_year'] ?? '',
            'listed_in' => $doc['listed_in'] ?? '',
        ])->toArray();

        return response()->json([
            'results' => $formattedResults,
            'total' => count($formattedResults),
        ]);
    }
}
