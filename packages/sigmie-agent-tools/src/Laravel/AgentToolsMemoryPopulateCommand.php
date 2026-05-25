<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\Laravel;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Sigmie\AgentTools\Laravel\Jobs\SyncMemoryToElasticsearchJob;
use Sigmie\AgentTools\Laravel\Models\AgentUserMemory;

/**
 * Backfills {@see AgentUserMemory} rows into the agent memory Elasticsearch index (e.g. after indices recreate).
 */
class AgentToolsMemoryPopulateCommand extends Command
{
    protected $signature = 'sigmie:agent-tools:memory-populate
                            {--chunk=500 : Rows per database chunk}
                            {--user= : Only rows for this user_id}
                            {--sync : Run each sync job inline instead of queueing}';

    protected $description = 'Dispatch jobs to index agent_user_memory rows into the agent memory Elasticsearch index';

    public function handle(): int
    {
        $chunk = max(1, (int) $this->option('chunk'));
        $userFilter = $this->option('user');
        $userFilter = is_string($userFilter) && $userFilter !== '' ? $userFilter : null;
        $sync = (bool) $this->option('sync');

        $query = AgentUserMemory::query()
            ->when($userFilter !== null, fn ($q) => $q->where('user_id', $userFilter))
            ->orderBy('id');

        $dispatched = 0;

        $query->chunkById($chunk, function ($memories) use ($sync, &$dispatched): void {
            foreach ($memories as $memory) {
                if ($sync) {
                    Bus::dispatchSync(new SyncMemoryToElasticsearchJob($memory->id));
                } else {
                    Bus::dispatch(new SyncMemoryToElasticsearchJob($memory->id));
                }
                $dispatched++;
            }
        });

        if ($dispatched === 0) {
            $this->warn('No agent_user_memory rows matched; nothing dispatched.');

            return self::SUCCESS;
        }

        $this->info("Dispatched {$dispatched} ".($sync ? 'sync ' : '').'job(s) for agent memory index.');

        return self::SUCCESS;
    }
}
