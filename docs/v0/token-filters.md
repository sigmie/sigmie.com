# Token filters

## Introduction

## Available Token Filters

Filters are applied in the order that you specify them.

###  Stemming
```php
$newAnalyzer->stemming([
                ['am', ['be', 'are']],
                ['mouse', ['mice']],
                ['feet', ['foot']],
            ]);
```

### Stopwords
```php
$newAnalyzer->stopwords(['about', 'after', 'again'], 'sigmie_stopwords');
```

### Unique
```php
$newAnalyzer->unique(name: 'unique_filter', onlyOnSamePosition: true);
```

### Trim
```php
$newAnalyzer->trim();
```

### Synonyms
```php
$newAnalyzer->synonyms([
                'ipod' => ['i-pod', 'i pod'],
                ['treasure', 'gem', 'gold', 'price'],
            ]);
```

#### One-Way
```php
$newAnalyzer->oneWaySynonyms([
                'ipod' => ['i-pod', 'i pod'],
            ]);
```

#### Two-Way
```php
$newAnalyzer->twoWaySynonyms([
                ['treasure', 'gem', 'gold', 'price'],
                ['friend', 'buddy', 'partner'],
            ]);
```


### Lowercase
```php
$newAnalyzer->lowercase();
```

### Upercase
```php
$newAnalyzer->uppercase();
```

### Decimal Digit
```php
$newAnalyzer->decimalDigit();
```

### Ascii Folding
```php
$newAnalyzer->asciiFolding();
```

### Token Limit
```php
$newAnalyzer->tokenLimit(maxTokenCount: 10);
```

### Truncate
```php
$newAnalyzer->truncate(length: 10);
```

### Unique
```php
$newAnalyzer->unique(onlyOnSamePosition: false);
```

### Keywords

```php
$newAnalyzer->keywords([
    'jumping'
]);
```

## Elaticsearch Plugin Filters

```php
        TokenFilter::filterMap([
            'skroutz_greeklish' => SkroutzGreeklish::class,
            'skroutz_stem_greek' => SkroutzGreekStemmer::class,
        ]);
```
