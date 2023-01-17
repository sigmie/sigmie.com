# Introduction
Elasticsearch Scout by Sigmie is a [Laravel Scout](https://laravel.com/docs/9.x/scout) driver for Elasticsearch. It provides a quick and easy way for adding a full-text search to your [Eloquent models](https://laravel.com/docs/9.x/eloquent) using Elasticsearch.

# Installation

Since this is **only a driver for Laravel Scout** you need to have **Laravel Scout** installed beforehand.

To install Laravel Scout run:
```bash
composer require laravel/scout
```

Once you have **Laravel Scout** installed, you need to publish its configuration file using:

```bash
php artisan vendor:publish --provider="Laravel\Scout\ScoutServiceProvider"
```

This will publish the `scout.php` configuration file into your  `config/scount.php`.

Then you can install the **Sigmie Elasticsearch Scout** package by running:

```php
composer require sigmie/elasticsearch-scout
```

The next step is to tell Laravel to use the `elasticsearch` driver. You can do this by changing the `SCOUT_DRIVER` in your `.env` file or you can change it directly in the published scout configuration file in `config/scout.php`.

```php
'driver' => env('SCOUT_DRIVER', 'elasticsearch'),
```

**Optionally** you can also publish the `elasticsearch-scout.php` file by running:

```bash
php artisan vendor:publish --provider="Sigmie\ElasticsearchScout\ElasticsearchScoutServiceProvider"
```

This will publish the following config file into `config/elasticsearch-scout.php`.

```php
return [
    'hosts' => ENV('ELASTICSEARCH_HOSTS', '127.0.0.1:9200'),
    'auth' => [
        'type' => env('ELASTICSEARCH_AUTH_TYPE', 'none'),
        'user' => env('ELASTICSEARCH_USER', ''),
        'password' => env('ELASTICSEARCH_PASSWORD', ''),
        'token' => env('ELASTICSEARCH_TOKEN', ''),
        'headers' => [],
    ],
    'guzzle_config' => [
        'allow_redirects' => false,
        'http_errors' => false,
        'connect_timeout' => 15,
    ],
    'index-settings' => [
        'shards' => env('ELASTICSEARCH_INDEX_SHARDS', 1),
        'replicas' => env('ELASTICSEARCH_INDEX_REPLICAS', 2),
    ]
];
```

# Connection
Once Elasticsearch Scout is properly installed you are ready to start using it. To do so first you have to set up the Elasticsearch connection.

## Local
It’s common for **local development** to have Elasticsearch running at  `127.0.0.1`  and listening on port `9200`. In this case, you won’t need any further configuration. 

If you haven’t an Elasticsearch running locally, you can start an Elasticsearch docker container for **local** development by running:
```bash
docker run -p 127.0.0.1:9200:9200 -e "discovery.type=single-node" docker.elastic.co/elasticsearch/elasticsearch-oss:7.10.2-amd64
```

This command will start Elasticsearch on your **local** machine and listen for connections at `9200`.

## Production
In **production** use the `ELASTICSEARCH_HOSTS` environmental variable to tell scout where to find your Elasticsearch hosts.

```
ELASTICSEARCH_HOSTS=10.0.0.1:9200
```
It’s also common that in **production** you will have an Elasticsearch Cluster with more than 1 node. You can multiple Elasticsearch nodes by separating them with a comma `,`.

```
ELASTICSEARCH_HOSTS=10.0.0.1:9200,10.0.0.2:9200,10.0.0.3:9200
```

# Authentication
You can authenticate the Elasticsearch using one of the supported methods or by using your own custom headers. 

By default, no authentication method is used. 

## Basic
To use the Basic Authentication the environment variable `ELASTICSEARCH_AUTH_TYPE` to `basic` and use the `ELASTICSEARCH_USER` and `ELASTICSEARCH_PASSWORD` to fill your user’s credentials.

```php
ELASTICSEARCH_AUTH_TYPE=basic
ELASTICSEARCH_USER=user
ELASTICSEARCH_PASSWORD=password
```

## Token
For Bearer Token authentication set `ELASTICSEARCH_AUTH_TYPE` to `token` and assign your token to the `ELASTICSEARCH_TOKEN` variable.
```php
ELASTICSEARCH_AUTH_TYPE=token
ELASTICSEARCH_TOKEN=eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ
```

## Headers
If no build-in authentication method is fitting you, you can publish the `elasticsearch-config.php` file, and pass any custom headers with each Elasticsearch request.

To publish the `elasticsearch-config.php` use:
```bash
php artisan vendor:publish --provider="Sigmie\ElasticsearchScout\ElasticsearchScoutServiceProvider"
```

Then populate the `headers` section with your desired values.

```php
return [
     // [tl! collapse:start]
    'hosts' => ENV('ELASTICSEARCH_HOSTS', '127.0.0.1:9200'),
    'auth' => [
        'type' => env('ELASTICSEARCH_AUTH_TYPE','none'),
        'user' => env('ELASTICSEARCH_USER', ''),
        'password' => env('ELASTICSEARCH_PASSWORD',''),
        'token' => env('ELASTICSEARCH_TOKEN',''),
          // [tl! collapse:end]
        'headers' => [
          // eg. 'X-App-Token' => "token"
         ], 
     // [tl! collapse:start]
    ],
    'guzzle_config' => [
        'allow_redirects' => false,
        'http_errors' => false,
        'connect_timeout' => 15,
    ],
      'index-settings' => [
        'shards' => env('ELASTICSEARCH_INDEX_SHARDS', 1),
        'replicas' => env('ELASTICSEARCH_INDEX_REPLICAS', 2),
     ]
// [tl! collapse:end]
];
```

# Guzzle Configs
Sigmie uses the [Guzzle HTTP Client](https://docs.guzzlephp.org/en/stable/) for communicating with Elasticsearch. Use the `guzzle_config` to change the Guzzle configuration to your needs. 
```php
return [
     // [tl! collapse:start]
    'hosts' => ENV('ELASTICSEARCH_HOSTS', '127.0.0.1:9200'),
    'auth' => [
        'type' => env('ELASTICSEARCH_AUTH_TYPE','none'),
        'user' => env('ELASTICSEARCH_USER', ''),
        'password' => env('ELASTICSEARCH_PASSWORD',''),
        'token' => env('ELASTICSEARCH_TOKEN',''),
        'headers' => [], 
// [tl! collapse:end]
    ],
    'guzzle_config' => [
        'allow_redirects' => false,
        'http_errors' => false,
        'connect_timeout' => 15,
    ]
// [tl! collapse:end]
];
```


# Indexing
If you are installing Elasticsearch Scout into an existing project, that already uses a different scout driver you will need to **replace** the native `Laravel\Scount\Searchable` trait with `Sigmie\Elasticsearch\Searchable`.
```php
use Laravel\Scout\Searchable;  // [tl! remove]
use Sigmie\ElasticsearchScout\Searchable;  // [tl! add]
use Sigmie\Mappings\NewProperties;

class Movie extends Model
{
    use Searchable; // [tl! highlight]
}
```

The Sigmie `Searchable` trait contains the `elasticsearchProperties` that is used to define your Models mappings.

You can find more information in this documentation’s [Mapping](https://sigmie.com/docs/v0/mappings) section, but here’s an example of a `Movies` model mapping.

```php
// [tl! collapse:start]
use Sigmie\ElasticsearchScout\Searchable;
use Sigmie\Mappings\NewProperties;

class Movie extends Model
{
    use Searchable;

// [tl! collapse:end]
    public function elasticsearchProperties(NewProperties $properties)
    { 
        $properties->title('title');
        $properties->name('director');
        $properties->category();
        $properties->date('created_at');
        $properties->date('updated_at');
    } 
// [tl! collapse:start]
}
// [tl! collapse:end]
```

After defining your mappings you need to run the following command for Sigmie to build your model’s search index.
```bash
php artisan scout:index "App\Models\Movie" 
```

Now are ready to start using Laravel Scout like you are used to.

## Indexing existing database records
Remember that if you are installing Laravel Scout into an existing project, you need to import your existing database records by running. 
```bash
php artisan scout:import "App\Models\Movie"
```

## Updating mappings
Every time you change some **fields mappings** or **Index configurations** you need to call the `sync-index-settings` scout command for changes to take effect.
```bash
php artisan scout:sync-index-settings "App\Models\ Movie"
```

# Searching
The default search is searching **all** your Model’s attributes, without any typo tolerance of match highlighting.

You can get the most out of the search by defining the `elasticsearchSearch`  method on each model instance. There you can use all the Sigmie searching options available.

For example:
```php
use Sigmie\ElasticsearchScout\Searchable;
use Sigmie\Mappings\NewProperties;
use Sigmie\Search\NewSearch;  // [tl! highlight]

class Movie extends Model
{
    use Searchable;  

    public function elasticsearchProperties(NewProperties $properties) // [tl! collapse:start]
    {
        $properties->title('title');
        $properties->name('director');
        $properties->category();
        $properties->date('created_at');
        $properties->date('updated_at');
    } // [tl! collapse:end]

    public function elasticsearchSearch(NewSearch $newSearch) // [tl! highlight]
    { // [tl! highlight]
        $newSearch->typoTolerance(); // [tl! highlight]
        $newSearch->typoTolerantAttributes(['name', 'director']); // [tl! highlight]
        $newSearch->retrieve(['name', 'director']); // [tl! highlight]
        $newSearch->fields(['name', 'director']); // [tl! highlight]
        $newSearch->highlighting( // [tl! highlight]
            ['name', 'title'], // [tl! highlight]
            '<span class="font-bold">', // [tl! highlight]
            '</span>' // [tl! highlight]
        ); // [tl! highlight]
    } // [tl! highlight]
} 
```

In the above code, we are telling Laravel Scout to 
* Search **only** the `name` and `director` attributes
* Retrieve **only** the `name` and `director` attributes from the Search engine
* Allow some **Typo Tolerance** for the `name` and `director` attributes
* Add the Tailwind `font-bold` class to the matching terms

You can find all possible Search options in the [Search](https://sigmie.com/docs/v0/search) section.

# Analysis
The default Searchable configuration will tokenize text fields on **Word Boundaries**, and then**trim** and **lowercase** all tokens.

It’s recommended to override the `elasticsearchIndex` method to create a suitable analysis process Index for your Models. 

```php
use Sigmie\ElasticsearchScout\Searchable;
use Sigmie\Mappings\NewProperties;
use Sigmie\Search\NewSearch;
use Sigmie\Index\NewIndex; // [tl! highlight]

class Movie extends Model
{
    use Searchable;  

    public function elasticsearchProperties(NewProperties $properties) // [tl! collapse:start]
    {
        $properties->title('title');
        $properties->name('director');
        $properties->category();
        $properties->date('created_at');
        $properties->date('updated_at');
    } // [tl! collapse:end]

    public function elasticsearchSearch(NewSearch $newSearch) // [tl! collapse:start]
    {
        $newSearch->typoTolerance();
        $newSearch->typoTolerantAttributes(['name', 'title']);
        $newSearch->retrieve(['name', 'title']);
        $newSearch->fields(['name', 'title']);
        $newSearch->highlighting(
            ['name', 'title'],
            '<span class="font-bold">',
            '</span>'
        );
    } // [tl! collapse:end]

    public function elasticsearchIndex(NewIndex $newIndex) // [tl! highlight]
    { // [tl! highlight]
        $newIndex->tokenizeOnWordBoundaries() // [tl! highlight]
             ->lowercase()  // [tl! highlight]
             ->trim(); // [tl! highlight]
    } // [tl! highlight]
} 
```

Visit the [Analysis section](http://sigmie.test/docs/v0/analysis) you find more information about the Index analysis process.

The default Index **Shards** and **Replicas** are defined inside the `elasticsearch-scout.php`  config file in the `index-settings` key. You can change those setting by calling the `shards` and `replicas` methods inside the `elasticsearchIndex` method on the `NewIndex` instance.
```php
use Sigmie\ElasticsearchScout\Searchable;
use Sigmie\Mappings\NewProperties;
use Sigmie\Search\NewSearch;
use Sigmie\Index\NewIndex; // [tl! highlight]

class Movie extends Model
{
    use Searchable;  

    public function elasticsearchProperties(NewProperties $properties) // [tl! collapse:start]
    {
        $properties->title('title');
        $properties->name('director');
        $properties->category();
        $properties->date('created_at');
        $properties->date('updated_at');
    } // [tl! collapse:end]

    public function elasticsearchSearch(NewSearch $newSearch) // [tl! collapse:start]
    {
        $newSearch->typoTolerance();
        $newSearch->typoTolerantAttributes(['name', 'title']);
        $newSearch->retrieve(['name', 'title']);
        $newSearch->fields(['name', 'title']);
        $newSearch->highlighting(
            ['name', 'title'],
            '<span class="font-bold">',
            '</span>'
        );
    } // [tl! collapse:end]

    public function elasticsearchIndex(NewIndex $newIndex)
    { 
        $newIndex->tokenizeOnWordBoundaries()
             ->lowercase()
             ->trim()
             ->shards(3) // [tl! highlight]
             ->replicas(3); // [tl! highlight]
    }
} 
```

# Timestamps
The default supported DateTime format in Sigmie is `Y-m-d H:i:s.u`. Sigmie uses the Laravel native `toSearchableArray` method to convert the values of your `created_at` and `updated_at` fields to match the ones expected by Elasticsearch.

```php
use Sigmie\ElasticsearchScout\Searchable;
use Sigmie\Mappings\NewProperties;
use Sigmie\Search\NewSearch;
use Sigmie\Index\NewIndex;

class Movie extends Model
{
// [tl! collapse:start]
    use Searchable;  

    public function elasticsearchProperties(NewProperties $properties)
    {
        $properties->title('title');
        $properties->name('director');
        $properties->category();
        $properties->date('created_at');
        $properties->date('updated_at');
    }

    public function elasticsearchSearch(NewSearch $search)
    {
        $search->typoTolerance();
        $search->typoTolerantAttributes(['name', 'category', 'title']);
        $search->retrieve(['name', 'title', 'created_at', 'updated_at']);
        $search->fields(['name', 'director', 'category']);
        $search->highlighting(
            ['name', 'category', 'director'],
            '<span class="font-bold">',
            '</span>'
        );
    }
    // [tl! collapse:end]
    public function toSearchableArray()
    {
        $array = $this->toArray();

        $array['created_at'] = $this->created_at?->format('Y-m-d H:i:s.u');         // [tl! highlight]
        $array['updated_at'] = $this->updated_at?->format('Y-m-d H:i:s.u'); // [tl! highlight]

        return $array;
    }
// [tl! collapse:start]
} 
// [tl! collapse:end]
```

In case the Model uses the `toSearchableArray` method, you need to either define those fields yourself or pass the **Elasticsearch Java Date format** in the `elasticsearchProperties` method.

```php
  public function elasticsearchProperties(NewProperties $properties)
  {
        $properties->date('created_at')->format('MM/dd/yyyy');
        $properties->date('updated_at')->format('MM/dd/yyyy');
  }
```

# Hit
A `public readonly array $hit` attribute is available on all Models that use the `Searchable` trait. This is populated every time a **Model** is returned by the Elasticsearch Scout driver.

Use this attribute to access things like `_score` and `highlighting`.

```php
$movie = Movies::search('Star Wars')->get()->first();

$movie->hit['_score']; // 32.343453

$movie->hit['highlight']['name'][0] // <span class="font-bold">Start Wars</span>
``` 


# Nova
The Elasticsearch Scout package is fully compatible with Laravel Nova.

## Score
If you want to debug your Searches and you are using Laravel Nova, you can create a resource field like this:
```php
Text::make('Score', function () {

    $score = $this->hit['_score'] ?? '0';

    return $score;
})
->showOnPreview()
->readonly(true)
->asHtml(),
```

This will add a `Score` field to your Nova Resource, that shows how well the Model matched the given query.

## Highlight
Also here is how you can use the **Highlighting** feature for your Model attributes in Laravel Nova. 
```php
Text::make('Name', function () {

    return $this->hit['highlight']['name'][0] ?? $this->name;
})
->showOnPreview()
->asHtml();
```
