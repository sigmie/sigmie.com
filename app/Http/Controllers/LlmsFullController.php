<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Documentation;
use Illuminate\Http\Response;
use League\CommonMark\MarkdownConverter;

class LlmsFullController extends Controller
{
    public function __invoke(MarkdownConverter $converter): Response
    {
        $baseUrl = rtrim((string) config('app.url'), '/');
        $defaultVersion = collect(config('docs.versions', []))->firstWhere('default', true)['value'] ?? 'v2';

        $documentation = new Documentation($converter);
        $navigation = $documentation->buildNavigation($defaultVersion);

        $body = "# Sigmie — Full Documentation\n\n";
        $body .= "> A modern, developer-friendly Elasticsearch and OpenSearch library for PHP and Laravel. Fluent search, semantic and hybrid retrieval, AI-ready, no boilerplate.\n\n";
        $body .= "This file concatenates the full Sigmie documentation for one-shot LLM ingestion. Individual pages live under /docs/{$defaultVersion}/{slug}.md.\n\n";

        foreach ($navigation as $section) {
            foreach ($section['links'] as $link) {
                $slug = basename($link['href']);
                $path = base_path("docs/{$defaultVersion}/{$slug}.md");

                file_exists($path) || throw new \RuntimeException("Doc page missing: {$path}");

                $markdown = file_get_contents($path);
                $parsed = $documentation->parseFrontmatter($markdown);

                $body .= "\n\n---\n\n";
                $body .= "<!-- source: {$baseUrl}/docs/{$defaultVersion}/{$slug} -->\n\n";
                $body .= trim($parsed['content']) . "\n";
            }
        }

        return response($body, 200, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
