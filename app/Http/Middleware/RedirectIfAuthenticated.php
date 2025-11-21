<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {

            if (Auth::user()->role === 'admin') {
                return redirect('/admin/dashboard');
            }

            // Perbaikan: wajib pakai slash di depan
            return redirect('/customer/dashboard');
        }

        return $next($request);
    }
}
