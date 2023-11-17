## Introduction

Sigmie includes Elasticsearch native fields like **Keyword** and **Text** (for unstructured text), as well as high-level fields such as **Name**, **Tags**, or **Category**.

These high-level fields are essentially wrappers around the native Elasticsearch fields, optimized for specific use cases.

## Properties

Consider a scenario where we have a `users` index and we're storing the user’s address in a **Text** field named `address`.

When creating our index, we initialize the `NewProperties` class and call the `address` method on it.

This instance of `NewProperties` is then passed to the `properties` method on the Index builder.

Once the Index is created, we use the same properties to perform searches.

Here's an example:

```php
use Sigmie\Mappings\NewProperties;

$properties = new NewProperties;
$properties->address();

$sigmie->newIndex(name: 'users')->properties($properties)->create();

$sigmie->newSearch()->properties($properties)->get();
```

## Native Types

Let's explore the native Elasticsearch types supported by Sigmie.

### Text

Text is often the most used field when using Elasticsearch for full-text search. By default, Elasticsearch treats indexed string fields as **unstructured text**, such as an article or a book description.

#### Unstructured Text

```php
$properties->text('description');
```

You can also explicitly specify that your `string` is an **unstructured text** by chaining the `unstructuredText` method.

```php
$properties->text('description')
           ->unstructuredText();  // [tl! add]
```

#### Search-as-you-Type

You can define a **search-as-you-type** field using `searchAsYouType` and removing the `unstructuredText` method.

```php
$properties->text()
           ->unstructuredText();  // [tl! remove]
           ->searchAsYouType();  // [tl! add]
```

#### Index Prefixed

You can instruct Elasticsearch to **index** field term prefixes by calling the `indexPrefixes` method. This is useful if you plan to use the `Prefix` query on this attribute.

```php
$properties->text('description')
           ->unstructuredText();
           ->indexPrefixes(); // [tl! add]
```

#### Keyword

If you need to use **filter** or **sort** on your `text` field, you need to chain the `keyword` method. This will store the field one more time with the `.keyword` suffix.

For example, we have the `description` field that is analyzed and can be used for querying, and we also have the `description.keyword` field that’s stored as it is, allowing us to use it for aggregations, sorting, and filtering.

```php
$properties->text('description')->keyword();
```

### Keyword

The `keyword` field type stores your field **as-it-is** without any analysis.

```php
$properties->keyword('ISBN');
```

### Number

You can map numbers with the `number` method, which maps them as `integers` by default. You can chain the corresponding **number type** to specify a number type different from an `int`.

```php
$properties->number('rating')->float();
```

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

@info
If your time format is different, you can pass the preferred **Elasticsearch format** as an argument to the `date` method.

```php
$properties->date('MM/dd/yyyy');
```
@endinfo

## High-level types

High-level types are field types that aren’t directly supported in Elasticsearch. They are created by Sigmie and optimized for the types they represent.

### Searchable Number

The **Searchable Number** field represents a number that can be searched by an input field.

```php
$properties->searchableNumber('birth_year');
```

Normally, users won't input the product stock in a search field, so it wouldn't be wise to use it for a `stock` property of a document. However, if you're storing `users` in your search index, you might want to find users by the `birth_year`.

In this case, it would be beneficial to map the property as a `searchableNumber`.

Some field examples for a **Searchable Number** are:

-   Year
-   Reservation number

### Name

Name fields are optimized for storing and searching names.

```php
$properties->name(); // username, city, country
```

Some field examples for a **Name** mapping are:

-   Username
-   City Name
-   Company Name

### Title

The `title` field is optimized for storing various **Titles**.

```php
$properties->title(); // movie, book / short string
```

Some field examples for a **Title** mapping are:

-   Movie Title
-   Book Title
-   Any short string
-   A Sentence

### HTML

The HTML field strips the HTML tags from the field.

```php
$properties->html();
```

This is normally useful for data that are crawled from a website.

### Case Sensitive Keyword

By default, the **Keyword** mapped strings are lowercase. If your **Keyword** is case-sensitive, you can use the `caseSensitiveKeyword` mapping.

```php
$properties->caseSensitiveKeyword();
```

### Category

The `category` field is used for fields that distinguish Documents into categories.

```php
$properties->category();
```

Some field examples for a **Category** mapping are:

-   Movie Category Horror, Action
-   Shoe Category eg. Running, sneakers
-   Car Manufacturer eg. Hyundai, Ford, BMW

### Long Text

Long Text is used for large string fields.

```php
$properties->longText();
```

Some field examples for a **Long Text** mapping are:

-   Description
-   Comment
-   Book Summary

### Id

Id fields are optimized for filtering and grouping.

```php
$properties->id(); // user_id, product_id, category_id (filterable)
```

Some field examples for an **Id** mapping are:

-   A Database Primary key `id`
-   A Database Foreign key like `user_id` or `category_id`

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

-   Product sizes `S|M|L|XL`
-   Tags `travel, laugh, happy,`

### Price

The **Price** field is optimized for **range queries** since it’s unlikely that a user searches by a price.

```php
$properties->price(); // price
```

## Property classes

You can also define your own custom property types. Below is an example of how you may create a `Color` mapping type.

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

In the `configure` method, you specify the Elasticsearch native field type. In our example, we are mapping the color as a native unstructured text field to use it with a `Match` query. Then by calling the `indexPrefixes`, we tell Elasticsearch to index the prefixes since we plan to use a `Prefix` query on it. Lastly, we save the `raw` value to use it with a `Term` query.

Since colors can have two or more words (eg. sky blue) we define a custom field analyzer that splits the string into tokens whenever it encounters a **whitespace** and also **lowercases** all tokens.

Once you have created your `Color` type class, you can pass it to the `type` method of the properties builder instance.

```php
$newProperties->type(new Color)
```

This will map the `color` attribute field to the `Color` class.

<!-- ### Examples

```bash
Elasticsearch
├─ Text
├─ Number
├─ Boolean
├─ Date
```

```bash
Sigmie
├─ Title
├─ Name
├─ Category
├─ Long Text
├─ Searchable Number
├─ Id
├─ Email
├─ Address
├─ Tags
├─ Price
├─ HTML
├─ Case Sensitive Keyword
```

```json
{
  "event_id": "296147634", // number
  "name": "20th Athens Laravel meetup: Honey-Driven Development", // text
  "category": "Web Development", // text
  "date": "2024-02-19", // date
  "location": "Karagiorgi Servias 9, Athina", // text
  "speaker": "Winnie the Pooh" // text
}
```

```json
{
  "event_id": "296147634", // id
  "name": "20th Athens Laravel meetup: Honey-Driven Development", // title
  "category": "Web Development", // category
  "date": "2024-02-19", // date
  "location": "Karagiorgi Servias 9, Athina", // address 
  "speaker": "Winnie the Pooh" // name 
}
``` -->
