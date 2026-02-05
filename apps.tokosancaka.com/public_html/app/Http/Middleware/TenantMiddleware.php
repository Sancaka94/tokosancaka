<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Tenant;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\URL; // Wajib Import

class TenantMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // 1. AMBIL SUBDOMAIN
        $host = $request->getHost();
        $parts = explode('.', $host);
        $subdomain = $parts[0];

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
        URL::defaults(['subdomain' => $subdomain]);

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
        // [RULE 4] CEK DATABASE TENANT
        // -------------------------------------------------------------
        $tenant = Tenant::where('subdomain', $subdomain)->first();

        // [MODIFIKASI PERMINTAAN KAMU]
        // Jika subdomain TIDAK ADA, lempar ke Halaman Daftar
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
