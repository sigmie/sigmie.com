<?php

namespace App\Logging;

use Carbon\Carbon;
use Monolog\Formatter\ElasticsearchFormatter;
use Monolog\Handler\ElasticsearchHandler;
use Psr\Log\LoggerInterface;
use Elastic\Elasticsearch\ClientBuilder;
use Illuminate\Log\Logger;
use Monolog\Logger as MonologLogger;

class ElasticLogger
{
    public function __invoke(array $config): LoggerInterface
    {
        $index =  $config['prefix'] . '_' . Carbon::now()->format('Y_m_d');

        $client = ClientBuilder::create()
            ->setElasticCloudId($config['cloud_id'])
            ->setBasicAuthentication('elastic', $config['password'])
            ->build();

        $handler = new ElasticsearchHandler($client);
        $handler->setFormatter(new ElasticsearchFormatter($index, '_doc'));

        $monolog = new MonologLogger('elastic', [$handler]);

        return (new Logger($monolog))->withContext(['env' => config('app.env')]);
    }
}
