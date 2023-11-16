# Aggregations

Aggregations provide a way to perform complex data analysis on your documents. They allow you to group your data in various ways and perform calculations on each group, such as summing, averaging, or counting the documents in each group.

Here's an example of how to use aggregations:

```php
use Sigmie\Query\Aggs;

$res = $sigmie->newQuery('orders')
    ->matchAll()
    ->aggregate(function (Aggs $aggregation) {
        $aggregation->sum(name:'turnover', field: 'price');
    })
    ->get();

$res->aggregation('turnover.value'); // 54.403 [tl! highlight]
```

## Metrics

Metric aggregations are simple aggregations that yield a **single value**. They are used to perform simple calculations on the numeric values of your documents.

Sigmie supports the following metric aggregations:

### Sum
The sum aggregation returns the total sum of a numeric field. This is useful when you want to calculate the total value of a specific field across all documents.
```php
$aggregation->sum(name:'stock_sum', field:'stock');
```

Equivalent SQL:
```sql
SELECT SUM(stock) AS stock_sum;
```

Accessing the result:
```php
$res->aggregation('stock_sum.value');
```

### Max
The max aggregation returns the maximum value of a numeric field. This is useful when you want to find the highest value of a specific field across all documents.
```php
$aggregation->max(name:'max_price', field:'price');
```

Equivalent SQL:
```sql
SELECT MAX(stock) AS max_price;
```

Accessing the result:
```php
$res->aggregation('max_price.value');
```

### Min
The min aggregation returns the minimum value of a numeric field. This is useful when you want to find the lowest value of a specific field across all documents.
```php
$aggregation->min(name:'min_price', field:'price');
```

Equivalent SQL:
```sql
SELECT MIN(price) AS min_price;
```

Accessing the result:
```php
$res->aggregation('min_stock.value');
```

### Avg
The average aggregation returns the average value of a numeric field. This is useful when you want to calculate the average value of a specific field across all documents.
```php
$aggregation->avg(name:'avg_rating', field:'count');
```

Equivalent SQL:
```sql
SELECT AVG(rating) AS avg_rating;
```

Accessing the result:
```php
$res->aggregation('avg_rating.value');
```

### Value Count
The value count aggregation returns the count of unique values for a field. This is useful when you want to count the number of unique values of a specific field across all documents.
```php
$aggregation->valueCount(name:'categories_count', field:'category');
```

Equivalent SQL:
```sql
SELECT COUNT(DISTINCT category) AS categories_count;
```

Accessing the result:
```php
$res->aggregation('categories_count.value');
```

## Bucket
Bucket aggregations don't calculate metrics over fields like the previous examples (min, avg, value count). Instead, they create buckets of documents. Each bucket is associated with a criterion (depending on the aggregation type) which determines whether a document in the current context falls into it. This is useful when you want to group your documents based on certain criteria and perform calculations on each group.

@info
The difference between bucket aggregations and metric aggregations is that bucket aggregations provide a way of grouping documents in a more complex way than just by a specific field. They allow you to categorize documents into "buckets" based on a certain criterion, and then calculate metrics for each of these buckets.
@endinfo

#### Stats
The stats aggregation provides a quick summary of the distribution of a set of data. This is useful when you want to get a quick overview of the statistical distribution of a specific field across all documents.
```php
$aggregation->stats(name:'sales_stats', field:'count');
```

Accessing the result:
```php
$res->aggregation('sales_stats');
```

The result will be an array with the following keys:
```php
[
   "count" => 133,
   "min"   => 5.33,
   "max"   => 128.58,
   "avg"   => 73.53,
   "sum"   => 9779.49,
]
```


### Terms
The terms aggregation is used to group your documents based on the unique values of a specific field. This is useful when you want to categorize your documents based on the unique values of a specific field and count the number of documents in each category.
```php
$aggregation->terms(name:'category_terms', field: 'type')->missing('N/A');
```

Accessing the result:
```php
$res->aggregation('category_terms.buckets');
```

Here is the actual array of buckets, each represented as an array with a key and a document count:
```php
[
    [
      "key"=> "Musical",
      "doc_count"=> 18 
    ],
    [
      "key"=> "Adventure",
      "doc_count"=> 13 
    ],
    [
      "key"=> "Fantasy",
      "doc_count"=> 20 
    ],
    [
      "key"=> "N/A",
      "doc_count"=> 7 
    ]
]
```

### Range
The range aggregation is used to group your documents based on ranges of numeric values. This is useful when you want to categorize your documents based on ranges of a specific numeric field and count the number of documents in each range.

```php
$aggregation->range(name: 'price_ranges', field: 'price', [
    ['key' => '0-100', 'to' => 100 ],
    ['key' => '100-200', 'from'=> 100, 'to' => 200 ],
    ['key' => '200+', 'from' => 200 ],
]);
```

Accessing the result:
```php
$res->aggregation('price_ranges.buckets');
```

The result will be an array of buckets, each represented as an array with a key and a document count:
```php
[
    "0-100" => [
      "to"=> 100.0,
      "doc_count"=> 803
    ],
    "100-200"=> [
      "from"=> 100.0,
      "to"=> 200.0,
      "doc_count"=> 422
    ],
    "200+" => [
      "from"=> 200.0,
      "doc_count"=> 343
    ],
]
```
