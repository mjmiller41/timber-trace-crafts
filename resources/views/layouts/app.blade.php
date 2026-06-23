<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@stack('title', 'Home') | Timber Trace Crafts</title>
    @hasSection('canonical')
        <link rel="canonical" href="@yield('canonical')">
    @endif
    <meta name="description" content="@yield('meta_description', 'Handcrafted laser-cut wooden jewelry, boxes, and gifts. Made with precision and love.')">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@300;400;500;600&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="bg-oak-sand text-charcoal font-body antialiased">

    @include('components.nav')

    @include('components.flash')

    <main>
        @yield('content')
    </main>

    @include('components.footer')

    @include('components.cookie-consent')

    @stack('scripts')

</body>
</html>
