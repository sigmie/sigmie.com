## Introduction
Once you've indexed all your documents into an index, you'll want to start searching. However, "searching" in Elasticsearch can mean many different things. To search for your indexed documents, you'll need to make decisions like "Title is more important than a tag," and these decisions can be even more complex when you need to filter and sort the matches.

Even after you've made these decisions, converting them into an Elasticsearch query can be quite challenging, especially if you're not familiar with it. Sigmie provides an abstraction that simplifies this process by providing more user-friendly APIs.

Let's see how you can do this using an example from the fairy tales open domain.

Imagine having the following documents:

```php    
[
    new Document([
        'name' => 'Snow White',
        'description' => 'Adventure in the woods'
    ]),
    new Document([
        'name' => 'Cinderella',
        'description' => 'Lost her glass slipper'
    ]),
    new Document([
        'name' => 'Sleeping Beauty',
        'description' => 'Cursed to sleep for a hundred years'
    ]),
]
```

Like when defining an index, we need an instance of `NewProperties` that we will pass to the `NewSearch` builder class.

In our case, the properties look like this:

```php
use Sigmie\Mappings\NewProperties;

$properties = new NewProperties;
$properties->name();
$properties->text('description');

```

You can find a deeper explanation of properties in the Mappings section.

Now that we have our properties defined, we can use them to search for our documents.

Let's have a look at a full example where we search for the `Snow White` **query string**. 

```php
 // [tl! collapse:start]
use Sigmie\Mappings\NewProperties;

$index->merge([
    new Document([
        'name' => 'Snow White',
        'description' => 'Adventure in the woods'
    ]),
    new Document([
        'name' => 'Cinderella',
        'description' => 'Lost her glass slipper'
    ]),
    new Document([
        'name' => 'Sleeping Beauty',
        'description' => 'Cursed to sleep for a hundred years'
    ]),
]);

$properties = new NewProperties;
$properties->name();
$properties->text('description');
// [tl! collapse:end]
$sigmie->newSearch(index:'fairy-tales')
       ->properties($properties) // [tl! highlight]
       ->queryString('snow white') // [tl! highlight]
       ->get()
       ->json('hits');
```

In the above example, we passed the properties to the `properties` method. This way, Sigmie knows how to search for each property.

In the `queryString`, we pass the `string` that we are searching for, and after we call the `get` method, we receive an instance of `ElasticsearchResponse`.

We can access the matches **hits** by passing the `’hits'` key to the `json` method.

## Query String and Properties
Properties and the Query string are the 2 required parameters that the search builder needs.

```php
$sigmie->newSearch(index:'fairy-tales')
       ->properties($properties) // [tl! highlight]
       ->queryString('Snow White') // [tl! highlight]
       ->get();
```


## Searchable Attributes
To search only for specific fields in Sigmie, you can use the `fields` method on the `NewSearch` builder instance. The `fields` method allows you to only look for the query string in the specific fields.

Here’s an example of how you might use it.

```php
$sigmie->newSearch(index:'fairy-tales')  // [tl! collapse:start]
       ->properties($properties)
       ->queryString('Snow White') // [tl! collapse:end]
       ->fields(['name']) // [tl! highlight]
       ->get();
```

In this example, we query the `fairy-tales` Index for the `Snow White` query string, and we are looking only in the `name` attribute.

## Retrievable Attributes

To only retrieve some attributes of the documents, use the `retrieve` method. This method accepts an array of the attributes that you want to retrieve.

Here’s an example of how you to use it to retrieve **only** the `description` attribute.

```php
$sigmie->newSearch(index:'fairy-tales') // [tl! collapse:start]
       ->properties($properties)
       ->queryString('Snow White') // [tl! collapse:end]
       ->retrieve(['description']) // [tl! highlight]
       ->get();
```

In the above example, we passed an array containing the `description` key that should be retrieved.

## Sorting
To sort the records returned from Elasticsearch, you can use the `sort` method. This method expects a string with the attributes and their sorting direction.

Here is an example of how you can use it:
```php
$sigmie->newSearch(index:'fairy-tales') // [tl! collapse:start]
       ->properties($properties)
       ->queryString('Snow White') // [tl! collapse:end]
       ->sort('name:asc year:desc') // [tl! highlight]
       ->get();
```

This code sorts the **matched hits** first by their `name` in ascending direction, and secondly by the `description` in descending order. 

By default, the matched hits are sorted by their `_score`, which shows how well a document matches the query.

You can also use  `_score` in the sort string like this:

```php
$sigmie->newSearch(index:'fairy-tales') // [tl! collapse:start]
       ->properties($properties)
       ->queryString('Snow White') // [tl! collapse:end]
       ->sort('_score name:asc') // [tl! highlight]
       ->get();
```

This will sort the **hits** first by their `_score` and then ascending by their `name`  attribute.

## Filtering

