# Aggregations

## Introduction

```php
$this->sigmie->newIndex($name)->create();

$collection = $this->sigmie->collect($name, refresh: true);

$docs = [
    new Document([
        'date' => '2020-01-01',
    ]),
    new Document([
        'date' => '2019-01-01',
    ]),
    new Document([
        'date' => '2018-01-01',
    ]),
    new Document([
        'date' => '2018-01-01',
    ]),
    new Document([
        'name' => 'nico',
    ]),
    new Document([
        'date' => '2016-01-01',
    ]),
    new Document([
        'date' => '1999-01-01',
    ]),
];

        $collection->merge($docs);

        $res = $this->sigmie->newQuery($name)
            ->matchAll()
            ->aggregate(function (SearchAggregation $aggregation) {
                $aggregation->dateHistogram('histogram', 'date', CalendarInterval::Year)
                    ->aggregate(function (SearchAggregation $aggregation) {
                        $aggregation->dateHistogram('histogram_nested', 'date', CalendarInterval::Day)
                            ->missing('2021-01-01');
                    })
                    ->missing('2021-01-01');
            })
            ->get();

$value = $res->aggregation('histogram');
```

## Metrics

### Sum
```php
$aggregation->sum(name:'stock_sum', field:'stock');
```

```sql
SELECT SUM(stock) AS stock_sum FROM movies;
```

### Max
```php
$aggregation->max('max_stock', 'count');
```

```sql
SELECT MAX(stock) AS max_stock FROM movies;
```

### Min
```php
$aggregation->min(name:'min_stock', field:'count');
```
```sql
SELECT MIN(stock) AS min_stock FROM movies;
```

### Avg
```php
$aggregation->avg(name:'avg_ranting', field:'count');
```

```sql
SELECT AVG(rating) AS avg_rating FROM movies;
```

### Value Count

```php
$aggregation->valueCount(name:'categories_count', field:'category');
```

```sql
SELECT COUNT(DISTINCT category) AS categories_count FROM movies;
```

## Bucket

### Significant Text
```php
$aggregation->significantText('significant', 'title');
```

### Stats
```php
$aggregation->stats('stats', 'count');
```


### Terms
```php
$aggregation->terms('genders', 'type')->missing('N/A');
```

### Date Histogram
```php
$aggregation->dateHistogram('histogram', 'date', CalendarInterval::Year)
            ->aggregate(function (SearchAggregation $aggregation) {
                        $aggregation->dateHistogram('histogram_nested', 'date', CalendarInterval::Day)
                            ->missing('2021-01-01');
                })
            ->missing('2021-01-01');
```

### Range
```php
$aggregation->range('price_ranges', 'price', [
    ['to' => 100],
    ['from' => 200, 'to' => 400],
    ['from' => 300],
]);
```

### Percentiles
```php
$aggregation->percentiles('percentile', 'type', [1, 2]);
```

```php
        $res = $this->sigmie->newQuery($name)
            ->matchAll()
            ->aggregate(function (SearchAggregation $aggregation) {
                $aggregation->percentileRanks('percentile_rank', 'type', [3, 2]);
            })
            ->get();

        $res->aggregation('percentile_rank.values');
```
