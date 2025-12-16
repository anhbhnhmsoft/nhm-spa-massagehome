<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Massage Home')</title>
    <meta name="description" content="@yield('meta_description', 'Massage Home')">
    <meta name="keywords" content="@yield('meta_keywords', 'Massage Home')">
    <meta name="author" content="Massage Home">

    <!-- Open Graph -->
    <meta property="og:title" content="@yield('og_title', 'Massage Home')">
    <meta property="og:description" content="@yield('og_description', 'Massage Home')">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="@yield('og_image', asset('images/default-og.jpg'))">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('twitter_title', 'Massage Home')">
    <meta name="twitter:description" content="@yield('twitter_description', 'Massage Home')">
    <meta name="twitter:image" content="@yield('twitter_image', asset('images/default-twitter.jpg'))">
    <meta name="color-scheme" content="only light">
    <meta name="supported-color-schemes" content="light">
    <meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)">

    <style>
        :root,
        html,
        body {
            color-scheme: only light !important;
            background-color: ##f2f4f6 !important;
            color: #111111 !important;
            forced-color-adjust: none;
        }
    </style>


    <style>
        html {
            background-color: oklch(1 0 0);
        }
    </style>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased">
    @yield('content')
</body>

</html>
