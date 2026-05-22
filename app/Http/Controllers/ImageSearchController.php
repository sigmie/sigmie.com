<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Indices\ImageData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Sigmie\Document\Hit;

class ImageSearchController extends Controller
{
    public const DEFAULT_QUERIES = [
        'Safari', 'Owl', 'Space', 'Snow', 'Love',
        'Balloons', 'Nature', 'Forest', 'Fox',
    ];

    private const CUSTOM_TTL = 600;

    public function searchByText(Request $request)
    {
        $request->validate([
            'query' => 'required|string|max:500'
        ]);

        $query = $request->input('query');

        $cacheKey = 'images:text:' . md5($query);
        $isDefault = in_array($query, self::DEFAULT_QUERIES, true);

        $payload = $isDefault
            ? Cache::rememberForever($cacheKey, fn () => $this->runTextSearch($query))
            : Cache::remember($cacheKey, self::CUSTOM_TTL, fn () => $this->runTextSearch($query));

        return response()->json($payload);
    }

    public function searchByImage(Request $request)
    {
        $request->validate([
            'image' => 'required|string'
        ]);

        $imageData = $request->input('image');

        $cacheKey = 'images:image:' . md5($imageData);

        $payload = Cache::remember(
            $cacheKey,
            self::CUSTOM_TTL,
            fn () => $this->runImageSearch($imageData),
        );

        return response()->json($payload);
    }

    private function runTextSearch(string $query): array
    {
        $imageIndex = app(ImageData::class);
        $blueprint = $imageIndex->properties();

        $search = $imageIndex
            ->newSearch()
            ->properties($blueprint)
            ->semantic();

        // Use default ids only if search query is empty
        if (empty(trim($query))) {
            $defaultIds = [
                '363.jpg', // woman
                '1047.jpg', // fox
                '853.jpg', // owl
                '591.jpg', // rose
                '476.jpg' // medusa
            ];

            $defaultFilters = implode(',', $defaultIds);
            $defaultFilters = "'{$defaultFilters}'";
            $search = $search->filters("_id:[{$defaultFilters}]");
        }

        $search = $search
            ->queryString($query)
            ->fields(['image'])
            ->size(4);

        $response = $search->get();

        $formattedResults = collect($response->hits())->map(fn (Hit $doc) => [
            '_id' => $doc->_id,
            'image' => $doc['image'] ?? '',
        ])->toArray();

        return [
            'results' => $formattedResults,
            'total' => count($formattedResults),
        ];
    }

    private function runImageSearch(string $imageData): array
    {
        $imageIndex = app(ImageData::class);
        $blueprint = $imageIndex->properties();

        $search = $imageIndex
            ->newSearch()
            ->properties($blueprint)
            ->semantic()
            ->queryImage($imageData)
            ->fields(['image'])
            ->size(4);

        $response = $search->get();

        $formattedResults = collect($response->hits())->map(fn (Hit $doc) => [
            '_id' => $doc->_id,
            'image' => $doc['image'] ?? '',
        ])->toArray();

        return [
            'results' => $formattedResults,
            'total' => count($formattedResults),
        ];
    }
}
