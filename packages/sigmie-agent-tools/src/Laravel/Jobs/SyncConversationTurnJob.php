<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\Laravel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Sigmie\AgentTools\Elasticsearch\AgentConversationsElasticsearchIndex;
use Sigmie\Document\Document;

class SyncConversationTurnJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /**
     * @param  array<int, mixed>  $toolCalls
     * @param  array<int, mixed>  $toolResults
     */
    public function __construct(
        public string $userId,
        public string $conversationId,
        public string $userMessage,
        public string $assistantMessage,
        public string $conversationMessageId,
        public array $toolCalls = [],
        public array $toolResults = [],
        public ?string $documentCreatedAt = null,
        public ?string $documentUpdatedAt = null,
    ) {}

    public function handle(AgentConversationsElasticsearchIndex $index): void
    {
        $now = (new \DateTimeImmutable)->format(\DateTimeInterface::ATOM);
        $createdAt = $this->documentCreatedAt ?? $now;
        $updatedAt = $this->documentUpdatedAt ?? $now;
        $docId = $this->conversationMessageId !== ''
            ? $this->conversationMessageId
            : (string) Str::uuid();

        $index->indexDocument(new Document([
            'user_id' => $this->userId,
            'conversation_id' => $this->conversationId,
            'user_message' => $this->userMessage,
            'assistant_message' => $this->assistantMessage,
            'tool_calls' => $this->toolCalls !== [] ? json_encode($this->toolCalls, JSON_UNESCAPED_UNICODE) : '',
            'tool_results' => $this->toolResults !== [] ? json_encode($this->toolResults, JSON_UNESCAPED_UNICODE) : '',
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ], $docId));
    }
}
