<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IsSeller
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        // LOGIKA CERDAS:
        // Halaman Seller boleh dibuka oleh: Seller SENDIRI, Agent (Bos), atau Admin (Dewa)
        $allowedRoles = ['seller', 'agent', 'admin'];

        if ($user && in_array($user->role, $allowedRoles)) {
            return $next($request);
        }

        // Jika bukan siapa-siapa, lempar ke dashboard pelanggan
        return redirect()->route('customer.dashboard')->with('error', 'Anda harus buka toko dulu.');
    }
}