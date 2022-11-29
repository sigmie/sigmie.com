# Search

## Introduction
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
```

```php
$newSearch->queryString('Mickey')
        ->fields(['name', 'description'])
        ->sort('_score')
        ->get();
```

## Essentials

### Properties
```php
$newSearch->properties($properties)
        ->queryString('Mickey')
        ->get();
```

### Query String

```php
$newSearch->queryString('Mickey')->get();
```

### Searchable Attributes
```php
$newSearch->queryString('Mickey')
        ->fields(['name','category'])
        ->get();
```

### Retrievable Attributes
```php
$newSearch->retrieve(['name','description'])
        ->get();
```

## Enhancements
### Sorting
```php
$newSearch->queryString('Mickey')
        ->sort('_score')
        ->get();
```

### Filtering
```php
$newSearch->queryString('Mickey')
        ->filter('is:active AND stock>=0 AND NOT category:horror')
        ->get();
```

### Typo-Tolerant attributes

```php
$sigmie->newSearch($indexName)
        ->typoTolerance(oneTypoChars: 3, twoTypoChars: 6)
        ->typoTolerantAttributes(['name'])
        ->get();
```

#### 1 Typo
```php
$newSearch->minCharsForTwoTypo(1)
        ->minCharsForOneTypo()
        ->get();
```

#### 2 Typo
```php
$newSearch->minCharsForTwoTypo(1)->get();
```

### Highlighting
```php
$sigmie->newSearch($indexName)
        ->highlighting(['category',], '<span class="font-bold">', '</span>')
        ->get();
```
#### Prefix
#### Suffix

### Weight
```php
$newSearch->weight([ 'name'=> 1, 'surname'=> 10])->get();
```
## Pagination

### Size
```php
$sigmie->newSearch($indexName)
        ->size(10)
        ->get();
```

### From
```php
$sigmie->newSearch($indexName)
        ->from(0)
        ->get();
```
