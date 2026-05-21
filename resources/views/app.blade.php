@php
    $defaultTitle = 'Sigmie — A modern Elasticsearch library for PHP';
    $defaultDescription = 'Sigmie is a modern, developer-friendly Elasticsearch and OpenSearch library for PHP and Laravel. Fluent search, semantic and hybrid retrieval, no boilerplate.';
    $props = $page['props'] ?? [];
    $component = $page['component'] ?? '';
    $title = $props['title'] ?? null;
    $description = $props['description'] ?? $defaultDescription;
    $card = $props['card'] ?? (config('app.url') . '/og-image.png');
    $href = $props['href'] ?? (config('app.url') . ($page['url'] ?? '/'));
    $ogType = in_array($component, ['Post', 'Document']) ? 'article' : 'website';
    $headline = match (true) {
        ! $title => $defaultTitle,
        str_starts_with($title, 'Sigmie') => $title,
        default => "{$title} — Sigmie",
    };

    $orgId = config('app.url') . '/#organization';
    $graph = [
        [
            '@type' => 'Organization',
            '@id' => $orgId,
            'name' => 'Sigmie',
            'url' => config('app.url') . '/',
            'logo' => config('app.url') . '/logo.png',
            'sameAs' => [
                'https://github.com/sigmie',
                'https://packagist.org/packages/sigmie/sigmie',
            ],
        ],
        [
            '@type' => 'WebSite',
            '@id' => config('app.url') . '/#website',
            'url' => config('app.url') . '/',
            'name' => 'Sigmie',
            'publisher' => ['@id' => $orgId],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => config('app.url') . '/search?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ],
    ];
    if ($component === 'Welcome') {
        $graph[] = [
            '@type' => 'SoftwareApplication',
            'name' => 'Sigmie',
            'applicationCategory' => 'DeveloperApplication',
            'operatingSystem' => 'Cross-platform',
            'url' => config('app.url') . '/',
            'description' => $defaultDescription,
            'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD'],
            'publisher' => ['@id' => $orgId],
        ];
    } elseif ($component === 'Post') {
        $graph[] = [
            '@type' => 'Article',
            'headline' => $title,
            'description' => $description,
            'image' => $card,
            'url' => $href,
            'mainEntityOfPage' => $href,
            'author' => ['@type' => 'Organization', 'name' => 'Sigmie', '@id' => $orgId],
            'publisher' => ['@id' => $orgId],
        ];
    } elseif ($component === 'Document') {
        $graph[] = [
            '@type' => 'TechArticle',
            'headline' => $title,
            'description' => $description,
            'image' => $card,
            'url' => $href,
            'mainEntityOfPage' => $href,
            'proficiencyLevel' => 'Beginner',
            'author' => ['@id' => $orgId],
            'publisher' => ['@id' => $orgId],
        ];
    }
    $jsonLd = json_encode([
        '@context' => 'https://schema.org',
        '@graph' => $graph,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title inertia>{{ $headline }}</title>

    <meta name="description" content="{{ $description }}">
    <meta name="author" content="Sigmie Team">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">

    <link rel="canonical" href="{{ $href }}">

    <meta property="og:type" content="{{ $ogType }}">
    <meta property="og:url" content="{{ $href }}">
    <meta property="og:title" content="{{ $headline }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:image" content="{{ $card }}">
    <meta property="og:site_name" content="Sigmie">
    <meta property="og:locale" content="en_US">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{{ $href }}">
    <meta name="twitter:title" content="{{ $headline }}">
    <meta name="twitter:description" content="{{ $description }}">
    <meta name="twitter:image" content="{{ $card }}">

    <meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#000000" media="(prefers-color-scheme: dark)">

    <link rel="apple-touch-icon" sizes="76x76" href="{{ asset('/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('/favicon-16x16.png') }}">
    <link rel="manifest" href="{{ asset('/site.webmanifest') }}">
    <link rel="mask-icon" href="{{ asset('/safari-pinned-tab.svg') }}" color="#5bbad5">
    <meta name="msapplication-TileColor" content="#da532c">

    <script type="application/ld+json">{!! $jsonLd !!}</script>

    <script src="https://analytics.sigmie.com/script.js" data-site="KZAGYEMG" defer></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    @routes
    @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
    @inertiaHead
</head>

<body class="antialiased">
    @inertia
</body>

</html>
