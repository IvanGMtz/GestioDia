<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#2E7D32">
    <title>@yield('title', 'GestioDia')</title>

    <link rel="icon" href="/brand/favicon.ico">
    <link rel="apple-touch-icon" href="/brand/apple-touch-icon.png">
    <link rel="preload" as="font" type="font/woff2" href="/fonts/poppins/poppins-400.woff2" crossorigin>

    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body class="@if (app()->bound(\App\Models\Member::class)) pb-nav @endif">
    @yield('content')

    @if (app()->bound(\App\Models\Member::class))
        @include('partials.bottom-nav')
    @endif
</body>
</html>
