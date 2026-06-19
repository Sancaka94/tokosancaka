<?php

use Illuminate\Foundation\Application;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // ✅ DIPERBAIKI: Daftarkan middleware di sini
        $middleware->web(append: [
            \App\Http\Middleware\UpdateUserLastSeenAt::class,
            \App\Http\Middleware\UpdateLastSeen::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'api/webhook/kiriminaja', // <--- Tambahkan ini
            'digiflazz/webhook', // <--- Tambahkan baris ini
            'payment/*', // Jika ada webhook payment lain
            'webhook/fonnte', // <--- Tambahkan baris ini
            //'api/telegram/webhook', // Sesuaikan dengan route
            'api/webhook/doku-jokul', // <--- PASTIIN INI ADA
            'api/callback/tripay',
            'api/callback/doku',
            'telegram-webhook',
            'doku/*',
            'ppob/webhook',
            'api/midtrans/*', // Mengecualikan semua route di dalam prefix midtrans
            'api/mobile/dana/notify',
            'api/mobile/dana/callback',
            'api/mobile/dana/*',
            'dana/*',
            'api/dana/*',
            'dana/notify', // Sesuaikan dengan route webhook DANA Anda
            'dana/callback',
            'api/topup-dana',
            'api/topup-dana/*',
            'api/admin/dana/*',  // Mengecualikan rute Transfer & Inquiry Bank yang baru kita buat
            'api/*',             // (OPSIONAL TAPI SANGAT DISARANKAN) Mengecualikan SEMUA rute yang berawalan api/ agar kamu tidak pusing lagi ke depannya
            'api/webhook/paypal',
            'webhook/deliveree',
            'webhook/lalamove',
            'destination/inquiry',
            'order/create',
            'order/detail',

            ]);

        // --- TAMBAHKAN BAGIAN INI ---
        $middleware->alias([
            'is_agent' => \App\Http\Middleware\IsAgent::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions) {

    $exceptions->render(function (TokenMismatchException $e, Request $request) {

            // Arahkan paksa ke login
            return redirect()
                ->route('login')
                ->with('error', 'Sesi Anda telah habis. Silakan login kembali.');
        });

            //
    })->create();
