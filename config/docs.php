<?php

return [
    'versions' => [
        [
            'value' => 'v1',
            'label' => 'v1.x',
            'status' => 'stable',
            'default' => true
        ],
        [
            'value' => 'v2',
            'label' => 'v2.x',
            'status' => 'beta',
            'default' => true
        ],
        // Future versions can be added here
        // [
        //     'value' => 'v1',
        //     'label' => 'v1.x',
        //     'status' => 'beta'
        // ],
    ],
    'v1' => [
        'navigation' => [
            [
                'title' => 'Getting Started',
                'links' => [
                    [
                        'title' => 'Introdution',
                        'href' => '/docs/v1/introduction'
                    ],
                    [
                        'title' => 'Installation',
                        'href' => '/docs/v1/installation'
                    ]
                ]
            ],
            [
                'title' => 'Basics',
                'links' => [
                    [
                        'title' => 'Index',
                        'href' => '/docs/v1/index'
                    ],
                    [
                        'title' => 'Document',
                        'href' => '/docs/v1/document'
                    ],
                    [
                        'title' => 'Mappings',
                        'href' => '/docs/v1/mappings'
                    ],
                    [
                        'title' => 'Search',
                        'href' => '/docs/v1/search'
                    ],
                    [
                        'title' => 'Filter & Sorting',
                        'href' => '/docs/v1/filter-parser'
                    ],
                    [
                        'title' => 'Facets',
                        'href' => '/docs/v1/facets'
                    ],
                    [
                        'title' => 'Update',
                        'href' => '/docs/v1/update'
                    ],
                ]
            ],
            [
                'title' => 'Analysis',
                'links' => [
                    [
                        'title' => 'Analyisis',
                        'href' => '/docs/v1/analysis'
                    ],
                    [
                        'title' => 'Char filters',
                        'href' => '/docs/v1/char-filters'
                    ],
                    [
                        'title' => 'Tokenizer',
                        'href' => '/docs/v1/tokenizers'
                    ],
                    [
                        'title' => 'Token filters',
                        'href' => '/docs/v1/token-filters'
                    ],
                    [
                        'title' => 'Language',
                        'href' => '/docs/v1/language'
                    ],
                ]
            ],
            [
                'title' => 'Deeper',
                'links' => [
                    [
                        'title' => 'Query',
                        'href' => '/docs/v1/query'
                    ],
                    [
                        'title' => 'Aggregations',
                        'href' => '/docs/v1/aggregations'
                    ],
                    [
                        'title' => 'Semantic Search',
                        'href' => '/docs/v1/semantic-search'
                    ],
                    [
                        'title' => 'Search Template',
                        'href' => '/docs/v1/template'
                    ],
                    [
                        'title' => 'RAG (Retrieval-Augmented Generation)',
                        'href' => '/docs/v1/rag'
                    ],
                ]
            ],
            [
                'title' => 'Parse',
                'links' => [
                    [
                        'title' => 'Sort parser',
                        'href' => '/docs/v1/sort-parser'
                    ]
                ]
            ],
            [
                'title' => 'More',
                'links' => [
                    [
                        'title' => 'Testing',
                        'href' => '/docs/v1/testing'
                    ],
                    [
                        'title' => 'Packages',
                        'href' => '/docs/v1/packages'
                    ],
                    [
                        'title' => 'Docker',
                        'href' => '/docs/v1/docker'
                    ],
                ]
            ],
            [
                'title' => 'Integrations',
                'links' => [
                    [
                        'title' => 'Laravel Scout',
                        'href' => '/docs/v1/laravel-scout',
                        'card' => config('app.url') . '/cards/elasticsearch-scout.png'
                    ],
                ]
            ]
        ]
    ],
    'v2' => [
        'navigation' => [
            [
                'title' => 'Getting Started',
                'links' => [
                    [
                        'title' => 'Introdution',
                        'href' => '/docs/v2/introduction'
                    ],
                    [
                        'title' => 'Installation',
                        'href' => '/docs/v2/installation'
                    ]
                ]
            ],
            [
                'title' => 'Basics',
                'links' => [
                    [
                        'title' => 'Index',
                        'href' => '/docs/v2/index'
                    ],
                    [
                        'title' => 'Document',
                        'href' => '/docs/v2/document'
                    ],
                    [
                        'title' => 'Mappings',
                        'href' => '/docs/v2/mappings'
                    ],
                    [
                        'title' => 'Search',
                        'href' => '/docs/v2/search'
                    ],
                    [
                        'title' => 'Filter & Sorting',
                        'href' => '/docs/v2/filter-parser'
                    ],
                    [
                        'title' => 'Facets',
                        'href' => '/docs/v2/facets'
                    ],
                    [
                        'title' => 'Update',
                        'href' => '/docs/v2/update'
                    ],
                ]
            ],
            [
                'title' => 'Analysis',
                'links' => [
                    [
                        'title' => 'Analyisis',
                        'href' => '/docs/v2/analysis'
                    ],
                    [
                        'title' => 'Char filters',
                        'href' => '/docs/v2/char-filters'
                    ],
                    [
                        'title' => 'Tokenizer',
                        'href' => '/docs/v2/tokenizers'
                    ],
                    [
                        'title' => 'Token filters',
                        'href' => '/docs/v2/token-filters'
                    ],
                    [
                        'title' => 'Language',
                        'href' => '/docs/v2/language'
                    ],
                ]
            ],
            [
                'title' => 'Deeper',
                'links' => [
                    [
                        'title' => 'Query',
                        'href' => '/docs/v2/query'
                    ],
                    [
                        'title' => 'Aggregations',
                        'href' => '/docs/v2/aggregations'
                    ],
                    [
                        'title' => 'Semantic Search',
                        'href' => '/docs/v2/semantic-search'
                    ],
                    [
                        'title' => 'Search Template',
                        'href' => '/docs/v2/template'
                    ],
                    [
                        'title' => 'RAG (Retrieval-Augmented Generation)',
                        'href' => '/docs/v2/rag'
                    ],
                ]
            ],
            [
                'title' => 'Parse',
                'links' => [
                    [
                        'title' => 'Sort parser',
                        'href' => '/docs/v2/sort-parser'
                    ]
                ]
            ],
            [
                'title' => 'More',
                'links' => [
                    [
                        'title' => 'Testing',
                        'href' => '/docs/v2/testing'
                    ],
                    [
                        'title' => 'Packages',
                        'href' => '/docs/v2/packages'
                    ],
                    [
                        'title' => 'Docker',
                        'href' => '/docs/v2/docker'
                    ],
                ]
            ],
            [
                'title' => 'Integrations',
                'links' => [
                    [
                        'title' => 'Laravel Scout',
                        'href' => '/docs/v2/laravel-scout',
                        'card' => config('app.url') . '/cards/elasticsearch-scout.png'
                    ],
                ]
            ]
        ]
    ]
];
