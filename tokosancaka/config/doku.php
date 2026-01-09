<?php
// config/doku.php

return [
    /*
    |--------------------------------------------------------------------------
    | Mode Operasi ('sandbox' atau 'production')
    |--------------------------------------------------------------------------
    | Ini adalah saklar utama. Pastikan DOKU_MODE di .env Anda
    | di-set ke 'sandbox' atau 'production'.
    */
    'mode' => env('DOKU_MODE', 'production'), // Dibaca oleh DokuJokulService

    /*
    |--------------------------------------------------------------------------
    | Kredensial API Checkout (Untuk Top Up)
    |--------------------------------------------------------------------------
    | Minta Client ID & Secret Key "Checkout" ke DOKU.
    | Ini BEDA dengan Client ID "SAC Merchant".
    */
    'client_id' => trim(env('DOKU_CLIENT_ID', 'BRN-0252-1758532090816')),
    'secret_key' => trim(env('DOKU_SECRET_KEY', 'SK-uzwQf5XMq4YRfnxwcvhF')),

    /*
    |--------------------------------------------------------------------------
    | Kredensial API SAC Merchant (Untuk Dompet Sancaka)
    |--------------------------------------------------------------------------
    | Ini adalah Client ID 'BRN-...' Anda.
    */
    'sac_client_id' => trim(env('DOKU_SAC_CLIENT_ID', 'BRN-0252-1758532090816')),
    'sac_secret_key' => trim(env('DOKU_SAC_SECRET_KEY', 'SK-uzwQf5XMq4YRfnxwcvhF')),

    /*
    |--------------------------------------------------------------------------
    | ID Akun Utama/Pusat (Untuk Fitur Pencairan)
    |--------------------------------------------------------------------------
    | Ini adalah SAC ID milik Anda (pemilik platform)
    */
    'main_sac_id' => trim(env('DOKU_MAIN_SAC_ID', 'BRN-0252-1758532090816')),
    
 

    /*
    |--------------------------------------------------------------------------
    | URL Endpoint API
    |--------------------------------------------------------------------------
    */
    'sandbox_url' => 'https://api-sandbox.doku.com',
    'production_url' => 'https://api.doku.com',

    /*
    | Kunci Lainnya (dari file lama Anda, jaga-jaga jika masih dipakai)
    */
    'doku_public_key' => env('DOKU_PUBLIC_KEY'),
    'merchant_private_key' => env('MERCHANT_PRIVATE_KEY'),
    'merchant_private_key_passphrase' => env('MERCHANT_PRIVATE_KEY_PASSPHRASE'),
    'merchant_public_key' => env('MERCHANT_PUBLIC_KEY'),
    
];