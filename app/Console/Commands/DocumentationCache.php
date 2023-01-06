<?php

namespace App\Console\Commands;

use App\Services\Blog;
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

        $blog = new Blog();

        $blog->cache();

        $this->output->success('sigmie.com was cached.');
    }
}
