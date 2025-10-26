<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Indices\Docs;
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

            // Read and convert markdown to HTML
            $fileContent = file_get_contents($file);
            $document = $converter->convert($fileContent);
            $htmlString = (string) $document;

            // Parse HTML to extract headings and content
            $html5 = new HTML5(['encode_entities' => true]);
            $dom = $html5->loadHTML(mb_encode_numericentity($htmlString, [0x80, 0x10FFFF, 0, ~0], 'UTF-8'));

            $headings = [];
            $contentParts = [];

            // Extract headings
            foreach ($dom->getElementsByTagName('h1') as $node) {
                $headings[] = $node->nodeValue;
            }
            foreach ($dom->getElementsByTagName('h2') as $node) {
                $headings[] = $node->nodeValue;
            }
            foreach ($dom->getElementsByTagName('h3') as $node) {
                $headings[] = $node->nodeValue;
            }

            // Extract paragraphs for content
            foreach ($dom->getElementsByTagName('p') as $node) {
                $value = $node->nodeValue;
                $value = str_replace('@info', '', $value);
                $value = str_replace('@endinfo', '', $value);
                $contentParts[] = $value;
            }

            // Skip if no content
            if (empty($contentParts) && empty($headings)) {
                $this->warn("Skipping {$relativePath} (no content)");
                continue;
            }

            $title = $headings[0] ?? ucfirst(str_replace('-', ' ', $page));
            $content = implode("\n", $contentParts);
            $url = "/docs/{$version}/{$page}";

            $docData = [
                '_id' => md5($file),
                'title' => $title,
                'version' => $version,
                'page' => $page,
                'url' => $url,
                'content' => $content,
                'headings' => $headings,
            ];

            $documents = [
                ...$documents,
                ...$index->toDocuments($docData),
            ];

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
