# Nova

## Score
```php
Text::make('Score', function () {

    $score = $this->hit['_score'] ?? '0';

    return $score;
})
->showOnPreview()
->readonly(true)
->asHtml(),
```

## Highlight
```php
Text::make('Name', function () {

    return $this->hit['highlight']['name'][0] ?? $this->name;
})
->showOnPreview()
->asHtml();
```
