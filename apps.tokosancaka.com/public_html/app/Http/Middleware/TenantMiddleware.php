<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Tenant;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // Aku nambah iki ben gak error

class TenantMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // 1. JUPUK SUBDOMAIN (Contoh: 'apps', 'toko1', 'demo')
        $host = $request->getHost();
        $parts = explode('.', $host);
        $subdomain = $parts[0];

        // Set default URL ben gak error route
        URL::defaults(['subdomain' => $subdomain]);

        // -------------------------------------------------------------
        // [RULE 0] JALUR VIP (API, DANA, PAYMENT) - GAK USAH DICEK
        // -------------------------------------------------------------
        if (
            $request->is('api/*') ||
            $request->is('dana/*') ||
            $request->is('tenant/generate-payment') ||
            $request->routeIs('tenant.suspended')
        ) {
            return $next($request);
        }

        // -------------------------------------------------------------
        // [RULE 1] KHUSUS DOMAIN UTAMA (APPS / WWW) - IKI SING GARAI ERROR MAU
        // -------------------------------------------------------------
        if ($subdomain === 'apps' || $subdomain === 'www') {

            // KITA NGAPUSI SYSTEM, BEN DIKIRA ONO TENANT E
            $systemTenant = new Tenant();
            $systemTenant->name = 'Sancaka Central Admin';
            $systemTenant->subdomain = 'apps';
            $systemTenant->status = 'active';

            // Share data 'palsu' iki nang View ben gak error variable
            View::share('currentTenant', $systemTenant);

            return $next($request);
        }

        // -------------------------------------------------------------
        // [RULE 2] KHUSUS DEMO
        // -------------------------------------------------------------
        if ($subdomain === 'demo') {
            // Setting database manual gawe demo
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

            // Share Dummy Tenant demo
            $demoTenant = new Tenant();
            $demoTenant->name = 'Toko Demo Sancaka';
            $demoTenant->subdomain = 'demo';
            $demoTenant->status = 'active';
            View::share('currentTenant', $demoTenant);

            return $next($request);
        }

        // -------------------------------------------------------------
        // [RULE 3] CEK DATABASE TENANT (GAWE TOKO MEMBER)
        // -------------------------------------------------------------
        $tenant = Tenant::where('subdomain', $subdomain)->first();

        // Nek tenant gak onok -> Lempar nang pendaftaran
        if (!$tenant) {
            return redirect()->away('https://apps.tokosancaka.com/daftar-pos');
        }

        // -------------------------------------------------------------
        // [RULE 4] CEK MASA AKTIF / EXPIRED
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
        // [RULE 5] BERES KABEH, LEBOKNO DATA NANG SYSTEM
        // -------------------------------------------------------------
        $request->merge(['tenant' => $tenant]);
        View::share('currentTenant', $tenant);

        return $next($request);
    }
}
