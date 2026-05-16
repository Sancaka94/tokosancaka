<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CheckTenantStatus
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. DETEKSI TENANT BERDASARKAN URL BROWSER (Bukan dari Auth User)
        $host = $request->getHost();
        $subdomain = explode('.', $host)[0];

        // Ambil data tenant yang punya subdomain ini
        $tenant = DB::table('tenants')->where('subdomain', $subdomain)->first();

        // Jika domain yang diakses bukan bagian dari tenant (misal domain utama), loloskan saja
        if (!$tenant) {
            return $next($request);
        }

        // 2. TENTUKAN STATUS BERDASARKAN DATA TENANT DI URL
        // Expired jika: Ada tanggal expired DAN tanggalnya sudah terlewat dari waktu sekarang
        $isExpired = ($tenant->expired_at && Carbon::now()->gt(Carbon::parse($tenant->expired_at)));
        
        // Tetap dianggap Aktif jika statusnya 'active' ATAU memang belum expired
        $isActive = ($tenant->status === 'active' || !$isExpired);

        // 3. CEK HALAMAN SAAT INI
        $onSuspendedPage = $request->is('*account-suspended*');

        // URL Dinamis mengikuti subdomain yang sedang aktif di browser
        $protocol = $request->secure() ? 'https://' : 'http://';
        $baseUrl = "{$protocol}{$tenant->subdomain}.tokosancaka.com";


        // --- SKENARIO 1: AKUN SUDAH AKTIF, TAPI MASIH DI HALAMAN SUSPENDED ---
        // (Kasus: Toko masih aktif seperti 'gerai', tapi iseng/tersasar buka halaman suspended)
        if ($isActive && $onSuspendedPage) {
            // Tendang balik ke Dashboard subdomain itu sendiri (Halaman Utama)
            return redirect()->to($baseUrl . "/dashboard");
        }


        // --- SKENARIO 2: AKUN EXPIRED, COBA BUKA HALAMAN APAPUN ---
        // (Kasus: User expired mau nyoba buka dashboard, laporan, atau setting)
        if ($isExpired && !$onSuspendedPage) {

            // Pengecualian: Jangan blokir Logout/Login/API/Proses Bayar Tenant biar tidak nyangkut
            if (!$request->is('logout') && 
                !$request->is('login') && 
                !$request->is('api/*') && 
                !$request->is('tenant/*') &&
                !$request->is('*/login') && 
                !$request->is('*/logout')) {
                
                // Tendang paksa ke halaman Suspended milik subdomain tersebut
                return redirect()->to($baseUrl . "/account-suspended");
            }
        }

        return $next($request);
    }
}