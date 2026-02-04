<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; // Tambahkan ini

class CheckRole
{
    public function handle(Request $request, Closure $next, $roles)
    {
        // 1. Cek apakah user dianggap Login oleh Laravel?
        if (!Auth::check()) {
            Log::warning('CheckRole: User terdeteksi BELUM LOGIN / Session Hilang.', [
                'url' => $request->url(),
                'ip' => $request->ip()
            ]);
            return redirect('login');
        }

        $user = Auth::user();

        // 2. Log data user untuk melihat role aslinya di database
        Log::info('CheckRole: Pengecekan Akses', [
            'user_id' => $user->id,
            'email' => $user->email,
            'role_di_database' => $user->role, // <--- Ini kuncinya!
            'role_yang_diminta' => $roles
        ]);

        // --- BYPASS SUPER ADMIN ---
        if ($user->role === 'super_admin') {
            return $next($request);
        }

        $allowedRoles = explode('|', $roles);

        // 3. Cek apakah role user ada di daftar yang diizinkan
        if (in_array($user->role, $allowedRoles)) {
            return $next($request);
        }

        // Jika ditolak, catat lognya
        Log::error('CheckRole: AKSES DITOLAK (403)', [
            'reason' => 'Role user tidak cocok dengan role yang diminta',
            'user_role' => $user->role,
            'allowed' => $allowedRoles
        ]);

        abort(403, 'Anda tidak memiliki akses ke halaman ini.');
    }
}
