# Tokenizers

## Introduction
```php
$newAnalyzer->tokenizer($tokenizer);
```
```php
$properties = new NewProperties;
$properties->text('description')
    ->withNewAnalyzer(function (NewAnalyzer $newAnalyzer) { $newAnalyzer->tokenizer($tokenizer); });
```

## Available Tokenizers
### Word Boundaries

```php
use Sigmie\Index\Analysis\Tokenizers\WordBoundaries;

$newAnalyer->tokenizer(new WordBoundaries(name: 'word_boundaries_tokenizer', maxTokenLength: 255));

// OR

$newAnalyer->tokenizeOnWordBoundaries(maxTokenLength: 255);
```

```php
| "Aw shucks, pluto. I can’t be mad at ya!" |
| ------------------------------------------|
| Word Boundaries                           |
| ------------------------------------------|
| "Aw"                                      |
| "shucks"                                  |
| "pluto"                                   |
| "I"                                       |
| "can’t"                                   |
| "be"                                      |
| "mad"                                     |
| "at"                                      |
| "ya"                                      |
```

### Whitespace

```php
use Sigmie\Index\Analysis\Tokenizers\Whitespace;

$newAnalyer->tokenizer(new Whitespace(name: 'whitespace_tokenizer'));

// OR

$newAnalyer->tokenizeOnWordBoundaries();
```

```php
| "Aw shucks, pluto. I can’t be mad at ya!" |
| ----------------------------------------- |
| Whitespace                                |
| ----------------------------------------- |
| "Aw"                                      |
| "shucks,"                                 | // [tl! highlight]
| "pluto."                                  | // [tl! highlight]
| "I"                                       |
| "can’t"                                   |
| "be"                                      |
| "mad"                                     |
| "at"                                      |
| "ya!"                                     | // [tl! highlight]
```

### Noop

```php
use Sigmie\Index\Analysis\Tokenizers\Noop;

$newAnalyzer->tokenizer(new Noop(name: 'noop_tokenizer'));

// OR

$newAnalyzer->dontTokenize();
```

```php
| "If you ain’t scared, you ain’t alive." |
|----------------------------------------|
| Noop                                   |
| ---------------------------------------|
| "If you ain’t scared, you ain’t alive." |
```

### Pattern

```php
use Sigmie\Index\Analysis\Tokenizers\Pattern;

$newAnalyzer->tokenizer(new Pattern(name: 'pattern_tokenizer', ','));

// OR

$newAnalyzer->tokenizeOnPattern(',')
```

### Simple pattern
```php
| "Though at times it may feel like the sky is falling around you, never give up, for every day is a new day" |
| ----------------------------------------------------------------------------------------------------------- |
| Pattern  ","                                                                                                |
| ----------------------------------------------------------------------------------------------------------- |
| "Though at times it may feel like the sky is falling around you"                                            |
| " never give up"                                                                                            |
| " for every day is a new day"                                                                               |
```

### Pattern match

```php
use Sigmie\Index\Analysis\Tokenizers\SimplePattern;

$newAnalyzer->tokenizer(new SimplePattern(name: 'simple_pattern_tokenizer', "'.*'"))

// OR

$newAnalyzer->tokenizeOnPattern("'.*'");
```

@info
Makes sense here to trim whitespaces
@endinfo

```php
|"I remember daddy told me 'Fairytales can come true'."|
| ---------------------------------------------------- |
| Pattern Match  "'.*'"                                |
| ---------------------------------------------------- |
| "'Fairytales can come true'"                         |
```

### Path hierarchy

```php
```

```php
|""|
| ---------------------------------------------------- |
| Path hierarchy                                   |
| ---------------------------------------------------- |
| "                         |
```

### Non Letter

```php
```

```php
|""|
| ---------------------------------------------------- |
| Non Letter |
| ---------------------------------------------------- |
| "                         |
