<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Indices\Docs;
use App\Services\Documentation;
use Illuminate\Console\Command;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use Masterminds\HTML5;
use Sigmie\Sigmie;

class IndexDocs extends Command
{
    protected $signature = 'docs:index {--fresh : Drop and recreate the index}';

    protected $description = 'Index documentation markdown files to Sigmie';

    public function handle(): int
    {
        /** @var Docs $index */
        $index = app(Docs::class);

        /** @var Sigmie $sigmie */
        $sigmie = $index->sigmie();

        /** @var Documentation $documentation */
        $documentation = app(Documentation::class);

        // Drop old index if fresh flag is set
        if ($this->option('fresh')) {
            $sigmie->deleteIfExists($index->name());
            $this->info("Dropped index: {$index->name()}");
        }

        $index->create();
        $this->info("Created index: {$index->name()}");

        // Set up markdown converter
        $environment = new Environment();
        $environment->addExtension(new CommonMarkCoreExtension());
        $converter = new MarkdownConverter($environment);

        // Get all markdown files from docs directory
        $docsPath = base_path('docs');
        $markdownFiles = glob("{$docsPath}/*/*.md");

        if (empty($markdownFiles)) {
            $this->error("No markdown files found in {$docsPath}");
            return Command::FAILURE;
        }

        $documents = [];
        $totalIndexed = 0;
        $collected = $index->collect(refresh: true);

        $this->info("Found " . count($markdownFiles) . " documentation files");

        foreach ($markdownFiles as $file) {
            // Extract version and page name from path
            // e.g., /path/to/docs/v1/introduction.md
            $relativePath = str_replace($docsPath . '/', '', $file);
            $parts = explode('/', $relativePath);
            $version = $parts[0] ?? 'v1';
            $page = str_replace('.md', '', $parts[1] ?? '');

            // Skip README files
            if (strtoupper($page) === 'README') {
                continue;
            }

            // Read markdown file
            $fileContent = file_get_contents($file);

            // Parse and strip YAML frontmatter
            $parsed = $documentation->parseFrontmatter($fileContent);
            $frontmatter = $parsed['frontmatter'];
            $markdownContent = $parsed['content'];

            // Convert markdown (without YAML) to HTML
            $document = $converter->convert($markdownContent);
            $htmlString = (string) $document;

            // Parse HTML to extract headings and content with proper association
            $html5 = new HTML5(['encode_entities' => true]);
            $dom = $html5->loadHTML('<html><body>' . $htmlString . '</body></html>');

            $allHeadings = [];
            $contentParts = [];

            // Get all body nodes in order to track current heading for each paragraph
            $body = $dom->getElementsByTagName('body')->item(0);
            if (!$body || !$body->hasChildNodes()) {
                $this->warn("Skipping {$relativePath} (no content nodes)");
                continue;
            }

            $currentHeading = $frontmatter['title'] ?? ucfirst(str_replace('-', ' ', $page));
            $currentHeadingId = '';

            // Process all nodes in order to associate paragraphs with their headings
            foreach ($body->childNodes as $node) {
                if ($node->nodeType !== XML_ELEMENT_NODE) {
                    continue;
                }

                $tagName = $node->nodeName;

                // Track headings (h1, h2, h3)
                if (in_array($tagName, ['h1', 'h2', 'h3'])) {
                    $headingText = $node->nodeValue;
                    $allHeadings[] = $headingText;

                    // Generate heading ID (same logic as TableOfContents)
                    $currentHeading = $headingText;
                    $currentHeadingId = strtolower(preg_replace('/[^\w\s-]/', '', $headingText));
                    $currentHeadingId = preg_replace('/\s+/', '-', $currentHeadingId);
                }

                // Process paragraphs
                if ($tagName === 'p') {
                    $value = $node->nodeValue;
                    $value = str_replace('@info', '', $value);
                    $value = str_replace('@endinfo', '', $value);
                    $value = str_replace('@danger', '', $value);
                    $value = str_replace('@enddanger', '', $value);
                    $value = str_replace('@warning', '', $value);
                    $value = str_replace('@endwarning', '', $value);

                    // Skip empty paragraphs
                    if (trim($value) === '') {
                        continue;
                    }

                    $contentParts[] = [
                        'content' => $value,
                        'heading' => $currentHeading,
                        'heading_id' => $currentHeadingId,
                    ];
                }
            }

            // Skip if no content
            if (empty($contentParts)) {
                $this->warn("Skipping {$relativePath} (no content)");
                continue;
            }

            $pageTitle = $frontmatter['title'] ?? $allHeadings[0] ?? ucfirst(str_replace('-', ' ', $page));
            $baseUrl = "/docs/{$version}/{$page}";

            // Create a separate document for each paragraph
            foreach ($contentParts as $paragraphIdx => $paragraphData) {
                $url = $baseUrl;
                if (!empty($paragraphData['heading_id'])) {
                    $url .= '#' . $paragraphData['heading_id'];
                }

                $docData = [
                    '_id' => md5($file . '-' . $paragraphIdx),
                    'title' => $paragraphData['heading'],
                    'page_title' => $pageTitle,
                    'description' => $frontmatter['short_description'] ?? null,
                    'category' => $frontmatter['category'] ?? null,
                    'keywords' => $frontmatter['keywords'] ?? [],
                    'version' => $version,
                    'page' => $page,
                    'url' => $url,
                    'content' => $paragraphData['content'],
                    'headings' => $allHeadings,
                    'paragraph_index' => $paragraphIdx,
                ];

                $documents = [
                    ...$documents,
                    ...$index->toDocuments($docData),
                ];
            }

            // Merge in batches of 50
            if (count($documents) >= 50) {
                $collected->merge($documents);
                $totalIndexed += count($documents);
                $this->info("Indexed {$totalIndexed} documents...");
                $documents = [];
            }
        }

        // Merge any remaining documents
        if (count($documents) > 0) {
            $collected->merge($documents);
            $totalIndexed += count($documents);
        }

        $this->info("Successfully indexed {$totalIndexed} documentation pages");

        return Command::SUCCESS;
    }
}
