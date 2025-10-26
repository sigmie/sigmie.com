<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Indices\Docs;
use Illuminate\Http\Request;
use Sigmie\Document\Hit;

class DocsSearchController extends Controller
{
    public function search(Request $request)
    {
        $request->validate([
            'query' => 'required|string|max:500',
        ]);

        $query = $request->input('query');

        $docsIndex = app(Docs::class);
        $blueprint = $docsIndex->properties();

        $search = $docsIndex
            ->newSearch()
            ->properties($blueprint)
            ->semantic()
            ->noResultsOnEmptySearch()
            ->queryString($query)
            ->fields([
                'title',
                'content',
                'headings'
            ])
            ->retrieve([
                'title',
                'version',
                'page',
                'url',
                'content'
            ])
            ->size(10);

        $results = $search->hits();

        $formattedResults = collect($results)->map(fn(Hit $doc) => [
            '_id' => $doc->_id,
            'title' => $doc['title'] ?? '',
            'version' => $doc['version'] ?? '',
            'page' => $doc['page'] ?? '',
            'url' => $doc['url'] ?? '',
            'content' => $doc['content'] ?? '',
        ])->toArray();

        return response()->json([
            'results' => $formattedResults,
            'total' => count($formattedResults)
        ]);
    }
}
