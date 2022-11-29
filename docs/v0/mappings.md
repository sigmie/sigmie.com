# Mappings

## Introduction

## Properties

```php
use Sigmie\Mappings\NewProperties;

$properties = new NewProperties;
$properties->address();

$sigmie->newIndex(name: 'users')->properties($properties)->create();

$sigmie->newSearch()->properties($properties)->get();
```

## Native Types

### Text

#### Unstructured Text
```php
$properties->text();
```

```php
$properties->text()
           ->unstructuredText();  // [tl! add]
```

#### Search-as-you-Type
```php
$properties->text()
           ->unstructuredText();  // [tl! remove]
           ->searchAsYouType();  // [tl! add]
```

#### Index Prefixed
```php
$properties->text()
           ->unstructuredText();
           ->indexPrefixes(); // [tl! add]
```

#### Keyword

```php
$properties->text()->keyword(); 
```

### Keyword
```php
$properties->keyword();
```

## Number

#### Float
```php
$properties->number()->float();
```
#### Integer

```php
$properties->number()->integer();
```

### Boolean
```php
$properties->boolean(); 
```
### Date
```php
$properties->date(); 
```

#### Format
```php
$date->format->('Y-m-d H:i:s.u');
```

## More types

#### Searchable Number
```php
$properties->searchableNumber();
```
* Year

#### Name
```php
$properties->name(); // username, city, country
```
* Username
* City Name
* Company Name

#### Title
```php
$properties->title(); // movie, book / short string
```
* Movie title
* Book title
Short string

#### HTML
```php
$properties->html();
```

#### Case Sensitive Keyword

```php
$properties->caseSensitiveKeyword();
```
* Don't know

#### Category
```php
$properties->category();
```

* Movie Category, Horror etc.
* Shoe Category Running, sneakers
* Cat Manufacture Hunday, Ford, BMW

#### Long Text
```php
$properties->longText();
```
* Description
* Comment
* Book Summary

#### Id
```php
$properties->id(); // user_id, product_id, category_id (filterable) 
```
* Foreign id
* user_id
* category_id

#### Email
```php
$properties->email(); // email
```


#### Address
```php
$properties->address(); // address
```

#### Tags
```php
$properties->tags(); // tags
```

#### Price
```php
$properties->price(); // price
```

## Custom Types

#### Callback
```php
use Sigmie\Index\NewAnalyzer;
use Sigmie\Query\Queries\Term\Prefix;
use Sigmie\Query\Queries\Term\Term;
use Sigmie\Query\Queries\Text\Match_;

$blueprint->text('color')
    ->unstructuredText()
    ->indexPrefixes()
    ->keyword()
    ->withNewAnalyzer(function (NewAnalyzer $newAnalyzer) {
        $newAnalyzer->tokenizeOnWhitespaces();
        $newAnalyzer->lowercase();
    })->withQueries(function (string $queryString) {
        return [
            new Match_('color', $queryString),
            new Prefix('color', $queryString),
            new Term("color.keyword", $queryString)
        ];
    });
```

#### Property classes

```php
use Sigmie\Index\NewAnalyzer;
use Sigmie\Query\Queries\Term\Prefix;
use Sigmie\Query\Queries\Term\Term;
use Sigmie\Query\Queries\Text\Match_;

class Color extends Text implements Configure, Analyze
{
    public string $name = 'color';

    public function configure(): void
    {
        $this->unstructuredText()->indexPrefixes()->keyword();
    }

    public function analyze(NewAnalyzer $newAnalyzer): void
    {
        $newAnalyzer->tokenizeOnWhitespaces();
        $newAnalyzer->lowercase();
    }

    public function queries(string $queryString): array
    {
        return [
            new Match_($this->name, $queryString),
            new Prefix($this->name, $queryString),
            new Term("{$this->name}.keyword", $queryString)
        ];
    }
}
```
```php
$newProperties->type(new Color)
```

## Mappings

You can easily define your index mappings using the `mapping` 
method when creating your **index**. This method expects
a `callable` argument.

The argument is then called with an instance of the `Sigmie\Mappings\Blueprint` class. This class
gives us some nice methods to define our **index** mappings.
```php
$properties->text('title');
$properties->text('title')->searchAsYouType();

$properties->number('adults')->integer();
$properties->number('price')->float();

$properties->date('created_at');

$properties->bool('active');
```
