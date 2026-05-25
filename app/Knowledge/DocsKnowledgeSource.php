<?php

declare(strict_types=1);

namespace App\Knowledge;

use App\Services\Documentation;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use Masterminds\HTML5;
use Sigmie\AgentTools\Knowledge\KnowledgeDocument;
use Sigmie\AgentTools\Knowledge\KnowledgeSource;

/**
 * Yields one KnowledgeDocument per h2/h3 section across all docs/v*\/*.md.
 * Mirrors the chunking strategy of {@see \App\Console\Commands\IndexDocs}
 * so the agent's knowledge index and the on-site `docs` search index stay
 * conceptually aligned.
 */
class DocsKnowledgeSource implements KnowledgeSource
{
    public function __construct(
        private readonly Documentation $documentation,
    ) {}

    public function documents(): iterable
    {
        $converter = $this->markdownConverter();
        $files = glob(base_path('docs/*/*.md'));

        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            $relative = str_replace(base_path('docs').'/', '', $file);
            [$version, $filename] = explode('/', $relative, 2) + [null, null];

            if ($version === null || $filename === null) {
                continue;
            }

            $page = preg_replace('/\.md$/', '', $filename);

            if (strtoupper($page) === 'README') {
                continue;
            }

            $raw = file_get_contents($file);
            if ($raw === false) {
                continue;
            }

            $parsed = $this->documentation->parseFrontmatter($raw);
            $frontmatter = $parsed['frontmatter'];
            $html = (string) $converter->convert($parsed['content']);

            foreach ($this->splitSections($html) as $i => $section) {
                $headingId = $section['heading_id'];
                $url = "/docs/{$version}/{$page}".($headingId !== '' ? '#'.$headingId : '');
                $pageTitle = $frontmatter['title'] ?? ucfirst(str_replace('-', ' ', $page));

                yield new KnowledgeDocument(
                    content: $section['heading']."\n\n".$section['content'],
                    sourceId: "docs:{$version}:{$page}",
                    meta: [
                        'title' => $section['heading'],
                        'page' => $page,
                        'page_title' => $pageTitle,
                        'version' => $version,
                        'url' => $url,
                        'section_index' => $i,
                    ],
                );
            }
        }
    }

    private function markdownConverter(): MarkdownConverter
    {
        $env = new Environment;
        $env->addExtension(new CommonMarkCoreExtension);

        return new MarkdownConverter($env);
    }

    /**
     * @return list<array{heading: string, heading_id: string, content: string}>
     */
    private function splitSections(string $html): array
    {
        $html5 = new HTML5(['encode_entities' => true]);
        $dom = $html5->loadHTML('<html><body>'.$html.'</body></html>');
        $body = $dom->getElementsByTagName('body')->item(0);

        if ($body === null || ! $body->hasChildNodes()) {
            return [];
        }

        $sections = [];
        $current = null;

        foreach ($body->childNodes as $node) {
            if ($node->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            if (in_array($node->nodeName, ['h2', 'h3'], true)) {
                if ($current !== null && trim($current['content']) !== '') {
                    $sections[] = $current;
                }

                $heading = (string) $node->nodeValue;
                $headingId = (string) preg_replace('/\s+/', '-', (string) preg_replace('/[^\w\s-]/', '', strtolower($heading)));

                $current = [
                    'heading' => $heading,
                    'heading_id' => $headingId,
                    'content' => '',
                ];

                continue;
            }

            $text = trim((string) $node->nodeValue);
            if ($text === '' || $current === null) {
                continue;
            }

            foreach (['@info', '@endinfo', '@danger', '@enddanger', '@warning', '@endwarning'] as $marker) {
                $text = str_replace($marker, '', $text);
            }

            $current['content'] .= $text."\n\n";
        }

        if ($current !== null && trim($current['content']) !== '') {
            $sections[] = $current;
        }

        return $sections;
    }
}
