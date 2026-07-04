<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        // Inline @section('title', $x) already escapes via startSection(), so the
        // value from yieldContent() is HTML-safe — a second e() here double-encodes
        // (e.g. "Terms & Conditions" → "&amp;amp;"). Render it as-is with {!! !!}.
        $metaTitle       = \Illuminate\Support\Facades\View::yieldContent('title', 'Home');
        // Like $metaTitle: inline @section('meta_description', $x) already escapes,
        // so a second e() double-encodes '&'/quotes in the tag. Render with {!! !!}.
        $metaDescription = \Illuminate\Support\Facades\View::yieldContent('meta_description', 'Handcrafted laser-cut wooden jewelry, boxes, and gifts. Made with precision and love.');
        // URL-valued metas: inline @section() already escapes its value, so pre-escape
        // the *default* too and drop the outer e() — otherwise a section-set URL with
        // query-string '&'s double-encodes (e.g. og_image on a journal post).
        $metaCanonical   = \Illuminate\Support\Facades\View::yieldContent('canonical', e(url()->current()));
        $metaRobots      = e(\Illuminate\Support\Facades\View::yieldContent('robots', 'index, follow'));
        $metaOgType      = e(\Illuminate\Support\Facades\View::yieldContent('og_type', 'website'));
        $metaOgImage     = \Illuminate\Support\Facades\View::yieldContent('og_image', e(asset('images/og-default.jpg')));
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

    {{-- Self-hosted variable fonts — preload the two romans to avoid FOIT --}}
    <link rel="preload" as="font" type="font/woff2" href="/fonts/fraunces.woff2" crossorigin>
    <link rel="preload" as="font" type="font/woff2" href="/fonts/inter-tight.woff2" crossorigin>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @php
        $sameAsUrls = collect([$socialInstagram, $socialFacebook, $socialPinterest, $socialYoutube, $socialLinkedin])->filter()->values()->all();
        $siteSchema = json_encode([
            '@context' => 'https://schema.org',
            '@graph'   => [
                [
                    '@type'        => 'Organization',
                    '@id'          => url('/').'#organization',
                    'name'         => $siteName,
                    'url'          => url('/'),
                    'description'  => 'Handcrafted laser-cut wood earrings, jewelry boxes, and engraved tumblers made in Avon Park, Florida.',
                    'email'        => 'hello@timbertracecrafts.com',
                    'logo'         => ['@type' => 'ImageObject', 'url' => $siteLogoUrl],
                    'contactPoint' => [
                        '@type'       => 'ContactPoint',
                        'email'       => 'hello@timbertracecrafts.com',
                        'contactType' => 'customer service',
                        'areaServed'  => 'US',
                    ],
                    'sameAs'       => $sameAsUrls,
                ],
                [
                    '@type'       => 'Person',
                    '@id'         => url('/').'#author',
                    'name'        => 'Michael J. Miller',
                    'url'         => url('/about-us'),
                    'jobTitle'    => 'Founder & Maker',
                    'description' => 'Michael J. Miller is the founder and maker behind Timber Trace Crafts, handcrafting laser-cut wood earrings, jewelry boxes, and engraved tumblers in Avon Park, Florida.',
                    'worksFor'    => ['@id' => url('/').'#organization'],
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
    @include('partials.analytics')
</head>
<body class="bg-oak-sand text-charcoal font-body antialiased">

    @include('components.nav')

    @include('components.flash')

    <main>
        @yield('content')
    </main>

    @include('components.footer')

    @include('components.cookie-consent')

    @if(config('services.recaptcha.site_key'))
        <script src="https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.site_key') }}"></script>
        <script>
            window.recaptchaSiteKey = @json(config('services.recaptcha.site_key'));

            // Resolves a fresh reCAPTCHA v3 token for the given action. Used directly
            // by pages with custom submit flows (e.g. checkout); auto-wired below for
            // plain form posts via [data-recaptcha-action].
            window.recaptchaToken = function (action) {
                return new Promise((resolve) => {
                    grecaptcha.ready(() => {
                        grecaptcha.execute(window.recaptchaSiteKey, { action }).then(resolve);
                    });
                });
            };

            document.addEventListener('DOMContentLoaded', () => {
                document.querySelectorAll('form[data-recaptcha-action]').forEach((form) => {
                    form.addEventListener('submit', (e) => {
                        if (form.dataset.recaptchaVerified === 'true') {
                            return;
                        }
                        e.preventDefault();
                        window.recaptchaToken(form.dataset.recaptchaAction).then((token) => {
                            let input = form.querySelector('input[name="g-recaptcha-response"]');
                            if (!input) {
                                input = document.createElement('input');
                                input.type = 'hidden';
                                input.name = 'g-recaptcha-response';
                                form.appendChild(input);
                            }
                            input.value = token;
                            form.dataset.recaptchaVerified = 'true';
                            form.requestSubmit ? form.requestSubmit() : form.submit();
                        });
                    });
                });
            });
        </script>
    @endif

    @stack('scripts')

</body>
</html>
