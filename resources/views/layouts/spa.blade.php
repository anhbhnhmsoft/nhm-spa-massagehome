<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('meta_title', 'Massage Home')</title>
    <meta name="description" content="@yield('meta_description', 'Premium Massage and Spa Services at Home')">
    <meta name="keywords" content="@yield('meta_keywords', 'massage, spa, home service')">
    <link rel="icon" type="image/x-icon" href="{{ asset('images/logo.png') }}">

    <!-- Open Graph -->
    <meta property="og:title" content="@yield('meta_title', 'Massage Home')">
    <meta property="og:description" content="@yield('meta_description', 'Premium Massage and Spa Services at Home')">
    <meta property="og:image" content="@yield('og_image', asset('images/default-og.jpg'))">
    <meta property="og:type" content="website">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @yield('styles')
</head>

<body class="antialiased text-slate-900 bg-white">

    <main>
        @yield('content')
    </main>

</body>

</html>
