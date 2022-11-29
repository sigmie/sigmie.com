# Document

## Introduction

## Collecting an Index

```php
$doc = $sigmie->collect('movies')->get(_id: 'rIapIQBhb8W_9CdjGoe');
```

### Add Documents async
```php
$docs = [
    new Document(['name' => 'Mary Poppins']),
    new Document(['name' => 'The Mighty Ducks']),
    new Document(['name' => 'The Parent Trap']),
];

$sigmie->collect('movies')->merge($docs); // [tl! highlight]
```

```php
$sigmie->newIndex('movies')->create();

$doc = new Document(['name' => 'Mary Poppins']);

$movies = $sigmie->collect('movies');

$movies->add($docs);

$movies->count(); // 0 [tl! highlight]
```

@warning
Elasticsearch is **Near real-time** search.
@endwarning

### Add Documents sync
```php
$movies = $sigmie->collect('movies');  // [tl! remove]
$movies = $sigmie->collect('movies', refresh: true); // [tl! add]

$movies->count(); // 0

$doc = new Document(['name' => 'Mary Poppins']);

$movies->add($docs);

$movies->count(); // 1 [tl! highlight]
```

```php
$movies = $sigmie->collect('movies');

$movies->count(); // 0

$doc = new Document(['name' => 'Mary Poppins']);

$movies->add($docs);

$movies->refresh(); // [tl! add]

$movies->count(); // 1 [tl! highlight]
```

## Document _id

### Add Document with an `_id`
```php
$movies->add(new Document( 
              _source: ['name' => 'Mary Poppins'], 
              _id: 1 //[tl! highlight]
            ));
```

### Add Document without _id
```php
$doc = new Document(['name' => 'The Parent Trap']);

$sigmie->collect('movies')->add($docs); 

$doc->_id; //  7IapIQBhb8W_9CdjGoe [tl! highlight]
```

```php
$doc = new Document(['name' => 'The Mighty Ducks'], _id: 99);

$sigmie->collect('movies')->add($docs); 

$doc->_id; // 99 [tl! highlight]
```


## Update a Document
```php
$doc = new Document(['name' => 'The Parent Trap'], _id: 2 );

$sigmie->collect('movies')->merge([$docs]); 

$doc->name; // The Parent Trap [tl! highlight]
```


```php
$sigmie->collect('movies')
       ->merge([new Document(['name' => 'Mary Poppins'], _id: 2 )]); 

$doc->name; // Mary Poppins [tl! highlight]
```

```php
$doc = new Document(['name' => 'Mary Poppins'], _id: 2 );

$sigmie->collect('movies')
       ->merge([$doc]); // [tl! remove] 
       ->replace($doc)); // [tl! add]

$doc->name; // Mary Poppins
```

## Iterate over Index Documents

```php
$movies = $sigmie->colelct('movies')->chunk(500);

$movies->each(function (Document $document, string $_index) {
    // do something...
});
```

```php
foreach ($movies->all() as $index => $movie) {
    // do something...
}

```

## Clear Index Documents
```php
$movies = $sigmie->collect('movies');

$movies->count(); // 100
```

```php
$movies = $sigmie->collect('movies');

$movies->clear(); // [tl! add]

$movies->count(); // 100 [tl! remove]
$movies->count(); // 0 [tl! add]
```

## Index Upsert

```php
$sigmie->index('movies')->delete(); // [tl! highlight]

$index = $sigmie->collect('movies')

$index->add($doc);

$index->count(); // [tl! highlight]
```
@info
Consider creating an index template LINK
@endinfo

## More Methods

```php
//Remove document by `_id`
$index->remove(_id: 1);

// Check if document exists
$index->has(_id: 1);

// get all documents as an array
$index->toArray();

// check if the index has documents
$index->isEmpty();

$index->isNotEmpty();

// get the document count
$index->count();
```

The collected **Index** implements tha `ArrayAccess` and the `Countable` interfaces in order to use the `isset` and `count` php functions.
```php
isset($index['2'])

count($index)
```

Keep in mind that integer key aren't allowed.
