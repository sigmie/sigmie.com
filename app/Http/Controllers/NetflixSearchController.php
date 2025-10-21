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
            'query' => 'required|string|max:500'
        ]);

        $query = $request->input('query');

        $netflixIndex = app(NetflixTitles::class);
        $blueprint = $netflixIndex->properties();

        $search = $netflixIndex
            ->newSearch()
            ->properties($blueprint)
            ->semantic()
            ->noResultsOnEmptySearch()
            ->disableKeywordSearch()
            ->queryString($query)
            ->filters('type:"TV Show" OR type:"Movie"')
            ->facets('type')
            ->fields(['type', 'title', 'director', 'cast', 'country', 'date_added', 'release_year'])
            ->retrieve(['type', 'title', 'director', 'cast', 'country', 'date_added', 'release_year'])
            ->size(20);

        $response = $search->get();

        $formattedResults = collect($response->hits())->map(fn (Hit $doc) => [
            '_id' => $doc->_id,
            'type' => $doc['type'] ?? '',
            'title' => $doc['title'] ?? '',
            'director' => $doc['director'] ?? '',
            'cast' => $doc['cast'] ?? '',
            'country' => $doc['country'] ?? '',
            'date_added' => $doc['date_added'] ?? '',
            'release_year' => $doc['release_year'] ?? '',
        ])->toArray();

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
            'total' => count($formattedResults)
        ]);
    }
}
