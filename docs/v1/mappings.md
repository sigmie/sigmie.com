# Mappings

You can easily define your index mappings using the `mapping` 
method when creating your **index**. This method expects
a `callable` argument.

The argument is then called with an instance of the `Sigmie\Mappings\Blueprint` class. This class
gives us some nice methods to define our **index** mappings.

```php
use Sigmie\Mappings\Blueprint; // [tl! focus]

$sigmie->newIndex($alias)
    ->mapping(function (Blueprint $blueprint) { // [tl! focus]
        $blueprint->text('title'); // [tl! focus]
        $blueprint->text('description'); // [tl! focus]
        $blueprint->number('adults')->integer(); // [tl! focus]
        $blueprint->number('price')->float(); // [tl! focus]
        $blueprint->date('created_at'); // [tl! focus]
        $blueprint->bool('active'); // [tl! focus]
    })
    ->create();
```
