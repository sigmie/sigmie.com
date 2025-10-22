<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use League\Csv\Reader;
use Sigmie\Document\Document;
use Sigmie\Sigmie;
use Sigmie\Index\NewIndex;

class Ingest extends Command
{
    protected $signature = 'sigmie:ingest {index? : The index class name (e.g., NetflixTitles, ImageData)} {--fresh : Drop and recreate the index} {--chunk=200 : Number of documents to process per batch}';

    protected $description = 'Create and populate an index from CSV data';

    public function handle(): int
    {
        // Get index class name from argument, default to NetflixTitles
        $indexClassName = $this->argument('index') ?? 'NetflixTitles';

        // Build full class name
        $fullClassName = "App\\Indices\\{$indexClassName}";

        // Check if class exists
        if (!class_exists($fullClassName)) {
            $this->error("Index class not found: {$fullClassName}");
            $this->info("Available indices: NetflixTitles, ImageData, Resumes, AsosProducts");
            return Command::FAILURE;
        }

        // Get index instance
        $index = app($fullClassName);
        /** @var Sigmie $sigmie */
        $sigmie = $index->sigmie();

        // Drop old indices if fresh flag is set
        if ($this->option('fresh')) {
            $sigmie->deleteIfExists($index->name());
            $this->info("Dropped index: {$index->name()}");
        }

        $index->create();

        $this->info("Created index: {$index->name()}");

        // Read and parse CSV file

        if (!file_exists($index->csvPath())) {
            $this->error("CSV file not found at: {$index->csvPath()}");

            return Command::FAILURE;
        }

        $chunkSize = (int) $this->option('chunk');
        $documents = [];
        $totalIndexed = 0;

        // Load CSV using League CSV
        $csv = Reader::createFromPath($index->csvPath(), 'r');
        $csv->setHeaderOffset(0);

        // Get collection for the actual index (not the alias)
        $collected = $index->collect(refresh: true);

        // Process each record
        foreach ($csv->getRecords() as $record) {
            $documents = [
                ...$documents,
                ...$index->toDocuments($record),
            ];

            // Merge when chunk size is reached
            if (count($documents) >= $chunkSize) {
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

        $this->info("Successfully indexed {$totalIndexed} documents");

        return Command::SUCCESS;
    }
}
