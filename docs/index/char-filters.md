# Char Filters

Generate authentication keys to authenticate your API.

## Pattern

```php
use Sigmie\Index\Analysis\CharFilter\Pattern;

$sigmie->newIndex($alias)
            ->charFilter(new PatternCharFilter('pattern_char_filter', '/foo/', '$1'))
            ->create();

$this->newIndex($alias)
        ->patternReplace('/foo/','$1')
        ->create();
```

## Char Mapping
```php
use Sigmie\Index\Analysis\CharFilter\Mapping;

$this->sigmie->newIndex($alias)
            ->charFilter(new Mapping('sigmie_mapping_char_filter', ['a' => 'bar', 'f' => 'foo']))
            ->create();

$sigmie->newIndex($alias)
       ->mapChars([
        ':)'=> 'happy',
        ':('=> 'sad',
       ]);
       ->create();
```

## HTML Strip
```php
use Sigmie\Index\Analysis\CharFilter\HTMLStrip;

        $this->sigmie->newIndex($alias)
            ->charFilter(new HTMLStrip())
            ->create();

        $this->newIndex($alias)
        ->stripHTML()
        ->create();
```
