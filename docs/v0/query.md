# Introduction
If you are familiar with Elasticsearch and the Search functionality doesn’t cover your needs, you can of course send any query that you wish to Elasticsearch using the `newQuery` method on the Sigmie client instance.

Below is an example of some of the available options:
```php
$sigmie->newQuery(index: 'disney-movies')
        ->bool(function (Boolean $boolean) {

            $boolean->filter->matchAll();

            $boolean->filter()->multiMatch('goofy', ['name', 'description']);

            $boolean->must->term('is_active', true);

            $boolean->mustNot->term('is_active', false);

            $boolean->mustNot->wildcard('foo', '**/*');

            $boolean->should->bool(fn (Boolean $boolean) => $boolean->must->match('name', 'Mickey'));

        })
        ->sort('name.keyword', 'asc')
        ->fields(['name','description', 'stock', 'rating'])
        ->from(0)
        ->size(15)
        ->getDSL();
```

The above example uses the `getDLS` method to get the underlying JSON query. You can execute the queries directly using the `get` method on the query builder instance.

```php
$newQuery->get();
```

# Boolean
The `boolean`  query is probably the most powerful of the queries available. It allows you to combine queries when searching for documents, and it can be nested multiple times.

You can define a `boolean` query using a callback function as a parameter to the `bool` method like this:
```php
use Sigmie\Query\Queries\Compound\Boolean;

$newQuery->bool(function (Boolean $boolean) {

});
```

The callback gets an instance of the `Sigmie\Query\Queries\Compound\Boolean` class. 

A Boolean query has 4 options for nesting queries inside of them, and each of them handles the nested queries differently.

Those are:
* Must
* Must Not
* Filter
* Should


### Must
For a Document to match the query clause inside of a `must`, it has to match **all** the must’s nested queries.

Look at the following example:
```php
// [tl! collapse:start]
use Sigmie\Query\Queries\Compound\Boolean;

$newQuery->bool(function (Boolean $boolean) {
// [tl! collapse:end]
    $boolean->must()->term('is_active', true);

    $boolean->must()->range('stock', ['>' => 0 ]); 
// [tl! collapse:start]
});
// [tl! collapse:end]
```

In this example, we defined 2 query clauses in the **must** section of the boolean query. One is saying that the matched documents need to have the `is_active` flag set to `true`, and the second says that the Document’s `stock` attribute has to be greater than `0`.

If **all of those conditions** are met, then the Document matches our query. But if only **one or none** of the conditions are met, the Document isn’t matched.

In an SQL example, the above query would look like this:

```sql
SELECT * FROM movies WHERE is_active = TRUE AND stock >= 0;
```

### Must Not
The **Must Not** occurrence type is the opposite of  `must`. This means that **all** queries of the `must_not` occurrence need to be evaluated as `false` for a Document to match the query.

We could convert the above into a `not_must` example like this:
```php
// [tl! collapse:start]
use Sigmie\Query\Queries\Compound\Boolean;

$newQuery->bool(function (Boolean $boolean) {
 // [tl! collapse:end]
    $boolean->mustNot()->term('is_active', false);

    $boolean->mustNot()->term('stock', 0);
// [tl! collapse:start]
 });
// [tl! collapse:end]
```

Now instead of saying that the `is_active` attribute has to be `true`, we are saying that the `is_active` attribute needs to **NOT** be `false` and the `stock` needs to **NOT** be `0`.

And here is an example of the reverted SQL query that the `must_not` example corresponds to.
```sql
SELECT * FROM movies WHERE is_active != FALSE AND stock != 0;
```

### Should
Unlike the `must` and `must_not` the `should`  needs **at least one** query to be true for the Document to match the query. 

In `must` and `must_not` the queries inside them are combined with the **AND** logical operator, and in the `should` they are combined with **OR**.

Look at the example below:
```php
// [tl! collapse:start]
use Sigmie\Query\Queries\Compound\Boolean;

$sigmie->newQuery(index: 'movies')
    ->bool(function (Boolean $boolean) {
// [tl! collapse:end] 
    $boolean->should()->term('category', 'fantasy');

    $boolean->should()->term('category', 'musical');
// [tl! collapse:start]

    });
// [tl! collapse:end]
```

In this example we are looking for Documents whose `category` attribute is `fantasy` **OR** `musical`. If any of those two queries evaluates as true, the Document is a match. 

The above example in an SQL query looks like this:
```sql
SELECT * FROM movies WHERE category = 'fantasy' OR category = 'musical';
```

### Filter
The `filter`  behaves exactly like `must` with one key difference, it **doesn’t affect** scoring.

Unless specified otherwise the Documents that match a query are sorted by **“How well they match the query”**. For this Elasticsearch assigns a float `_score` attribute that indicates the answer to this question.

There are various criteria for how the `_score` is calculated for each query and its field, but the important to know here is that a query inside a `must` influences the `_score` and the `filter` **does NOT**.

