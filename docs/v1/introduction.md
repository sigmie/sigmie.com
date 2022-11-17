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

<div class="mermaid mx-auto h-auto" style="width: 24rem">
    graph TD;
    Document1-->Index;
    Document2-->Index;
    Document3-->Index;
    Search-->Index;
    Index-->Search;
</div>
