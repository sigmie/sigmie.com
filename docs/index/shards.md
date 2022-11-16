# Shared

```php
        $this->sigmie->newIndex($alias)
            ->shards(4)
            ->replicas(3)
            ->create();
```

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
