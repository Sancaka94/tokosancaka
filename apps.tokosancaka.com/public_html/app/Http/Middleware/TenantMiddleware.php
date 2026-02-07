<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Tenant;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\URL; // Wajib Import
use Illuminate\Support\Facades\Config; // Tambahkan ini
use Illuminate\Support\Facades\DB;     // Tambahkan ini

class TenantMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // 1. AMBIL SUBDOMAIN
        $host = $request->getHost();
        $parts = explode('.', $host);
        $subdomain = $parts[0];

        \Illuminate\Support\Facades\URL::defaults(['subdomain' => $subdomain]);

        // -------------------------------------------------------------
        // [MODIFIKASI KHUSUS DEMO]
        // Jika subdomain adalah 'demo', paksa gunakan database demo
        // -------------------------------------------------------------
        if ($subdomain === 'demo') {
            // Ubah koneksi default secara runtime ke mysql_demo
            Config::set('database.default', 'mysql_demo');
            DB::purge('mysql_demo'); // Bersihkan cache koneksi agar perubahan diterapkan

            // Lewati pengecekan tabel 'tenants' karena ini database statis/demo
            return $next($request);
        }

        // -------------------------------------------------------------
        // [RULE 1] JIKA AKSES DOMAIN UTAMA (APPS), LANGSUNG LEWAT
        // -------------------------------------------------------------
        // PENTING: 'apps' dan 'www' tidak boleh dicek di database tenant
        if ($subdomain === 'apps' || $subdomain === 'www') {
            return $next($request);
        }

        // -------------------------------------------------------------
        // [RULE 2] SET DEFAULT URL PARAMETER (WAJIB PALING ATAS)
        // -------------------------------------------------------------
        // Agar tidak error "Missing parameter: subdomain"
        //URL::defaults(['subdomain' => $subdomain]);

        // -------------------------------------------------------------
        // [RULE 3] WHITELIST ROUTE TERTENTU
        // -------------------------------------------------------------
        // API & Payment Gateway harus lolos tanpa cek tenant
        if (
            $request->is('api/*') ||
            $request->is('tenant/generate-payment') ||
            $request->routeIs('tenant.suspended')
        ) {
            return $next($request);
        }

        // -------------------------------------------------------------
        // [RULE 4] CEK DATABASE TENANT (Untuk subdomain selain apps & demo)
        // -------------------------------------------------------------
        $tenant = Tenant::where('subdomain', $subdomain)->first();

        if (!$tenant) {
            return redirect()->away('https://apps.tokosancaka.com/daftar-pos');
        }

        // -------------------------------------------------------------
        // [RULE 5] CEK EXPIRED / INACTIVE
        // -------------------------------------------------------------
        if ($tenant->expired_at && now()->gt($tenant->expired_at)) {
            if ($tenant->status !== 'inactive') {
                $tenant->update(['status' => 'inactive']);
            }
            if (!$request->expectsJson()) {
                return redirect()->route('tenant.suspended');
            }
        }

        if ($tenant->status === 'inactive' && !$request->expectsJson()) {
             return redirect()->route('tenant.suspended');
        }

        // -------------------------------------------------------------
        // [RULE 6] INJEKSI DATA KE APLIKASI
        // -------------------------------------------------------------
        $request->merge(['tenant' => $tenant]);
        View::share('currentTenant', $tenant);

        return $next($request);
    }
}
