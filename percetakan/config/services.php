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
        'merchant_id' => env('DANA_MERCHANT_ID'),
        'client_id' => env('DANA_CLIENT_ID'),
        'client_secret' => env('DANA_CLIENT_SECRET'),
        'base_url' => env('DANA_URL', 'https://api.sandbox.dana.id'),
        'private_key' => env('DANA_PRIVATE_KEY'),
        
        // Path ke file kunci privat Anda (untuk tanda tangan request)
        'private_key_path' => env('DANA_PRIVATE_KEY_PATH', 'keys/dana_private_key.pem'),
        
        // Path ke file kunci publik DANA (untuk verifikasi balasan/callback)
        'public_key_path' => env('DANA_PUBLIC_KEY_PATH', 'keys/dana_public_key.pem'),
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
