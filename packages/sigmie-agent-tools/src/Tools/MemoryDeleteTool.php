<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool as AiTool;
use Laravel\Ai\Tools\Request;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Sigmie\AgentTools\Laravel\Jobs\DeleteMemoryFromElasticsearchJob;
use Sigmie\AgentTools\Laravel\Models\AgentUserMemory;
use Sigmie\AgentTools\Laravel\RecordsAgentToolDebug;

class MemoryDeleteTool implements AiTool, LoggerAwareInterface
{
    use LoggerAwareTrait;
    use RecordsAgentToolDebug;

    /**
     * @param  \Closure(): string  $userId
     */
    public function __construct(private \Closure $userId) {}

    public function name(): string
    {
        return 'memory_delete';
    }

    public function description(): string
    {
        return 'Delete an outdated or incorrect fact from user memory by its _id. Use after unified_search (memory) returns the fact you want to remove.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()
                ->description('The _id of the fact to delete (from unified_search memory results).')
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
        $id = trim((string) ($arguments['id'] ?? ''));

        if ($id === '') {
            return ['deleted' => false, 'reason' => 'empty_id'];
        }

        $record = AgentUserMemory::where('user_id', $uid)->find($id);
        $factText = $record !== null ? trim((string) $record->fact) : '';
        $factForLog = $factText !== ''
            ? $factText
            : ($record === null ? '(not found)' : '(empty fact)');

        ($this->logger ?? new NullLogger)->info('memory_delete.start', [
            'id' => $id,
            'fact' => $factForLog,
        ]);

        $deleted = $record !== null;
        $record?->delete();

        dispatch(new DeleteMemoryFromElasticsearchJob($id, $uid));

        ($this->logger ?? new NullLogger)->info('memory_delete.done', [
            'id' => $id,
            'fact' => $factText !== '' ? $factText : $factForLog,
            'deleted' => $deleted,
        ]);

        return ['deleted' => $deleted];
    }
}
