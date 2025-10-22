<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Indices\AsosProducts;
use Illuminate\Http\Request;
use Sigmie\Document\Hit;

class AsosProductsController extends Controller
{
    public function search(Request $request)
    {
        $request->validate([
            'query' => 'required|string|max:500'
        ]);

        $query = $request->input('query');

        $asosIndex = app(AsosProducts::class);
        $blueprint = $asosIndex->properties();

        ray($query);

        $search = $asosIndex
            ->newSearch()
            ->properties($blueprint)
            ->semantic()
            ->noResultsOnEmptySearch()
            // ->disableKeywordSearch()
            ->queryString($query)
            // ->facets('category', 'color', 'size')
            // ->fields(['name', 'category', 'price', 'color', 'size', 'description', 'images', 'sku'])
            // ->retrieve(['name', 'category', 'price', 'color', 'size', 'description', 'images', 'sku'])
            ->size(20);

        $response = $search->get();

        $formattedResults = collect($response->hits())->map(fn (Hit $doc) => [
            '_id' => $doc->_id,
            'name' => $doc['name'] ?? '',
            'category' => $doc['category'] ?? '',
            'price' => $doc['price'] ?? 0,
            'color' => $doc['color'] ?? '',
            'size' => $doc['size'] ?? '',
            'description' => $doc['description'] ?? '',
            'images' => $doc['images'] ?? [],
            'sku' => $doc['sku'] ?? '',
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

        $asosIndex = app(AsosProducts::class);

        ray($seedIds);

        $recommend = $asosIndex
            ->newRecommend()
            ->rrf(rrfRankConstant: 60, rankWindowSize: 10)
            ->topK(4)
            ->seedIds($seedIds)
            ->field(fieldName: 'color', weight: 3);
            // ->field(fieldName: 'description', weight: 2);
            // ->field(fieldName: 'category', weight: 0.5);

        // if ($mmr !== null) {
            $recommend->mmr($mmr);
        // }

        $results = $recommend->hits();

        $formattedResults = collect($results)->map(fn (Hit $doc) => [
            '_id' => $doc->_id,
            'name' => $doc['name'] ?? '',
            'category' => $doc['category'] ?? '',
            'price' => $doc['price'] ?? 0,
            'color' => $doc['color'] ?? '',
            'size' => $doc['size'] ?? '',
            'description' => $doc['description'] ?? '',
            'images' => $doc['images'] ?? [],
            'sku' => $doc['sku'] ?? '',
        ])->toArray();

        return response()->json([
            'results' => $formattedResults,
            'total' => count($formattedResults)
        ]);
    }
}
