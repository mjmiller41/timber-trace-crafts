<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Umami (client-side, privacy-respecting)
    |--------------------------------------------------------------------------
    |
    | Cookieless, GDPR-friendly page + event analytics. Both values are
    | provided by the Umami instance (Umami Cloud free tier or self-hosted).
    | When the website id is empty the tracker markup is not rendered, so the
    | integration is completely inert until credentials are supplied.
    |
    */

    'umami' => [
        'script_url' => env('UMAMI_SCRIPT_URL', 'https://cloud.umami.is/script.js'),
        'website_id' => env('UMAMI_WEBSITE_ID'),
        // Restrict tracking to production traffic by default.
        'domains' => env('UMAMI_DOMAINS', 'timbertracecrafts.com'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Server-side funnel events
    |--------------------------------------------------------------------------
    |
    | First-party, dependency-free conversion tracking. Key commerce events
    | are written to a dedicated log channel so they are captured even when a
    | visitor blocks client-side analytics — the source of truth for revenue
    | funnel metrics. Only non-PII attributes (ids, counts, amounts) are
    | recorded.
    |
    */

    'server_events' => [
        'enabled' => env('ANALYTICS_SERVER_EVENTS', true),
        'channel' => env('ANALYTICS_LOG_CHANNEL', 'analytics'),
    ],

];
