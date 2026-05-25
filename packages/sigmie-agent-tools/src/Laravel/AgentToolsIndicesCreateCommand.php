<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\Laravel;

use Illuminate\Console\Command;
use Sigmie\SigmieIndex;

/**
 * Creates Elasticsearch indices for {@see config('agent-tools.unified_search_indices')}.
 */
class AgentToolsIndicesCreateCommand extends Command
{
    protected $signature = 'sigmie:agent-tools:indices-create';

    protected $description = 'Create Elasticsearch indices for unified search (from agent-tools.unified_search_indices)';

    public function handle(): int
    {
        foreach (UnifiedSearchIndices::classNames() as $indexClass) {
            /** @var SigmieIndex $index */
            $index = app($indexClass);
            $this->info('Creating '.$indexClass.' ('.$index->name().')...');
            $index->create();
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}
