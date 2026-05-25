<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\Laravel\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;
use Sigmie\AgentTools\Knowledge\KnowledgeDocument;
use Sigmie\AgentTools\Knowledge\KnowledgePipeline;
use RuntimeException;

class IndexKnowledgeChunkJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** 5 minutes — covers Cohere embed + Elasticsearch merge for a full chunk. */
    public int $timeout = 300;

    /** @var list<int> */
    public array $backoff = [30, 60, 120];

    /**
     * Time-based expiry instead of attempt count so throttle releases don't consume retries.
     * Default: 2 hours from dispatch — enough for large batches at 6 jobs/min (~720 chunks).
     */
    public function retryUntil(): \DateTimeInterface
    {
        $minutes = (int) config('agent-tools.kb_populate_retry_for_minutes', 120);

        return now()->addMinutes($minutes);
    }

    /**
     * @param  list<KnowledgeDocument>  $documents
     * @param  array<string, int>  $positionStartBySourceId  next ES `position` per `source_id` when this job continues a prior batch
     */
    public function __construct(
        public array $documents,
        public array $positionStartBySourceId = [],
    ) {}

    public function handle(KnowledgePipeline $pipeline): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $jobsPerMinute = (int) config('agent-tools.kb_populate_jobs_per_minute', 6);
        $releaseAfter = (int) config('agent-tools.kb_populate_rate_limit_release', 10);
        $blockSeconds = (int) config('agent-tools.kb_populate_throttle_block_seconds', 3);
        $everySeconds = (int) ceil(60 / $jobsPerMinute);

        Redis::throttle('sigmie-kb-populate')
            ->block(max(0, $blockSeconds))
            ->allow(1)
            ->every($everySeconds)
            ->then(
                function () use ($pipeline): void {
                    $written = $pipeline->ingest($this->documents, $this->positionStartBySourceId);
                    if ($written === 0 && $this->hasNonEmptyDocuments()) {
                        throw new RuntimeException(
                            'Knowledge ingestion wrote 0 chunks to Elasticsearch despite non-empty job payload; check content, language filter, and chunking.'
                        );
                    }
                },
                fn () => $this->release($releaseAfter),
            );
    }

    private function hasNonEmptyDocuments(): bool
    {
        foreach ($this->documents as $doc) {
            if ($doc instanceof KnowledgeDocument && trim($doc->content) !== '') {
                return true;
            }
        }

        return false;
    }
}
