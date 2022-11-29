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

```php
$sigmie->newQuery()->matchAll()->
    ->aggregate(function (SearchAggregation $aggregation) {
                $aggregation->dateHistogram('histogram', 'date', CalendarInterval::Year)

                    ->aggregate(function (SearchAggregation $aggregation) {
                        $aggregation->dateHistogram('histogram_nested', 'date', CalendarInterval::Day)
                            ->missing('2021-01-01');
                    })
                    ->missing('2021-01-01');
            })
            ->get();

```

## Bucket

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

### Significant Text
```php
$aggregation->significantText('significant', 'title');
```

### Sum
```php
$aggregation->sum('count_sum', 'count');
```

### Max
```php
$aggregation->max('maxCount', 'count');
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

## Metrics


### Cardinality
```php
$aggregation->cardinality('type_count', 'type');
```
### Min
```php
$aggregation->min('minCount', 'count');
```
### Avg
```php
$aggregation->avg('averageCount', 'count');
```


### Value Count
```php
$aggregation->valueCount('type_count', 'type');
```
