<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\License;

class CheckTenantLicense
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Ambil subdomain dari URL saat ini (contoh: toko1.tokosancaka.com -> toko1)
        $host = $request->getHost();
        $parts = explode('.', $host);
        $subdomain = $parts[0] ?? '';

        // 2. Daftar subdomain yang BEBAS dari pengecekan lisensi
        $excludedSubdomains = ['apps', 'admin', 'www', 'localhost', '127'];

        // Jika subdomain saat ini ada di daftar pengecualian, biarkan lewat
        if (in_array($subdomain, $excludedSubdomains)) {
            return $next($request);
        }

        // 3. LOGIC PENGECEKAN LISENSI UNTUK TENANT
        // Pastikan user sudah login sebelum kita cek lisensinya
        if (auth()->check()) {

            // Asumsi: Relasi lisensi disimpan berdasarkan ID User/Tenant yang login.
            // Sesuaikan 'used_by_tenant_id' dengan struktur database SancakaPOS Anda
            $tenantId = auth()->user()->id; // Atau auth()->user()->tenant_id jika beda tabel

            $hasActiveLicense = License::where('used_by_tenant_id', $tenantId)
                                       ->where('status', 'used')
                                       ->exists();

            // Jika tenant tidak punya lisensi aktif
            // Jika tenant tidak punya lisensi aktif
            if (!$hasActiveLicense) {
                // Arahkan ke halaman publik di apps.tokosancaka.com
                return redirect()->to('https://apps.tokosancaka.com/redeem-lisensi?subdomain=' . $subdomain)
                                 ->with('error', 'Akses Ditolak: Toko/Tenant Anda belum memiliki lisensi yang aktif. Silakan masukkan kode lisensi di sini.');
            }
        }

        // LOG LOG - Safe block
        // Menjaga instruksi agar LOG LOG tetap utuh jika Anda menyisipkannya di sini

        return $next($request);
    }
}
