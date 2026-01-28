<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mode Operasi ('sandbox' atau 'production')
    |--------------------------------------------------------------------------
    | Ini adalah saklar utama.
    */
    'mode' => env('DOKU_MODE', 'production'),

    /*
    |--------------------------------------------------------------------------
    | [WAJIB ADA] Is Production Boolean
    |--------------------------------------------------------------------------
    | Baris inilah yang menyebabkan error "Undefined array key" sebelumnya.
    | Controller butuh nilai boolean true/false, bukan string.
    */
    'is_production' => (env('DOKU_MODE') === 'production'),

    /*
    |--------------------------------------------------------------------------
    | Kredensial API Checkout (Untuk Top Up)
    |--------------------------------------------------------------------------
    */
    'client_id' => trim(env('DOKU_CLIENT_ID', 'BRN-0252-1758532090816')),
    'secret_key' => trim(env('DOKU_SECRET_KEY', 'SK-uzwQf5XMq4YRfnxwcvhF')),

    /*
    |--------------------------------------------------------------------------
    | Kredensial API SAC Merchant (Untuk Dompet Sancaka)
    |--------------------------------------------------------------------------
    */
    'sac_client_id' => trim(env('DOKU_SAC_CLIENT_ID', 'BRN-0252-1758532090816')),
    'sac_secret_key' => trim(env('DOKU_SAC_SECRET_KEY', 'SK-uzwQf5XMq4YRfnxwcvhF')),

    /*
    |--------------------------------------------------------------------------
    | ID Akun Utama/Pusat (Untuk Fitur Pencairan)
    |--------------------------------------------------------------------------
    | PERHATIAN: Value di bawah ini sepertinya salah jika masih 'BRN-...'
    | Harusnya diawali 'SAC-...' (Contoh: SAC-4534-1763129369247)
    | Tapi tidak apa-apa ditaruh di sini sebagai default, nanti di-override
    | oleh Database (AppServiceProvider).
    */
    'main_sac_id' => trim(env('DOKU_MAIN_SAC_ID', '')),

    /*
    |--------------------------------------------------------------------------
    | URL Endpoint API
    |--------------------------------------------------------------------------
    */
    'sandbox_url' => 'https://api-sandbox.doku.com',
    'production_url' => 'https://api.doku.com',

    /*
    | Kunci Lainnya (Legacy)
    */
    'doku_public_key' => env('DOKU_PUBLIC_KEY'),
    'merchant_private_key' => env('MERCHANT_PRIVATE_KEY'),
    'merchant_private_key_passphrase' => env('MERCHANT_PRIVATE_KEY_PASSPHRASE'),
    'merchant_public_key' => env('MERCHANT_PUBLIC_KEY'),
];
