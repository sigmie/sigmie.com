export const RELEVANCE_PRESETS = [
    { label: 'Undercover spy', query: 'secret agent undercover spy mission' },
    { label: 'Stranded in space', query: 'astronaut stranded in deep space' },
    { label: 'Corrupt politics', query: 'corrupt politicians behind closed doors' },
    { label: 'Bad road trip', query: 'family road trip goes horribly wrong' },
];

export const RELEVANCE_MODES = [
    {
        id: 'keyword',
        label: 'Keyword',
        short: 'Word match only',
        detail: 'Scores titles and plots by the words they share with the query, typos included.',
    },
    {
        id: 'hybrid',
        label: 'Hybrid',
        short: 'Word match plus meaning',
        detail: 'Blends word matches with meaning. The code below sets the weight of each.',
    },
    {
        id: 'vector',
        label: 'Vector',
        short: 'Meaning only',
        detail: 'Ranks by meaning alone, so a plot can match without sharing a word.',
    },
];

export const TYPE_FILTERS = [
    { id: 'all', label: 'All', expr: 'type:"TV Show" OR type:"Movie"' },
    { id: 'movie', label: 'Movies', expr: 'type:"Movie"' },
    { id: 'tv', label: 'TV Shows', expr: 'type:"TV Show"' },
];

export const IMAGE_SUGGESTIONS = [
    'Owl',
    'Fox',
    'Space',
    'Safari',
    'Forest',
    'Snow',
    'Balloons',
    'Nature',
    'Love',
];

export const PLATFORM_REFERENCES = [
    {
        id: 'mappings',
        title: 'Mappings & semantic fields',
        summary: 'Declare intent types once. Sigmie wires analyzers, sub-fields, and vector dimensions.',
        href: '/docs/v2/mappings',
        filename: 'Schema.php',
        code: [
            '$props = new NewProperties;',
            "$props->title('title');",
            "$props->text('description')",
            "    ->semantic(api: 'embeddings', dimensions: 384);",
            "$props->price('price');",
            "$props->category('brand');",
            "$sigmie->newIndex('products')->properties($props)->create();",
        ].join('\n'),
    },
    {
        id: 'filters',
        title: 'Readable filters, facets & sorting',
        summary: 'Write range, boolean, and facet clauses as compact strings instead of nested bool JSON.',
        href: '/docs/v2/filter-parser',
        filename: 'Filters.php',
        code: [
            "$sigmie->newSearch('products')",
            '    ->properties($props)',
            "    ->queryString('lapto')",
            '    ->typoTolerance()',
            "    ->filters('price<=1500 AND in_stock:true')",
            "    ->facets('brand category price:100')",
            "    ->sort('_score:desc price:asc')",
            '    ->get();',
        ].join('\n'),
    },
    {
        id: 'analytics',
        title: 'One-request analytics',
        summary: 'Compute KPI deltas, calendar trends, ranked breakdowns, cohorts, and funnels.',
        href: '/docs/v2/analytics',
        filename: 'Analytics.php',
        code: [
            "$orders->analytics('created_at')",
            "    ->from(new DateTimeImmutable('-30 days'))",
            '    ->to(new DateTimeImmutable)',
            "    ->filters(\"status:'paid'\")",
            "    ->kpiDelta('revenue', Metric::Sum, 'amount')",
            "    ->trend('daily', Metric::Sum, 'amount', CalendarInterval::Day)",
            '    ->get();',
        ].join('\n'),
    },
    {
        id: 'rag',
        title: 'RAG retrieval & reranking',
        summary: 'Pull a wide hybrid candidate pool and rescore the top passages with a cross-encoder.',
        href: '/docs/v2/rag',
        filename: 'Retrieval.php',
        code: [
            "$response = $sigmie->newSearch('docs')",
            '    ->properties($props)',
            '    ->semantic()',
            "    ->queryString('What is your return policy?')",
            '    ->size(20)',
            '    ->get();',
            '',
            "$top5 = $response->rerank('reranker', ['content'], topK: 5);",
        ].join('\n'),
    },
    {
        id: 'engines',
        title: 'Elasticsearch 7–9 & OpenSearch 2–3',
        summary: 'Own your cluster with zero per-query vendor pricing. Same PHP API on both engines.',
        href: '/docs/v2/opensearch',
        filename: 'Connection.php',
        code: [
            "use Sigmie\\Sigmie;",
            '',
            "$sigmie = Sigmie::create(hosts: ['localhost:9200']);",
            "// Or target OpenSearch with the exact same search chain",
            "$sigmie = Sigmie::create(hosts: ['https://opensearch.internal:9200']);",
        ].join('\n'),
    },
    {
        id: 'laravel',
        title: 'Laravel Scout & AI SDK',
        summary: 'Index Eloquent models via Scout or expose any SigmieIndex as a Laravel AI tool.',
        href: '/docs/v2/laravel-ai',
        filename: 'LaravelAi.php',
        code: [
            'class ProductIndex extends SigmieIndex',
            '{',
            '    use AsTool;',
            "    public function name(): string { return 'products'; }",
            '}',
            '',
            'new SigmieIndexTool(app(ProductIndex::class));',
        ].join('\n'),
    },
];

export const MCP_CONFIG_JSON = `{
  "mcpServers": {
    "sigmie-docs": {
      "type": "http",
      "url": "https://sigmie.com/mcp"
    }
  }
}`;
