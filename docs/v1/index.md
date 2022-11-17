# Index

```php
$index = $sigmie->newIndex($indexName)->create();

$index->delete();
```

```php
$index = $this->sigmie->collect($indexName, true);
```

```php
        $index = $this->sigmie->collect($indexName, true);

        $docs = [
            new Document(['foo' => 'bar'], '4'),
            new Document(['foo' => 'baz'], '89'),
            new Document(['baz' => 'john'], '2'),
        ];

        $index->merge($docs);
```

```php
        $index->each(function (Document $document, string $_index) use (&$count) {
            $count++;
        });
```

```php
        $docs = [new Document(['bar' => 'foo'], '1'),
                 new Document(['foo' => 'bar'], '2')
                ];

        $index->merge($docs,);

        $this->assertCount(2, $index);

        $index->remove('1');
        $index->remove('2');
```

```php
$index->merge([$document]);
```

```php
$index->has(_id:'');
```

```php
$iterator = $index->all();
```

```php
$index->replace(new Document([],_id: '1'));
```

```php
$index->refresh();
```

```php
$index->clear();
```

```php
$index->toArray();
```

```php
$index->isEmpty();
```

```php
$index->isNotEmpty();
```

```php
$index->remove(_id: 'some');
```

```php
$count = $index->count();
```

```php
isset($index['some-id'])
count($index)
$document = $index['some-id'];
```
