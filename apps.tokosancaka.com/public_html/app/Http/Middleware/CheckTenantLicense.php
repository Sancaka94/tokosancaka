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
        // 1. Ambil subdomain dari URL saat ini (contoh: operator.tokosancaka.com -> operator)
        $host = $request->getHost();
        $parts = explode('.', $host);
        $subdomain = $parts[0] ?? '';

        // 2. Daftar subdomain yang BEBAS dari pengecekan lisensi
        $excludedSubdomains = ['apps', 'admin', 'www', 'localhost', '127'];

        // Jika subdomain saat ini ada di daftar pengecualian, biarkan lewat
        if (in_array($subdomain, $excludedSubdomains)) {
            return $next($request);
        }

        // 3. LOGIC PENGECEKAN LISENSI BERDASARKAN SUBDOMAIN
        // Cari data tenant berdasarkan subdomain yang sedang diakses
        $tenant = \Illuminate\Support\Facades\DB::table('tenants')->where('subdomain', $subdomain)->first();

        // Jika tenant tidak ditemukan, atau statusnya bukan 'active'
        if (!$tenant || $tenant->status !== 'active') {
             return redirect()->to('https://apps.tokosancaka.com/redeem-lisensi?subdomain=' . $subdomain)
                              ->with('error', 'Akses Ditolak: Toko Anda belum diaktifkan.');
        }

        // 4. PENGECEKAN TANGGAL KEDALUWARSA (EXPIRED_AT)
        if ($tenant->expired_at) {
            $expiredDate = \Carbon\Carbon::parse($tenant->expired_at);

            // Jika hari ini sudah melewati tanggal expired
            if (now()->isAfter($expiredDate)) {

                // Opsional: Otomatis ubah status tenant jadi inactive di database jika sudah lewat
                \Illuminate\Support\Facades\DB::table('tenants')->where('id', $tenant->id)->update(['status' => 'inactive']);

                return redirect()->to('https://apps.tokosancaka.com/redeem-lisensi?subdomain=' . $subdomain)
                                 ->with('error', 'Akses Ditolak: Masa aktif lisensi Toko Anda telah habis. Silakan perpanjang layanan Anda.');
            }
        } else {
             // Jika expired_at NULL tapi statusnya active, kita asumsikan tidak valid (harus ada tanggal expired)
             return redirect()->to('https://apps.tokosancaka.com/redeem-lisensi?subdomain=' . $subdomain)
                              ->with('error', 'Akses Ditolak: Data masa aktif toko tidak valid.');
        }

        // Jika semua lolos (Tenant ada, status active, belum expired), izinkan masuk!
        return $next($request);
    }
}
