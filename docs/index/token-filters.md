# Token filters

```php
        $this->sigmie->newIndex($alias)
            ->stemming([
                ['am', ['be', 'are']],
                ['mouse', ['mice']],
                ['feet', ['foot']],
            ], 'sigmie_stemmer_overrides')
            ->create();

```

```php
        $this->sigmie->newIndex($alias)
            ->stopwords(['about', 'after', 'again'], 'sigmie_stopwords')
            ->create();
```

```php
        $this->sigmie->newIndex($alias)
            ->unique(name: 'unique_filter', onlyOnSamePosition: true)
            ->create();
```

```php
        $this->sigmie->newIndex($alias)
            ->trim()
            ->create();
```

```php
        $this->sigmie->newIndex($alias)
            ->uppercase(name: 'uppercase_filter_name')
            ->create();
```

```php
        $this->sigmie->newIndex($alias)
            ->twoWaySynonyms([
                ['treasure', 'gem', 'gold', 'price'],
                ['friend', 'buddy', 'partner'],
            ], name: 'sigmie_two_way_synonyms',)
            ->create();
```

```php
        $index = $this->sigmie->newIndex($alias)
            ->synonyms([
                'ipod' => ['i-pod', 'i pod'],
                ['treasure', 'gem', 'gold', 'price'],
            ])
            ->create();
```

```php
        $this->sigmie->newIndex($alias)
            ->lowercase('custom_lowercase')
            ->create();
```

```php
        $this->sigmie->newIndex($alias)
            ->oneWaySynonyms([
                ['ipod', ['i-pod', 'i pod']],
            ], name: 'sigmie_one_way_synonyms',)
            ->create();
```
