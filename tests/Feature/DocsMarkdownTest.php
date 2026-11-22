<?php

namespace Tests\Feature;

use App\Services\Documentation;
use Tests\TestCase;

class DocsMarkdownTest extends TestCase
{
    public function test_markdown_route_refuses_paths_outside_the_docs_folder(): void
    {
        $this->get('/docs/%2E%2E/AGENTS.md')->assertNotFound();
        $this->get('/docs/%2E%2E/README.md')->assertNotFound();
        $this->get('/docs/v3/search.md')->assertNotFound();
    }

    public function test_html_route_refuses_paths_outside_the_docs_folder(): void
    {
        $this->get('/docs/%2E%2E/AGENTS')->assertNotFound();
        $this->get('/docs/v2/..%2F..%2FREADME')->assertNotFound();
    }

    public function test_markdown_route_serves_the_page_without_torchlight_annotations(): void
    {
        $response = $this->get('/docs/v2/search.md')->assertOk();

        $this->assertStringContainsString('text/markdown', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('# Search', $response->getContent());
        $this->assertStringNotContainsString('[tl!', $response->getContent());
        $this->assertStringContainsString('](' . config('app.url') . '/docs/v2/query.md)', $response->getContent());
    }

    public function test_docs_code_is_highlighted_with_the_site_palette(): void
    {
        $html = app(Documentation::class)->get('v2', 'search');

        $this->assertStringContainsString('style="color: var(--code-function);">newSearch</span>', $html);
        $this->assertStringContainsString('<span class="line highlight">', $html);
        $this->assertStringNotContainsString('[tl!', $html);
    }

    public function test_docs_page_links_open_pages_not_their_markdown(): void
    {
        $html = app(Documentation::class)->get('v2', 'search');

        $this->assertStringContainsString('href="facets"', $html);
        $this->assertStringContainsString('href="filter-parser#errors"', $html);
        $this->assertDoesNotMatchRegularExpression('/href="[a-z0-9-]+\.md/', $html);
    }

    public function test_docs_page_answers_markdown_when_the_client_prefers_it(): void
    {
        $response = $this->get('/docs/v2/search', ['Accept' => 'text/markdown, text/html;q=0.8'])->assertOk();

        $this->assertStringContainsString('text/markdown', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('# Search', $response->getContent());
        $this->assertContains('Accept', $response->headers->all('vary'));
    }

    public function test_headings_carry_stable_ids_and_keep_the_old_content_fragments(): void
    {
        $props = $this->get('/docs/v2/search')->assertOk()->viewData('page')['props'];

        $this->assertStringContainsString('<h2 id="filter"><a id="content-filter" href="#filter" class="heading-permalink"', $props['html']);
        $this->assertStringNotContainsString('¶', $props['html']);
        $this->assertContains(['id' => 'filter', 'text' => 'Filter', 'level' => 2], $props['headings']);
        $this->assertSame([], array_filter($props['headings'], fn (array $h) => str_starts_with($h['text'], '#')));
    }

    public function test_every_fragment_the_docs_link_lands_on_a_heading(): void
    {
        $documentation = app(Documentation::class);
        $missing = [];

        foreach (glob(base_path('docs/v2/*.md')) as $file) {
            preg_match_all('/\]\(([a-z0-9-]*)(?:\.md)?#([^)]+)\)/', file_get_contents($file), $links, PREG_SET_ORDER);

            foreach ($links as [, $page, $fragment]) {
                $page = $page ?: basename($file, '.md');
                $ids = array_column($documentation->headings($documentation->get('v2', $page)), 'id');

                in_array($fragment, $ids, true) || $missing[] = "{$page}#{$fragment}";
            }
        }

        $this->assertSame([], $missing);
    }

    public function test_repeated_headings_get_unique_ids(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'doc');
        file_put_contents($path, "## Setup\n\nOne.\n\n## Setup\n\nTwo.\n");

        $documentation = app(Documentation::class);
        $ids = array_column($documentation->headings($documentation->buildPage($path)), 'id');
        unlink($path);

        $this->assertSame(['setup', 'setup-1'], $ids);
    }

    public function test_unversioned_links_keep_their_representation(): void
    {
        $this->get('/docs/search')->assertRedirect('/docs/v2/search');
        $this->get('/docs/search.md')->assertRedirect('/docs/v2/search.md');
        $this->get('/docs/v2')->assertRedirect('/docs/v2/introduction');
        $this->get('/docs/v2.md')->assertRedirect('/docs/v2/introduction.md');
    }

    public function test_sitemap_lists_each_canonical_page_once_with_its_commit_date(): void
    {
        $xml = simplexml_load_string($this->get('/sitemap.xml')->assertOk()->getContent());
        $base = rtrim((string) config('app.url'), '/');
        $urls = [];
        foreach ($xml->url as $url) {
            $urls[] = [(string) $url->loc, isset($url->lastmod) ? (string) $url->lastmod : null];
        }
        $locs = array_column($urls, 0);
        $urls = collect(array_column($urls, 1, 0));

        $this->assertSame(array_unique($locs), $locs, 'a page is listed twice');
        $this->assertFalse($urls->has("{$base}/docs"), '/docs is a redirect, not a page');
        $this->assertNull($urls["{$base}/"], 'the home page has no known date');

        $committed = trim(shell_exec('git -C ' . escapeshellarg(base_path()) . ' log -1 --format=%cI -- docs/v2/search.md'));
        $this->assertSame($committed, $urls["{$base}/docs/v2/search"]);
        $this->assertNull(app(Documentation::class)->lastModified('v2', 'not-a-page'));
    }

    public function test_home_answers_llms_txt_when_the_client_prefers_markdown(): void
    {
        $response = $this->get('/', ['Accept' => 'text/markdown'])->assertOk();

        $this->assertStringContainsString('text/markdown', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('# Sigmie', $response->getContent());
    }
}
