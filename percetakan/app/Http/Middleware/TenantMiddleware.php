<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
{
    $host = $request->getHost(); // Contoh: gemini.tokosancaka.com
    $subdomain = explode('.', $host)[0];

    // Daftar subdomain yang harus diabaikan (milik sistem utama)
    $exclude = ['www', 'tokosancaka', 'localhost', 'mail'];

    if (!in_array($subdomain, $exclude)) {
        // Cari tenant di database berdasarkan subdomain
        $tenant = \App\Models\Tenant::where('subdomain', $subdomain)->first();

        if (!$tenant) {
            abort(404, "Toko '$subdomain' belum terdaftar.");
        }

        // Simpan data tenant ke dalam request agar bisa dipanggil di Controller manapun
        $request->merge(['current_tenant' => $tenant]);
    }

    if ($tenant->expired_at && now()->gt($tenant->expired_at)) {
        // Jika lewat tanggal expired, ubah status jadi inactive
        $tenant->update(['status' => 'inactive']);
        abort(403, "Masa aktif paket Bapak telah habis. Silakan hubungi Sancaka untuk perpanjang.");
    }

    return $next($request);
}
}
