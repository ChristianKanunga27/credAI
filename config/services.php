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

    'transaction_api' => [
        'name' => env('TRANSACTION_API_NAME', 'Phone transaction provider'),
        'url' => env('TRANSACTION_API_URL'),
        'token' => env('TRANSACTION_API_TOKEN'),
        'timeout' => env('TRANSACTION_API_TIMEOUT', 15),
    ],
    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
    ],

    'ai' => [
        'provider' => env('AI_PROVIDER') ?: 'gemini',
        'api_url' => env('AI_API_URL'),
        'api_key' => env('AI_API_KEY') ?: env('GEMIN_API_KEY'),
        'model' => env('AI_MODEL') ?: 'gemini-3.8-flash',
        'fallback_model' => env('AI_FALLBACK_MODEL', 'gemini-flash-lite-latest'),
        'timeout' => (int) env('AI_TIMEOUT_SECONDS', 20),
    ],

];
