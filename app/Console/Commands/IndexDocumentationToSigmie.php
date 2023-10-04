<?php

namespace App\Console\Commands;

use App\Services\Documentation;
use Illuminate\Console\Command;
use League\CommonMark\MarkdownConverter;

class IndexDocumentationToSigmie extends Command
{
    protected $signature = 'docs:index';

    protected $description = 'Index Documentation';

    public function handle()
    {
        $converter = app(MarkdownConverter::class);

        $documentation = new Documentation($converter);

        $documentation->eachPage(function ($file) {

            

            dd($file);
        });
    }
}
