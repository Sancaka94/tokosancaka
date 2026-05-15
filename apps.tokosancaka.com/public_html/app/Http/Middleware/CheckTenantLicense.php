<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\View;

class CheckTenantLicense
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. AMBIL SUBDOMAIN
        $host = $request->getHost();
        $parts = explode('.', $host);
        $subdomain = $parts[0] ?? '';

        // Abaikan pengecekan untuk subdomain utama / khusus
        $excludedSubdomains = ['apps', 'admin', 'www', 'localhost', '127', 'demo'];
        if (in_array($subdomain, $excludedSubdomains)) {
            return $next($request);
        }

        // Whitelist rute agar tidak terjadi Infinite Redirect Loop
        // Tambahkan rute 'redeem' ke dalam whitelist ini
        if ($request->is('suspended') || $request->is('redeem-lisensi') || $request->is('api/*') || $request->is('dana/*')) {
            return $next($request);
        }

        // 2. CEK TABEL TENANTS
        $tenant = DB::table('tenants')->where('subdomain', $subdomain)->first();

        // JIKA TOKO TIDAK ADA
        if (!$tenant) {
            return redirect()->away('https://apps.tokosancaka.com/daftar-pos');
        }

        // 3. LOGIKA BARU: CEK APAKAH BELUM MASUKKAN KODE AKTIVASI (REDEEM)
        // Kita cek apakah ada lisensi milik tenant ini yang statusnya masih 'available'
        $pendingLicense = DB::table('licenses')
            ->where('tenant_id', $tenant->id)
            ->where('status', 'available')
            ->exists();

        if ($pendingLicense) {
            // Jika ada lisensi yang belum di-redeem, arahkan ke halaman redeem
            // Pastikan Anda sudah memiliki rute /redeem-lisensi di web.php
            return redirect('/redeem-lisensi')->with('info', 'Silakan masukkan kode aktivasi Anda untuk melanjutkan.');
        }

        // 4. CEK STATUS AKTIF & EXPIRED
        if ($tenant->status !== 'active') {
            return redirect('/suspended')->with('error', 'Toko Anda sedang ditangguhkan.');
        }

        if ($tenant->expired_at) {
            $expiredDate = Carbon::parse($tenant->expired_at)->timezone('Asia/Jakarta');
            
            if (now()->timezone('Asia/Jakarta')->isAfter($expiredDate)) {
                DB::table('tenants')->where('id', $tenant->id)->update(['status' => 'inactive']);
                return redirect('/suspended')->with('error', 'Masa aktif toko telah habis.');
            }
        }

        // Lolos semua pengecekan
        View::share('currentTenant', $tenant);
        $request->merge(['tenant' => $tenant]);

        return $next($request);
    }
}