<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Http\Controllers\ImageSearchController;
use App\Http\Controllers\NetflixSearchController;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Throwable;

class WarmLandingCache extends Command
{
    protected $signature = 'landing:warm {--fresh : Clear cached defaults before warming}';

    protected $description = 'Pre-populate the cache for every default landing-page query so first-visit loads are instant';

    public function handle(NetflixSearchController $netflix, ImageSearchController $images): int
    {
        if ($this->option('fresh')) {
            $this->call('cache:clear');
        }

        $this->warmNetflix($netflix);
        $this->warmImages($images);

        $this->info('Landing cache warmed.');

        return Command::SUCCESS;
    }

    private function warmNetflix(NetflixSearchController $controller): void
    {
        foreach (NetflixSearchController::DEFAULT_QUERIES as $query) {
            $request = Request::create('/api/search/netflix', 'POST', [
                'query' => $query,
                'retrieve' => NetflixSearchController::DEFAULT_RETRIEVE,
                'filters' => NetflixSearchController::DEFAULT_FILTERS,
            ]);

            $this->runQuietly("netflix: {$query}", fn () => $controller->search($request));
        }
    }

    private function warmImages(ImageSearchController $controller): void
    {
        foreach (ImageSearchController::DEFAULT_QUERIES as $query) {
            $request = Request::create('/api/search/images/text', 'POST', ['query' => $query]);

            $this->runQuietly("images: {$query}", fn () => $controller->searchByText($request));
        }
    }

    private function runQuietly(string $label, callable $fn): void
    {
        try {
            $fn();
            $this->line("  ✓ {$label}");
        } catch (Throwable $e) {
            $this->warn("  ✗ {$label} — {$e->getMessage()}");
        }
    }
}
