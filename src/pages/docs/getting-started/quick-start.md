---
title: Quick start 
description: empty
---

---

```php
$users = $users->map(fn($user) => $user->toArray());

$sigmie->collect(index:'users')->merge($users);
```

```php
$response = $sigmie->newSearch('users')
                   ->fields(['name'])
                   ->queryString($query)
                   ->get();

$result = $response->json();
```

```json
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
