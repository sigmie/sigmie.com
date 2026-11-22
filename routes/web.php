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
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;

Route::get('/sitemap.xml', SitemapController::class);
Route::get('/llms.txt', LlmsController::class);
Route::get('/llms-full.txt', LlmsFullController::class);

Route::get('/', function () {
    $presetPath = resource_path('data/landing-presets.json');
    $presets = is_file($presetPath)
        ? json_decode((string) file_get_contents($presetPath), true)
        : [];

    return Inertia::render('Welcome', [
        'title' => 'Fluent Elasticsearch and OpenSearch for PHP',
        'description' => 'Sigmie is a fluent Elasticsearch and OpenSearch library for PHP and Laravel. Compare keyword, hybrid, and vector search live, inspect the generated DSL, and query CLIP images.',
        'href' => config('app.url') . '/',
        'card' => config('app.url') . '/og-image.png',
        'initialRelevance' => $presets['relevance'] ?? [],
        'initialClip' => $presets['clip'] ?? ['query' => 'Owl', 'results' => []],
        'initialMcp' => $presets['mcp'] ?? [],
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
// `/docs/search.md` keeps asking for markdown: it lands on `/docs/v2/search.md`,
// and `/docs/v2.md` on `/docs/v2/introduction.md`.
Route::get('/docs/{endpoint}', function ($endpoint) {
    $extension = str_ends_with($endpoint, '.md') ? '.md' : '';
    $endpoint = substr($endpoint, 0, strlen($endpoint) - strlen($extension));

    $versions = collect(config('docs.versions', []))->pluck('value')->toArray();

    if (in_array($endpoint, $versions, true)) {
        return redirect("/docs/{$endpoint}/introduction{$extension}");
    }

    $defaultVersion = collect(config('docs.versions', []))
        ->firstWhere('default', true);

    $version = $defaultVersion['value'] ?? 'v2';

    return redirect("/docs/{$version}/{$endpoint}{$extension}");
})->where('endpoint', '[^/]+');

// Serve page markdown for LLM/agent consumption (llms.txt spec convention).
Route::get('/docs/{version}/{slug}.md', function (string $version, string $slug, MarkdownConverter $converter) {
    $markdown = (new Documentation($converter))->agentPage($version, $slug);

    abort_if($markdown === null, 404);

    return response($markdown, 200, [
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

    $updatedAt = $documentation->lastModified($version, $endpoint);

    return Inertia::render('Document', [
        'navigation' => $navigation,
        'title' => $title,
        'pageHeading' => $heading,
        'html' => $html,
        'headings' => $documentation->headings($html),
        'card' => $card,
        'href' => config('app.url') . "/docs/{$version}/{$endpoint}",
        'description' => $description,
        'publishedAt' => $updatedAt,
        'updatedAt' => $updatedAt,
        'proficiency' => $metadata['proficiency'] ?? 'Beginner',
    ]);
})
    ->where('endpoint', '.*');
