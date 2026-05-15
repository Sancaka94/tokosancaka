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
        // Default menggunakan data Sandbox (Akan ditimpa otomatis oleh controller jika mode Production aktif)
        'merchant_id'         => env('DANA_MERCHANT_ID'),
        'client_id'           => env('DANA_X_PARTNER_ID'), // Di SNAP BI, Client ID nilainya sama dengan Partner ID
        'x_partner_id'        => env('DANA_X_PARTNER_ID'),
        'private_key'         => env('DANA_PRIVATE_KEY'),
        'private_key_path'    => env('DANA_PRIVATE_KEY_PATH'),
        'origin'              => env('DANA_ORIGIN'),
        'dana_public_key'     => env('DANA_PUBLIC_KEY'),
        'dana_public_key_path'=> env('DANA_PUBLIC_KEY_PATH'),
        'client_secret'       => env('DANA_CLIENT_SECRET'),
        'redirect_url_oauth'  => env('DANA_REDIRECT_URL_OAUTH'),
        'external_shop_id'    => env('DANA_EXTERNAL_SHOP_ID'),
        
        'dana_env'            => env('DANA_ENV', 'SANDBOX'),
        
        'base_url'            => env('DANA_ENV', 'SANDBOX') === 'PRODUCTION'
                                    ? 'https://api.saas.dana.id' // <--- Ganti di sini juga
                                    : 'https://api.sandbox.dana.id',
    ],

    'openai' => [
    'key' => env('OPENAI_API_KEY'),
    'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
    ],

    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL'),
    ],

    // --- Konfigurasi IAK PPOB ---
    'iak' => [
        'mode'              => env('IAK_MODE', 'development'),
        'user_hp'           => env('IAK_USER_HP'),
        'api_key'           => env('IAK_API_KEY'),
        'prepaid_base_url'  => env('IAK_PREPAID_BASE_URL', 'https://prepaid.iak.dev'),
        'postpaid_base_url' => env('IAK_POSTPAID_BASE_URL', 'https://testpostpaid.mobilepulsa.net'),
    ],

];
