<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Indices\NetflixTitles;
use Illuminate\Console\Command;
use League\Csv\Reader;
use Sigmie\Document\Document;
use Sigmie\Sigmie;
use Sigmie\Index\NewIndex;

class IndexNetflixTitles extends Command
{
    protected $signature = 'sigmie:index-netflix {--fresh : Drop and recreate the index} {--chunk=200 : Number of documents to process per batch}';

    protected $description = 'Create and populate the netflix_titles index from CSV data';

    public function handle(): int
    {
        /** @var Sigmie $sigmie */
        $sigmie = app(Sigmie::class);

        // Get NetflixTitles index instance for properties
        $netflixIndex = new NetflixTitles();

        // Drop old indices if fresh flag is set
        if ($this->option('fresh')) {
            $netflixIndex->delete();
            $this->info("Dropped index: {$netflixIndex->name()}");
        }

        $netflixIndex->create();

        $this->info("Created index: {$netflixIndex->name()}");

        // Read and parse CSV file

        if (!file_exists($netflixIndex->csvPath())) {
            $this->error("CSV file not found at: {$netflixIndex->csvPath()}");

            return Command::FAILURE;
        }

        $chunkSize = (int) $this->option('chunk');
        $documents = [];
        $totalIndexed = 0;

        // Load CSV using League CSV
        $csv = Reader::createFromPath($netflixIndex->csvPath(), 'r');
        $csv->setHeaderOffset(0);

        // Get collection for the actual index (not the alias)
        $collected = $netflixIndex->collect();

        // Process each record
        foreach ($csv->getRecords() as $record) {
            $documents = [
                ...$documents,
                ...$netflixIndex->toDocuments($record),
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

        $this->info("Successfully indexed {$totalIndexed} Netflix titles");

        return Command::SUCCESS;
    }
}
