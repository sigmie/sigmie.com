<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Documentation;
use Illuminate\Http\Response;
use League\CommonMark\MarkdownConverter;

class LlmsController extends Controller
{
    public function __invoke(MarkdownConverter $converter): Response
    {
        $baseUrl = rtrim((string) config('app.url'), '/');
        $defaultVersion = collect(config('docs.versions', []))->firstWhere('default', true)['value'] ?? 'v2';

        $documentation = new Documentation($converter);
        $navigation = $documentation->buildNavigation($defaultVersion);

        $body = "# Sigmie\n\n";
        $body .= "> A modern, developer-friendly Elasticsearch and OpenSearch library for PHP and Laravel. Fluent search, semantic and hybrid retrieval, AI-ready, no boilerplate.\n\n";

        foreach ($navigation as $section) {
            $body .= "## " . $section['title'] . "\n";
            foreach ($section['links'] as $link) {
                $line = "- [" . $link['title'] . "](" . $baseUrl . $link['href'] . ")";
                $link['description'] ?? null and $line .= ": " . $link['description'];
                $body .= $line . "\n";
            }
            $body .= "\n";
        }

        $body .= "## Blog\n";
        foreach (collect(config('blog.navigation.0.links', [])) as $post) {
            $body .= "- [" . $post['title'] . "](" . $baseUrl . $post['href'] . "): " . $post['description'] . "\n";
        }
        $body .= "\n";

        $body .= "## Optional\n";
        $body .= "- [MCP Server](" . $baseUrl . "/mcp): Streamable HTTP MCP endpoint for AI agents — exposes search_docs, read_doc, and list_docs tools\n";
        $body .= "- [Sitemap](" . $baseUrl . "/sitemap.xml): Machine-readable list of indexable URLs\n";

        return response($body, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
