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

    'telegram' => [
        'token' => env('TELEGRAM_BOT_TOKEN'),
    ],

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
    

    'kiriminaja' => [
        'base_url' => env('KIRIMINAJA_BASE_URL', 'https://client.kiriminaja.com'),
        'token'    => env('KIRIMINAJA_TOKEN'),
    ],
    'fonnte' => [
        'key' => env('FONNTE_API_KEY'),
    ],

      'dana' => [
        'merchant_id'         => env('MERCHANT_ID'),
        'x_partner_id'        => env('X_PARTNER_ID'),
        'private_key'         => env('PRIVATE_KEY'),
        'private_key_path'    => env('PRIVATE_KEY_PATH'),
        'origin'              => env('ORIGIN'),
        'dana_public_key'     => env('DANA_PUBLIC_KEY'),
        'dana_public_key_path'=> env('DANA_PUBLIC_KEY_PATH'),
        'client_secret'       => env('CLIENT_SECRET'),
        'redirect_url_oauth'  => env('REDIRECT_URL_OAUTH'),
        'external_shop_id'    => env('EXTERNAL_SHOP_ID'),
        'dana_env'            => env('DANA_ENV', 'SANDBOX'),
        'base_url'            => env('DANA_BASE_URL', 'https://api.sandbox.dana.id'), // Tambahkan default value ini
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
