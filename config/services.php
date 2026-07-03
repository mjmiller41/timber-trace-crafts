<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'recaptcha' => [
        'site_key' => env('RECAPTCHA_SITE_KEY'),
        'secret_key' => env('RECAPTCHA_SECRET_KEY'),
        'min_score' => (float) env('RECAPTCHA_MIN_SCORE', 0.5),
    ],

    'etsy' => [
        'keystring' => env('ETSY_KEYSTRING'),
        'shared_secret' => env('ETSY_SHARED_SECRET'),
        'webhook_secret' => env('ETSY_WEBHOOK_SECRET'),
        'shop_name' => env('ETSY_SHOP_NAME', 'timbertracecrafts'),
        'redirect_uri' => env('ETSY_REDIRECT_URI'), // override for ngrok / local dev
    ],

    'indexnow' => [
        // IndexNow key. Not a secret — it is published at /{key}.txt on the site root.
        // If overridden via env, host a matching {key}.txt in public/.
        'key' => env('INDEXNOW_KEY', '43cf04c90b16ed6f72f647f01835ae23'),
    ],

    'imap' => [
        'host' => env('IMAP_HOST', 'imap.hostinger.com'),
        'port' => env('IMAP_PORT', 993),
        'encryption' => env('IMAP_ENCRYPTION', 'ssl'),
        'username' => env('IMAP_USERNAME'),
        'password' => env('IMAP_PASSWORD'),
        'novalidate_cert' => env('IMAP_NOVALIDATE_CERT', false),
    ],

];
