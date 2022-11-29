# Installation


## Installing Sigmie

```bash
composer require sigmie/sigmie
```

## Autoloading

Sigmie supports PSR-4 autoloading.

```bash
require_once 'vendor/autoload.php';
```


## Client


```php
$sigmie = Sigmie::create(hosts:['127.0.0.1:9200'], config: ['connect_timeout' => 15 ]);
```

```php
// Create JSON http client
$jsonClient = JSONClient::create(hosts:  [ '10.0.0.1', '10.0.0.0.2', '10.0.0.3'],
                                 config: [ 'allow_redirects' => false,
                                           'http_errors' => false,
                                           'connect_timeout' => 15,
                                ]);

// Create new Elasticsearch connection
$elasticsearchConnection = new ElasticsearchConnection($jsonClient);

// Initialize the Sigmie client
$sigmie = new Sigmie($elasticsearchConnection);
```
