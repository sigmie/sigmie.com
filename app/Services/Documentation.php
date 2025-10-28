<?php

declare(strict_types=1);

namespace App\Services;

use DOMDocument;
use League\CommonMark\MarkdownConverter;
use Symfony\Component\Yaml\Yaml;

class Documentation
{
    public function __construct(protected MarkdownConverter $converter)
    {
    }

    public function parseFrontmatter(string $markdown): array
    {
        if (!preg_match('/^---\s*\n(.*?)\n---\s*\n/s', $markdown, $matches)) {
            return ['frontmatter' => [], 'content' => $markdown];
        }

        $frontmatter = Yaml::parse($matches[1]) ?? [];
        $content = substr($markdown, strlen($matches[0]));

        return [
            'frontmatter' => $frontmatter,
            'content' => $content
        ];
    }

    public function getPageMetadata(string $version, string $slug): ?array
    {
        $path = base_path("docs/{$version}/{$slug}.md");

        if (!file_exists($path)) {
            return null;
        }

        $markdown = file_get_contents($path);
        $parsed = $this->parseFrontmatter($markdown);

        return [
            'slug' => $slug,
            'title' => $parsed['frontmatter']['title'] ?? ucfirst(str_replace('-', ' ', $slug)),
            'description' => $parsed['frontmatter']['short_description'] ?? null,
            'category' => $parsed['frontmatter']['category'] ?? 'Uncategorized',
            'order' => $parsed['frontmatter']['order'] ?? 999,
            'keywords' => $parsed['frontmatter']['keywords'] ?? [],
            'related_pages' => $parsed['frontmatter']['related_pages'] ?? [],
        ];
    }

    public function buildNavigation(string $version): array
    {
        $docsPath = base_path("docs/{$version}");

        if (!is_dir($docsPath)) {
            return [];
        }

        $files = glob("{$docsPath}/*.md");
        $pages = [];

        foreach ($files as $file) {
            $slug = basename($file, '.md');

            // Skip README files
            if (strtoupper($slug) === 'README') {
                continue;
            }

            $metadata = $this->getPageMetadata($version, $slug);

            if ($metadata) {
                $pages[] = $metadata;
            }
        }

        // Define category order
        $categoryOrder = [
            'Getting Started' => 1,
            'Core Concepts' => 2,
            'Features' => 3,
            'Text Analysis' => 4,
            'Utilities' => 5,
            'Advanced' => 6,
            'Configuration' => 7,
            'Integrations' => 8,
            'Reference' => 9,
        ];

        // Group by category and sort pages within each category
        $grouped = collect($pages)
            ->groupBy('category')
            ->map(fn($items) => $items->sortBy('order')->values())
            ->toArray();

        // Sort categories by predefined order
        $sortedCategories = collect($grouped)
            ->sortBy(fn($items, $category) => $categoryOrder[$category] ?? 999)
            ->toArray();

        // Convert to navigation format
        $navigation = [];

        foreach ($sortedCategories as $category => $items) {
            $links = [];

            foreach ($items as $item) {
                $links[] = [
                    'title' => $item['title'],
                    'href' => "/docs/{$version}/{$item['slug']}",
                    'description' => $item['description'],
                ];
            }

            $navigation[] = [
                'title' => $category,
                'links' => $links,
            ];
        }

        return $navigation;
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

        // Strip YAML frontmatter
        $parsed = $this->parseFrontmatter($markdown);
        $markdown = $parsed['content'];

        foreach ([
            'danger' => 'bg-red-900/20 border-red-800 text-red-400',
            'info' => 'bg-blue-900/20 border-blue-800 text-blue-400',
            'warning' => 'bg-yellow-900/20 border-yellow-800 text-yellow-400'
        ] as $value => $classes) {
            preg_match_all('/@' . $value . '((.|\n)*?)@end' . $value . '/', $markdown, $matches);

            $title = ucfirst($value);

            foreach ($matches[0] ?? [] as $index => $match) {
                $replacement = $this->converter->convert($matches[1][$index]);
                $markdown = str_replace($match, "<div class=\"callout {$value} p-4 mb-6 rounded-lg border\"><div class=\"font-semibold mb-2\">{$title}</div>{$replacement}</div>", $markdown);
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
