# Filter parser

```php
return [
    'extensions' => [
        SomeOtherHighlighter::class, // [tl! remove]
        TorchlightExtension::class,
    ]
]
```

```bash
category:action OR category:horror
```

```json
{
    "bool": {
        "should": [
            {
                "term": {
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
