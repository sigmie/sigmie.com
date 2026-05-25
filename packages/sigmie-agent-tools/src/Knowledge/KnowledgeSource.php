<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\Knowledge;

interface KnowledgeSource
{
    /**
     * @return iterable<KnowledgeDocument>
     */
    public function documents(): iterable;
}
