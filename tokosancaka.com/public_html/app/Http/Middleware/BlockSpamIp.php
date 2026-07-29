<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\BlockedIp;
use Illuminate\Support\Facades\Cache;

class BlockSpamIp
{
    public function handle(Request $request, Closure $next)
    {
        // Ambil daftar IP dari cache (disimpan selama 24 jam / 86400 detik).
        // Jika tidak ada di cache, ambil dari database.
        $blockedIps = Cache::remember('blocked_ips', 31104000, function () {
            return BlockedIp::pluck('ip_address')->toArray();
        });

        if (in_array($request->ip(), $blockedIps)) {
            abort(403, 'Akses dari IP Anda telah diblokir karena aktivitas mencurigakan.');
        }

        return $next($request);
    }
}
