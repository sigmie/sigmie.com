## Introduction
Documents are JSON objects stored within an Index. You can think of an Index as a multidimensional array.

Sigmie treats an Index as a **Collection** that contains instances of `Document\Document`.

## Collecting an Index
To treat an Index as a **Collection**, use the `collect` method on the Sigmie facade instance.

For example:
```php
$movies = $sigmie->collect('movies');
```

After collecting your Index, you can start adding `Document\Document` instances to it, like so:

```php
$movies->add(new Document([ 'name' => 'Mickey Mouse' ]));
```

## Indexing Documents

Remember that Elasticsearch operates in **” Near real-time ”**. This means that documents added to an Index are usually available for searching after about **1 second**.

For testing purposes, you can make documents immediately available in your Index by using the `refresh` flag when collecting an Index.

@danger
Using `refresh: true` is **NOT** recommended in production code.
@enddanger

### Async Indexing
Consider the following example, where we index a Movie into our newly created `movies` Index:

```php
$sigmie->newIndex('movies')->create();

$doc = new Document(['name' => 'Snow White']);

$movies = $sigmie->collect('movies');

$movies->add($docs);

$movies->count(); // 0 [tl! highlight]
```

In this case, we add the Movie and then **immediately** call the `count` method on the Alive Index Collection.

The `count` method returns `0` because the document is not available yet.

### Sync Indexing
Here's a different example where we ensure that a Document was inserted into our Index before proceeding:

```php
$sigmie->newIndex('movies')->create();

$movies = $sigmie->collect('movies'); // [tl! remove]
$movies = $sigmie->collect('movies', refresh: true); // [tl! add]

$doc = new Document(['name' => 'Snow White']);

$movies->add($docs);

$this->assertCount(1, $movies->count()); // [tl! highlight]
```

@info
By passing the `refresh: true` parameter when collecting the Index, we ensure that all inserts made to the `movies` Index are immediately visible.
@endinfo

## Document _id
Every time a Document is indexed, Elasticsearch automatically assigns it an `_id`.

### Adding a Document with an `_id`
You have the option to either let Elasticsearch automatically assign the `_id` field when indexing a Document, or you can manually specify it. To manually specify the `_id`, you can do so in the `Document\Document` constructor, like this: `_id: 1`.  
```php
$movies->add(new Document( 
              _source: ['name' => 'Snow White'], 
              _id: 1 //[tl! highlight]
            ));
```

@info
Manually specifying the `_id` is a common practice when indexing Documents from a SQL database. This approach provides the advantage of allowing you to perform **update** and **delete** operations on the document at a later stage.
@endinfo

### Adding a Document without an `_id`

If you add a Document without specifying an `_id`, Elasticsearch will automatically assign one. You can retrieve this `_id` using the `$doc->_id` property, which is read-only. 

Here's an example where Elasticsearch assigns an `_id`:
```php
$doc = new Document(['name' => 'The Lion King']);

$sigmie->collect('movies')->add($doc); 

echo $doc->_id; // Outputs: 7IapIQBhb8W_9CdjGoe
```

In contrast, if you're indexing Documents from a database, you might want to manually specify the `_id`. Here's how you can do that:

```php
$doc = new Document(['name' => 'The Lion King'], _id: 99);

$sigmie->collect('movies')->add($doc); 

echo $doc->_id; // Outputs: 99
```


## Retrieving a Document
You can retrieve a Document by its `_id` using the `get` method on the Index collection.

```php
$doc = $sigmie->collect('movies')->get('rIapIQBhb8W_9CdjGoe');
```


## Updating a Document
There may be instances where you need to update the contents of a Document.

### Updating a Single Document
To update an existing Document, you can use the `replace` method.
```php
$doc = new Document(['name' => 'Snow White'], _id: 2 );

$sigmie->collect('movies')
       ->replace($doc)); // [tl! highlight]
```

Alternatively, you can use the `merge` method to update multiple records simultaneously.
```php
$doc = new Document(['name' => 'Snow White'], _id: 2 );

$sigmie->collect('movies')
        ->merge([$doc]); // [tl! add] 
```

If you pass a `Document` without an `_id` to the merge function, a new `Document` will be created.

## Iterating over Index Documents
The `each` method accepts a callback, allowing you to iterate over all Index Documents.

The `chunk` method specifies how many records to load into memory at a time, to avoid memory exceptions. This is especially useful if you want to iterate over large indices.

```php
$movies = $sigmie->collect('movies')->chunk(500);

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

## Purging Index Documents
The `clear` function enables you to delete all Documents from an Index.

For instance, consider a `movies` Index that contains 100 movies.
```php
$movies = $sigmie->collect('movies');

$movies->count(); // 100
```

After invoking the `clear` function, the count of Documents in our Index will be zero.
```php
$movies = $sigmie->collect('movies');

$movies->clear(); // [tl! highlight]

$movies->count(); // 0
```

## Upserting an Index
You can begin adding Documents to an Index, even if the Index **does not exist yet**.

```php
$sigmie->index('movies')->delete(); // [tl! highlight]

$index = $sigmie->collect('movies')

$index->add($doc);

$index->count(); 1 // [tl! highlight]
```

@warning
While it's possible to add documents to an Index before creating it, **we strongly recommend creating the Index first**. This is because adding documents prior to Index creation can lead to issues with the `boost` and `autocomplete` attributes.
@endwarning

## Additional Functions
Here are some more functions available on the Index collection.

```php
//Remove document by `_id`
$index->remove(_id: 1);

// Verify if document exists
$index->has(_id: 1);

// Retrieve all documents as an array
$index->toArray();

// Verify if the index contains documents
$index->isEmpty();

$index->isNotEmpty();

// Retrieve the document count
$index->count();
```

The collected **Index** implements the `ArrayAccess` and the `Countable` interfaces. Hence, using the `isset` and `count` PHP functions will not result in any `Exceptions`.
```php
isset($index['2'])

count($index)
```
