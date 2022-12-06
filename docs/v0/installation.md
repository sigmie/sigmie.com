To use Sigmie add the `sigmie/sigmie`  package as a dependency.

```bash
composer require sigmie/sigmie
```

Sigmie supports PSR-4 autoloading.

```bash
require_once 'vendor/autoload.php';
```

Once Sigmie is installed you need to initialize the `Sigmie\Sigmie` facade. Typically in a development environment where Elasticsearch is running on the same machine as your code, you can initialize the Sigmie **Client** as this.

```php
$sigmie = Sigmie::create(
    hosts:  ['127.0.0.1:9200'],
    config: ['connect_timeout' => 15]
);
```

The `hosts` parameter tells where the Elasticsearch is located, and the `config` parameter accepts all the [Guzzle](https://docs.guzzlephp.org/en/stable/index.html) available options. 

The `Sigmie\Sigmie::create` method is a simplification of the following code:
  
```php
use Sigmie\Http\JSONClient;
use Sigmie\Base\Http\ElasticsearchConnection;
use Sigmie\Sigmie;

// Create JSON http client
$jsonClient = JSONClient::create(hosts:  [ '10.0.0.1', '10.0.0.0.2', '10.0.0.3'],
                                 config: [ 'allow_redirects' => false,
                                           'http_errors' => false,
                                           'connect_timeout' => 15,
                                ]);

// Create a new Elasticsearch connection
$elasticsearchConnection = new ElasticsearchConnection($jsonClient);

// Initialize the Sigmie client
$sigmie = new Sigmie($elasticsearchConnection);
```
