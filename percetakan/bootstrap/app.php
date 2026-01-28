<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule; // <-- Tambahkan ini

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php', // <--- PENTING: Mendaftarkan routes/api.php
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        $middleware->append(\App\Http\Middleware\TenantMiddleware::class);

        // PENTING: Mengecualikan Route Webhook dari proteksi CSRF
        // Karena server luar (KiriminAja/Tripay/Doku) tidak memiliki token CSRF aplikasi kita.
        $middleware->validateCsrfTokens(except: [
            'customers/store-ajax', // Masukkan URL route yang error di sini
            'api/kiriminaja/webhook', // Webhook KiriminAja
            //'dana/*',           // Membuka semua jalur DANA
            'dana/notify',      // Spesifik notifikasi
            'dana/callback',    // Spesifik callback
            'dana/pay',
            'dana/return',
            'dana/*', // Tambahkan ini
            //'tripay/callback',        // Callback Tripay (Jika ada)
            //'doku/notify',            // Callback Doku (Jika ada)
            //'api/*',                  // Opsional: Membuka semua route API dari CSRF
        ]);

    })

    ->withSchedule(function (Schedule $schedule) {
        // INI KERNELNYA: Jalankan retry otomatis setiap menit
        // $schedule->command('dana:retry-inquiry')->everyMinute();
    })

    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
