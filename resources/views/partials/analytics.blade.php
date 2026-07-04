{{--
    Client-side analytics (Umami) — privacy-respecting, cookieless, GDPR-friendly.

    Renders only when a website id is configured (config/analytics.php ← env
    UMAMI_WEBSITE_ID), so the site ships zero tracking until credentials exist.
    Funnel events are fired from markup via `data-umami-event` attributes and
    mirror the server-side events in App\Support\Analytics::EVENTS.
--}}
@php($umami = config('analytics.umami'))
@if (! empty($umami['website_id']))
    <script
        defer
        src="{{ $umami['script_url'] }}"
        data-website-id="{{ $umami['website_id'] }}"
        @if (! empty($umami['domains'])) data-domains="{{ $umami['domains'] }}" @endif
    ></script>
@endif
