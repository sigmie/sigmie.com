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

    public function test_home_answers_llms_txt_when_the_client_prefers_markdown(): void
    {
        $response = $this->get('/', ['Accept' => 'text/markdown'])->assertOk();

        $this->assertStringContainsString('text/markdown', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('# Sigmie', $response->getContent());
    }
}
