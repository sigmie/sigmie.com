## What is Sigmie?

Sigmie is an opinionated Elasticsearch PHP library focused on creating **Searches** for your applications.

## Why use Sigmie?
Elasticsearch is an awesome tool with everything you need to create a fast and relevant search for your application. Unfortunately, there is a deep learning curve to get the most out of it. 

Sigmie is taking away the learning pain and making the use of Elasticsearch **easy**.  Also, it includes years of experience building **Searches** packed in easy-to-use code abstractions. 

If you are anything like me, seeing a code example will make you understand faster what Sigmie is about, than thousands of words.

Let’s have a look at a simple example. 

## Basic Usage

Let’s assume that we have a `users` table in our database that we want to make searchable.

Image an SQL table like this.

```php
|                           users                           |
| ----------------- | -------------------- | -------------- |
| name              | email                | year_of_birth  |
| ----------------- | -------------------- | -------------- |
| "Walt Disney"     | "walt@disney.com"    | 1901           |
| "Roy O. Disney"   | "roy.o@disney.com"   | 1893           |
| "Lillian Disney"  | "lillian@disney.com" | 1899           |
```

There are four steps required
1. Define the `users`  properties.
2. Create a Search **Index** that will contain the `users`.
3. Add the users to the **Index**.
4. Search for a **Query String**.

The simplest implementation of the above looks like this: 

```php

$user = new NewProperties;
$user->name();
$user->email();
$user->searchableNumber('year_of_birth');

$sigmie->newIndex(name:'users')->properties($user)->create();

$sigmie->collect(index:'users', refresh: true)->merge([
    new Document([
        'name' => 'Walt Disney',
        'email' => 'walt@disney.com'
        'year_of_birth' => '1901'
    ]),
    new Document([
        'name' => 'Roy O. Disney',
        'email' => 'roy.o@disney.com'
        'year_of_birth' => '1893'
    ]),
    new Document([
        'name' => 'Lillian Disney',
        'email' => 'lillian@disney.com'
        'year_of_birth' => '1899'
    ]),
]);

$sigmie->newSearch(index:'disney')
    ->queryString('lilian')
    ->fields(['name'])
    ->retrieve(['name','email', 'year_of_birth'])
    ->get()
    ->json('hits');
```
We can find the records that matched our **Query String**  called **Hits** in the response array.

```php
[ // [tl! collapse:start]
    'total' => [ 
        'value' => 1,
        'relation' => 'eq',
    ],
    'max_score' => 0.9808291, // [tl! collapse:end]
    'hits' => [
        0 => [
            '_index' => 'users_20221118135236605861',
            '_type' => '_doc',
            '_id' => 'rYwDi4QBZcx7VxtuP11z',
            '_score' => 0.9808291,
            '_source' => [
                'name' => 'Lillian Disney', // [tl! focus]
                'email' => 'lillian@disney.com' // [tl! focus]
                'year_of_birth' => '1899' // [tl! focus]
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
