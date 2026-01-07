<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php', // <--- PENTING: Mendaftarkan routes/api.php
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        
        // PENTING: Mengecualikan Route Webhook dari proteksi CSRF
        // Karena server luar (KiriminAja/Tripay/Doku) tidak memiliki token CSRF aplikasi kita.
        $middleware->validateCsrfTokens(except: [
            'api/kiriminaja/webhook', // Webhook KiriminAja
            'dana/notify', // Whitelist route ini
            'dana/pay',
            'dana/return',
            'dana/*', // Tambahkan ini
            //'tripay/callback',        // Callback Tripay (Jika ada)
            //'doku/notify',            // Callback Doku (Jika ada)
            //'api/*',                  // Opsional: Membuka semua route API dari CSRF
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();