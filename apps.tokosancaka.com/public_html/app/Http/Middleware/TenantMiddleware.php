<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Tenant;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // [DEBUG LOG] Cek apakah DANA sampai sini
        if ($request->is('dana/*')) {
            \Illuminate\Support\Facades\Log::info('Middleware: DANA Request Detected passing through...');
            return $next($request); // <--- JALUR VIP
        }

        // 1. AMBIL SUBDOMAIN
        $host = $request->getHost();
        $parts = explode('.', $host);
        $subdomain = $parts[0];

        \Illuminate\Support\Facades\URL::defaults(['subdomain' => $subdomain]);
        
        // -------------------------------------------------------------
        // [MAGIC FIX: PAKSA CONFIG DI RUNTIME UNTUK DEMO]
        // -------------------------------------------------------------
        if ($subdomain === 'demo') {
            config(['database.connections.mysql_demo' => [
                'driver'    => 'mysql',
                'host'      => env('DB_HOST_DEMO', '127.0.0.1'),
                'port'      => env('DB_PORT_DEMO', '3306'),
                'database'  => env('DB_DATABASE_DEMO', 'tokq3391_demo'),
                'username'  => env('DB_USERNAME_DEMO', 'tokq3391_demo'),
                'password'  => env('DB_PASSWORD_DEMO', ''),
                'charset'   => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix'    => '',
                'strict'    => true,
                'engine'    => null,
            ]]);

            Config::set('database.default', 'mysql_demo');
            DB::purge('mysql_demo');
            DB::reconnect('mysql_demo');

            View::share('currentTenant');
            return $next($request);
        }

        // -------------------------------------------------------------
        // [RULE 1] JIKA AKSES DOMAIN UTAMA (APPS/WWW), LANGSUNG LEWAT
        // -------------------------------------------------------------
        if ($subdomain === 'apps' || $subdomain === 'www') {
            return $next($request);
        }

        // -------------------------------------------------------------
        // [RULE 2] CEK DATABASE TENANT TERLEBIH DAHULU (Pindah ke Atas)
        // -------------------------------------------------------------
        // Kita harus ambil data ini dulu agar halaman Suspended bisa menggunakannya
        $tenant = Tenant::where('subdomain', $subdomain)->first();

        if (!$tenant) {
            return redirect()->away('https://apps.tokosancaka.com/daftar-pos');
        }

        // -------------------------------------------------------------
        // [RULE 3] INJEKSI DATA KE APLIKASI
        // -------------------------------------------------------------
        $request->merge(['tenant' => $tenant]);
        View::share('currentTenant', $tenant);

        // -------------------------------------------------------------
        // [RULE 4] WHITELIST ROUTE TERTENTU (Setelah Data Tenant Didapat)
        // -------------------------------------------------------------
        // Jika user mengakses halaman ini, biarkan lewat (tidak peduli status tenant)
        if (
            $request->is('api/*') ||
            $request->is('dana/*') ||
            $request->is('tenant/generate-payment') ||
            $request->is('account-suspended') ||
            $request->routeIs('tenant.suspended')
        ) {
            return $next($request);
        }

        // -------------------------------------------------------------
        // [RULE 5] CEK EXPIRED / INACTIVE
        // -------------------------------------------------------------
        // Jika statusnya habis atau inactive, lempar ke URL /account-suspended
        if ($tenant->expired_at && now()->gt($tenant->expired_at)) {
            if ($tenant->status !== 'inactive') {
                $tenant->update(['status' => 'inactive']);
            }
            return redirect('/account-suspended'); // Gunakan absolute path
        }

        if ($tenant->status === 'inactive') {
             return redirect('/account-suspended'); // Gunakan absolute path
        }

        return $next($request);
    }
}