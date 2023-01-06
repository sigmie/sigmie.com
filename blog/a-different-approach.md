
For every problem out there, there exist many solutions. Not all solutions are the same.

And not everybody likes the same solutions.

Look around how many programming languages exist, and
how each of them has advantages and disadvantages.

So it's always worth it to create something that already exists.

The important thing is to
do it differently, and add your own perspective to it.

With this in mind, I created the Sigmie PHP library that takes a different approach to Elasticsearch.

This solution uses 20 percent of the Elasticsearch features but fits in 80 percent of
the world scenarios.

It's made for companies and developers that don't have enough
time to invest in Elasticsearch, but still want a decent Search.

To use it you **don't** need any Elasticsearch knowledge at all. You only need an Elasticsearch server running somewhere.

Let me show you some features.

### You don't need to know Elasticsearch

So simple.


### Easy to get started

You can start searching with 3 lines of code. Let me show you.


Let's assume that you have a `users` table in your database and you want to make your users searchable.

The first step is to add your users to a search index called `users`.

```php
$users = $users->map(fn($user) => $user->toArray());

$sigmie->collect(index:'users')->merge($users);
```

with this two lines we our `$users` to a **search index**. Now we are ready to search for them.

Now let's assume that in our UI we have a text input where our user searches for `john`.

```php
$query = $request->get('query'); // john

$response = $sigmie->newSearch('users')->fields(['name', 'email'])->queryString($query)->get();

$result = $response->json();

```

With the above lines, we search the `users` index if the `name` or the `email` attribute contains
the string `john`.

Here is and example how the JSON response may look like:

```php
{
    "took": 2,
        //
    "hits": {
        //
        "hits": [
            {
                "_index": "636cf7730ba43_20221110130659053736",
                "_type": "_doc",
                "_id": "mA6mYYQBxEY4zeoFmp4P",
                "_score": 0.9808291,
                "_source": {
                    "id": "93",
                    "name": "John Doe",
                    "email": "johny_99@gmai.com",
                    "active": true
                }
            }
        ]
    }
}
```

Now we can use the results to show the found users.


In this example we used the most minimal settings to quickly make our users searchable and start
searching them.

Let's have a look on some options.

### Creating an Index

In the first example, the Search Index is created as soon as we add our first user. But we can also **first** create
an Index with some configurations and add the `$users` afterward.

```php
$sigmie->newIndex('users')->create();

$sigmie->collect('users')->merge($users);
```

This way you can add stopwords, synonyms, define an index language or even strip HTML.

Here's an example how you can add some stopwords.

```php
$sigmie->newIndex('books')
       ->stopwords(['and','but'])
       ->create();
```

Calling the `stopwords` method instructuts the Index to ignore the words `and` and `but` when searching.

There are plenty of methods available to create an index specific to our needs.


### Searching

There are also more methods for searching.

For example we can specify which attributes can handle some typos.

```php
$response = $sigmie->newSearch('books')
                   ->fields(['name', 'author'])
                   ->typoTolerantAttributes(['name'])
                   ->queryString($query)
                   ->get();
```

Using the `typoTolerantAttributes` method above we say that we tolerate some typos in the `name` attribute.

And another example where we search for **all** published `books` and we sort them by `name`.

```php
$response = $sigmie->newSearch('books')
                   ->filter('is:published')
                   ->sort('name:asc')
                   ->queryString($query)
                   ->get();
```


### Think of an **Indices** as a collections.

Bellow are some examples of what you can do.


#### Iterate on big indices

```php
$sigmie->collect('books')
      ->chunk(500)
      ->each(function($document)=>{
        // do something
      });
```

#### Add a document

```php
use Document\Document;

$sigmie->collect('movies')->add(new Document(['name'=> 'Spider Man 3']));
```

#### Get the document count

```php
$index = $sigmie->collect('movies');

count($index); // 200
```

#### Remove all documents

```php
$index = $sigmie->collect('movies');

count($index); // 200

$index->clear();

count($index); // 0
```


#### Check that a document exists

```php
$sigmie->collect('movies')->has('mA6mYYQBxEY4zeoFmp4P');
```

### Some words

The goal of this post was to give you an idea of what we made possible with our implementation and also
to show how Sigmie differs from the traditional Elasticsearch libraries out there.


All this is available in the `sigmie/sigmie` package, and will also exist in the `sigmie/elasticsearch-scout` once it's released.
