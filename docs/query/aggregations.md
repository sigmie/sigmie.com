# Aggregations

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
