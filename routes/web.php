<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\NetflixSearchController;
use App\Http\Controllers\ImageSearchController;
use App\Http\Controllers\ResumesSearchController;
use App\Http\Controllers\AsosProductsController;
use App\Http\Controllers\DocsSearchController;
use App\Services\Blog;
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

Route::get('/', function () {
    return Inertia::render('Welcome', [
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
                    // [
                    //     'title' => 'Smart Discovery',
                    //     'href' => '/#recommendations',
                    // ],
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

// Chat API endpoint
Route::post('/api/chat', [ChatController::class, 'chat']);

// Search endpoints
Route::get('/search', function () {
    return Inertia::render('Search');
});
Route::post('/api/search/rag', [SearchController::class, 'rag']);
Route::post('/api/search/rag-stream', [SearchController::class, 'ragStream']);
Route::post('/api/search/standard', [SearchController::class, 'standard']);
Route::post('/api/search/clear-conversation', [SearchController::class, 'clearConversation']);

// Netflix search
Route::post('/api/search/netflix', [NetflixSearchController::class, 'search']);
Route::post('/api/recommendations/netflix', [NetflixSearchController::class, 'recommend']);

// ASOS Products
Route::post('/api/search/products', [AsosProductsController::class, 'search']);
Route::post('/api/recommendations/products', [AsosProductsController::class, 'recommend']);

// Image search
Route::post('/api/search/images/text', [ImageSearchController::class, 'searchByText']);
Route::post('/api/search/images/image', [ImageSearchController::class, 'searchByImage']);

// Resumes search
Route::get('/resumes', function () {
    return Inertia::render('Resumes');
});
Route::post('/api/search/resumes', [ResumesSearchController::class, 'search']);

// Documentation search
Route::post('/api/search/docs', [DocsSearchController::class, 'search']);

Route::get('/blog', function () {

    return Inertia::render('Blog', [
        'title' => 'Posts',
        'posts' => config("blog.navigation")
    ]);
});

Route::any('/blog/{endpoint?}', function ($endpoint, MarkdownConverter $converter) {

    $blog = new Blog($converter);

    $html = $blog->get($endpoint);

    $link = collect(config('blog.navigation.0.links'))
        ->filter(fn ($link)  => $link['href'] === "/blog/{$endpoint}")
        ->first();

    return Inertia::render('Post', [
        'navigation' => config("blog.navigation"),
        'html' => $html,
        'card' => config('app.url') . $link['card'],
        'title' => $link['title'],
        'href' => config('app.url') . $link['href'],
        'description' => $link['description']
    ]);
})
    ->where('endpoint', '.*');

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

Route::any('/docs/{version}/{endpoint?}', function ($version, $endpoint, MarkdownConverter $converter) {

    // Strip .md extension if present
    $endpoint = preg_replace('/\.md$/', '', $endpoint);

    $documentation = new Documentation($converter);

    // Build dynamic navigation from YAML frontmatter
    $navigation = $documentation->buildNavigation($version);

    $html = $documentation->get($version, $endpoint);

    // Get page metadata from YAML frontmatter
    $metadata = $documentation->getPageMetadata($version, $endpoint);

    $title = $metadata['title'] ?? ucfirst(str_replace('-', ' ', $endpoint ?? 'Documentation'));
    $description = $metadata['description'] ?? config('app.description');
    $card = config('app.url') . '/twitter-card.png';

    return Inertia::render('Document', [
        'navigation' => $navigation,
        'title' => $title,
        'html' => $html,
        'card' => $card,
        'href' => config('app.url') . "/docs/{$version}/{$endpoint}",
        'description' => $description,
    ]);
})
    ->where('endpoint', '.*');
