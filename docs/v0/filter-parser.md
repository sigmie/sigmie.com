# Filter parser

## Introduction

```php
$boolean = new Boolean;
$boolean->filter()->bool(function (Boolean $boolean) {
    $boolean->should()->term('category', 'thriller'); 
    $boolean->should()->term('category', 'horror'); 
);
```

```php
$parser->parse('category:thriller OR category:horror');
```

What you write


```bash
category:action OR category:horror
```

What you get
```json
{ 
    "bool": { 
        "should": [
            {
                "term": { // [tl! highlight:1]
                    "category.keyword": { // [tl! highlight:1]
                        "value": "action", // [tl! highlight:1]
                        "boost": 1
                    }
                } // [tl! collapse:start] 
            },
            { // [tl! collapse:end] 
                "bool": {
                    "should": [
                        {
                            "term": { // [tl! highlight:1]
                                "category.keyword": { // [tl! highlight:1]
                                    "value": "horror", // [tl! highlight:1]
                                    "boost": 1 
                                }
                            }
                        } // [tl! collapse:start] 
                    ],
                    "boost": 1
                } 
            }
        ],
        "boost": 1
    } 
} // [tl! collapse:end] 
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
