# Introduction

## What is Sigmie?


## Basic Usage
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

## Requirements

The Sigmie system requirements:

* PHP >= **8.1**
* Elasticsearch **^7**

## Security Vulnerabilities

If you discover a security vulnerability within Sigmie, please send an email to nico@sigmie.com. All security vulnerabilities will be promptly addressed.
