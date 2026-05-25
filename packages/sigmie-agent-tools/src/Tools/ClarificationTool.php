<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool as AiTool;
use Laravel\Ai\Tools\Request;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Sigmie\AgentTools\Laravel\RecordsAgentToolDebug;

/**
 * Returns structured questions; the model should ask them in natural language in its reply.
 */
class ClarificationTool implements AiTool, LoggerAwareInterface
{
    use LoggerAwareTrait;
    use RecordsAgentToolDebug;

    public function name(): string
    {
        return 'clarification';
    }

    public function description(): string
    {
        return 'Ask the user one or more questions when information is missing or ambiguous. After calling this tool, incorporate the questions into your reply to the user.';
    }

    public function schema(JsonSchema $schema): array
    {
        $questionItem = $schema->object([
            'id' => $schema->string()->required(),
            'prompt' => $schema->string()->required(),
            'type' => $schema->string()->enum(['choice', 'text', 'number', 'date'])->required(),
            'choices' => $schema->array()
                ->items($schema->string())
                ->description('For type=choice: list of options. Pass null for other types.')
                ->nullable()
                ->required(),
        ])->withoutAdditionalProperties();

        return [
            'questions' => $schema->array()
                ->items($questionItem)
                ->max(10)
                ->description('Questions to ask the user (max 10).')
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
        ($this->logger ?? new NullLogger)->info('clarification.questions', [
            'questions' => count($arguments['questions'] ?? []),
        ]);

        return [
            'questions' => $arguments['questions'] ?? [],
        ];
    }
}
