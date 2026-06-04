<?php
/**
 * Konfigurasi PayPal
 * Anda bisa mengambil nilai dari env() atau jika sudah menggunakan 
 * standar model Api::getValue(), panggil langsung di service/controller pembayaran Anda.
 */
return [
    'mode'    => env('PAYPAL_MODE', 'sandbox'), // 'sandbox' atau 'live'
    
    'sandbox' => [
        'client_id'         => env('PAYPAL_SANDBOX_CLIENT_ID', ''),
        'client_secret'     => env('PAYPAL_SANDBOX_SECRET', ''),
        'app_id'            => 'APP-80W284485P519543T', // App-ID standar testing Sandbox PayPal
    ],
    
    'live' => [
        'client_id'         => env('PAYPAL_LIVE_CLIENT_ID', ''),
        'client_secret'     => env('PAYPAL_LIVE_SECRET', ''),
        'app_id'            => env('PAYPAL_LIVE_APP_ID', ''),
    ],

    // Konfigurasi NVP/SOAP Klasik (Opsional jika Anda tidak memakai REST API)
    'payment_action' => 'Sale', // Bisa juga 'Order' atau 'Authorization'
    'currency'       => env('PAYPAL_CURRENCY', 'USD'),
    'notify_url'     => env('PAYPAL_NOTIFY_URL', ''), 
    'locale'         => env('PAYPAL_LOCALE', 'en_US'), 
    'validate_ssl'   => env('PAYPAL_VALIDATE_SSL', true),
];