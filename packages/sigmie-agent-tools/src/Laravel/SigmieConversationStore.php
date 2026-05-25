<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\Laravel;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Ai\Messages\AssistantMessage;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Messages\ToolResultMessage;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\Data\ToolCall;
use Laravel\Ai\Responses\Data\ToolResult;
use Laravel\Ai\Storage\DatabaseConversationStore;
use Sigmie\AgentTools\Laravel\Jobs\SyncConversationTurnJob;

/**
 * Extends Laravel AI's database-backed conversation store.
 * All DB writes (conversations, messages) are inherited from {@see DatabaseConversationStore}.
 * On each assistant message, a job is dispatched to sync the full turn to Elasticsearch.
 *
 * When reloading tool turns from the DB, {@see result_id} may be null for older rows. OpenAI's
 * Responses API requires every {@code call_id} to be a string — we fall back to the tool row id.
 */
class SigmieConversationStore extends DatabaseConversationStore
{
    public function storeAssistantMessage(
        string $conversationId,
        string|int|null $userId,
        AgentPrompt $prompt,
        AgentResponse $response,
    ): string {
        $messageId = parent::storeAssistantMessage($conversationId, $userId, $prompt, $response);

        $uid = (string) ($userId ?? '');
        if ($uid !== '') {
            dispatch(new SyncConversationTurnJob(
                userId: $uid,
                conversationId: $conversationId,
                userMessage: $prompt->prompt,
                assistantMessage: (string) $response->text,
                conversationMessageId: $messageId,
                toolCalls: $response->toolCalls->toArray(),
                toolResults: $response->toolResults->toArray(),
            ));
        }

        return $messageId;
    }

    /**
     * @return Collection<int, Message>
     */
    public function getLatestConversationMessages(string $conversationId, int $limit): Collection
    {
        return DB::table('agent_conversation_messages')
            ->where('conversation_id', $conversationId)
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values()
            ->flatMap(function ($record) {
                $toolCalls = collect(json_decode($record->tool_calls, true));
                $toolResults = collect(json_decode($record->tool_results, true));

                if ($record->role === 'user') {
                    return [new Message('user', $record->content)];
                }

                if ($toolCalls->isNotEmpty()) {
                    $messages = [];

                    $messages[] = new AssistantMessage(
                        $record->content ?: '',
                        $toolCalls->map(fn (array $toolCall) => new ToolCall(
                            id: (string) ($toolCall['id'] ?? ''),
                            name: (string) ($toolCall['name'] ?? ''),
                            arguments: is_array($toolCall['arguments'] ?? null) ? $toolCall['arguments'] : [],
                            resultId: $this->nonNullOpenAiCallId($toolCall, 'result_id', 'id'),
                        ))
                    );

                    if ($toolResults->isNotEmpty()) {
                        $messages[] = new ToolResultMessage(
                            $toolResults->map(fn (array $toolResult) => new ToolResult(
                                id: (string) ($toolResult['id'] ?? ''),
                                name: (string) ($toolResult['name'] ?? ''),
                                arguments: is_array($toolResult['arguments'] ?? null) ? $toolResult['arguments'] : [],
                                result: $toolResult['result'] ?? '',
                                resultId: $this->nonNullOpenAiCallId($toolResult, 'result_id', 'id'),
                            ))
                        );
                    }

                    return $messages;
                }

                return [new AssistantMessage($record->content)];
            });
    }

    /**
     * OpenAI Responses API rejects null {@code call_id}. Prefer stored {@code result_id}, else the tool row {@code id}.
     *
     * @param  array<string, mixed>  $row
     */
    private function nonNullOpenAiCallId(array $row, string $resultKey, string $fallbackKey): string
    {
        $primary = $row[$resultKey] ?? null;
        if (is_string($primary) && $primary !== '') {
            return $primary;
        }

        $fallback = $row[$fallbackKey] ?? null;
        if (is_string($fallback) && $fallback !== '') {
            return $fallback;
        }
        if (is_numeric($fallback)) {
            return (string) $fallback;
        }

        return (string) Str::uuid();
    }
}
