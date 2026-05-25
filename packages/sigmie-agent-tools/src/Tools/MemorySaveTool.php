<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\Tools;

use Illuminate\Support\Str;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool as AiTool;
use Laravel\Ai\Tools\Request;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Sigmie\AgentTools\Laravel\Jobs\SyncMemoryToElasticsearchJob;
use Sigmie\AgentTools\Laravel\Models\AgentUserMemory;
use Sigmie\AgentTools\Laravel\RecordsAgentToolDebug;

class MemorySaveTool implements AiTool, LoggerAwareInterface
{
    use LoggerAwareTrait;
    use RecordsAgentToolDebug;

    /**
     * @param  \Closure(): string  $userId
     */
    public function __construct(private \Closure $userId) {}

    public function name(): string
    {
        return 'memory_save';
    }

    public function description(): string
    {
        return 'Persist a concise factual statement about the user (preference, name, constraint). '
            .'Elasticsearch applies the same Sigmie indexing pipeline as the knowledge base; `topic` is filled at index time.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'fact' => $schema->string()->description('The fact to remember about the user.')->required(),
            'category' => $schema->string()
                ->description('Optional category label; pass null when not used.')
                ->nullable()
                ->required(),
        ];
    }

    public function handle(Request $request): string
    {
        $args = $request->toArray();
        $result = $this->execute($args);
        $this->recordAgentToolDebug($this->name(), $args, $result);

        return json_encode($result, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    public function execute(array $arguments): array
    {
        $uid = ($this->userId)();
        $fact = trim((string) ($arguments['fact'] ?? ''));
        $category = isset($arguments['category']) ? (string) $arguments['category'] : null;

        ($this->logger ?? new NullLogger)->info('memory_save.start', [
            'user_id' => $uid,
            'fact' => $fact,
            'category' => $category,
        ]);

        if ($fact === '') {
            return ['saved' => false, 'reason' => 'empty_fact'];
        }

        $id = (string) Str::uuid();

        AgentUserMemory::create([
            'id' => $id,
            'user_id' => $uid,
            'fact' => $fact,
            'category' => $category,
        ]);

        dispatch(new SyncMemoryToElasticsearchJob($id));

        ($this->logger ?? new NullLogger)->info('memory_save.done', [
            'user_id' => $uid,
            'fact' => $fact,
        ]);

        return ['saved' => true];
    }
}
