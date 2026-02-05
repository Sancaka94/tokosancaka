<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule; // <-- Tambahkan ini
use Illuminate\Http\Request; // Tambahan import

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php', // <--- PENTING: Mendaftarkan routes/api.php
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        // 1. Percayai Proxy Cloudflare
        $middleware->trustProxies(at: '*');

        // 2. [PERBAIKAN] Panggil Middleware Class (Bukan Closure)
        // Ini akan berjalan paling awal untuk memperbaiki Hostname
        $middleware->prepend(\App\Http\Middleware\FixCloudflareHost::class);

        // Alias Middleware
        $middleware->alias([
            'tenant' => \App\Http\Middleware\TenantMiddleware::class,
            'role'   => \App\Http\Middleware\CheckRole::class,
            'check.status' => \App\Http\Middleware\CheckTenantStatus::class,
            'check.subscription' => \App\Http\Middleware\CheckSubscription::class,
        ]);

        // Cara yang benar untuk menambahkan banyak middleware sekaligus
        $middleware->web(append: [
            //\App\Http\Middleware\TenantMiddleware::class,
            //\App\Http\Middleware\CheckSubscription::class,
            // \App\Http\Middleware\CheckTenantStatus::class,
        ]);
        // PENTING: Mengecualikan Route Webhook dari proteksi CSRF
        // Karena server luar (KiriminAja/Tripay/Doku) tidak memiliki token CSRF aplikasi kita.
        $middleware->validateCsrfTokens(except: [
            'customers/store-ajax', // Masukkan URL route yang error di sini
            'api/kiriminaja/webhook', // Webhook KiriminAja
            '*/tenant/hubungi-admin-api', // Izinkan tembak API tanpa token CSRF khusus route ini
            //'dana/*',           // Membuka semua jalur DANA
            'dana/notify',      // Spesifik notifikasi
            'dana/callback',    // Spesifik callback
            'dana/pay',
            'dana/return',
            'dana/*', // Tambahkan ini
            'tenant/generate-payment', // Tambahkan rute ini ke daftar pengecualian
            //'tripay/callback',        // Callback Tripay (Jika ada)
            //'doku/notify',            // Callback Doku (Jika ada)
            //'api/*',                  // Opsional: Membuka semua route API dari CSRF
            'login',
            'logout',

            // Jaga-jaga jika register juga bermasalah
            'register',

            // Wildcard untuk menangani subdomain (misal: sancaka.tokosancaka.com/login)
            '*/login',
            '*/logout',
            'http://*/login',
            'https://*/login',
            'login',
            'logout',
            'register',
            '*/login',
            '*/logout',
        ]);

    })

    ->withSchedule(function (Schedule $schedule) {
        // INI KERNELNYA: Jalankan retry otomatis setiap menit
        // $schedule->command('dana:retry-inquiry')->everyMinute();
    })

    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
