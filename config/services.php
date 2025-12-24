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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],


    'tochka' => [
        'sandbox' => [
            'base_url' => env('TOCHKA_SANDBOX_URL', 'https://enter.tochka.com/sandbox/v2/sbp'),
            'api_version' => env('TOCHKA_API_VERSION', 'v1.0'),
        ],
        'production' => [
            'base_url' => env('TOCHKA_PRODUCTION_URL', 'https://enter.tochka.com/uapi/sbp'),
            'api_version' => env('TOCHKA_API_VERSION', 'v1.0'),
        ],
        'timeout' => env('TOCHKA_TIMEOUT', 30),
        'retry_times' => env('TOCHKA_RETRY_TIMES', 3),
        'retry_sleep' => env('TOCHKA_RETRY_SLEEP', 100),
    ],
    'telegram' => [
        'client_bot_token' => env('CLIENT_TG_BOT_TOKEN'),
        'manager_bot_token' => env('MANAGER_TG_BOT_TOKEN'),
        'manager_chat_id' => env('MANAGER_TELEGRAM_CHAT_ID'),
        'manager_chat_ids' => array_filter(
            explode(',', env('MANAGER_TELEGRAM_CHAT_IDS', ''))
        ),
    ],
];
