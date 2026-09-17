<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
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

        'base_url' => env(
            'PAYPAL_BASE_URL',
            'https://api-m.sandbox.paypal.com'
        ),

        'exchange_rate' => env(
            'PAYPAL_EXCHANGE_RATE',
            26054
        ),
    ],

];