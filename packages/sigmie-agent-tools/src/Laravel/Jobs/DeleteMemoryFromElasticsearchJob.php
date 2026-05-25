<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\Laravel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Sigmie\AgentTools\Elasticsearch\AgentUserMemoryElasticsearchIndex;

class DeleteMemoryFromElasticsearchJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(
        public string $memoryId,
        public string $userId,
    ) {}

    public function handle(AgentUserMemoryElasticsearchIndex $index): void
    {
        $doc = $index->getDocument($this->memoryId);

        if ($doc === null) {
            return;
        }

        if ((string) $doc->get('user_id') !== $this->userId) {
            return;
        }

        $index->removeDocument($this->memoryId);
    }
}
