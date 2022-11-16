# Introduction

## Types

```php
use Sigmie\Index\Analysis\CharFilter\Pattern;

$sigmie->newIndex($alias)
            ->charFilter(new PatternCharFilter('pattern_char_filter', '/foo/', '$1'))
            ->create();

$this->newIndex($alias)
        ->patternReplace('/foo/','$1')
        ->create();
```
