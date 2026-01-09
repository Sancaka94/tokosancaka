<?php

return [
    /*
    |--------------------------------------------------------------------------
    | DANA Environment
    |--------------------------------------------------------------------------
    |
    | Atur environment untuk API DANA.
    | Opsi yang valid: 'development' (untuk sandbox) atau 'production'.
    |
    */
    'environment' => env('DANA_ENVIRONMENT', 'development'),

    /*
    |--------------------------------------------------------------------------
    | DANA Credentials
    |--------------------------------------------------------------------------
    |
    | Kredensial ini digunakan untuk mengautentikasi request Anda ke DANA.
    | Pastikan semua nilai ini sudah diatur di file .env Anda.
    |
    */
    'credentials' => [
        // URL endpoint API DANA. SDK akan otomatis memilih URL sandbox/produksi
        // berdasarkan 'environment' di atas. Anda bisa mengaturnya secara manual di .env jika perlu.
        'serverUrl' => env('DANA_API_URL', ''), 
        
        // Merchant ID yang Anda dapatkan dari DANA.
        'merchantId' => env('DANA_MERCHANT_ID', ''),
        
        // Client ID yang Anda dapatkan dari DANA.
        'clientId' => env('DANA_CLIENT_ID', ''),
        
        // Client Secret yang Anda dapatkan dari DANA.
        'clientSecret' => env('DANA_CLIENT_SECRET', ''),
        
        // Kunci privat Anda untuk menandatangani request.
        // Pastikan formatnya benar di .env (satu baris dengan '\n').
        'privateKey' => env('DANA_PRIVATE_KEY', ''),
        
        // Kunci publik DANA untuk memverifikasi notifikasi/callback.
        // Pastikan formatnya benar di .env (satu baris dengan '\n').
        'danaPublicKey' => env('DANA_PUBLIC_KEY', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Notification URL
    |--------------------------------------------------------------------------
    |
    | URL ini akan menerima notifikasi status pembayaran dari DANA.
    | Sebaiknya diatur langsung di dalam PaymentController menggunakan route().
    |
    */
    'notification_url' => env('DANA_NOTIFICATION_URL', '/payment/callback'),

    /*
    |--------------------------------------------------------------------------
    | Redirect URL after Payment
    |--------------------------------------------------------------------------
    |
    | URL tujuan setelah pengguna menyelesaikan pembayaran di halaman DANA.
    | Sebaiknya diatur langsung di dalam PaymentController menggunakan route().
    |
    */
    'redirect_url' => env('DANA_REDIRECT_URL', '/payment/finish'),
];
