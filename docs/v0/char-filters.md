# Char Filters


## Pattern

```php
use Sigmie\Index\Analysis\CharFilter\HTMLStrip;

$newAnalyer->charFilter(new HTMLStrip);

// OR

$newAnalyer->stripHTML();
```

```php
 "<span>Some people are worth melting for.</span>" 
 ------------------------------------------------- 
 Strip HTML                                        
 ------------------------------------------------- 
 "Some people are worth melting for."              
```

## Char Mapping
```php
use Sigmie\Index\Analysis\CharFilter\Mapping;

$newAnalyer->charFilter(new Mapping(
    name: 'mapping_char_filter',
    mappings: [
        ':)' => 'happy',
        ':(' => 'sad',
    ]
));

// OR

$newAnalyzer->mapChars([':)'=> 'happy']);
```

```php
 "Even miracles take a little time. :)"    
 ----------------------------------------- 
 Map Chars ":)" -> "happy"                 
 ----------------------------------------- 
 "Even miracles take a little time. happy" 
```

## Pattern replace
```php
use Sigmie\Index\Analysis\CharFilter\Pattern;

$newAnalyer->charFilter(new Pattern(
    name: 'pattern_replace_char_filter',
    pattern: ':D|:\)',
    replace: 'happy'
));

// OR

$newAnalyer->patternReplace(pattern: ':D|:\)', replace:'happy');
```

```php
 "This is the perfect time to panic! :D :)"      
 ------------------------------------------------ 
 Pattern Replace ":D|:\)" -> "happy"              
 ------------------------------------------------ 
 "This is the perfect time to panic! happy happy" 
```
