---
title: Tokenizers
description: empty
---

Generate authentication keys to authenticate your API.

---


## Word Boundaries
```
use Sigmie\Index\Analysis\Tokenizers\WordBoundaries;

$this->sigmie->newIndex($alias)
            ->tokenizer(new WordBoundaries)
            ->create();

$this->newIndex($alias)
        ->tokenizeOnWordBoundaries()
        ->create();
```

## Whitespace
```php
use Sigmie\Index\Analysis\Tokenizers\Whitespace;

$this->sigmie->newIndex($alias)
        ->tokenizer(new Whitespace)
            ->create();

$sigmie->newIndex($alias)
        ->tokenizeOnWhiteSpaces()
       ->create();
```

## Pattern
```php
use Sigmie\Index\Analysis\Tokenizers\Pattern;

$this->sigmie->newIndex($alias)
        ->tokenizer(new Pattern('/something/'))
        ->create();

$this->newIndex($alias)
        ->tokenizeOnPattern('/something/')
        ->create();
```
