<?php

use App\Http\Controllers\NetflixSearchController;
use App\Http\Controllers\ImageSearchController;
use App\Http\Controllers\AsosProductsController;
use App\Http\Controllers\DocsChatController;
use App\Http\Controllers\DocsSearchController;
use App\Http\Controllers\LlmsController;
use App\Http\Controllers\LlmsFullController;
use App\Http\Controllers\SitemapController;
use App\Services\Documentation;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use League\CommonMark\CommonMarkConverter;
use Torchlight\Block;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use Torchlight\Commonmark\V2\TorchlightExtension;

Route::get('/sitemap.xml', SitemapController::class);
Route::get('/llms.txt', LlmsController::class);
Route::get('/llms-full.txt', LlmsFullController::class);

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'title' => 'A modern Elasticsearch library for PHP',
        'description' => 'Sigmie is a modern, developer-friendly Elasticsearch and OpenSearch library for PHP and Laravel. Fluent search, semantic and hybrid retrieval, AI-ready, no boilerplate.',
        'href' => config('app.url') . '/',
        'card' => config('app.url') . '/og-image.png',
        'navigation' => [
            [
                'title' => 'Products',
                'links' => [
                    [
                        'title' => 'Semantic Search',
                        'href' => '/#semantic-search',
                    ],
                    [
                        'title' => 'Text to Image',
                        'href' => '/#image-search',
                    ],
                ]
            ],
            [
                'title' => 'Resources',
                'links' => [
                    [
                        'title' => 'About',
                        'href' => '/#about',
                    ],
                ]
            ]
        ]
    ]);
});

// Netflix search
Route::post('/api/search/netflix', [NetflixSearchController::class, 'search']);
Route::post('/api/recommendations/netflix', [NetflixSearchController::class, 'recommend']);

// ASOS Products
Route::post('/api/search/products', [AsosProductsController::class, 'search']);
Route::post('/api/recommendations/products', [AsosProductsController::class, 'recommend']);

// Image search
Route::post('/api/search/images/text', [ImageSearchController::class, 'searchByText']);
Route::post('/api/search/images/image', [ImageSearchController::class, 'searchByImage']);

// Documentation search
Route::post('/api/search/docs', [DocsSearchController::class, 'search']);

// Documentation chat agent (Claude Haiku + Sigmie RAG)
// Per-IP throttle; a global daily budget is enforced inside the controller.
Route::post('/api/agent/chat', DocsChatController::class)
    ->middleware(['throttle:agent-chat', \App\Http\Middleware\DisableResponseBuffering::class]);
Route::post('/api/agent/clear', [DocsChatController::class, 'clear'])
    ->middleware('throttle:30,1');

// Redirect /docs to the default version
Route::get('/docs', function () {
    $defaultVersion = collect(config('docs.versions', []))
        ->firstWhere('default', true);

    $version = $defaultVersion['value'] ?? 'v2';

    return redirect("/docs/{$version}/introduction");
});

// Catch docs links without version and redirect to default version
Route::get('/docs/{endpoint}', function ($endpoint) {
    // Strip .md extension if present
    $endpoint = preg_replace('/\.md$/', '', $endpoint);

    // Check if this is actually a version by looking at available versions
    $versions = collect(config('docs.versions', []))->pluck('value')->toArray();

    if (in_array($endpoint, $versions)) {
        // This is a version, redirect to its introduction page
        return redirect("/docs/{$endpoint}/introduction");
    }

    // This is an endpoint without version, redirect to default version
    $defaultVersion = collect(config('docs.versions', []))
        ->firstWhere('default', true);

    $version = $defaultVersion['value'] ?? 'v2';

    return redirect("/docs/{$version}/{$endpoint}");
})->where('endpoint', '[^/]+');

// Serve raw markdown for LLM/agent consumption (llms.txt spec convention).
Route::get('/docs/{version}/{slug}.md', function (string $version, string $slug) {
    $path = base_path("docs/{$version}/{$slug}.md");

    abort_unless(file_exists($path), 404);

    return response(file_get_contents($path), 200, [
        'Content-Type' => 'text/markdown; charset=UTF-8',
        'Cache-Control' => 'public, max-age=3600',
    ]);
})->where(['version' => '[^/]+', 'slug' => '[^/.]+']);

Route::any('/docs/{version}/{endpoint?}', function ($version, $endpoint, MarkdownConverter $converter) {

    // Strip .md extension if present
    $endpoint = preg_replace('/\.md$/', '', $endpoint);

    $documentation = new Documentation($converter);

    // Build dynamic navigation from YAML frontmatter
    $navigation = $documentation->buildNavigation($version);

    $html = $documentation->get($version, $endpoint);

    // Get page metadata from YAML frontmatter
    $metadata = $documentation->getPageMetadata($version, $endpoint);

    $heading = $metadata['title'] ?? ucfirst(str_replace('-', ' ', $endpoint ?? 'Documentation'));
    $title = "{$heading} — Sigmie Docs for PHP";
    $description = $metadata['description'] ?? config('app.description');
    $card = config('app.url') . '/og-image.png';

    $sourcePath = base_path("docs/{$version}/{$endpoint}.md");
    $updatedAt = file_exists($sourcePath) ? date('c', (int) filemtime($sourcePath)) : null;

    return Inertia::render('Document', [
        'navigation' => $navigation,
        'title' => $title,
        'pageHeading' => $heading,
        'html' => $html,
        'card' => $card,
        'href' => config('app.url') . "/docs/{$version}/{$endpoint}",
        'description' => $description,
        'publishedAt' => $updatedAt,
        'updatedAt' => $updatedAt,
        'proficiency' => $metadata['proficiency'] ?? 'Beginner',
    ]);
})
    ->where('endpoint', '.*');
