<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        // Generate angka acak untuk captcha
        $angka1 = rand(1, 10);
        $angka2 = rand(1, 10);
        session(['captcha_jawaban' => $angka1 + $angka2]);

        return view('auth.login', compact('angka1', 'angka2'));
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // Validasi Captcha Matematika
        $request->validate([
            'captcha' => 'required|numeric|in:' . session('captcha_jawaban'),
        ], [
            'captcha.required' => 'Captcha wajib diisi.',
            'captcha.in' => 'Jawaban matematika salah, silakan coba lagi.',
        ]);
        
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
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
