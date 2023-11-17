<?php

namespace App\Services;

use DOMDocument;
use League\CommonMark\MarkdownConverter;

class Documentation
{
    public function __construct(protected MarkdownConverter $converter)
    {
    }

    public function cacheClear()
    {
        $path = storage_path('docs');

        array_map('unlink', glob("{$path}/**/*.html"));

        array_map('rmdir', glob("{$path}/*", GLOB_ONLYDIR));
    }

    public function eachPage(callable $callback)
    {
        $docsPath = base_path("docs");

        $filesSubdir = glob("{$docsPath}/**/*.{md}", GLOB_BRACE);

        $filesDir = glob("{$docsPath}/*.{md}", GLOB_BRACE);

        $files = [...$filesSubdir, ...$filesDir];

        foreach ($files as $file) {
            $callback($file);
        }
    }

    public function cache()
    {
        $this->eachPage(function ($file) {

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
        });
    }

    public function buildPage(string $path)
    {
        abort_if(!file_exists($path), 404);

        $markdown = file_get_contents($path);

        foreach ([
            'danger' => 'text-red-500 font-medium',
            'info' => 'text-blue-500 font-medium',
            'warning' => 'text-yellow-400 font-medium'
        ] as $value => $classes) {
            preg_match_all('/@' . $value . '((.|\n)*?)@end' . $value . '/', $markdown, $matches);

            $title = ucfirst($value);

            foreach ($matches[0] ?? [] as $index => $match) {
                $replacement = $this->converter->convert($matches[1][$index]);
                $markdown = str_replace($match, "<div class=\"p-4 mb-4 text-sm leading-relaxed rounded-lg bg-gray-50  prose border prose-xl min-w-full\"><div class=\"{$classes}\">{$title}</div>{$replacement}</div>", $markdown);
            }
        }

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


    public function get(string $version, string $endpoint): string
    {
        $path = storage_path("docs/{$version}/{$endpoint}.html");

        if (file_exists($path)) {
            return file_get_contents($path);
        }

        $path = base_path("docs/{$version}/{$endpoint}.md");

        return $this->buildPage($path);
    }
}
