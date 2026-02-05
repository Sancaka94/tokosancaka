<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Tenant;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\URL; // Jangan lupa ini

class TenantMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // 1. AMBIL HOST & SUBDOMAIN
        $host = $request->getHost();
        $subdomain = explode('.', $host)[0];

        // ==================================================================
        // [PERBAIKAN] SET DEFAULT URL PARAMETER (PINDAH KE SINI - PALING ATAS)
        // ==================================================================
        // Agar route() tidak error meskipun subdomain masuk daftar exclude (seperti admin)
        URL::defaults(['subdomain' => $subdomain]);

        // 2. DAFTAR PENGECUALIAN
        // Jika 'admin' adalah Toko Pusat yang ada di database tenants,
        // sebaiknya HAPUS 'admin' dari daftar ini.
        $exclude = ['www', 'tokosancaka', 'localhost', 'mail', 'apps', 'system'];

        // 3. WHITELIST JALUR
        if (
            $request->is('api/*') ||
            $request->is('tenant/generate-payment') ||
            $request->routeIs('tenant.suspended') ||
            in_array($subdomain, $exclude)
        ) {
            return $next($request);
        }

        // 4. CARI TENANT DI DATABASE
        $tenant = Tenant::where('subdomain', $subdomain)->first();

        if (!$tenant) {
            abort(404);
        }

        // 5. CEK EXPIRED
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

        // 6. INJEKSI DATA TENANT
        $request->merge(['tenant' => $tenant]);
        View::share('currentTenant', $tenant);

        return $next($request);
    }
}
