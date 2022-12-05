<?php

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

    return redirect('/v0/introduction');
    // return Inertia::render('Welcome', []);
});

Route::any('/{version}/{endpoint?}', function ($version, $endpoint, MarkdownConverter $converter) {

    $documentation = new Documentation($converter);

    $html = $documentation->get($version, $endpoint);

    return Inertia::render('Document', [
        'navigation' => config("docs.{$version}.navigation"),
        'title' => ucwords($endpoint),
        'html' => $html,
    ]);
})
    ->where('endpoint', '.*');
