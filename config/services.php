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
        'key' => env('STRIPE_KEY'),                       // pk_... (publishable)
        'secret' => env('STRIPE_SECRET'),                 // sk_... (secret)
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'), // whsec_...
        'currency' => env('STRIPE_CURRENCY', 'usd'),
    ],

    'groq' => [
        'key' => env('GROQ_API_KEY'),   // chatbot; can also be set in Admin → Settings
    ],

    // UPS APIs (rating, tracking, shipping labels, address validation).
    // Everything here can also be set in Admin → Settings → Shipping (UPS); the
    // Setting value wins over the env fallback below.
    'ups' => [
        'client_id' => env('UPS_CLIENT_ID'),
        'client_secret' => env('UPS_CLIENT_SECRET'),
        'account' => env('UPS_ACCOUNT'),          // 6-char UPS account/shipper number (labels & negotiated rates)
        'env' => env('UPS_ENV', 'sandbox'),       // 'sandbox' (CIE) or 'production'
    ],

];
