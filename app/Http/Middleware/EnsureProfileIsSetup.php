<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class EnsureProfileIsSetup
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if ($user) {
            $hideMenus = $user->setup_token !== null 
                        && $user->profile_setup_at === null 
                        && $user->status === 'Tidak Aktif';

            if ($hideMenus) {
                // Jika belum ada setup_token, generate baru dan simpan
                if (!$user->setup_token) {
                    $token = Str::random(40);

                    // Pastikan token unik
                    while (\App\Models\User::where('setup_token', $token)->exists()) {
                        $token = Str::random(40);
                    }

                    $user->setup_token = $token;
                    $user->save();
                }

                return redirect()->route('customer.profile.setup', ['token' => $user->setup_token]);
            }
        }

        return $next($request);
    }
}
