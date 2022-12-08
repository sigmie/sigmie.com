## Introduction
Documents are JSON objects stored in an Index. If we change our perspective, an Index is a multidimensional array.

Sigmie handles an Index as a **Collection** containing instances of `Document\Document`.

## Collecting an Index
To use an Index as a **Collection** call the `collect` method on the Sigmie facade instance.
```php
$movies = $sigmie->collect('movies');
```

Once you have collected your Index your can start adding `Document\Document` instances to it.
```php
$movies->add(new Document([ 'name' => 'Peter Pan' ]));
```


## Indexing Documents
Keep in mind that Elasticsearch is **” Near real-time ”**. Documents added to an Index are usually available for searching after **1 second**.

You can make Documents directly available in our Index for testing purposes using the `refresh` flag when collecting an Index.

Using `refresh=true` is **NOT** recommended in production code.

### Async
Let’s have a look at the below example, where we index a Movie into our freshly created `movies` Index.

```php
$sigmie->newIndex('movies')->create();

$doc = new Document(['name' => 'Mary Poppins']);

$movies = $sigmie->collect('movies');

$movies->add($docs);

$movies->count(); // 0 [tl! highlight]
```

After adding the Movie we call **directly** the `count` method on the Alive Index Collection. The `count` method returns `0`.

### Sync
Bellow if a different example where we test that a Document was inserted into our Index. 

```php
$sigmie->newIndex('movies')->create();

$movies = $sigmie->collect('movies');  // [tl! remove]
$movies = $sigmie->collect('movies', refresh: true); // [tl! add]

$doc = new Document(['name' => 'Mary Poppins']);

$movies->add($docs);

$this->assertCount(1, $movies->count()); // [tl! highlight]
```

By passing the `refresh: true` parameter when collecting the Index, we made sure that all inserts made to the `movies` Index are immediately visible.

## Document _id
Each time you index a Document Elasticsearch assigns an `_id` to it. 

### Add a Document with an `_id`
You can omit the `_id` field when indexing a Document, or you can pass it to the `Document\Document`’s
constructor `_id: 1`.  
```php
$movies->add(new Document( 
              _source: ['name' => 'Mary Poppins'], 
              _id: 1 //[tl! highlight]
            ));
```

It’s a common practice to manually pass the `_id` when indexing Documents from a SQL database so that you can later perform **update** and **delete** operations on it.

### Add a Document without an `_id`

You can use the **read-only**  `$doc->_id` property to access the `_id` of a Document that exists in your index. 

Here is an example with an `_id` that was assigned from Elasticsearch:
```php
$doc = new Document(['name' => 'The Parent Trap']);

$sigmie->collect('movies')->add($docs); 

$doc->_id; //  7IapIQBhb8W_9CdjGoe [tl! highlight]
```

And here is an example with a hypothetical `_id` of **99** that’s coming from the database.

```php
$doc = new Document(['name' => 'The Mighty Ducks'], _id: 99);

$sigmie->collect('movies')->add($docs); 

$doc->_id; // 99 [tl! highlight]
```


## Retrieve a Document
You can retrieve a Document by it’s `_id` using the `get` method on the Index collection.

```php
$doc = $sigmie->collect('movies')->get(_id: 'rIapIQBhb8W_9CdjGoe');
```


## Update a Document
Many times you may wish to update Documents.

### Update a single Document
Use the `replace` method to update an existing Document.
```php
$doc = new Document(['name' => 'Mary Poppins'], _id: 2 );

$sigmie->collect('movies')
       ->replace($doc)); // [tl! highlight]
```

You can also achieve the same by using the `merge` method, which allows you to **update multiple records at once**.
```php
$doc = new Document(['name' => 'Mary Poppins'], _id: 2 );

$sigmie->collect('movies')
      ->replace($doc)); // [tl! remove] 
        ->merge([$doc]); // [tl! add] 
```

If you pass a `Document` without an `_id` to the merge function, the `Document` will then be created.

## Iterate over Index Documents
There is an `each` method that accepts a callback, which allows you to iterator over all Index Documents.

The `chuck` method specifies how many records to load in memory each time, to avoid memory exceptions.

This is especially useful if you want to iterate over big indices.

```php
$movies = $sigmie->colelct('movies')->chunk(500);

$movies->each(function (Document $document, string $_index) {
    // do something...
});
```

You can also call the `all` method to use a traditional `foreach` loop.

```php
foreach ($movies->all() as $index => $movie) {
    // do something...
}

```

## Clear Index Documents
You can remove all Documents from an Index with the `clear` method.

Let’s assume that we have a `movies` Index containing 100 movies.
```php
$movies = $sigmie->collect('movies');

$movies->count(); // 100
```

After calling the `clear` method our Index Document count will be zero.
```php
$movies = $sigmie->collect('movies');

$movies->clear(); // [tl! highligh]

$movies->count(); // 0
```

## Index Upsert
You can start adding Documents to an Index, even if the Index **doesn’t exist yet**.

```php
$sigmie->index('movies')->delete(); // [tl! highlight]

$index = $sigmie->collect('movies')

$index->add($doc);

$index->count(); 1 // [tl! highlight]
```

When you using this approach, consider creating an Index template.

## More Methods
Bellow are some more methods available on the Index collection.

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

The collected **Index** implements the `ArrayAccess` and the `Countable` interfaces.
Using the `isset` and `count` PHP functions won’t throw any `Exceptions`.
```php
isset($index['2'])

count($index)
```
