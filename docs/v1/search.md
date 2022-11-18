# Search

```php
$index->merge([
    new Document([
        'name' => 'Mickey',
        'description' => 'Adventure in the woods'
    ]),
    new Document([
        'name' => 'Goofy',
        'description' => 'Mickey and his friends'
    ]),
    new Document([
        'name' => 'Donald',
        'description' => 'Chasing Goofy'
    ]),
]);

$sigmie->newSearch($indexName)
    ->queryString('mickey')
    ->fields(['name'])
    ->retrieve(['name','description'])
    ->get()
    ->json('hits');

$hits = $search->response()->json('hits.hits');

        $this->assertEquals('Mickey', $hits[0]['_source']['name']);
        $this->assertCount(2, $hits);

        $search = $this->sigmie->newSearch($indexName)
            ->queryString('Mickey')
            ->fields(['name', 'description'])
            ->sort('_score')
            ->weight([
                'name' => 1,
                'description' => 5
            ])
            ->get();

        $hits = $search->response()->json('hits.hits');

        $this->assertEquals('Goofy', $hits[0]['_source']['name']);
        $this->assertCount(2, $hits);
```


```php
$sigmie->newSearch($indexName)
        ->queryString('Mickey')
        ->fields(['name', 'description'])
        ->sort('_score')
        ->get();
```


```php
$sigmie->newSearch($indexName)
        ->queryString('Mickey')
        ->get();
```
```php
$sigmie->newSearch($indexName)
        ->queryString('Mickey')
        ->sort('_score')
        ->get();
```
```php
$sigmie->newSearch($indexName)
        ->queryString('Mickey')
        ->fields(['name','category'])
        ->sort('_score')
        ->get();
```

```php
$sigmie->newSearch($indexName)
        ->queryString('Mickey')
        ->get();
```


```php
$sigmie->newSearch($indexName)
        ->queryString('Mickey')
        ->filter('is:active')
        ->get();
```

```php
$sigmie->newSearch($indexName)
        ->queryString('Mickey')
        ->properties($properties)
        ->sort('name:asc')
        ->get();
```


```php
$sigmie->newSearch($indexName)
        ->fields('Mickey')
        ->get();
```


```php
$sigmie->newSearch($indexName)
        ->typoTolerantAttributes('Mickey')
        ->get();
```
```php
$sigmie->newSearch($indexName)
        ->highlighting(['category',], '<span class="font-bold">', '</span>')
        ->get();
```

```php
$sigmie->newSearch($indexName)
        ->retrieve([])
        ->get();
```

```php
$sigmie->newSearch($indexName)
        ->weight([ 'name'=> 1, 'surname'=> 10])
        ->get();
```

```php
$sigmie->newSearch($indexName)
        ->minCharsForTwoTypo(1)
        ->minCharsForOneTypo()
        ->get();
```

```php
$sigmie->newSearch($indexName)
        ->size(10)
        ->get();
```

```php
$sigmie->newSearch($indexName)
        ->typoTolerance(oneTypoChars: 3, twoTypoChars: 6)
        ->get();
```
