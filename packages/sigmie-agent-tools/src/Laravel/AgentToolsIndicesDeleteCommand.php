<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\Laravel;

use Illuminate\Console\Command;
use Sigmie\SigmieIndex;

/**
 * Deletes Elasticsearch indices listed in {@see config('agent-tools.unified_search_indices')} (no-op if missing).
 */
class AgentToolsIndicesDeleteCommand extends Command
{
    protected $signature = 'sigmie:agent-tools:indices-delete';

    protected $description = 'Delete Elasticsearch indices for unified search (from agent-tools.unified_search_indices)';

    public function handle(): int
    {
        foreach (UnifiedSearchIndices::classNames() as $indexClass) {
            /** @var SigmieIndex $index */
            $index = app($indexClass);
            $this->deleteIndex($indexClass, $index);
        }

        $this->info('Done.');

        return self::SUCCESS;
    }

    private function deleteIndex(string $label, SigmieIndex $index): void
    {
        $name = $index->name();
        $this->line("Deleting <fg=yellow>{$label}</> index <fg=yellow>{$name}</> (if it exists)...");
        $index->sigmie()->deleteIfExists($name);
    }
}
