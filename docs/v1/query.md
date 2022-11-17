# Query


```php
$sigmie->newQuery(index: 'disney-movies')
        ->bool(function (Boolean $boolean) {

            $boolean->filter->matchAll();

            $boolean->filter()->multiMatch('goofy', ['name', 'description']);

            $boolean->must->term('is_active', true);

            $boolean->mustNot->term('is_active', false);

            $boolean->mustNot->wildcard('foo', '**/*');

            $boolean->should->bool(fn (Boolean $boolean) => $boolean->must->match('name', 'Mickey'));

        })
        ->sort('name.keyword', 'asc')
        ->fields(['name','description', 'stock', 'rating'])
        ->from(0)
        ->size(15)
        ->getDSL();
```

```php
$sigmie->newQuery($name)
    ->range('count', ['>=' => 233])
    ->response();
```


```php
$sigmie->newQuery($name)
    ->term('is_active', true)
    ->response();
```

```php
$sigmie->newQuery($name)
    ->matchAll()
    ->response();
```

```php
$sigmie->newQuery($name)
    ->matchAll(boost: 5)
    ->response();
```

```php
$sigmie->newQuery($name)
    ->matchNone()
    ->response();
```

```php
$sigmie->newQuery($name)
    ->multiMatch()
    ->response();
```

```php
$sigmie->newQuery($name)
    ->exists()
    ->response();
```

```php
$sigmie->newQuery($name)
    ->ids(['','',''])
    ->response();
```

```php
$sigmie->newQuery($name)
    ->fuzzy('name','lion')
    ->response();
```

```php
$sigmie->newQuery($name)
    ->terms(field:'category', values:['horror','action'])
    ->response();
```

```php
$sigmie->newQuery($name)
    ->regex('category','/(horror|action)/')
    ->response();
```

```php
$sigmie->newQuery($name)
    ->wildcard('name','john*')
    ->response();
```
