<?php

return [
    'navigation' => [
        [
            'title' => 'Posts',
            'links' => [
                [
                    'title' => 'A different approach for Elasticsearch',
                    'href' => '/blog/a-different-approach',
                    'card' => '/cards/a-different-approach.png',
                    'description' => 'Why Sigmie takes a different approach to Elasticsearch in PHP — fluent API, no boilerplate, focus on relevance instead of low-level mappings.',
                ],
                [
                    'title' => 'Why are Search services expensive',
                    'href' => '/blog/why-are-search-services-expensive',
                    'card' => '/cards/why-are-search-services-expensive.png',
                    'description' => 'Why hosted search-as-a-service is so expensive — what you actually pay for under the hood and when running your own Elasticsearch makes sense.',
                ],
                [
                    'title' => 'High level Elasticsearch properties',
                    'href' => '/blog/high-level-properties',
                    'card' => '/cards/high-level-properties.png',
                    'description' => 'A walkthrough of Sigmie\'s predefined Elasticsearch property types and the analysis decisions baked in to get the best search relevance out of the box.',
                ],
                [
                    'title' => 'Elasticsearch shards rules',
                    'href' => '/blog/calculating-index-shards',
                    'card' => '/cards/elasticsearch-shard-rules.png',
                    'description' => 'Practical rules of thumb for calculating Elasticsearch primary and replica shards — sizing, distribution, and the math behind cluster capacity.',
                ],
            ]
        ],
    ]
];
