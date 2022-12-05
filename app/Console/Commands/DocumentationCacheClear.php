<?php

namespace App\Console\Commands;

use App\Services\Documentation;
use Illuminate\Console\Command;
use League\CommonMark\MarkdownConverter;

class DocumentationCacheClear extends Command
{
    protected $signature = 'docs:cache:clear';

    protected $description = 'Clear Documentation Cache';

    public function handle()
    {
        $converter = app(MarkdownConverter::class);

        $documentation = new Documentation($converter);

        $documentation->cacheClear();
    }
}
