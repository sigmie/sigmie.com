<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Indices\Docs;
use App\Services\Documentation;
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

        // The index holds the page slug; the dialog shows the page's own title.
        $documentation = app(Documentation::class);
        $pageTitles = [];

        $formattedResults = collect($results)->map(function (Hit $doc) use ($documentation, &$pageTitles) {
            $page = $doc['page'] ?? '';
            $pageTitles[$page] ??= $documentation->getPageMetadata($doc['version'] ?? 'v2', $page)['title'] ?? '';

            return [
                '_id' => $doc->_id,
                'title' => $doc['title'] ?? '',
                'version' => $doc['version'] ?? '',
                'page' => $page,
                'page_title' => $pageTitles[$page],
                'url' => $doc['url'] ?? '',
                'content' => $doc['content'] ?? '',
            ];
        })->toArray();

        return response()->json([
            'results' => $formattedResults,
            'total' => count($formattedResults)
        ]);
    }
}
