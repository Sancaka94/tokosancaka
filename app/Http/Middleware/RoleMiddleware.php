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
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect('login');
        }

        $user = Auth::user();

        // Gabungkan semua parameter role (misal 'Pelanggan|Seller', 'Admin') 
        // dan pecah stringnya berdasarkan karakter '|' menjadi satu array.
        $allowedRoles = [];
        foreach ($roles as $role) {
            $allowedRoles = array_merge($allowedRoles, explode('|', $role));
        }

        // PERBAIKAN PENTING: Lakukan pengecekan secara case-insensitive.
        // Ini akan memastikan 'Seller' di file rute cocok dengan 'seller' di database.
        $userRole = strtolower($user->role ?? ''); // Ambil role pengguna, ubah ke huruf kecil
        $allowedRolesLower = array_map('strtolower', $allowedRoles); // Ubah semua role yang diizinkan ke huruf kecil

        // Cek apakah role pengguna ada di dalam daftar role yang diizinkan.
        if (in_array($userRole, $allowedRolesLower)) {
            return $next($request);
        }

        // Jika tidak punya akses, tolak dengan halaman 403.
        abort(403, 'Akses Ditolak. Anda tidak memiliki otorisasi yang sesuai.');
    }
}

