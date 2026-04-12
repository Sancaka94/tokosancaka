<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Carbon\Carbon; // <--- 🟢 WAJIB DITAMBAHKAN UNTUK MANIPULASI WAKTU

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // 1. Proses Login (Cek Email & Password)
        $request->authenticate();

        // 2. Regenerasi Session (Keamanan)
        $request->session()->regenerate();

        // 3. AMBIL DATA USER YANG SEDANG LOGIN
        $user = $request->user();
        $role = $user->role; // Pastikan nama kolom di database adalah 'role'

        // 4. LOGIKA REDIRECT BERDASARKAN ROLE
        // ============================================================

        if ($role === 'Admin') {
            // Jika Admin, arahkan ke /admin/dashboard
            // Pastikan route name 'admin.dashboard' sudah ada di web.php
            return redirect()->intended(route('admin.dashboard', absolute: false));
        }

        elseif ($role === 'Pelanggan') {
            // Jika Pelanggan, arahkan ke /customer/dashboard
            // Pastikan route name 'customer.dashboard' sudah ada di web.php
            return redirect()->intended(route('customer.dashboard', absolute: false));
        }

        elseif ($role === 'Seller') {
            // Jika Seller, arahkan ke /seller/dashboard
            // Pastikan route name 'seller.dashboard' sudah ada di web.php
            return redirect()->intended(route('seller.dashboard', absolute: false));
        }

        // 5. DEFAULT FALLBACK (Jaga-jaga jika role tidak dikenali)
        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // 🟢 TAMBAHAN KODE BARU: Reset status Online menjadi Offline seketika
        if (Auth::check()) {
            $user = Auth::user();

            // Mundurkan waktu last_seen menjadi 10 menit yang lalu
            $user->last_seen = Carbon::now()->subMinutes(10);

            // Jika tabel Anda juga punya kolom last_seen_at, update sekalian agar seragam
            if (\Schema::hasColumn('Pengguna', 'last_seen_at')) {
                $user->last_seen_at = Carbon::now()->subMinutes(10);
            }

            $user->save();
        }
        // =================================================================

        // Proses logout bawaan Laravel
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
