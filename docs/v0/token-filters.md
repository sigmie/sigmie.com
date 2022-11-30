# Token filters

## Introduction

## Available Token Filters

Filters are applied in the order that you specify them.

###  Stemming
```php
$newAnalyzer->stemming([
    ['go', ['going']]
    // more
]);
```

```php
 "Where"                  
 "are"                     
 "you"                    
 "going"                  
 ------------------------ 
 Stemming "going" -> "go" 
 ------------------------ 
 "Where"                  
 "are"                     
 "you"                    
 "going"                   // [tl! remove]
 "go"                      // [tl! add]
```

### Stopwords
```php
$newAnalyzer->stopwords(['but']);
```

```php
 "Ladies"              
 "do"                   
 "not"                 
 "start"               
 "fights"              
 "but"                 
 "they"                
 "can"                 
 "finish"              
 "them"                
 --------------------- 
 Stopwords "but","not" 
 --------------------- 
 "Ladies"              
 "do"                  
 "not"                  // [tl! remove]
 "start"               
 "fights"              
 "but"                  // [tl! remove]
 "they"                
 "can"                 
 "finish"              
 "them"                
```

### Unique
```php
$newAnalyzer->unique(onlyOnSamePosition: false);
```
```php
 "I"
 "was"
 "hiding"
 "under"
 "your"
 "porch"
 "because"
 "I"
 "love"
 "you"
 --------------------- 
 Unique
 --------------------- 
 "I" // [tl! highlight]
 "was"
 "hiding"
 "under"
 "your"
 "porch"
 "because"
 "I"  // [tl! remove]
 "love"
 "you"
```


### Trim
```php
$newAnalyzer->trim();
```

```php
  "Though at times it may feel like the sky is falling around you"
  " never give up"
  " for every day is a new day"
 --------------------- 
 Trim
 --------------------- 
  "Though at times it may feel like the sky is falling around you" 
  " never give up" // [tl! remove]
  "never give up" // [tl! add]
  " for every day is a new day" // [tl! remove]
  "for every day is a new day" // [tl! add]
```


### Synonyms

#### One-Way

```php
$newAnalyzer->oneWaySynonyms([
                'ipod' => ['i-pod', 'i pod'],
            ]);
```

```php
$newAnalyzer->synonyms([
                ['joy' ,['fun']]
            ]);
```

```php
 "It’s"
 "kind"
 "of"
 "fun"
 "to"
 "do"
 "the"
 "impossible"
 --------------------- 
 Synonyms "fun" -> "joy"
 --------------------- 
 "It’s"
 "kind"
 "of"
 "fun" // [tl! remove]
 "joy" // [tl! add]
 "to"
 "do"
 "the"
 "impossible"
```

#### Two-Way

```php
$newAnalyzer->synonyms([
                ['joy' ,'fun']
            ]);
```

```php
 "It’s"
 "kind"
 "of"
 "fun"
 "to"
 "do"
 "the"
 "impossible"
 --------------------- 
 Synonyms "fun", "joy"
 --------------------- 
 "It’s"
 "kind"
 "of"
 "fun" // [tl! highlight]
 "joy" // [tl! add]
 "to"
 "do"
 "the"
 "impossible"
```


### Lowercase
```php
$newAnalyzer->lowercase();
```

```php
 "You"
 "better"
 "be"
 "back"
 "ASAP"
 --------------------- 
 Lowercase
 --------------------- 
 "You" // [tl! remove]
 "you" // [tl! add]
 "better"
 "be"
 "back"
 "ASAP" // [tl! remove]
 "asap" // [tl! add]
```
### Upercase
```php
$newAnalyzer->uppercase();
```
```php
"Miserable"
"darling"
"as"
"usual"
"perfectly"
"wretched"
 --------------------- 
 Upercase
 --------------------- 
"Miserable" // [tl! remove]
"darling" // [tl! remove]
"as" // [tl! remove]
"usual" // [tl! remove]
"perfectly" // [tl! remove]
"wretched" // [tl! remove]
"MISERABLE" // [tl! add]
"DARLING" // [tl! add]
"AS" // [tl! add]
"USUAL" // [tl! add]
"PERFECTLY" // [tl! add]
"WRETCHED" // [tl! add]
```

### Decimal Digit
```php
$newAnalyzer->decimalDigit();
```
```php
// Lao Digits from 1 to 5
 "໑"
 "໒"
 "໓"
 "໔"
 "໕"
 --------------------- 
 Decimal Digit
 --------------------- 
 "໑" // [tl! remove]
 "໒" // [tl! remove]
 "໓" // [tl! remove]
 "໔" // [tl! remove]
 "໕" // [tl! remove]
 "1" // [tl! add]
 "2" // [tl! add]
 "3" // [tl! add]
 "4" // [tl! add]
 "5" // [tl! add]
```

### Ascii Folding
```php
$newAnalyzer->asciiFolding();
```
```php
 "Por"
 "favor"
 "manténgase"
 "alejado"
 "de"
 "las"
 "puertas"
 --------------------- 
 Ascii Folding
 --------------------- 
  "Por"
  "favor"
  "manténgase" // [tl! remove]
  "mantengase"// [tl! add]
  "alejado"
  "de"
  "las"
  "puertas"
  ```

### Token Limit
```php
$newAnalyzer->tokenLimit(maxTokenCount: 10);
```

```php
 "I"
 "was"
 "hiding"
 "under"
 "your"
 "porch"
 "because"
 "I"
 "love"
 "you"
 --------------------- 
 Token Limit 5
 --------------------- 
 "I"
 "was"
 "hiding"
 "under"
 "your"
 "porch" // [tl! remove]
 "because" // [tl! remove]
 "I"  // [tl! remove]
 "love" // [tl! remove]
 "you" // [tl! remove]
```

### Truncate
```php
$newAnalyzer->truncate(length: 10);
```
```php
 "Supercalifragilisticexpialidocious"
 --------------------- 
 Truncate 10
 --------------------- 
 "Supercalifragilisticexpialidocious" // [tl! remove]
 "Supercalif" // [tl! add]
```

### Keywords

```php
$newAnalyzer
->keywords(['going'])
->stemming([
    ['go', ['going']]
]);
```

```php
 "Where"                  
 "are"                     
 "you"                    
 "going"                  
 ------------------------ 
 Keywords "going"
 ------------------------ 
 Stemming "going" -> "go" 
 ------------------------ 
 "Where"                  
 "are"                     
 "you"                    
 "going" // [tl! highlight]
```

@info
This is important that keywords comes before stemmings!
@endinfo

@info
This is usefull when using the language token filters.
@endinfo

## Elaticsearch Plugin Filters

```php
        TokenFilter::filterMap([
            'skroutz_greeklish' => SkroutzGreeklish::class,
            'skroutz_stem_greek' => SkroutzGreekStemmer::class,
        ]);
```
