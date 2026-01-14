<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

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
        ]);

        $middleware->validateCsrfTokens(except: [
            'digiflazz/webhook', // <--- Tambahkan baris ini
            'payment/*', // Jika ada webhook payment lain
            'webhook/fonnte', // <--- Tambahkan baris ini
            //'api/telegram/webhook', // Sesuaikan dengan route
            'telegram-webhook',
            'dana/notify', // URL Webhook DANA dibebaskan dari CSRF
        ]);

        // --- TAMBAHKAN BAGIAN INI ---
        $middleware->alias([
            'is_agent' => \App\Http\Middleware\IsAgent::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
