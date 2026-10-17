<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    'paypal' => [
        'mode' => env('PAYPAL_MODE', 'sandbox'),

        'client_id' => env('PAYPAL_CLIENT_ID'),

        'client_secret' => env('PAYPAL_CLIENT_SECRET'),

        'merchant_id' => env('PAYPAL_MERCHANT_ID'),

        'base_url' => env(
            'PAYPAL_BASE_URL',
            'https://api-m.sandbox.paypal.com'
        ),

        'exchange_rate' => env(
            'PAYPAL_EXCHANGE_RATE',
            26054
        ),

        'timeout' => (int) env('PAYPAL_HTTP_TIMEOUT', 12),
    ],

    // Server-side only. Supports OpenAI-compatible providers, including
    // OpenRouter. No provider request is sent unless a key is configured.
    'bloomgift_ai' => [
        'driver' => env('BLOOMGIFT_AI_PROVIDER', 'none'),
        'api_key' => env('BLOOMGIFT_AI_API_KEY'),
        'base_url' => env('BLOOMGIFT_AI_BASE_URL', env('BLOOMGIFT_AI_PROVIDER') === 'openrouter' ? 'https://openrouter.ai/api/v1' : 'https://api.openai.com/v1'),
        'model' => env('BLOOMGIFT_AI_MODEL', env('BLOOMGIFT_AI_PROVIDER') === 'openrouter' ? 'openrouter/free' : null),
        'http_referer' => env('BLOOMGIFT_AI_HTTP_REFERER'),
        'title' => env('BLOOMGIFT_AI_TITLE', 'BloomGift'),
        'timeout' => (int) env('BLOOMGIFT_AI_TIMEOUT', 20),
        'stream_timeout' => (int) env('BLOOMGIFT_AI_STREAM_TIMEOUT', 45),
        'json_mode' => env('BLOOMGIFT_AI_JSON_MODE', true),
    ],

    // Brave Search is optional inspiration research for the configured AI.
    // BloomGift product data is never sourced from these results.
    'bloomgift_web_search' => [
        'driver' => env('BLOOMGIFT_WEB_SEARCH_PROVIDER', 'none'),
        'api_key' => env('BLOOMGIFT_WEB_SEARCH_API_KEY'),
        'base_url' => env('BLOOMGIFT_WEB_SEARCH_BASE_URL', 'https://api.search.brave.com/res/v1/web/search'),
        'timeout' => (int) env('BLOOMGIFT_WEB_SEARCH_TIMEOUT', 5),
    ],

];
