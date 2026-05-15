<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\View;

class CheckTenantLicense
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $parts = explode('.', $host);
        $subdomain = $parts[0] ?? '';

        $excludedSubdomains = ['apps', 'admin', 'www', 'localhost', '127', 'demo'];
        if (in_array($subdomain, $excludedSubdomains)) {
            return $next($request);
        }

        // Whitelist wajib
        if ($request->is('suspended') || $request->is('api/*') || $request->is('dana/*')) {
            return $next($request);
        }

        $tenant = DB::table('tenants')->where('subdomain', $subdomain)->first();

        if (!$tenant) {
            return redirect()->away('https://apps.tokosancaka.com/daftar-pos');
        }

        // ===================================================================
        // LOGIKA 1: CEK MATI/EXPIRED DULUAN (PALING ATAS!)
        // ===================================================================
        if ($tenant->status === 'inactive' || $tenant->status === 'suspended') {
            
            // ---> JEBAKAN DEBUGGING <---
            // Kalau tulisan ini muncul di layar putih, berarti MIDDLEWARE SUDAH BENAR 100%.
            // dd('STOP COK! MIDDLEWARE JALAN. Status toko ini INACTIVE. Harusnya lari ke /suspended.');
            
            return redirect('/suspended');
        }

        if ($tenant->expired_at) {
            $expiredDate = Carbon::parse($tenant->expired_at)->timezone('Asia/Jakarta');
            if (now()->timezone('Asia/Jakarta')->isAfter($expiredDate)) {
                DB::table('tenants')->where('id', $tenant->id)->update(['status' => 'inactive']);
                return redirect('/suspended');
            }
        }

        // ===================================================================
        // LOGIKA 2: JIKA TOKO HIDUP & AKTIF, CEK APAKAH BUTUH REDEEM
        // ===================================================================
        // PERHATIKAN: Logika ini TIDAK AKAN PERNAH tersentuh kalau toko sudah Inactive/Expired
        $pendingLicense = DB::table('licenses')
            ->where('tenant_id', $tenant->id)
            ->where('status', 'available')
            ->exists();

        if ($pendingLicense) {
            return redirect()->to('https://apps.tokosancaka.com/redeem-lisensi?subdomain=' . $subdomain)
                             ->with('info', 'Silakan aktivasi layanan Anda.');
        }

        // ===================================================================
        // LOGIKA 3: LOLOS SEMUA HADANGAN, SILAKAN MASUK POS
        // ===================================================================
        View::share('currentTenant', $tenant);
        $request->attributes->add(['tenant' => $tenant]);
        
        return $next($request);
    }
}