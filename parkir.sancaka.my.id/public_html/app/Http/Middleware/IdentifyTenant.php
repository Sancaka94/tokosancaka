<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant;

class IdentifyTenant
{
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost(); // misal: cabang1.parkir.sancaka.my.id
        $parts = explode('.', $host);
        
        // Asumsi base domain adalah parkir.sancaka.my.id (3 bagian)
        if (count($parts) > 3) {
            $subdomain = $parts[0]; // mendapatkan 'cabang1'
            
            $tenant = Tenant::where('subdomain', $subdomain)->first();
            
            if ($tenant) {
                // Set tenant_id secara global di container atau config
                app()->instance('tenant_id', $tenant->id);
            } else {
                abort(404, 'Tenant tidak ditemukan.');
            }
        } else {
            // Main domain (untuk Superadmin)
            app()->instance('tenant_id', null);
        }

        return $next($request);
    }
}