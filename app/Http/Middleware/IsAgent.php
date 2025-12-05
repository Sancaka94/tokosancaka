<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class IsAgent
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Cek apakah user login DAN rolenya 'agent'
        if (Auth::check() && Auth::user()->role === 'agent') {
            return $next($request);
        }

        // Jika bukan agen, lempar ke halaman pendaftaran
        return redirect()->route('agent.register.index')->with('warning', 'Akses ditolak. Silakan daftar jadi Agen dulu.');
    }
}