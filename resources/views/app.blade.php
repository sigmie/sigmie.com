<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="keywords" content="elasticseach,sigmie,search">

    <meta name="title" content="{{ config('app.title') }}">
    <meta name="description" content="{{ config('app.description') }}">
    <meta name="author" content="nico@sigmie.com">

    {{-- Twitter --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@nicoorf">
    <meta name="twitter:title" content="{{ config('app.title') }}">
    <meta name="twitter:description" content="{{ config('app.description') }}">
    <meta name="twitter:image" content="{{ asset('img/twitter-card.png') }}">
    <meta name="twitter:creator" content="@nicoorf">

    {{-- Og --}}
    <meta property="og:url" content="https://sigmie.com">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ config('app.title') }}">
    <meta property="og:description" content="{{ config('app.description') }}">
    <meta property="og:image" content="{{ asset('img/twitter-card.png') }}">

    {{-- Safari Bar --}}
    <meta name="theme-color" content="#000000">

    {{-- Favicon --}}
    <link rel="apple-touch-icon" sizes="76x76" href="{{ asset('/apple-touch-icon.png')}}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('/favicon-32x32.png')}}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('/favicon-16x16.png')}}">
    <link rel="manifest" href="{{ asset('/site.webmanifest') }}">
    <link rel="mask-icon" href="{{ asset('/safari-pinned-tab.svg')}}" color="#5bbad5">
    <meta name="msapplication-TileColor" content="#da532c">
    <meta name="theme-color" content="#ffffff">

    {{-- Fonts  --}}
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">

    <!-- Fonts -->
    <link rel="stylesheet" href="https://fonts.bunny.net/css2?family=Nunito:wght@400;600;700&display=swap">

    <!-- Scripts -->
    @routes
    @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
    @inertiaHead
</head>

<body class="font-sans antialiased">
    @inertia
</body>

</html>
