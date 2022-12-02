# Laravel Scount

## Introduction

## Installation

```sh
composer require sigmie/elasticsearch-scout
```

```sh
SCOUT_DRIVER=sigmie-elasticsearch # [tl! add]
ELASTICSEARCH_AUTH_TYPE=none # [tl! add]
```

## Authentication

#### Headers

```php
```

#### None
```php
ELASTICSEARCH_AUTH_TYPE=none # [tl! add]
```
#### Basic
```php
ELASTICSEARCH_USER=user # [tl! add]
ELASTICSEARCH_PASSWORD=password # [tl! add]
```
#### Token
```php
ELASTICSEARCH_TOKEN=eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ # [tl! add]
```
## Elasticsearch Hosts
```env
ELASTICSEARCH_HOSTS=127.0.0.1:9200
```

## Guzzle Configs
```php
```

```php
use Sigmie\ElasticsearchScout\Searchable;
use Sigmie\Mappings\NewProperties;
use Sigmie\Index\NewIndex;
use Sigmie\Search\NewSearch;

class Movie extends Model
{
    use Searchable; // [tl! highlight]

    public function elasticsearchProperties(NewProperties $properties)
    { 
        $properties->title('title');
        $properties->name('director');
        $properties->category();
        $properties->date('created_at');
        $properties->date('updated_at');
    } 
}
```
This command publishes the `scout.php` configuration file to your config directory:

```php
php artisan vendor:publish --provider="Laravel\Scout\ScoutServiceProvider"
```


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
             ->shards(3) // [tl! highlight]
             ->replicas(3); // [tl! highlight]
    } // [tl! highlight]
} 
```



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

    public function elasticsearchIndex(NewIndex $index)
    { 
        $index->shards(3)->replicas(3); 
    } 

    // [tl! collapse:end]

    public function toSearchableArray()
    {
        $array = $this->toArray();

        $array['created_at'] = $this->created_at?->format('Y-m-d H:i:s.u');         // [tl! highlight]
        $array['updated_at'] = $this->updated_at?->format('Y-m-d H:i:s.u'); // [tl! highlight]

        return $array;
    }
} 
```

## Nova

### Score
```php
Text::make('Score', function () {

    $score = $this->hit['_score'] ?? '0';

    return $score;
})
->showOnPreview()
->readonly(true)
->asHtml(),
```

### Highlight
```php
Text::make('Name', function () {

    return $this->hit['highlight']['name'][0] ?? $this->name;
})
->showOnPreview()
->asHtml();
```
