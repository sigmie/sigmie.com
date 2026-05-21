<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $baseUrl = rtrim((string) config('app.url'), '/');

        $urls = [
            ...$this->staticPages(),
            ...$this->docsPages(),
            ...$this->blogPages(),
        ];

        return response($this->buildXml($baseUrl, $urls), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }

    private function staticPages(): array
    {
        $today = date('Y-m-d');

        return [
            ['loc' => '/',        'lastmod' => $today, 'changefreq' => 'weekly',  'priority' => '1.0'],
            ['loc' => '/docs',    'lastmod' => $today, 'changefreq' => 'weekly',  'priority' => '0.9'],
            ['loc' => '/blog',    'lastmod' => $today, 'changefreq' => 'weekly',  'priority' => '0.8'],
            ['loc' => '/search',  'lastmod' => $today, 'changefreq' => 'monthly', 'priority' => '0.6'],
            ['loc' => '/resumes', 'lastmod' => $today, 'changefreq' => 'monthly', 'priority' => '0.5'],
        ];
    }

    private function docsPages(): array
    {
        return collect(config('docs.versions', []))
            ->pluck('value')
            ->flatMap(fn (string $version) => $this->markdownFiles(base_path("docs/{$version}"))
                ->map(fn (string $file) => [
                    'loc' => "/docs/{$version}/" . basename($file, '.md'),
                    'lastmod' => date('Y-m-d', (int) filemtime($file)),
                    'changefreq' => 'weekly',
                    'priority' => '0.7',
                ]))
            ->all();
    }

    private function blogPages(): array
    {
        return $this->markdownFiles(base_path('blog'))
            ->map(fn (string $file) => [
                'loc' => '/blog/' . basename($file, '.md'),
                'lastmod' => date('Y-m-d', (int) filemtime($file)),
                'changefreq' => 'monthly',
                'priority' => '0.6',
            ])
            ->all();
    }

    private function markdownFiles(string $directory): Collection
    {
        is_dir($directory) || throw new \RuntimeException("Sitemap source directory missing: {$directory}");

        return collect(glob("{$directory}/*.md") ?: [])
            ->reject(fn (string $file) => strtoupper(basename($file, '.md')) === 'README');
    }

    private function buildXml(string $baseUrl, array $urls): string
    {
        $entries = collect($urls)
            ->map(fn (array $url) => sprintf(
                "  <url>\n    <loc>%s%s</loc>\n    <lastmod>%s</lastmod>\n    <changefreq>%s</changefreq>\n    <priority>%s</priority>\n  </url>",
                $baseUrl,
                htmlspecialchars($url['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                $url['lastmod'],
                $url['changefreq'],
                $url['priority'],
            ))
            ->implode("\n");

        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
            . "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n"
            . $entries
            . "\n</urlset>\n";
    }
}
