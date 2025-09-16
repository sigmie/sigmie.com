<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Sigmie\Document\Document;
use Sigmie\Mappings\NewProperties;
use Sigmie\Sigmie;

class CreateIndex extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sigmie:index-docs {--fresh : Drop and recreate the index}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Index all documentation files into Elasticsearch for search';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        /** @var Sigmie $sigmie */
        $sigmie = app(Sigmie::class);

        $indexName = 'documentation';

        // Drop index if fresh flag is set
        if ($this->option('fresh')) {
            try {
                $index = $sigmie->index($indexName);
                if ($index) {
                    $index->delete();
                    $this->info("Dropped existing index: {$indexName}");
                }
            } catch (\Exception $e) {
                $this->info("Index does not exist or could not be deleted: {$indexName}");
            }
        }

        // Define properties for the index
        $properties = new NewProperties;
        $properties->title('title')->semantic(accuracy: 5);  // Using title() method for optimized title field
        $properties->longText('content')->semantic(accuracy: 4);  // Long text for main content
        $properties->text('description')->semantic(accuracy: 3);  // Text field for description
        $properties->keyword('version');  // Keyword for exact matching
        $properties->keyword('section');  // Keyword for exact matching
        $properties->keyword('filename');  // Keyword for exact matching
        $properties->keyword('url');  // Keyword for exact matching

        // Create the index with properties
        try {
            $index = $sigmie->newIndex($indexName)
                ->properties($properties)
                ->shards(1)  // Single shard for small dataset
                ->replicas(0)  // No replicas for development
                ->create();
        } catch (\Exception $e) {
            $this->error("Failed to create index: " . $e->getMessage());
            return Command::FAILURE;
        }

        $this->info("Created index: {$indexName}");

        // Read all documentation files
        $documents = [];
        $docsPath = base_path('docs');
        $versions = ['v1', 'v2'];

        foreach ($versions as $version) {
            $versionPath = "{$docsPath}/{$version}";

            if (!is_dir($versionPath)) {
                $this->warn("Version directory not found: {$versionPath}");
                continue;
            }

            $files = glob("{$versionPath}/*.md");

            foreach ($files as $file) {
                $filename = basename($file, '.md');
                $content = file_get_contents($file);

                // Extract title from first H1 or use filename
                $title = $this->extractTitle($content) ?: $this->formatTitle($filename);

                // Extract description (first paragraph)
                $description = $this->extractDescription($content);

                // Find section from config
                $section = $this->findSection($version, $filename);

                // Clean content for indexing (remove markdown syntax)
                $cleanContent = $this->cleanMarkdown($content);

                $documents[] = new Document([
                    'title' => $title,
                    'content' => $cleanContent,
                    'version' => $version,
                    'section' => $section,
                    'filename' => $filename,
                    'url' => "/docs/{$version}/{$filename}",
                    'description' => $description,
                ]);

                $this->info("Indexed: {$version}/{$filename}");
            }
        }

        // Bulk index all documents
        if (!empty($documents)) {

            $sigmie->collect($indexName)->properties($properties)->merge($documents);

            $this->info("Successfully indexed " . count($documents) . " documents");
        } else {
            $this->warn("No documents found to index");
        }

        return Command::SUCCESS;
    }

    /**
     * Extract title from markdown content
     */
    private function extractTitle($content)
    {
        if (preg_match('/^#\s+(.+)$/m', $content, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }

    /**
     * Extract description from markdown content
     */
    private function extractDescription($content)
    {
        // Remove headers and code blocks
        $content = preg_replace('/^#+\s+.+$/m', '', $content);
        $content = preg_replace('/```[\s\S]*?```/', '', $content);
        $content = preg_replace('/`[^`]+`/', '', $content);

        // Get first paragraph
        $paragraphs = array_filter(explode("\n\n", $content));
        if (!empty($paragraphs)) {
            $firstParagraph = trim(array_shift($paragraphs));
            return substr($firstParagraph, 0, 200);
        }

        return '';
    }

    /**
     * Clean markdown to plain text for indexing
     */
    private function cleanMarkdown($content)
    {
        // Remove code blocks
        $content = preg_replace('/```[\s\S]*?```/', '', $content);

        // Remove headers but keep text
        $content = preg_replace('/^#+\s+(.+)$/m', '$1', $content);

        // Remove markdown links but keep text
        $content = preg_replace('/\[([^\]]+)\]\([^\)]+\)/', '$1', $content);

        // Remove inline code
        $content = preg_replace('/`[^`]+`/', '', $content);

        // Remove images
        $content = preg_replace('/!\[([^\]]*)\]\([^\)]+\)/', '', $content);

        // Remove bold and italic markers
        $content = preg_replace('/(\*\*|__)(.*?)\1/', '$2', $content);
        $content = preg_replace('/(\*|_)(.*?)\1/', '$2', $content);

        return trim($content);
    }

    /**
     * Format filename to title
     */
    private function formatTitle($filename)
    {
        $title = str_replace(['-', '_'], ' ', $filename);
        return ucwords($title);
    }

    /**
     * Find section from navigation config
     */
    private function findSection($version, $filename)
    {
        $navigation = config("docs.{$version}.navigation", []);

        foreach ($navigation as $section) {
            foreach ($section['links'] ?? [] as $link) {
                if (strpos($link['href'], $filename) !== false) {
                    return $section['title'];
                }
            }
        }

        return 'General';
    }
}