To filter the results of a search query in Sigmie, you can use the `filter` method on the search builder instance. Here is an example of how you could use this method:

```php
$sigmie->newSearch(index:'fairy-tales') // [tl! collapse:start]
       ->properties($properties) 
       ->queryString('Sleeping Beauty') // [tl! collapse:end]
       ->filter('stock>0') // [tl! highlight]
       ->get();
```

This code will look into the `fairy-tales` for the `Sleeping Beauty` string, but **only** in the documents whose `stock` attribute **is greater** than zero.

You can also chain multiple filter clauses to create more complex filters. For example:

```php
$sigmie->newSearch(index:'fairy-tales') // [tl! collapse:start]
       ->properties($properties) 
       ->queryString('Sleeping Beauty') // [tl! collapse:end]
       ->filter('stock>0 AND is:active AND NOT category:"Drama"') // [tl! highlight]
       ->get();
```

This code will search for records that match the query “Sleeping Beauty”, and have a `stock` greater than 0, an `active: true` attribute and their `category`  attribute is not `Drama`.

You can find more information about all the available filter clauses in the Filter Parser section.

## Typo-Tolerant Attributes

The `typoTolerance` method specifies how tolerant the search should be to spelling errors. This is useful for handling typos and other small errors that users may make when entering a search query.

The `oneTypoChars` and `twoTypoChars`  parameters determine the appropriate typo tolerance according to the length of the search term.

The default value for `oneTypoChars`  is `3` which means one typo is allowed once the search term has a length of 3 chars.

Next, the default value for `twoTypoChars` is `6` which again means **two** typos are allowed once the search term has a length of 6 chars

```php
$sigmie->newSearch(index:'fairy-tales') // [tl! collapse:start]
       ->properties($properties) 
       ->queryString('Sleeping Beauty') // [tl! collapse:end]
       ->typoTolerance(oneTypoChars: 3, twoTypoChars: 6)
       ->get();
```

You can combine the `typoTolerance` method with the `typoTolerantAttributes` where you can specify which attributes are typo tolerant.

Here is an example of how you can use it:
```php
$sigmie->newSearch(index:'fairy-tales') // [tl! collapse:start]
       ->properties($properties) 
       ->queryString('Sleeping Beauty') // [tl! collapse:end]
       ->typoTolerance(oneTypoChars: 3, twoTypoChars: 6)
       ->typoTolerantAttributes(['name']) // [tl! highlight]
       ->get();
```

This code accepts spelling errors only on the `name` attribute.

## Highlighting

To **highlight** the matching text, you can use the `highlighting` method to specify which attributes you want to highlight and what the highlighting prefix and suffix should be.

For example, you can use `<span class="font-bold">` as `prefix` and `</span>` as `suffix`. This way, you can directly display the **highlighted parts** in your application’s HTML.

The code to do this looks like this:
```php
$sigmie->newSearch(index:'fairy-tales') // [tl! collapse:start]
       ->properties($properties) 
       ->queryString('sleeping beauty') // [tl! collapse:end]
       ->highlighting(['name',], prefix:'<span class="font-bold">', suffix:'</span>')
       ->get();
```

In this code example, the first parameter of the `highlighting` function gets an array with the attributes that we want to highlight.

## Weight
The `weight` method specifies the relative importance of a field when boosting Clauses for a search query. This parameter allows you to influence the relevance score of Documents based on the values in specific fields.

The `weight` method accepts an array where the key is the attribute names and values is the attribute importance.

Here is an example:
```php
$sigmie->newSearch(index:'fairy-tales') // [tl! collapse:start]
       ->properties($properties) 
       ->queryString('sleeping beauty') // [tl! collapse:end]
       ->weight([ 'name'=> 4, 'description'=> 1])
       ->get();
```

In this code, we said that `name` importance score is `4`  and the `description` importance score is `1`. 

Now if a match is found in the `name` field in one document and in the `description` field in another document, the document with the query term in its `name` will be **scored higher** than the one with the match in the `description` field.

## Pagination
The `from` and `size` methods on the search builder can be used to specify the number of hits that should be skipped and the maximum number of records that should be returned by the search.

For example, you could use the `from` and `size` methods to retrieve the second page of hits, with 10 hits per page, like this:

```php
$sigmie->newSearch(index:'fairy-tales') // [tl! collapse:start]
       ->properties($properties) 
       ->queryString('sleeping beauty') // [tl! collapse:end]
       ->from(10) // [tl! highlight]
       ->size(10) // [tl! highlight]
       ->get();
```

This code will skip the first 10 records from the hits, and then return the next 10 hits.

The `from` method specifies the number of records that should be skipped, while the `size` method specifies the maximum number of hits that should be returned.

In this example, the combination of the `from` and `size` values creates a paginated result set with 10 hits per page. You can use these methods to paginate the results of a search and split them into smaller, more manageable pages.
