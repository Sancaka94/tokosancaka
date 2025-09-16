<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // Jika request bukan mengharapkan JSON, lanjutkan.
        if (! $request->expectsJson()) {
            
            // Jika pengguna mencoba mengakses URL yang diawali dengan 'admin'
            if ($request->is('admin') || $request->is('admin/*')) {
                // --- DIPERBARUI: Menggunakan url() untuk path absolut ---
                // Ini lebih andal daripada route() jika ada masalah cache.
                return url('/admin/login'); 
            }

            // Jika pengguna mencoba mengakses URL yang diawali dengan 'customer'
            if ($request->is('customer') || $request->is('customer/*')) {
                // --- DIPERBARUI: Menggunakan url() untuk path absolut ---
                return url('/customer/login');
            }

            // Untuk kasus lain, arahkan ke halaman login customer sebagai default.
            return url('/customer/login');
        }

        return null;
    }
}
