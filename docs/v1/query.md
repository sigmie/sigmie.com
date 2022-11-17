# Query


```php
$sigmie->newQuery('')->bool(function (QueriesCompoundBoolean $boolean) {
            $boolean->filter->matchAll();
            $boolean->filter->matchNone();
            $boolean->filter->fuzzy('bar', 'baz');
            $boolean->filter()->multiMatch('baz', ['foo', 'bar']);

            $boolean->must->term('foo', 'bar');
            $boolean->must->exists('bar');
            $boolean->must->terms('foo', ['bar', 'baz']);

            $boolean->mustNot->wildcard('foo', '**/*');
            $boolean->mustNot->ids(['unqie']);

            $boolean->should->bool(fn (QueriesCompoundBoolean $boolean) => $boolean->must->match('foo', 'bar'));
        })->sort('title.raw', 'asc')
            ->fields(['title'])
            ->from(0)
            ->size(2)
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
