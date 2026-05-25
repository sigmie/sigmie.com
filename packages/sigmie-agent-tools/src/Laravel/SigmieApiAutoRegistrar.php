<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\Laravel;

use InvalidArgumentException;
use Sigmie\AI\APIs\CohereEmbeddingsApi;
use Sigmie\AI\APIs\CohereRerankApi;
use Sigmie\Enums\CohereInputType;
use Sigmie\Sigmie;

/**
 * Registers doc/query embeddings and rerank APIs on {@see Sigmie} when
 * {@see config('agent-tools.auto_register_sigmie_apis')} is true and an API key is available.
 * Skips names that are already registered ({@see Sigmie::hasApi()}).
 */
final class SigmieApiAutoRegistrar
{
    public static function register(Sigmie $sigmie): void
    {
        if (! config('agent-tools.auto_register_sigmie_apis', true)) {
            return;
        }

        $key = self::resolveApiKey();
        if ($key === '') {
            return;
        }

        $provider = strtolower((string) config('agent-tools.embeddings_provider', 'cohere'));

        match ($provider) {
            'cohere' => self::registerCohere($sigmie, $key),
            default => throw new InvalidArgumentException(
                'Unsupported agent-tools embeddings_provider: '.$provider.'. '.
                'Supported: cohere. Or set SIGMIE_AGENT_AUTO_REGISTER_APIS=false and register APIs manually.'
            ),
        };
    }

    public static function resolveApiKey(): string
    {
        $fromConfig = config('agent-tools.embeddings_api_key');
        if (is_string($fromConfig) && $fromConfig !== '') {
            return $fromConfig;
        }

        foreach (['services.cohere.api_key', 'services.cohere.key'] as $path) {
            $v = config($path);
            if (is_string($v) && $v !== '') {
                return $v;
            }
        }

        return '';
    }

    private static function registerCohere(Sigmie $sigmie, string $apiKey): void
    {
        $docName = (string) config('agent-tools.embeddings_doc_api', 'cohere-doc');
        $queryName = (string) config('agent-tools.embeddings_query_api', 'cohere-query');
        $rerankName = (string) config('agent-tools.rerank_api', 'cohere-rerank');

        $embedModel = (string) config('agent-tools.cohere_embeddings_model', 'embed-english-v3.0');
        $rerankModel = (string) config('agent-tools.cohere_rerank_model', 'rerank-v3.5');

        if (! $sigmie->hasApi($docName)) {
            $sigmie->registerApi($docName, new CohereEmbeddingsApi($apiKey, CohereInputType::SearchDocument, $embedModel));
        }

        if (! $sigmie->hasApi($queryName)) {
            $sigmie->registerApi($queryName, new CohereEmbeddingsApi($apiKey, CohereInputType::SearchQuery, $embedModel));
        }

        if (! $sigmie->hasApi($rerankName)) {
            $sigmie->registerApi($rerankName, new CohereRerankApi($apiKey, $rerankModel));
        }
    }
}
