# Installation

```php
composer require sigmie/elasticsearch-scout
```

```php
use Sigmie\ElasticsearchScout\Searchable;
use Sigmie\Mappings\Blueprint;

class Movie extends Model
{
    use Searchable; # [tl! add]

    public function scoutMappings(Blueprint $blueprint) // [tl! add]
    { // [tl! add]
        $blueprint->text('name'); // [tl! add]
        $blueprint->number('year')->integer(); // [tl! add]
        $blueprint->date('created_at'); // [tl! add]
        $blueprint->date('updated_at'); // [tl! add]
    } // [tl! add]
}
```
This command publishes the `scout.php` configuration file to your config directory:

```php
php artisan vendor:publish --provider="Laravel\Scout\ScoutServiceProvider"
```

```sh
SCOUT_DRIVER=sigmie-elasticsearch # [tl! add]
ELASTICSEARCH_AUTH_TYPE=none # [tl! add]
```
