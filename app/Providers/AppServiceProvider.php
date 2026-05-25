<?php

declare(strict_types=1);

namespace App\Providers;

use App\Agent\SigmieDocsAgent;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use League\CommonMark\CommonMarkConverter;
use Torchlight\Block;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\SmartPunct\SmartPunctExtension;
use League\CommonMark\MarkdownConverter;
use Torchlight\Commonmark\V2\TorchlightExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Extension\TableOfContents\TableOfContentsExtension;
use Sigmie\AgentTools\AgentTools;
use Sigmie\AI\APIs\InfinityClipApi;
use Sigmie\AI\APIs\InfinityEmbeddingsApi;
use Sigmie\AI\APIs\OpenAIEmbeddingsApi;
use Sigmie\AI\LLMs\OpenAILLM;
use Sigmie\Base\Contracts\ElasticsearchConnection as ContractsElasticsearchConnection;
use Sigmie\Base\Http\ElasticsearchConnection;
use Sigmie\Enums\ElasticsearchVersion;
use Sigmie\Http\JSONClient;
use Sigmie\Sigmie;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        RateLimiter::for('agent-chat', fn (Request $request) => [
            Limit::perMinute((int) config('agent.chat.per_minute', 6))->by($request->ip()),
            Limit::perHour((int) config('agent.chat.per_hour', 30))->by($request->ip()),
        ]);
    }

    public function register(): void
    {
        $this->app->singleton(MarkdownConverter::class, function () {
            $environment = new Environment();
            $environment->addExtension(new CommonMarkCoreExtension);
            $environment->addExtension(new TableExtension);
            $environment->addExtension(new HeadingPermalinkExtension);
            $environment->addExtension(new SmartPunctExtension);
            // $environment->addExtension(new TableOfContentsExtension);
            $environment->addExtension(new TorchlightExtension);

            $converter = new MarkdownConverter($environment);

            return $converter;
        });

        $this->app->singleton(ContractsElasticsearchConnection::class, function () {

            $json = JSONClient::create(hosts: ['127.0.0.1:9200'], config: ['connect_timeout' => 15]);

            return new ElasticsearchConnection($json);
        });

        $this->app->singleton(Sigmie::class, function () {

            $elasticsearchConnection = app(ContractsElasticsearchConnection::class);

            $sigmie = new Sigmie($elasticsearchConnection);

            $sigmie->registerApi('infinity-embeddings', new InfinityEmbeddingsApi('http://localhost:7997'));
            $sigmie->registerApi('infinity-clip', new InfinityClipApi('http://localhost:7996'));

            return $sigmie;
        });

        AgentTools::defaultAgent(SigmieDocsAgent::class);
    }
}
