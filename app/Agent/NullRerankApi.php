<?php

declare(strict_types=1);

namespace App\Agent;

use Sigmie\AI\Contracts\RerankApi;

/**
 * Placeholder so SigmieAgent's RerankApi constructor parameter resolves.
 * UnifiedSearchTool is constructed with rerankApi = null inside
 * {@see SigmieDocsAgent::defaultTools()}, so this is never actually called.
 */
class NullRerankApi implements RerankApi
{
    public function rerank(array $newIndexes, string $query, ?int $topK = null): array
    {
        return [];
    }
}
