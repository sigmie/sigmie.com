## What is Sigmie?

Sigmie is a PHP library for Elasticsearch that simplifies the creation of **Searches** for your applications.

## Why use Sigmie?
While Elasticsearch is a powerful tool for creating fast and relevant searches for your application, it can be complex to learn and use effectively. 

Sigmie aims to alleviate this complexity, making Elasticsearch **easy** to use. It encapsulates years of experience in building **Searches** into simple, easy-to-use code abstractions. 

To better understand what Sigmie offers, let's look at a simple example.

## Basic Usage

Suppose we have a `books` table in our database that we want to make searchable.

Consider an SQL table like this.

```php
|                                books                                   |
| --------------------- | --------------------------- | ---------------- |
| title                 | author                      | publication_year |
| --------------------- | --------------------------- | ---------------- |
| "Moby Dick"           | "Herman Melville"           | 1851             |
| "War and Peace"       | "Leo Tolstoy"               | 1869             |
| "Pride and Prejudice" | "Jane Austen"               | 1813             |
```

```php
$sigmie->newSearch('books')
       ->properties($properties) // [tl! highlight]
       ->queryString('war')
       ->get()
       ->json();
```

There are four steps required
1. Define the `books` properties.
2. Create a Search **Index** that will contain the `books`.
3. Add the books to the **Index**.
4. Search for a **Query String**.

Here's a simple implementation of the above steps: 

```php

$book = new NewProperties;
$book->title();
$book->author();
$book->searchableNumber('publication_year');

$sigmie->newIndex(name:'books')->properties($book)->create();

$sigmie->collect(index:'books', refresh: true)->merge([
    new Document([
        'title' => 'Moby Dick',
        'author' => 'Herman Melville',
        'publication_year' => '1851'
    ]),
    new Document([
        'title' => 'War and Peace',
        'author' => 'Leo Tolstoy',
        'publication_year' => '1869'
    ]),
    new Document([
        'title' => 'Pride and Prejudice',
        'author' => 'Jane Austen',
        'publication_year' => '1813'
    ]),
]);

$sigmie->newSearch(index:'books')
    ->queryString('war')
    ->fields(['title'])
    ->retrieve(['title','author', 'publication_year'])
    ->get()
    ->json('hits');
```
The records that match our **Query String** are called **Hits** and can be found in the response array.

```php
[ // [tl! collapse:start]
    'total' => [ 
        'value' => 1,
        'relation' => 'eq',
    ],
    'max_score' => 0.9808291, // [tl! collapse:end]
    'hits' => [
        0 => [
            '_index' => 'books_20221118135236605861',
            '_type' => '_doc',
            '_id' => 'rYwDi4QBZcx7VxtuP11z',
            '_score' => 0.9808291,
            '_source' => [
                'title' => 'War and Peace', // [tl! focus]
                'author' => 'Leo Tolstoy' // [tl! focus]
                'publication_year' => '1869' // [tl! focus]
            ],
        ],
    ],
]; // [tl! collapse:start]
  // [tl! collapse:end]
```

## What’s next?
The documentation aims to not only show you the Sigmie functionalities but also to educate and give you a deeper knowledge of Elasticsearch and about how search engines work.

Explore the documentation to learn about all the possible options for the above example.

## Requirements

The Sigmie system requirements:

* PHP >= **8.1**
* Elasticsearch **^7**

## Security Vulnerabilities

If you discover a security vulnerability within Sigmie, please send an email to nico@sigmie.com. All security vulnerabilities will be promptly addressed.
