<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Indices\ImageData;
use Illuminate\Http\Request;
use Sigmie\Document\Hit;

class ImageSearchController extends Controller
{
    public function searchByText(Request $request)
    {
        $request->validate([
            'query' => 'required|string|max:500'
        ]);

        $query = $request->input('query');

        $imageIndex = app(ImageData::class);
        $blueprint = $imageIndex->properties();

        $search = $imageIndex
            ->newSearch()
            ->properties($blueprint)
            ->semantic()
            ->queryString($query)
            ->fields(['image'])
            ->size(12);

        $response = $search->get();

        $formattedResults = collect($response->hits())->map(fn (Hit $doc) => [
            '_id' => $doc->_id,
            'image' => $doc['image'] ?? '',
        ])->toArray();

        return response()->json([
            'results' => $formattedResults,
            'total' => count($formattedResults)
        ]);
    }

    public function searchByImage(Request $request)
    {
        $request->validate([
            'image' => 'required|string'
        ]);

        $imageData = $request->input('image');

        $imageIndex = app(ImageData::class);
        $blueprint = $imageIndex->properties();

        $search = $imageIndex
            ->newSearch()
            ->properties($blueprint)
            ->semantic()
            ->queryImage($imageData)
            ->fields(['image'])
            ->size(12);

        $response = $search->get();

        $formattedResults = collect($response->hits())->map(fn (Hit $doc) => [
            '_id' => $doc->_id,
            'image' => $doc['image'] ?? '',
        ])->toArray();

        return response()->json([
            'results' => $formattedResults,
            'total' => count($formattedResults)
        ]);
    }
}
