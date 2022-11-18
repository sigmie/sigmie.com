# Introduction

## Types

```php
$index = $sigmie->newIndex(name:'disney')
            ->mapping(function (Blueprint $blueprint) {
                $blueprint->text('name');
                $blueprint->text('description');
            })
            ->lowercase()
            ->create();

$index = $sigmie->collect(index:'disney', refresh: true);

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

$sigmie->newSearch(index:'disney')
    ->queryString('mickey')
    ->fields(['name'])
    ->retrieve(['name','description'])
    ->get()
    ->json('hits');
```

```php
[   'total' => [
        'value' => 1,
        'relation' => 'eq',
    ],
    'max_score' => 0.9808291,
    'hits' => [
        0 => [
            '_index' => '63778e2491581_20221118135236605861',
            '_type' => '_doc',
            '_id' => 'rYwDi4QBZcx7VxtuP11z',
            '_score' => 0.9808291,
            '_source' => [
                'name' => 'Mickey',
                'description' => 'Adventure in the woods',
            ],
        ],
    ],
];
```
