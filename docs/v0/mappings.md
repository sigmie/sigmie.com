## Introduction
We support more than the traditional JSON fields (string, integer, and boolean). 

For example, you can use the Elasticsearch native fields **Keyword** and **Text** (unstructured text) or high-level fields like **Name**, **Tags**, or **Category** that are included.

The high-level fields are nothing more than just wrappers around the **native Elasticsearch** fields that are optimized for specific cases.

## Properties
Let’s imagine that we have a `users` index and are saving the user’s address in a **Text** field called `address`. When we create our index we initialize the `NewProperties` class and call the `address` method on it.

Next, we pass the instance of `NewProperties` to the `properties` method on the Index builder.

Once our Index is created we use the same properties to search in it.

Here is an example of what this looks like:

```php
use Sigmie\Mappings\NewProperties;

$properties = new NewProperties;
$properties->address();

$sigmie->newIndex(name: 'users')->properties($properties)->create();

$sigmie->newSearch()->properties($properties)->get();
```

## Native Types
Let’s have a look at the native Elasticsearch types that are supported by Sigmie at the moment.

### Text
Text is probably the most used field when using Elasticsearch as a full-text search solution. By default, Elasticsearch assumes that the indexed string field is an **unstructured text** like an article or a book description.

#### Unstructured Text
```php
$properties->text('description');
```

You can also explicitly say that your `string` is an **unstructured text** chaining the `unstructuredText` method.

```php
$properties->text('description')
           ->unstructuredText();  // [tl! add]
```

#### Search-as-you-Type
Define a **search-as-you-type** field using `searchAsYouType` and removing the `unstructuredText` mtehod.
```php
$properties->text()
           ->unstructuredText();  // [tl! remove]
           ->searchAsYouType();  // [tl! add]
```

#### Index Prefixed

Additionally, you can tell Elasticsearch to **index** field term prefixes by calling the `indexPrefixes` method.
```php
$properties->text('description')
           ->unstructuredText();
           ->indexPrefixes(); // [tl! add]
```

This is a good idea if you plan to use thr `Prefix` query on this attribute.

#### Keyword

If you need to use **filter** or **sort** on your `text` field, you need to chain the `keyword` method.

```php
$properties->text('description')->keyword(); 
```

This will save you the field one more time with the `.keyword` suffix. In the above example, we have the `description` field that is analyzed and we can use it for querying, and we also have the `description.keyword` field that’s stored as it is allowing us to use it for aggregations, sorting, and filtering.

### Keyword
There is the `keyword` field type that stores your field **as-it-is** without analyzing it at all.

```php
$properties->keyword('ISBN');
```

### Number

You can map numbers with the `number` method, which maps them as `integers` by default.
```php
$properties->number('rating')->float();
```

You can chain the corresponding **number type** to specify a number type different that an `int`. 
#### Float
A property of type `float`.
```php
$properties->number('rating')->float();
```
#### Integer
A property of type `int`.
```php
$properties->number()->integer();
```

### Boolean
A `boolean` property.
```php
$properties->boolean('is_active'); 
```
### Date
A property that contains a `DateTime` string in the `Y-m-d H:i:s.u` **PHP** format.
```php
$properties->date('created_at'); 
```

Here is how you can format your `Date` instances to the **default** date field format.
```php
(new Date)->format('Y-m-d H:i:s.u');
```

In case your time format is different you can pass the preferred **Elasticsearch** format as an argument to the `date` method.

Eg. 
```php
$properties->date('MM/dd/yyyy');
```

## High-level types
High-level types are field types that aren’t supported directly in Elasticsearch. They are created by Sigmie and optimized for the types they represent.

### Searchable Number
The **Searchable Number** field represents a number that can be searched by an input field.
```php
$properties->searchableNumber('birth_year');
```
Normally you users won’t write the product stock in a search input, therefore it isn’t wise to use it for a `stock` property of a document. 

But let’s have another look at a scenario where you store `users` in your search index. You may find yourself trying to find users by the `birth_year`. In this case, it would be beneficial to map the property as a `searchableNumber`.

Some field examples for a **Searchable Number** are:
* Year
* Reservation number

### Name
Name fields are optimized for storing and searching names.
```php
$properties->name(); // username, city, country
```

Some field examples for a **Name** mapping are:
*  Username
* City Name
* Company Name

### Title
The `title` field is optimized for storing various **Titles**.
```php
$properties->title(); // movie, book / short string
```

Some field examples for a **Title** mapping are:
* Movie Title
* Book Title
* Any short string
* A Sentence

### HTML
The HTML field strips the HTML tags from the field.
```php
$properties->html();
```

This is normally useful for data that are crawled from a website.

### Case Sensitive Keyword
By default, the **Keyword** mapped strings are lowercase. In case your **Keyword** is case-sensitive you can use the `caseSensitiveKeyword` mapping.
```php
$properties->caseSensitiveKeyword();
```

### Category
The `category` field, is used for fields that distinguish Documents into categories.
```php
$properties->category();
```

Some field examples for a **Category** mapping are:
* Movie Category Horror, Action
* Shoe Category eg. Running, sneakers
* Cat Manufacture eg. Hunday, Ford, BMW

### Long Text
Long Text for big string fields.
```php
$properties->longText();
```

Some field examples for a **Long Text** mapping are:
* Description
* Comment
* Book Summary

### Id
Id fields are optimized for filtering a grouping.
```php
$properties->id(); // user_id, product_id, category_id (filterable) 
```

Some field examples for an **Id** mapping are:
* A Database Primary key `id`
* A Database Foreign key like `user_id` or `category_id`

### Email
Email field optimized for emails.
```php
$properties->email(); // email
```

### Address
Address field optimized for location addresses.
```php
$properties->address(); // address
```

### Tags
The **Tags** field is optimized for fields that contain multiple values separated by a word boundary.
```php
$properties->tags(); // tags
```

Some field examples for a **Tag** mapping are:
* Product sizes `S|M|L|XL`
* Tags `travel, laugh, happy,`


### Price
The **Price** field is optimized for **range queries** since it’s unlikely that a user searches by a price.
```php
$properties->price(); // price
```

## Property classes
You can also define your own custom property types.

Below is an example of how you may create a `Color` mapping type.
```php
use Sigmie\Index\NewAnalyzer;
use Sigmie\Query\Queries\Term\Prefix;
use Sigmie\Query\Queries\Term\Term;
use Sigmie\Query\Queries\Text\Match_;

class Color extends Text
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

In the `configure` method you specify the Elasticsearch native field type. In our example, we are mapping the color as a native unstructured text field to use it with a `Match` query. 

Then by calling the `indexPrefixes` we tell Elasticsearch to index the prefixes since we plan to use a `Prefix` query on it. 

And lastly, we save the `raw` value to use it with a `Term` query. 

Since colors can have two or more words (eg. sky blue) we define a custom field analyzer that splits the string into tokens whenever it encounters a **whitespace** and also **lowercases** all tokens.

Once you have created your `Color` type class, you can pass it to the `type` method of the properties builder instance.

```php
$newProperties->type(new Color)
```

This will map the `color` attribute field to the `Color` class.
