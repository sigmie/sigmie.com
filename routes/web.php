<?php

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

    return redirect('/docs/v0/introduction');
    // return Inertia::render('Welcome', []);
});

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

Route::any('/docs/{version}/{endpoint?}', function ($version, $endpoint, MarkdownConverter $converter) {

    $documentation = new Documentation($converter);

    $html = $documentation->get($version, $endpoint);

    $link = collect(config("docs.{$version}.navigation"))
        ->flatten(2)
        ->filter(fn ($link)  => isset($link['href']))
        ->filter(fn ($link)  => $link['href'] === "/docs/{$version}/{$endpoint}")
        ->first();

    return Inertia::render('Document', [
        'navigation' => config("docs.{$version}.navigation"),
        'title' => $link['title'],
        'html' => $html,
    ]);
})
    ->where('endpoint', '.*');