Simply: **Filter queries do NOT affect the order in which the hits are returned**.
```php
// [tl! collapse:start]
use Sigmie\Query\Queries\Compound\Boolean;

$sigmie->newQuery(index: 'movies')
    ->bool(function (Boolean $boolean) {
// [tl! collapse:end] 
    $boolean->filter()->term('is_active', true);
// [tl! collapse:start]
    });
// [tl! collapse:end]
```

# Queries
You can use the supported queries as part of a `Boolean` query, or standalone.

In simple scenarios is simple to directly call your query instead of putting it inside of a `Boolean` one.
```php
// [tl! collapse:start]
use Sigmie\Query\Queries\Compound\Boolean;

$sigmie->newQuery(index: 'movies') // [tl! collapse:remove]
    ->bool(function (Boolean $boolean) { // [tl! collapse:remove]
        $boolean->filter()->term('active', true); // [tl! collapse:remove]
    }); // [tl! collapse:remove]

$sigmie->newQuery(index: 'movies')->term('active', true);  // [tl! collapse:add]
```

Now let’s have a look at all Elasticsearch queries supported in Sigmie.

## Range
Using the `range` query you can filter `numbers` and `dates` by range. 
```php
$newQuery->range('count', ['>=' => 233]);
```

The first argument of the `range` query is the **attribute name** and the second argument is an array with the range criteria. 

The supported operators are:
* `>=` Greater of equal than
* `>` Greater than
* `<=` Less or equal than
* `<` Less than

You can pass multiple array items for defining ranges. For example, to match Documents that have a `price` attribute between `30.00` and `130.00`  use the `range` query like this:
```php
$newQuery->range('price', ['>=' => 30.00, '<='=> 130.00]);
```


## Term
The     `term` Query finds exact values of the Document’s attributes.

For example, if we were to find all **active** Documents we would use it as follow: 
```php
$newQuery->term('active', true);
```

Here is another example of how to find a Document that belongs to a user with an of **13**:
```php
$newQuery->term('user_id', 13);
```

@info
Using the `term` Query on text fields isn’t wise as it won’t match the desired Documents because **text fields are analyzed**.
@endinfo

If you plan to use `term` on a Text field, you need to map it also as a `keyword`, for Elasticsearch to also store its raw value.

You can do this by calling the `keyword` method on the properties builder.

```php
$properties->text('category')->keyword(); 
```

This instructs Elasticsearch to also store the field value as it is, without analyzing it. After you have done this the raw value of the `category` is also stored under the `category.keyword` key.

Using this key the `term` query will bring us the expected results.
```php
$newQuery->term('category', 'drama');
```

## Match All
The `matchAll` Query matches **all** the Documents.

```php
$newQuery->matchAll();
```

## Match None
The `matchAll` Query matches **none** of the Documents.

```php
$newQuery->matchNone();
```

## Match
The `match`  Query accepts as the first argument the Document field and as second argument the query value.

Unlike the `term` query passed value is **analyzed**, which makes this the preferred way for searching Text attributes.

```php
$newQuery->match('name', 'mickey');
```

## Multi Match
The `multiMatch` Query is the same as the `match` Query with the difference that the first argument is an array of the Document’s fields.

```php
$newQuery->multiMatch(['name', 'username'], 'mickey');
```

## Exists
The `exists` Query checks if the passed field exists on the Document.
```php
$newQuery->exists();
```

## Ids
The `ids` Query returns the Documents whose `_id` field is passed in the array parameter.
```php
$newQuery->ids(['dkKwMe4UBAUb2dMteRe2','wd6Me4UBAUb2dMJT');
```

## Terms
The `terms` Query is the same as the `term` Query with the difference that the second argument accepts an array of values.

```php
$newQuery->terms(field:'category', values:['horror','action']);
```

## Regex
The `regex`  Query accepts the **field name** as the first argument and a **Regular Expression expression** pattern as a second.

Then it returns the Documents whose attribute matches the Regular Expression.
```php
$newQuery->regex('category','(horror|action)');
```

## Wildcard
The `wildcard` Query accepts the wildcard operator `*` in the value parameter.
```php
$newQuery->wildcard('name','john*');
```

# Boosting
You can use the `boost` parameter to increase the Query importance when the `_score` is calculated.
```php
$newQuery->matchAll(boost: 5);
```

This is useful to say that a match in the `title` attribute is more important than a match in the `tags` attribute.

# Sort
By default, the returned Documents are sorted by the `_score` attribute. You can change this behavior using the `sort` method like below:
```php
$newQuery->sort('name.keyword', 'asc')
        ->sort('_score');
```

In this example, the matched Documents are first **sorted by** the `name.keyword` field, and then by the `_score`.

@info
It’s important to note here, that sorting on Text fields is only possible if they are mapped as Keywords**.
@endinfo

# Pagination

To paginate over your Query results you can use the `from` and `size` methods, which correspond to the SQL `LIMIT` and `OFFSET`.
## From
From is the SQL like `OFFSET`  that defines how many Documents should be skipped when returning the Search results.
```php
$newQuery->from(0);
```

## Size
Size is the SQL  `LIMIT` that defines how many Documents you get when you run your Query.
```php
$newQuery->size(15);
```
