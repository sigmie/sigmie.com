<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\Laravel;

use Illuminate\Console\Command;
use Sigmie\AgentTools\AgentTools;
use Sigmie\Sigmie;

/**
 * Shared helpers for agent Artisan commands.
 */
abstract class AgentToolsBaseCommand extends Command
{
    /**
     * @return class-string<SigmieAgent>|null
     */
    protected function resolveAgentClass(): ?string
    {
        $class = AgentTools::resolvedAgentClass();

        if (! is_string($class) || ! class_exists($class) || ! is_a($class, SigmieAgent::class, true)) {
            $this->error(
                'The default agent must extend '.SigmieAgent::class.'. '
                .'Register it with '.AgentTools::class.'::defaultAgent(...) in a service provider, or set config agent-tools.agent_class.'
            );

            return null;
        }

        return $class;
    }

    /**
     * Create missing Elasticsearch indices without deleting or reindexing existing data.
     */
    protected function ensureAgentElasticsearchIndices(): void
    {
        /** @var Sigmie $sigmie */
        $sigmie = app(Sigmie::class);

        foreach (UnifiedSearchIndices::classNames() as $indexClass) {
            $index = app($indexClass);
            if ($sigmie->index($index->name()) !== null) {
                continue;
            }
            $index->create();
        }
    }
}
