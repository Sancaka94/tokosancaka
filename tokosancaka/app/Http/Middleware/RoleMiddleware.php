<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // 1. Cek Login
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        
        // 2. Normalisasi Role User (Ubah ke huruf kecil biar aman)
        // Contoh: 'Agent' jadi 'agent'
        $userRole = strtolower($user->role ?? '');

        // 3. Normalisasi Role yang Diizinkan di Route
        // Mengubah parameter (misal: 'Seller|Pelanggan') menjadi array ['seller', 'pelanggan']
        $allowedRoles = [];
        foreach ($roles as $role) {
            $parts = explode('|', $role);
            foreach ($parts as $part) {
                $allowedRoles[] = strtolower($part);
            }
        }

        // =============================================================
        // LOGIKA HIERARKI (AGENT > SELLER > PELANGGAN)
        // =============================================================

        // A. Cek Kecocokan Langsung (Exact Match)
        // Misal: User 'seller' masuk route 'seller'.
        if (in_array($userRole, $allowedRoles)) {
            return $next($request);
        }

        // B. Logika 'Dewa' (Admin & Agent)
        // Jika User adalah Admin atau Agent, mereka boleh masuk ke area 'Seller' atau 'Pelanggan'
        if (in_array($userRole, ['admin', 'agent'])) {
            // Cek apakah route yang dituju adalah route bawahan?
            if (array_intersect(['seller', 'pelanggan'], $allowedRoles)) {
                return $next($request);
            }
        }

        // C. Logika Seller
        // Jika User adalah Seller, mereka boleh masuk ke area 'Pelanggan'
        if ($userRole === 'seller') {
            if (in_array('pelanggan', $allowedRoles)) {
                return $next($request);
            }
        }

        // =============================================================
        
        // Jika tidak lolos semua pengecekan di atas, tendang ke dashboard default
        // Sesuaikan route redirect dengan nama route dashboard pelanggan Anda
        return redirect()->route('customer.dashboard')
            ->with('error', 'Akses ditolak. Anda tidak memiliki izin untuk halaman tersebut.');
    }
}