<?php

use Illuminate\Foundation\Application;
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

    return Inertia::render('Welcome', []);
});

Route::any('/{endpoint?}', function ($endpoint, MarkdownConverter $converter) {

    $path = base_path("docs/{$endpoint}.md");

    abort_if(!file_exists($path), 404);

    $markdown = file_get_contents($path);

    $line = preg_split('#\r?\n#', $markdown, 2)[0];

    $title = str_replace('# ', '', $line);

    $markdown = preg_replace('/^.+\n/', '', $markdown);

    $html = $converter->convert($markdown);

    $html = str_replace('¶', '#', $html->getContent());

    return Inertia::render('Document', [
        'navigation' => config('docs.navigation'),
        'title' => $title,
        'html' => $html,
    ]);
})
    ->where('endpoint', '.*');
