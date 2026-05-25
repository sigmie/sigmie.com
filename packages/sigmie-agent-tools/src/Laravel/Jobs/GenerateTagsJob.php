<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\Laravel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateTagsJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [30, 60, 120, 300];

    /**
     * @param  class-string  $indexClass
     */
    public function __construct(
        public string $indexClass,
        public string $documentId,
    ) {}

    public function handle(): void
    {
        app($this->indexClass)->generateAndApplyTags($this->documentId);
    }
}
