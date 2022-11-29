# Analysis

## Introduction


```bash
Analysis
├─ Char filters
├─ Tokenizer
├─ Token filters
```

## Analyzer
```bash
Analyzer
├─ Char filters
│  ├─ Strip HTML
├─ Tokenizer
│  ├─ Word Boundaries
├─ Token filters
│  ├─ Lowercase
```

```php
"<span>Some people are worth melting for.</span>"
```

### Char filter
```php
"<span>Some people are worth melting for.</span>"  // [tl! remove]
"Some people are worth melting for."               // [tl! add]
```

### Tokenize
```php
"Some people are worth melting for."               // [tl! remove]
"Some"                                             // [tl! add]
"people"                                           // [tl! add]
"are"                                              // [tl! add]
"worth"                                            // [tl! add]
"melting"                                          // [tl! add]
"for"                                              // [tl! add]
```

### Token filters
```php
"Some"                                             // [tl! remove]
"some"                                             // [tl! add]
"people"                                          
"are"                                             
"worth"                                           
"melting"                                         
"for"                                             
```

```php
| Term         | Document 1  | Document 2  |
| -----------  | ----------- | ------------|
| "some"       | x           | x           |
| "people"     | x           | x           |
| "are"        | x           | x           |
| "worth"      | x           | x           |
| "melting"    |             | x           | // [tl! highlight]
| "for"        | X           | x           |
```

## Analyzers
### Index Analyzer
### Field Analyzer
