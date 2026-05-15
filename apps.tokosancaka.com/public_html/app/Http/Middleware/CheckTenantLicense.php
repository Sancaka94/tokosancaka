<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\License;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CheckTenantLicense
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Ambil subdomain
        $host = $request->getHost();
        $parts = explode('.', $host);
        $subdomain = $parts[0] ?? '';

        $excludedSubdomains = ['apps', 'admin', 'www', 'localhost', '127'];
        if (in_array($subdomain, $excludedSubdomains)) {
            return $next($request);
        }

        // Whitelist agar halaman /suspended tidak kena jebakan redirect lagi (Anti-Loop)
        if ($request->is('suspended')) {
            return $next($request);
        }

        // 2. CEK TABEL TENANTS
        $tenant = DB::table('tenants')->where('subdomain', $subdomain)->first();

        // JIKA TOKO TIDAK ADA ATAU STATUS INACTIVE -> Lempar ke /suspended
        if (!$tenant || $tenant->status !== 'active') {
             return redirect('/suspended')->with('error', 'Akses Ditolak: Toko Anda belum diaktifkan.');
        }

        if ($tenant->expired_at) {
            $expiredDate = Carbon::parse($tenant->expired_at);
            if (now()->isAfter($expiredDate)) {
                // Otomatis matikan toko jika expired
                DB::table('tenants')->where('id', $tenant->id)->update(['status' => 'inactive']);
                
                // Lempar ke /suspended
                return redirect('/suspended')->with('error', 'Akses Ditolak: Masa aktif toko telah habis.');
            }
        } else {
             return redirect('/suspended')->with('error', 'Akses Ditolak: Data masa aktif toko tidak valid.');
        }

        // 3. CEK TABEL LICENSES (VALIDASI SILANG)
        $activeLicense = \App\Models\License::withoutGlobalScopes()
                            ->where('used_by_tenant_id', $tenant->id)
                            ->where('status', 'used')
                            ->where('expires_at', '>', now())
                            ->exists();

        if (!$activeLicense) {
            // Jika lisensi tidak valid, bisa lempar ke suspended agar mereka aktivasi ulang
            return redirect('/suspended')->with('error', 'Lisensi tidak valid atau telah dicabut.');
        }

        return $next($request);
    }
}