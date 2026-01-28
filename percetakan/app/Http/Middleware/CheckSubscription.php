<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckSubscription
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        
        // Hanya cek jika user sudah login dan punya relasi tenant
        if ($user && $user->tenant) {
            $tenant = $user->tenant;

            // Jika status tidak active ATAU sudah melewati tanggal expired
            if ($tenant->status !== 'active' || ($tenant->expired_at && now()->isAfter($tenant->expired_at))) {
                // Simpan pesan di session untuk memicu modal popup
                session()->flash('subscription_expired', true);
                
                // Jika user mencoba akses fitur tulis data (POST/PUT/DELETE), kita blokir total
                if (!$request->isMethod('get')) {
                    return response()->json(['message' => 'Akun ditangguhkan. Silakan lakukan pembayaran.'], 403);
                }
            }
        }

        return $next($request);
    }
}