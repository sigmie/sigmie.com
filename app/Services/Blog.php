<?php

namespace App\Services;

use DOMDocument;
use Illuminate\Support\ServiceProvider;
use League\CommonMark\CommonMarkConverter;
use Torchlight\Block;
use League\CommonMark\Environment\Environment;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\SmartPunct\SmartPunctExtension;
use Torchlight\Commonmark\V2\TorchlightExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Extension\TableOfContents\TableOfContentsExtension;


class Blog
{
    public function __construct()
    {
        $environment = new Environment();
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new TableExtension);
        $environment->addExtension(new HeadingPermalinkExtension);
        $environment->addExtension(new SmartPunctExtension);
        // $environment->addExtension(new TableOfContentsExtension);
        $environment->addExtension(new TorchlightExtension);

        $this->converter = new MarkdownConverter($environment);
    }


    public function cacheClear()
    {
        $path = storage_path('blog');

        array_map('unlink', glob("{$path}/**/*.html"));

        array_map('rmdir', glob("{$path}/*", GLOB_ONLYDIR));

        rmdir($path);
    }

    public function cache()
    {
        $docsPath = base_path("blog/");

        $filesSubdir = glob("{$docsPath}/**/*.{md}", GLOB_BRACE);

        $filesDir = glob("{$docsPath}/*.{md}", GLOB_BRACE);

        $files = [...$filesSubdir, ...$filesDir];

        foreach ($files as $file) {

            $html = $this->buildPage($file);

            $file = str_replace(base_path(), '', $file);

            $path = storage_path($file);

            $folder = dirname($path);
            if (!is_dir($folder)) {
                mkdir(dirname($path), 0755, true);
            }

            $path = str_replace('.md', '.html', $path);

            touch($path);

            file_put_contents($path, $html);
        }

        return;
    }

    public function buildPage(string $path)
    {
        abort_if(!file_exists($path), 404);

        $markdown = file_get_contents($path);

        $html = $this->converter->convert($markdown);

        $html = str_replace('¶', '#', $html->getContent());

        return $html;
    }


    private function getTextBetweenTags(string $string, string $tagname)
    {
        $d = new DOMDocument();
        $d->loadHTML($string);
        $return = array();
        foreach ($d->getElementsByTagName($tagname) as $item) {
            $return[] = $item->textContent;
        }
        return $return;
    }


    public function get(string $endpoint): string
    {
        $path = storage_path("blog/{$endpoint}.html");

        if (file_exists($path)) {
            return file_get_contents($path);
        }

        $path = base_path("blog/{$endpoint}.md");

        return $this->buildPage($path);
    }
}
