<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Documentation;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

/**
 * Canonical pages only, each once. Redirects such as `/docs` stay out, and a
 * page's <lastmod> is its source's last commit date: it is left out when that
 * date is unknown (the home page has no single source).
 */
class SitemapController extends Controller
{
    public function __invoke(Documentation $documentation): Response
    {
        $baseUrl = rtrim((string) config('app.url'), '/');

        $urls = collect([
            ['loc' => '/', 'lastmod' => null, 'changefreq' => 'weekly', 'priority' => '1.0'],
            ...$this->docsPages($documentation),
        ])->unique('loc');

        return response($this->buildXml($baseUrl, $urls), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }

    private function docsPages(Documentation $documentation): array
    {
        return collect(config('docs.versions', []))
            ->pluck('value')
            ->flatMap(fn (string $version) => $this->markdownFiles(base_path("docs/{$version}"))
                ->map(fn (string $file) => basename($file, '.md'))
                ->map(fn (string $slug) => [
                    'loc' => "/docs/{$version}/{$slug}",
                    'lastmod' => $documentation->lastModified($version, $slug),
                    'changefreq' => 'weekly',
                    'priority' => '0.7',
                ]))
            ->all();
    }

    private function markdownFiles(string $directory): Collection
    {
        is_dir($directory) || throw new \RuntimeException("Sitemap source directory missing: {$directory}");

        return collect(glob("{$directory}/*.md") ?: [])
            ->reject(fn (string $file) => strtoupper(basename($file, '.md')) === 'README');
    }

    private function buildXml(string $baseUrl, Collection $urls): string
    {
        $entries = $urls
            ->map(fn (array $url) => "  <url>\n"
                . '    <loc>' . htmlspecialchars($baseUrl . $url['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</loc>\n"
                . ($url['lastmod'] === null ? '' : '    <lastmod>' . $url['lastmod'] . "</lastmod>\n")
                . "    <changefreq>{$url['changefreq']}</changefreq>\n"
                . "    <priority>{$url['priority']}</priority>\n"
                . '  </url>')
            ->implode("\n");

        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
            . "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n"
            . $entries
            . "\n</urlset>\n";
    }
}
