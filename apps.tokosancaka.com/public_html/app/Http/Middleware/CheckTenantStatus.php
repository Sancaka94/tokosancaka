<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckTenantStatus
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Cek hanya jika User Login & Punya Tenant
        if ($user && $user->tenant) {
            $tenant = $user->tenant;

            // 1. TENTUKAN STATUS
            // Expired jika: Tanggal lewat DAN Status bukan 'active'
            // (Kita beri toleransi jika status 'active' meski tanggal lewat, misal bonus hari dari admin)
            $isExpired = ($tenant->expired_at && now()->gt($tenant->expired_at));
            $isActive = ($tenant->status === 'active' || !$isExpired);

            // 2. CEK HALAMAN SAAT INI
            // Apakah user sedang di halaman "account-suspended"?
            $onSuspendedPage = $request->is('*account-suspended*');

            // URL Dinamis (Mengikuti subdomain user saat ini)
            $baseUrl = "https://{$tenant->subdomain}.tokosancaka.com";


            // --- SKENARIO 1: AKUN SUDAH AKTIF, TAPI MASIH DI HALAMAN SUSPENDED ---
            // (Kasus: User baru saja bayar dan sukses, halaman refresh)
            if ($isActive && $onSuspendedPage) {
                // Tendang balik ke Dashboard (Halaman Utama)
                return redirect()->to($baseUrl . "/dashboard");
            }


            // --- SKENARIO 2: AKUN EXPIRED, COBA BUKA HALAMAN APAPUN ---
            // (Kasus: User expired mau nyoba buka dashboard, laporan, atau setting)
            if ($isExpired && !$onSuspendedPage) {

                // Pengecualian: Jangan blokir Logout/Login biar gak nyangkut
                if (!$request->is('logout') && !$request->is('login') && !$request->is('api/*')) {
                    // Tendang paksa ke halaman Suspended
                    return redirect()->to($baseUrl . "/account-suspended");
                }
            }
        }

        return $next($request);
    }
}
