# Filter parser

```bash
category:action OR category:horror
```

```json
{
    "bool": {
        "should": [
            {
                "match": {
                    "category": {
                        "value": "action",
                        "boost": 1
                    }
                }
            },
            {
                "bool": {
                    "should": [
                        {
                            "term": {
                                "category": {
                                    "value": "horror",
                                    "boost": 1
                                }
                            }
                        }
                    ],
                    "boost": 1
                }
            }
        ],
        "boost": 1
    }
}
```


### Properties

```php
$mappings = new Properties();

$parser = new FilterParser($props);

$parser->parse('category:action');

```

```json
{
    "bool": {
        "should": [
            {
                "match": {
                    "category": {
                        "value": "action",
                        "boost": 1
                    }
                },
                "term": { // [tl! add]
                    "category.keyword": { // [tl! add]
                        "value": "action", // [tl! add]
                        "boost": 1 // [tl! add]
                    } // [tl! add]
                } // [tl! add]
            }
        ],
        "boost": 1
    }
}
```

```php
$blueprint = new Blueprint;
$blueprint->bool('active');
$blueprint->text('name')->keyword();
$blueprint->text('category');

$props = $blueprint();
```
