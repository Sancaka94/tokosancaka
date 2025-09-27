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
    
    'dana' => [
        'api_url' => env('DANA_API_URL'),
        'merchant_id' => env('DANA_MERCHANT_ID'),
        'client_id' => env('DANA_CLIENT_ID'),
        'client_secret' => env('DANA_CLIENT_SECRET'),
        'private_key' => str_replace('\n', "\n", env('DANA_PRIVATE_KEY')),
        'public_key' => str_replace('\n', "\n", env('DANA_PUBLIC_KEY')),
    ],
    'kiriminaja' => [
        'base_url' => env('KIRIMINAJA_BASE_URL', 'https://tdev.kiriminaja.com'),
        'token'    => env('KIRIMINAJA_TOKEN'),
    ],
    'fonnte' => [
        'key' => env('FONNTE_API_KEY'),
    ],

    'openai' => [
    'key' => env('OPENAI_API_KEY'),
    'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
],

'gemini' => [
    'key' => env('GEMINI_API_KEY'),
    'model' => env('GEMINI_MODEL', 'gemini-1.5-flash-latest'),
],

];
