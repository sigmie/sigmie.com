<?php

namespace App\Console\Commands;

use App\Services\Documentation;
use Illuminate\Console\Command;
use League\CommonMark\MarkdownConverter;

class DocumentationCache extends Command
{
    protected $signature = 'docs:cache';

    protected $description = 'Cache Documentation';

    public function handle()
    {
        $converter = app(MarkdownConverter::class);

        $documentation = new Documentation($converter);

        $documentation->cache();
    }
}
