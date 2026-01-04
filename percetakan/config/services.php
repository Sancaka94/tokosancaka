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

    // --- KONFIGURASI KIRIMINAJA ---
 
    'kiriminaja' => [
        'token'    => env('KIRIMINAJA_TOKEN'),
        // Default fallback hanya jika env kosong. Jika env ada isinya, dia pakai env.
        'base_url' => env('KIRIMINAJA_BASE_URL', 'https://client.kiriminaja.com'),
        
        'origin_district_id'    => env('KIRIMINAJA_ORIGIN_DISTRICT'),
        'origin_subdistrict_id' => env('KIRIMINAJA_ORIGIN_SUBDISTRICT'), 
        // TAMBAHKAN INI:
        'origin_lat' => env('KIRIMINAJA_ORIGIN_LAT'),
        'origin_lng' => env('KIRIMINAJA_ORIGIN_LNG'),
    ],

];
