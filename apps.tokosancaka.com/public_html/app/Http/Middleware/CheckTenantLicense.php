<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\License;

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

        // 2. CEK TABEL TENANTS (Apakah tokonya ada dan aktif?)
        $tenant = \Illuminate\Support\Facades\DB::table('tenants')->where('subdomain', $subdomain)->first();

        if (!$tenant || $tenant->status !== 'active') {
             return redirect()->to('https://apps.tokosancaka.com/redeem-lisensi?subdomain=' . $subdomain)
                              ->with('error', 'Akses Ditolak: Toko Anda belum diaktifkan.');
        }

        if ($tenant->expired_at) {
            $expiredDate = \Carbon\Carbon::parse($tenant->expired_at);
            if (now()->isAfter($expiredDate)) {
                // Otomatis matikan toko jika expired
                \Illuminate\Support\Facades\DB::table('tenants')->where('id', $tenant->id)->update(['status' => 'inactive']);
                return redirect()->to('https://apps.tokosancaka.com/redeem-lisensi?subdomain=' . $subdomain)
                                 ->with('error', 'Akses Ditolak: Masa aktif toko telah habis.');
            }
        } else {
             return redirect()->to('https://apps.tokosancaka.com/redeem-lisensi?subdomain=' . $subdomain)
                              ->with('error', 'Akses Ditolak: Data masa aktif toko tidak valid.');
        }

        // 3. CEK TABEL LICENSES (VALIDASI SILANG YANG ANDA MINTA)
        // Kita cari apakah BENAR ADA lisensi fisik yang terikat ke ID Toko ini, statusnya 'used', dan belum expired
        $activeLicense = \App\Models\License::withoutGlobalScopes()
                            ->where('used_by_tenant_id', $tenant->id)
                            ->where('status', 'used')
                            ->where('expires_at', '>', now())
                            ->exists();

        // Jika di tabel 'tenants' dibilang aktif, TAPI di tabel 'licenses' datanya hilang/dihapus:
        if (!$activeLicense) {
            return redirect()->to('https://apps.tokosancaka.com/redeem-lisensi?subdomain=' . $subdomain)
                             ->with('error', 'Sistem mendeteksi anomali: Lisensi Anda tidak valid atau telah dicabut. Silakan masukkan kode lisensi baru.');
        }

        // Lolos semua hadangan: Toko aktif & Lisensi fisik ada!
        return $next($request);
    }
}
