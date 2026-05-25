<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\Laravel;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Sigmie\AgentTools\Laravel\Jobs\SyncConversationTurnJob;

/**
 * Backfills {@code agent_conversation_messages} into the conversations Elasticsearch index by pairing
 * user then assistant rows (same shape as {@see SyncConversationTurnJob}).
 */
class AgentToolsConversationsPopulateCommand extends Command
{
    protected $signature = 'sigmie:agent-tools:conversations-populate
                            {--chunk=500 : Messages per database page (keyset pagination)}
                            {--conversation= : Only this conversation_id}
                            {--user= : Only rows where user_id matches}
                            {--sync : Run each sync job inline instead of queueing}';

    protected $description = 'Dispatch jobs to index conversation turns from agent_conversation_messages into Elasticsearch';

    public function handle(): int
    {
        $chunk = max(1, (int) $this->option('chunk'));
        $conversationFilter = $this->option('conversation');
        $conversationFilter = is_string($conversationFilter) && $conversationFilter !== '' ? $conversationFilter : null;
        $userFilter = $this->option('user');
        $userFilter = is_string($userFilter) && $userFilter !== '' ? $userFilter : null;
        $sync = (bool) $this->option('sync');

        $pendingUserMessage = '';
        $currentConversationId = null;
        $dispatched = 0;

        $lastConversationId = null;
        $lastId = null;

        while (true) {
            $q = DB::table('agent_conversation_messages')
                ->when($conversationFilter !== null, fn ($query) => $query->where('conversation_id', $conversationFilter))
                ->when($userFilter !== null, fn ($query) => $query->where('user_id', $userFilter))
                ->orderBy('conversation_id')
                ->orderBy('id');

            if ($lastConversationId !== null && $lastId !== null) {
                $q->where(function ($w) use ($lastConversationId, $lastId): void {
                    $w->where('conversation_id', '>', $lastConversationId)
                        ->orWhere(function ($w2) use ($lastConversationId, $lastId): void {
                            $w2->where('conversation_id', '=', $lastConversationId)
                                ->where('id', '>', $lastId);
                        });
                });
            }

            $rows = $q->limit($chunk)->get();

            if ($rows->isEmpty()) {
                break;
            }

            foreach ($rows as $row) {
                if ($currentConversationId !== $row->conversation_id) {
                    $pendingUserMessage = '';
                    $currentConversationId = $row->conversation_id;
                }

                $role = strtolower((string) $row->role);

                if ($role === 'user') {
                    $pendingUserMessage = (string) $row->content;
                } elseif ($role === 'assistant') {
                    $userId = (string) ($row->user_id ?? '');
                    if ($userId !== '') {
                        $toolCalls = json_decode((string) $row->tool_calls, true);
                        $toolResults = json_decode((string) $row->tool_results, true);
                        if (! is_array($toolCalls)) {
                            $toolCalls = [];
                        }
                        if (! is_array($toolResults)) {
                            $toolResults = [];
                        }

                        $createdAt = $this->formatAtom($row->created_at ?? null);
                        $updatedAt = $this->formatAtom($row->updated_at ?? null);

                        $job = new SyncConversationTurnJob(
                            userId: $userId,
                            conversationId: (string) $row->conversation_id,
                            userMessage: $pendingUserMessage,
                            assistantMessage: (string) $row->content,
                            conversationMessageId: (string) $row->id,
                            toolCalls: $toolCalls,
                            toolResults: $toolResults,
                            documentCreatedAt: $createdAt,
                            documentUpdatedAt: $updatedAt,
                        );

                        if ($sync) {
                            Bus::dispatchSync($job);
                        } else {
                            Bus::dispatch($job);
                        }

                        $dispatched++;
                        $pendingUserMessage = '';
                    } else {
                        $pendingUserMessage = '';
                    }
                }

                $lastConversationId = $row->conversation_id;
                $lastId = $row->id;
            }

            if ($rows->count() < $chunk) {
                break;
            }
        }

        if ($dispatched === 0) {
            $this->warn('No assistant messages matched (or all skipped); nothing dispatched.');

            return self::SUCCESS;
        }

        $this->info("Dispatched {$dispatched} ".($sync ? 'sync ' : '').'job(s) for conversations index.');

        return self::SUCCESS;
    }

    private function formatAtom(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format(\DateTimeInterface::ATOM);
        }

        if (is_string($value) && $value !== '') {
            try {
                return (new \DateTimeImmutable($value))->format(\DateTimeInterface::ATOM);
            } catch (\Exception) {
                return null;
            }
        }

        return null;
    }
}
