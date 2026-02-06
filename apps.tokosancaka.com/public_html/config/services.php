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
        // [WAJIB ADA] Logic otomatis menentukan URL Sandbox vs Production
        'base_url'            => env('DANA_ENV') === 'PRODUCTION'
                                    ? 'https://api.dana.id'
                                    : 'https://api.sandbox.dana.id',
    ],


    // --- KONFIGURASI KIRIMINAJA ---

    'kiriminaja' => [
        'token'    => env('KIRIMINAJA_TOKEN'),
        // Default fallback hanya jika env kosong. Jika env ada isinya, dia pakai env.
        'base_url' => env('KIRIMINAJA_BASE_URL', 'https://client.kiriminaja.com'),

        'origin_district_id'    => env('KIRIMINAJA_ORIGIN_DISTRICT'),
        'origin_subdistrict_id' => env('KIRIMINAJA_ORIGIN_SUBDISTRICT'),
        // TAMBAHKAN INI:
        'origin_lat' => env('KIRIMINAJA_ORIGIN_LAT'),
        'origin_long' => env('KIRIMINAJA_ORIGIN_LONG'), // <-- Pastikan ini menarik env yang benar
        'origin_address' => env('KIRIMINAJA_ORIGIN_ADDRESS'),
    ],

];
