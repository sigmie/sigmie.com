<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\Laravel;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Sigmie\AgentTools\Knowledge\KnowledgeDocument;
use Sigmie\AgentTools\Knowledge\KnowledgePipeline;
use Sigmie\AgentTools\Knowledge\KnowledgeSource;
use Sigmie\AgentTools\Laravel\Jobs\IndexKnowledgeChunkJob;

class AgentToolsKbPopulateCommand extends Command
{
    protected $signature = 'sigmie:agent-tools:kb-populate
                            {--class= : FQCN of a single KnowledgeSource; defaults to config agent-tools.knowledge_sources}
                            {--chunk= : KnowledgeDocument rows per job; defaults to config kb_populate_chunk_size}
                            {--queue= : Queue name; defaults to config kb_populate_queue}';

    protected $description = 'Dispatch batched queue jobs to ingest knowledge sources into the agent knowledge index';

    public function handle(KnowledgePipeline $pipeline): int
    {
        $classOption = $this->option('class');
        $classes = is_string($classOption) && $classOption !== ''
            ? [$classOption]
            : (array) config('agent-tools.knowledge_sources', []);

        $classes = array_values(array_filter($classes, fn ($c) => is_string($c) && $c !== ''));

        if ($classes === []) {
            $this->warn('No knowledge sources configured. Set agent-tools.knowledge_sources or pass --class=');

            return self::SUCCESS;
        }

        $chunkSize = (int) ($this->option('chunk') ?: config('agent-tools.kb_populate_chunk_size', 50));
        $chunkSize = max(1, $chunkSize);
        $queue = (string) ($this->option('queue') ?: config('agent-tools.kb_populate_queue', 'default'));

        foreach ($classes as $class) {
            if (! class_exists($class)) {
                $this->error("Class not found: {$class}");

                continue;
            }

            $source = app($class);
            if (! $source instanceof KnowledgeSource) {
                $this->error("{$class} must implement ".KnowledgeSource::class);

                continue;
            }

            $pending = Bus::batch([])
                ->name('kb-populate:'.class_basename($class))
                ->allowFailures()
                ->onQueue($queue);

            $buffer = [];
            $cursors = [];
            $docCount = 0;
            $jobCount = 0;

            foreach ($source->documents() as $kd) {

                if (! $kd instanceof KnowledgeDocument) {
                    $this->warn('Skipping non-'.KnowledgeDocument::class.' value from '.$class);

                    continue;
                }

                $buffer[] = $kd;
                $docCount++;

                if (count($buffer) >= $chunkSize) {
                    $pending->add([new IndexKnowledgeChunkJob($buffer, $cursors)]);
                    $cursors = $pipeline->positionCursorsAfter($buffer, $cursors);
                    $jobCount++;
                    $buffer = [];
                }
            }

            if ($buffer !== []) {
                $pending->add([new IndexKnowledgeChunkJob($buffer, $cursors)]);
                $jobCount++;
            }

            if ($jobCount === 0) {
                $this->warn("No KnowledgeDocument rows yielded from {$class}; nothing dispatched.");

                continue;
            }

            $batch = $pending->dispatch();

            $this->info("Dispatched batch {$batch->id} for {$class} ({$docCount} documents, {$jobCount} jobs).");
        }

        return self::SUCCESS;
    }
}
