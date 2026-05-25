<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\Laravel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Sigmie\AgentTools\Elasticsearch\AgentUserMemoryElasticsearchIndex;
use Sigmie\AgentTools\Laravel\Models\AgentUserMemory;
use Sigmie\Document\Document;

class SyncMemoryToElasticsearchJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(public string $memoryId) {}

    public function handle(AgentUserMemoryElasticsearchIndex $index): void
    {
        $model = AgentUserMemory::find($this->memoryId);

        if ($model === null) {
            return;
        }

        $now = (new \DateTimeImmutable)->format(\DateTimeInterface::ATOM);
        $source = [
            'user_id' => $model->user_id,
            'fact' => $model->fact,
            'created_at' => $now,
        ];
        if ($model->category !== null && $model->category !== '') {
            $source['category'] = $model->category;
        }

        $index->indexDocument(new Document($source, $model->id));
    }
}
