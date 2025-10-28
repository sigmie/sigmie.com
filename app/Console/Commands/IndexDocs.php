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

            // Parse HTML to extract sections (heading + content)
            $html5 = new HTML5(['encode_entities' => true]);
            $dom = $html5->loadHTML('<html><body>' . $htmlString . '</body></html>');

            $sections = [];
            $allHeadings = [];

            // Get all body nodes
            $body = $dom->getElementsByTagName('body')->item(0);
            if (!$body || !$body->hasChildNodes()) {
                $this->warn("Skipping {$relativePath} (no content nodes)");
                continue;
            }

            $currentSection = null;
            $sectionIndex = 0;

            // Process all nodes and group by headings
            foreach ($body->childNodes as $node) {
                if ($node->nodeType !== XML_ELEMENT_NODE) {
                    continue;
                }

                $tagName = $node->nodeName;

                // Start new section on heading (h2, h3 - skip h1 as it's usually the page title)
                if (in_array($tagName, ['h2', 'h3'])) {
                    // Save previous section if exists
                    if ($currentSection && !empty(trim($currentSection['content']))) {
                        $sections[] = $currentSection;
                        $sectionIndex++;
                    }

                    $headingText = $node->nodeValue;
                    $allHeadings[] = $headingText;

                    // Generate heading ID (same logic as TableOfContents)
                    $headingId = strtolower(preg_replace('/[^\w\s-]/', '', $headingText));
                    $headingId = preg_replace('/\s+/', '-', $headingId);

                    // Start new section
                    $currentSection = [
                        'heading' => $headingText,
                        'heading_id' => $headingId,
                        'heading_level' => $tagName,
                        'content' => '',
                        'section_index' => $sectionIndex,
                    ];
                } else {
                    // Add content to current section
                    $textContent = trim($node->nodeValue);

                    if (!empty($textContent)) {
                        // Clean up special markers
                        $textContent = str_replace('@info', '', $textContent);
                        $textContent = str_replace('@endinfo', '', $textContent);
                        $textContent = str_replace('@danger', '', $textContent);
                        $textContent = str_replace('@enddanger', '', $textContent);
                        $textContent = str_replace('@warning', '', $textContent);
                        $textContent = str_replace('@endwarning', '', $textContent);

                        if ($currentSection) {
                            $currentSection['content'] .= $textContent . "\n\n";
                        }
                    }
                }
            }

            // Don't forget the last section
            if ($currentSection && !empty(trim($currentSection['content']))) {
                $sections[] = $currentSection;
            }

            // Skip if no sections
            if (empty($sections)) {
                $this->warn("Skipping {$relativePath} (no sections)");
                continue;
            }

            $pageTitle = $frontmatter['title'] ?? $allHeadings[0] ?? ucfirst(str_replace('-', ' ', $page));
            $baseUrl = "/docs/{$version}/{$page}";

            // Create a separate document for each section
            foreach ($sections as $section) {
                $url = $baseUrl;
                if (!empty($section['heading_id'])) {
                    $url .= '#' . $section['heading_id'];
                }

                $docData = [
                    '_id' => md5($file . '-' . $section['section_index']),
                    'title' => $section['heading'],
                    'page_title' => $pageTitle,
                    'description' => $frontmatter['short_description'] ?? null,
                    'category' => $frontmatter['category'] ?? null,
                    'keywords' => $frontmatter['keywords'] ?? [],
                    'version' => $version,
                    'page' => $page,
                    'url' => $url,
                    'content' => trim($section['content']),
                    'headings' => $allHeadings,
                    'section_index' => $section['section_index'],
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
