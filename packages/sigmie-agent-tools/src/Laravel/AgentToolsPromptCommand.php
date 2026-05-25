<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\Laravel;

use Laravel\Ai\Enums\Lab;
use Sigmie\AgentTools\Elasticsearch\AgentConversationsElasticsearchIndex;
use Sigmie\AgentTools\Elasticsearch\AgentKnowledgeElasticsearchIndex;
use Sigmie\AgentTools\Elasticsearch\AgentUserMemoryElasticsearchIndex;

/**
 * Runs {@see SigmieAgent} with Laravel AI SDK (tools: unified search, memory, clarification).
 */
class AgentToolsPromptCommand extends AgentToolsBaseCommand
{
    protected $signature = 'sigmie:agent-tools:prompt
                            {message? : User message to send to the agent}
                            {--conversation-id= : Conversation id (default: agent class defaultConversationId)}
                            {--user-token= : User id for conversations and memory (default: agent class defaultUserToken)}
                            {--model= : Model (defaults to agent class defaultModel)}
                            {--clear : Delete and recreate Elasticsearch agent indices (conversations + memory), then run if a message is given}
                            {--debug : Show raw technical logs alongside the friendly output}';

    protected $description = 'Run the Sigmie agent (Laravel AI SDK) with tools. Memory and conversations persist in Elasticsearch per user-token.';

    public function handle(): int
    {
        $agentClass = $this->resolveAgentClass();
        if ($agentClass === null) {
            return self::FAILURE;
        }

        $userToken = (string) ($this->option('user-token') ?: $agentClass::defaultUserToken());
        $conversationId = (string) ($this->option('conversation-id') ?: $agentClass::defaultConversationId());

        /** @var AgentConversationsElasticsearchIndex $conversations */
        $conversations = app(AgentConversationsElasticsearchIndex::class);
        /** @var AgentUserMemoryElasticsearchIndex $memory */
        $memory = app(AgentUserMemoryElasticsearchIndex::class);
        /** @var AgentKnowledgeElasticsearchIndex $knowledge */
        $knowledge = app(AgentKnowledgeElasticsearchIndex::class);

        if ($this->option('clear')) {
            if ($this->clearAgentIndices($conversations, $memory) !== self::SUCCESS) {
                return self::FAILURE;
            }
            $this->info('Agent indices cleared and recreated.');
        } else {
            $this->ensureAgentElasticsearchIndices();
        }

        $userMessage = (string) ($this->argument('message') ?: '');
        if ($userMessage === '') {
            $userMessage = $agentClass::defaultDemoMessage();
        }

        $model = (string) ($this->option('model') ?: $agentClass::defaultModel());
        $providerStr = (string) config('ai.default', 'anthropic');
        $provider = Lab::tryFrom($providerStr) ?? Lab::Anthropic;

        $debug = (bool) $this->option('debug');
        $logger = new ConsoleLogger($this->output, $debug);

        /** @var SigmieAgent $agent */
        $agent = app()->make($agentClass, ['logger' => $logger]);

        $user = (object) ['id' => $userToken];
        $agent->continue($conversationId, $user);

        $this->line("<fg=cyan>You:</> {$userMessage}");
        $this->newLine();

        if ($debug) {
            $this->line("<fg=gray>user-token={$userToken}  conversation-id={$conversationId}</>");
            $this->printIndexVerification($conversations, $memory, $userToken, $conversationId);
            $this->newLine();
        }

        try {
            $response = $agent->prompt($userMessage, provider: $provider, model: $model !== '' ? $model : null);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->line('<fg=green>Agent:</> '.$response->text);
        $this->newLine();
        $this->line(str_repeat('─', 40));
        if ($debug) {
            $this->line('<fg=gray>Done. Model: '.$model.'</>');
        }

        if ($debug) {
            $this->newLine();
            $this->printIndexVerification($conversations, $memory, $userToken, $conversationId);
        }

        return self::SUCCESS;
    }

    private function clearAgentIndices(
        AgentConversationsElasticsearchIndex $conversations,
        AgentUserMemoryElasticsearchIndex $memory,
    ): int {
        foreach ([$conversations, $memory] as $index) {
            $name = $index->name();
            $this->line("Deleting index <fg=yellow>{$name}</>...");
            $index->delete();
            $this->line("Creating index <fg=yellow>{$name}</>...");
            $index->create();
        }

        return self::SUCCESS;
    }

    private function printIndexVerification(
        AgentConversationsElasticsearchIndex $conversations,
        AgentUserMemoryElasticsearchIndex $memory,
        string $userId,
        string $conversationId
    ): void {
        $this->line('<fg=magenta>── Index verification (Elasticsearch) ──</>');

        $hc = $conversations->countTurnsForUserConversation($userId, $conversationId);
        $this->line(sprintf(
            '<fg=white>Conversations</>: %d turn(s) for this user + conversation',
            $hc
        ));

        $fc = $memory->factCount($userId);
        $this->line(sprintf(
            '<fg=white>User memory</>: %d fact(s) for this user',
            $fc
        ));
    }
}
