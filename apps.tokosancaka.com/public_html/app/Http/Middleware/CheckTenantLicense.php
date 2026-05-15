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

        $excludedSubdomains = ['apps', 'admin', 'www', 'localhost', '127', 'demo'];
        if (in_array($subdomain, $excludedSubdomains)) {
            return $next($request);
        }

        // Whitelist agar tidak infinite redirect
        if ($request->is('suspended') || $request->is('api/*') || $request->is('dana/*')) {
            return $next($request);
        }

        // 2. CEK TABEL TENANTS
        $tenant = DB::table('tenants')->where('subdomain', $subdomain)->first();

        if (!$tenant) {
            return redirect()->away('https://apps.tokosancaka.com/daftar-pos');
        }

        // -------------------------------------------------------------------
        // [LOGIKA 1] JIKA BELUM REDEEM (KODE PERTAMA ANDA)
        // -------------------------------------------------------------------
        $pendingLicense = DB::table('licenses')
            ->where('tenant_id', $tenant->id)
            ->where('status', 'available')
            ->exists();

        if ($pendingLicense) {
            // Mengembalikan ke rute pusat sesuai kode awal Anda
            return redirect()->to('https://apps.tokosancaka.com/redeem-lisensi?subdomain=' . $subdomain)
                             ->with('info', 'Silakan aktivasi layanan Anda.');
        }

        // -------------------------------------------------------------------
        // [LOGIKA 2] JIKA EXPIRED / INACTIVE (KE HALAMAN SUSPEND LOKAL)
        // -------------------------------------------------------------------
        if ($tenant->status !== 'active') {
            return redirect('/suspended');
        }

        if ($tenant->expired_at) {
            $expiredDate = Carbon::parse($tenant->expired_at)->timezone('Asia/Jakarta');
            
            if (now()->timezone('Asia/Jakarta')->isAfter($expiredDate)) {
                DB::table('tenants')->where('id', $tenant->id)->update(['status' => 'inactive']);
                return redirect('/suspended');
            }
        }

        // -------------------------------------------------------------------
        // LOLOS SEMUA: TOKO AKTIF & SUDAH REDEEM
        // -------------------------------------------------------------------
        View::share('currentTenant', $tenant);
        $request->merge(['tenant' => $tenant]);

        return $next($request);
    }
}