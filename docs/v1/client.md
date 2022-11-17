# Client

```php
$sigmie = Sigmie::create(hosts:['10.0.0.2','10.0.0.3','10.0.0.4'],
                         config: ['connect_timeout' => 15 ]);
```

```php
$jsonClient = JSONClient::create(['localhost:9200']);

$elasticsearchConnection = new ElasticsearchConnection($jsonClient);

$sigmie = new Sigmie($elasticsearchConnection);
```
