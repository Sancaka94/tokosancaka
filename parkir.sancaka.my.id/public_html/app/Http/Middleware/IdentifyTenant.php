<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Tenant;

class IdentifyTenant
{
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost(); // Menangkap parkir.sancaka.my.id, toko1.sancaka.my.id, dll

        $parts = explode('.', $host);
        $subdomain = strtolower($parts[0]); // Ambil kata paling depan dan pastikan huruf kecil

        // 1. Buat daftar Subdomain Utama milik Anda di sini (Tambahkan jika ada yang baru)
        $mainWebsites = [
            'parkir',
            'bisnis',
            'panduan',
            'percetakan',
            'www'
        ];

        // 2. Cek apakah yang diakses adalah web utama Anda atau root domain (sancaka.my.id)
        if (in_array($subdomain, $mainWebsites) || count($parts) <= 2) {

            // Ini adalah Web Utama Anda (Akses Superadmin / Landing Page)
            app()->instance('tenant_id', null);

        } else {

            // 3. Jika bukan web utama, baru cari di database sebagai Tenant (contoh: toko1)
            $tenant = Tenant::where('subdomain', $subdomain)->first();

            if ($tenant) {
                // Tenant ditemukan, set ID-nya untuk memfilter data
                app()->instance('tenant_id', $tenant->id);
            } else {
                // Tenant tidak ada di database, lempar error 404
                abort(404, 'Halaman Tenant tidak ditemukan.');
            }
        }

        return $next($request);
    }
}
