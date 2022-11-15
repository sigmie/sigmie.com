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

    // $environment = Environment::createCommonMarkEnvironment([
    //     'html_input' => 'strip',
    //     'allow_unsafe_links' => false,
    // ]);

    $environment = new Environment();
    $environment->addExtension(new CommonMarkCoreExtension);
    $environment->addExtension(new TorchlightExtension);

    $converter = new MarkdownConverter($environment);

    $html = $converter->convert('```php $var = false; ```');

    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'html' => $html->getContent(),
    ]);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');
});
