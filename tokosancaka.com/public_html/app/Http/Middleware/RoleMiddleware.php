<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // 1. Cek Login
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        $userRole = strtolower($user->role ?? '');

        // 3. Normalisasi Role yang Diizinkan di Route
        $allowedRoles = [];
        foreach ($roles as $role) {
            $parts = explode('|', $role);
            foreach ($parts as $part) {
                $allowedRoles[] = strtolower($part);
            }
        }

        // =============================================================
        // LOGIKA HIERARKI (AGENT > SELLER > PELANGGAN, DLL)
        // =============================================================

        // A. Cek Kecocokan Langsung (Exact Match)
        if (in_array($userRole, $allowedRoles)) {
            return $next($request);
        }

        // B. Logika 'Dewa' (Admin & Agent)
        if (in_array($userRole, ['admin', 'agent'])) {
            if (array_intersect(['seller', 'pelanggan'], $allowedRoles)) {
                return $next($request);
            }
        }

        // C. Logika Seller, Driver, dan Koordinator (Boleh akses Pelanggan)
        if (in_array($userRole, ['seller', 'driver', 'koordinator'])) {
            if (in_array('pelanggan', $allowedRoles)) {
                return $next($request);
            }
        }
        // =============================================================

        // SAFETY CHECK: Mencegah Infinite Redirect Loop
        // Jika user sudah berada di /customer/dashboard tapi tetap ditolak, tampilkan 403.
        if ($request->routeIs('customer.dashboard') || $request->is('customer/dashboard')) {
            abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk halaman dashboard ini.');
        }

        // Jika tidak lolos semua pengecekan, tendang ke dashboard default
        return redirect()->route('customer.dashboard')
            ->with('error', 'Akses ditolak. Anda tidak memiliki izin untuk halaman tersebut.');
    }
}
