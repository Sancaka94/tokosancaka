<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class UpdateLastSeen
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();

            // 💡 OPTIMASI: Kita hanya update database jika `last_seen`
            // sudah lewat dari 1 menit. Ini mencegah server / database kamu
            // jebol (overload) kalau user nge-spam klik sana-sini.
            if (!$user->last_seen || Carbon::parse($user->last_seen)->diffInMinutes(now()) >= 1) {
                // Update menggunakan query builder agar tidak memicu event 'updated_at'
                // jika kamu tidak ingin kolom updated_at ikut berubah terus-menerus.
                \DB::table('users')->where('id', $user->id)->update([
                    'last_seen' => now()
                ]);
            }
        }

        return $next($request);
    }
}
