---
title: Filter parser
description: SQL like Boolean queries
---

Generate authentication keys to authenticate your API.

---

```sql
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
