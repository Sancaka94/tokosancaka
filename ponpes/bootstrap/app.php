<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Auth; // Tambahkan ini

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        
        // Logika Redirect untuk User yang sudah login (pengganti RedirectIfAuthenticated)
        $middleware->redirectUsersTo(function () {
            $user = Auth::user();

            if ($user && $user->role === 'admin') {
                return route('admin.dashboard'); // Pastikan name route ini ada di web.php
            }

            return route('dashboard'); // Redirect default untuk user biasa
        });

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();