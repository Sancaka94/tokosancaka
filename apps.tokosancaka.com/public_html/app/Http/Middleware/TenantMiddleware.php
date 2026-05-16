<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Tenant;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // 1. JALUR VIP: Biarkan webhook / callback lewat tanpa halangan
        if ($request->is('dana/*') || $request->is('api/*')) {
            return $next($request);
        }

        // 2. DETEKSI SUBDOMAIN
        $host = $request->getHost();
        $parts = explode('.', $host);
        $subdomain = $parts[0] ?? '';

        URL::defaults(['subdomain' => $subdomain]);

        // 3. PENGECUALIAN DOMAIN UTAMA / DEMO
        $excludedSubdomains = ['apps', 'admin', 'www', 'localhost', '127'];
        if (in_array($subdomain, $excludedSubdomains)) {
            return $next($request);
        }

        // [MAGIC FIX: PAKSA CONFIG DI RUNTIME UNTUK DEMO]
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
            View::share('currentTenant', null);
            return $next($request);
        }

        // 4. AMBIL DATA TENANT
        $tenant = Tenant::where('subdomain', $subdomain)->first();

        // Jika tenant tidak ditemukan, lempar ke pendaftaran
        if (!$tenant) {
            return redirect()->away('https://apps.tokosancaka.com/daftar-pos');
        }

        // 5. INJEKSI DATA TENANT (Agar bisa dipakai di Controller & View)
        $request->merge(['tenant' => $tenant]);
        View::share('currentTenant', $tenant);
        $request->attributes->add(['tenant' => $tenant]);

        // 6. WHITELIST URL (Halaman yang boleh diakses meski Expired)
        if (
            $request->is('account-suspended') || 
            $request->is('*account-suspended*') || 
            $request->is('suspended') || 
            $request->is('tenant/generate-payment') ||
            $request->is('logout') ||
            $request->is('*/logout')
        ) {
            return $next($request);
        }

        // 7. CEK STATUS EXPIRED / INACTIVE
        $isExpired = false;
        if ($tenant->expired_at) {
            $expiredDate = Carbon::parse($tenant->expired_at)->timezone('Asia/Jakarta');
            $isExpired = now()->timezone('Asia/Jakarta')->isAfter($expiredDate);
        }

        if ($isExpired || $tenant->status === 'inactive' || $tenant->status === 'suspended') {
            // Update status DB ke inactive jika murni karena expired waktu
            if ($isExpired && $tenant->status !== 'inactive') {
                $tenant->update(['status' => 'inactive']);
            }
            
            // Arahkan ke halaman Suspended dengan aman
            return redirect('/account-suspended'); 
        }

        // 8. CEK LISENSI REDEEM (Hanya jika toko sedang Aktif)
        $pendingLicense = DB::table('licenses')
            ->where('tenant_id', $tenant->id)
            ->where('status', 'available')
            ->exists();

        if ($pendingLicense) {
            return redirect()->to('https://apps.tokosancaka.com/redeem-lisensi?subdomain=' . $subdomain)
                             ->with('info', 'Silakan aktivasi layanan Anda.');
        }

        // 9. LOLOS SEMUA PENGECEKAN
        return $next($request);
    }
}