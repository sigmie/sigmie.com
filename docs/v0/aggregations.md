# Aggregations

## Introduction

```php
$res = $sigmie->newQuery('orders')
    ->matchAll()
    ->aggregate(function (SearchAggregation $aggregation) {
        $aggregation->sum(name:'turnover', field: 'price');
    })
    ->get();

$res->aggregation('turnover.value'); // 54.403 [tl! highlight]
```

## Metrics

### Sum
```php
$aggregation->sum(name:'stock_sum', field:'stock');
```

```sql
SELECT SUM(stock) AS stock_sum;
```

```php
$res->aggregation('stock_sum.value');
```

### Max
```php
$aggregation->max(name:'max_price', field:'price');
```

```sql
SELECT MAX(stock) AS max_price;
```

```php
$res->aggregation('max_price.value');
```

### Min

```php
$aggregation->min(name:'min_price', field:'price');
```

```sql
SELECT MIN(price) AS min_price;
```

```php
$res->aggregation('min_stock.value');
```

### Avg
```php
$aggregation->avg(name:'avg_ranting', field:'count');
```

```sql
SELECT AVG(rating) AS avg_rating;
```

```php
$res->aggregation('avg_ranting.value');
```

### Value Count

```php
$aggregation->valueCount(name:'categories_count', field:'category');
```

```sql
SELECT COUNT(DISTINCT category) AS categories_count;
```

```php
$res->aggregation('categories_count.value');
```

## Bucket

### Stats

```php
| Key          | Stat            |
| ------------ | --------------- |
| "Count"      | 133             |
| "Min"        | 5.33            |
| "Max"        | 128.58          |
| "Average"    | 73.53           |
| "Sum"        | 9779.49         |
```

```php
$aggregation->stats(name:'sales_stats', field:'count');
```

```php
$res->aggregation('sales_stats');
```

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

```php
| Key          | Document Count  |
| ------------ | --------------- |
| "Musical"    | 18              |
| "Adventure"  | 13              |
| "Fantasy"    | 20              |
| "N/A"        | 7               |
```
```php
$aggregation->terms(name:'category_terms', field: 'type')->missing('N/A');
```

```php
$res->aggregation('category_terms.buckets');
```

```php
[
    [
      "key"=> "Misical",
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

```php
| Key          | Document Count  |
| ------------ | --------------- |
| "0-100"      | 803             |
| "100-200"    | 422             |
| "200+"       | 343             |
```

```php
$aggregation->range(name: 'price_ranges', field: 'price', [
    ['key' => '0-100', 'to' => 100 ],
    ['key' => '100-200', 'from'=> 100, 'to' => 200 ],
    ['key' => '200+', 'from' => 200 ],
]);
```

```php
$res->aggregation('price_ranges.buckets');
```

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
