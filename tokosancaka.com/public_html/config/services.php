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
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

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
        'origin'              => env('ORIGIN', 'https://tokosancaka.com'),

        # DANA CORPORATE / B2B CONFIG
        'merchant_deposit_account' => env('DANA_MERCHANT_DEPOSIT_ACCOUNT'),
        'id_toko'                  => env('DANA_ID_TOKO'),
        'valid_id'                 => env('DANA_VALID_ID'),
        'partner_id_conf'          => env('DANA_PARTNER_ID_CONF'),

        'dana_env'            => env('DANA_ENV', 'SANDBOX'),

        'base_url'            => env('DANA_ENV', 'SANDBOX') === 'PRODUCTION'
                                    ? 'https://api.saas.dana.id' // <--- Ganti di sini juga
                                    : 'https://api.sandbox.dana.id',
    ],

    'midtrans' => [
        'is_production' => env('MIDTRANS_IS_PRODUCTION', false),

        // General Core API
        'merchant_id'   => env('MIDTRANS_MERCHANT_ID'),
        'client_key'    => env('MIDTRANS_CLIENT_KEY'),
        'server_key'    => env('MIDTRANS_SERVER_KEY'),

        // BI-SNAP Credentials
        'snap_client_id'     => env('MIDTRANS_SNAP_CLIENT_ID'),
        'snap_client_secret' => env('MIDTRANS_SNAP_CLIENT_SECRET'),

        // Path to Key Files
        'merchant_private_key_path' => env('MIDTRANS_MERCHANT_PRIVATE_KEY_PATH'),
        'midtrans_public_key_path'  => env('MIDTRANS_PUBLIC_KEY_PATH'),
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

    // --- Konfigurasi PAYPAL ---
    'paypal' => [
        'mode'    => env('PAYPAL_MODE', 'sandbox'),
        'sandbox' => [
            'client_id'  => env('PAYPAL_SANDBOX_CLIENT_ID'),
            'secret'     => env('PAYPAL_SANDBOX_SECRET'),
            'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
        ],
        'production' => [
            'client_id'  => env('PAYPAL_LIVE_CLIENT_ID'),
            'secret'     => env('PAYPAL_LIVE_SECRET'),
            'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
        ],
    ],

    // --- Konfigurasi DARMAWISATA ---
    'darmawisata' => [
        'mode' => env('DHARMAWISATA_MODE', 'development'),
        'development' => [
            'base_url'     => env('DARMAWISATA_API_URL', 'https://uat-backup.darmawisataindonesiah2h.co.id:7080/h2h/'),
            'user_id'      => env('DARMAWISATA_USER_ID'),
            'access_token' => env('DARMAWISATA_ACCESS_TOKEN'),
        ],
        'production' => [
            'base_url'     => env('DARMAWISATA_PROD_API_URL', 'https://www.darmawisataindonesiah2h.co.id/'),
            'user_id'      => env('DARMAWISATA_PROD_USER_ID', 'WSA63IU2QM'),
            'password'     => env('DARMAWISATA_PROD_ACCESS_TOKEN', 'M2E4FGCWUC'),
        ]
    ],

    // --- Konfigurasi IPAYMU ---
    'ipaymu' => [
        'mode'    => env('IPAYMU_MODE', env('IPAYMU_ENV', 'sandbox')),
        'va'      => env('IPAYMU_VA'),
        'api_key' => env('IPAYMU_API_KEY'),
    ],

    // --- Konfigurasi MANDIRI API ---
    'mandiri' => [
        'mode' => env('MANDIRI_MODE', 'sandbox'),
        'sandbox' => [
            'client_id'     => env('MANDIRI_CLIENT_ID_SANDBOX'),
            'client_secret' => env('MANDIRI_CLIENT_SECRET_SANDBOX'),
            'partner_id'    => env('MANDIRI_PARTNER_ID_SANDBOX', 'SANDBOX'), // Default 'SANDBOX' dari dokumentasi
            'private_key'   => env('MANDIRI_PRIVATE_KEY_SANDBOX'),
        ],
        'production' => [
            'client_id'     => env('MANDIRI_CLIENT_ID_PRODUCTION'),
            'client_secret' => env('MANDIRI_CLIENT_SECRET_PRODUCTION'),
            'partner_id'    => env('MANDIRI_PARTNER_ID_PRODUCTION'),
            'private_key'   => env('MANDIRI_PRIVATE_KEY_PRODUCTION'),
        ],
    ],

];
