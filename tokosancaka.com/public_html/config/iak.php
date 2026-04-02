<?php

return [
    /*
    |--------------------------------------------------------------------------
    | IAK Environment
    |--------------------------------------------------------------------------
    | Menentukan environment yang sedang aktif: 'development' atau 'production'
    */
    'env' => env('IAK_ENV', 'development'),

    /*
    |--------------------------------------------------------------------------
    | IAK Credentials
    |--------------------------------------------------------------------------
    */
    'credentials' => [
        'user_hp' => env('IAK_USER_HP', ''),
        'api_key' => env('IAK_API_KEY', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | IAK Base URLs
    |--------------------------------------------------------------------------
    */
    'base_url' => [
        'prepaid' => [
            // URL Baru (Direkomendasikan)
            'development' => env('IAK_PREPAID_DEV_URL', 'https://prepaid.iak.dev'),
            'production'  => env('IAK_PREPAID_PROD_URL', 'https://prepaid.iak.id'),

            // URL Lama (Sebagai referensi/cadangan)
            'development_old' => 'https://testprepaid.mobilepulsa.net',
            'production_old'  => 'https://api.mobilepulsa.net',
        ],

        'postpaid' => [
            'development' => env('IAK_POSTPAID_DEV_URL', 'https://testpostpaid.mobilepulsa.net'),
            'production'  => env('IAK_POSTPAID_PROD_URL', 'https://mobilepulsa.net'),
        ],
    ],
];
