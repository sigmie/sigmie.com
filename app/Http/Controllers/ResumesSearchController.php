<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Indices\Resumes;
use Illuminate\Http\Request;
use Sigmie\Document\Hit;

class ResumesSearchController extends Controller
{
    public function search(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:500',
            'description' => 'required|string|max:2000',
        ]);

        $title = $request->input('title');
        $description = $request->input('description');

        $resumesIndex = app(Resumes::class);
        $blueprint = $resumesIndex->properties();

        $search = $resumesIndex
            ->newSearch()
            ->properties($blueprint)
            ->semantic()
            ->noResultsOnEmptySearch()
            ->disableKeywordSearch()
            ->queryString($title, weight: 2.0)
            ->queryString($description, weight: 1.0)
            ->fields(['category', 'resume_html', 'resume_str'])
            ->retrieve(['category', 'resume_html', 'resume_str'])
            ->size(20);

        $response = $search->get();

        $formattedResults = collect($response->hits())->map(fn (Hit $doc) => [
            '_id' => $doc->_id,
            'category' => $doc['category'] ?? '',
            'resume_html' => $doc['resume_html'] ?? '',
        ])->toArray();

        return response()->json([
            'results' => $formattedResults,
            'total' => count($formattedResults)
        ]);
    }
}
