<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Indices\NetflixTitles;
use Illuminate\Http\Request;
use Sigmie\Document\Hit;

class NetflixSearchController extends Controller
{
    public function search(Request $request)
    {
        $request->validate([
            'query' => 'required|string|max:500',
            'retrieve' => 'nullable|array',
            'filters' => 'nullable|string'
        ]);

        $query = $request->input('query');
        $retrieve = $request->input('retrieve', ['title', 'description']);
        $filters = $request->input('filters', 'type:"TV Show" OR type:"Movie"');

        $netflixIndex = app(NetflixTitles::class);
        $blueprint = $netflixIndex->properties();

        $search = $netflixIndex
            ->newSearch()
            ->properties($blueprint)
            ->semantic()
            ->noResultsOnEmptySearch()
            ->disableKeywordSearch()
            ->queryString($query)
            ->filters($filters)
            ->facets('type')
            ->fields([
                'type',
                'title',
                'description'
            ])
            ->retrieve($retrieve)
            ->size(4);

        $results = $search->hits();

        $allFields = ['_id', 'type', 'title', 'director', 'cast', 'country', 'date_added', 'release_year', 'description'];

        $formattedResults = collect($results)->map(function(Hit $doc) use ($retrieve, $allFields) {
            $result = ['_id' => $doc->_id];

            foreach ($allFields as $field) {
                if ($field !== '_id' && in_array($field, $retrieve)) {
                    $result[$field] = $doc[$field] ?? '';
                }
            }

            return $result;
        })->toArray();

        return response()->json([
            'results' => $formattedResults,
            'total' => count($formattedResults)
        ]);
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

        $formattedResults = collect($results)->map(fn(Hit $doc) => [
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
            'total' => count($formattedResults)
        ]);
    }
}
