<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Gunakan DB Facade
use App\Models\Tenant;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // 1. Identifikasi Subdomain
        $host = $request->getHost();
        $subdomain = explode('.', $host)[0];

        // Daftar subdomain yang bukan toko
        $exclude = ['www', 'tokosancaka', 'localhost', 'mail', 'apps'];

        // 2. WHITELIST JALUR API & GENERATE PAYMENT (Supaya tidak kena redirect loop/403)
        if (
            $request->is('api/*') ||
            $request->is('tenant/generate-payment') ||
            $request->routeIs('tenant.suspended') ||
            $request->is('account-suspended') ||
            in_array($subdomain, $exclude)
        ) {
            return $next($request);
        }

        // 3. Cari Tenant di DB KEDUA (mysql_second)
        $tenant = DB::connection('mysql')
                    ->table('tenants')
                    ->where('subdomain', $subdomain)
                    ->first();

        if (!$tenant) {
            abort(404);
        }

        // Simpan data tenant ke request (opsional, buat helper view)
        $request->merge(['current_tenant' => (array) $tenant]);

        // 4. CEK EXPIRED
        if ($tenant->expired_at && now()->gt($tenant->expired_at)) {
            // Jika expired, update status jadi inactive
            if ($tenant->status !== 'inactive') {
                DB::connection('mysql')
                  ->table('tenants')
                  ->where('id', $tenant->id)
                  ->update(['status' => 'inactive']);
            }

            // Redirect ke halaman suspended (kecuali API)
            if (!$request->expectsJson()) {
                return redirect()->route('tenant.suspended');
            }
        }

        return $next($request);
    }
}
