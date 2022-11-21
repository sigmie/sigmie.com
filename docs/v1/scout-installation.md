# Installation

```php
composer require sigmie/elasticsearch-scout
```

```php
use Sigmie\ElasticsearchScout\Searchable;
use Sigmie\Mappings\Blueprint;

class Movie extends Model
{
    use HasFactory;
    use Searchable;

    public function scoutMappings(Blueprint $blueprint)
    {
        $blueprint->text('name');
        $blueprint->number('year')->integer();
        $blueprint->date('created_at');
        $blueprint->date('updated_at');
    }
}
```
This command publishes the scout.php configuration file to your config directory:

```php
php artisan vendor:publish --provider="Laravel\Scout\ScoutServiceProvider"
```

```
SCOUT_DRIVER=sigmie-elasticsearch
ELASTICSEARCH_AUTH_TYPE=none
```
