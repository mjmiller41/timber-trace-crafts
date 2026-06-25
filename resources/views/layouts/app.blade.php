<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $metaTitle       = e(\Illuminate\Support\Facades\View::yieldContent('title', 'Home'));
        $metaDescription = e(\Illuminate\Support\Facades\View::yieldContent('meta_description', 'Handcrafted laser-cut wooden jewelry, boxes, and gifts. Made with precision and love.'));
        $metaCanonical   = e(\Illuminate\Support\Facades\View::yieldContent('canonical', url()->current()));
        $metaRobots      = e(\Illuminate\Support\Facades\View::yieldContent('robots', 'index, follow'));
        $metaOgType      = e(\Illuminate\Support\Facades\View::yieldContent('og_type', 'website'));
        $metaOgImage     = e(\Illuminate\Support\Facades\View::yieldContent('og_image', asset('images/og-default.jpg')));
    @endphp
    <title>{!! $metaTitle !!} | Timber Trace Crafts</title>
    <link rel="canonical" href="{!! $metaCanonical !!}">
    <meta name="robots" content="{!! $metaRobots !!}">
    <meta name="description" content="{!! $metaDescription !!}">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Open Graph --}}
    <meta property="og:type" content="{!! $metaOgType !!}">
    <meta property="og:title" content="{!! $metaTitle !!} | {{ $siteName }}">
    <meta property="og:description" content="{!! $metaDescription !!}">
    <meta property="og:url" content="{!! $metaCanonical !!}">
    <meta property="og:image" content="{!! $metaOgImage !!}">
    <meta property="og:site_name" content="{{ $siteName }}">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{!! $metaTitle !!} | {{ $siteName }}">
    <meta name="twitter:description" content="{!! $metaDescription !!}">
    <meta name="twitter:image" content="{!! $metaOgImage !!}">

    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">

    {{-- R2 CDN preconnect --}}
    <link rel="preconnect" href="https://pub-82fe4a94d274416a9b5ab8028bcd8627.r2.dev" crossorigin>

    {{-- Page-specific LCP preload --}}
    @stack('preload')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style"
          href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;1,400&family=Montserrat:wght@400;500&display=swap"
          onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet"
              href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;1,400&family=Montserrat:wght@400;500&display=swap">
    </noscript>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @php
        $sameAsUrls = collect([$socialInstagram, $socialFacebook, $socialPinterest])->filter()->values()->all();
        $siteSchema = json_encode([
            '@context' => 'https://schema.org',
            '@graph'   => [
                [
                    '@type'  => 'Organization',
                    '@id'    => url('/').'#organization',
                    'name'   => $siteName,
                    'url'    => url('/'),
                    'logo'   => ['@type' => 'ImageObject', 'url' => $siteLogoUrl],
                    'sameAs' => $sameAsUrls,
                ],
                [
                    '@type'     => 'WebSite',
                    '@id'       => url('/').'#website',
                    'url'       => url('/'),
                    'name'      => $siteName,
                    'publisher' => ['@id' => url('/').'#organization'],
                ],
            ],
        ]);
    @endphp
    <script type="application/ld+json">{!! $siteSchema !!}</script>
    @stack('schema')
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
