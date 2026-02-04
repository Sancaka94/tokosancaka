<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IsAgent
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        // LOGIKA CERDAS:
        // Halaman Agent hanya boleh dibuka oleh: Agent atau Admin
        // Seller & Pelanggan DILARANG masuk sini.
        $allowedRoles = ['agent', 'admin'];

        if ($user && in_array($user->role, $allowedRoles)) {
            return $next($request);
        }

        // Jika user mencoba masuk tapi bukan agent, tawarkan untuk daftar
        return redirect()->route('agent.register.index');
    }
}