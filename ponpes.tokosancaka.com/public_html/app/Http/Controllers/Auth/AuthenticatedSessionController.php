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
    public function create(): View|RedirectResponse
    {
        // Tambahan: Jika user sudah login tapi mencoba akses halaman /login lewat URL,
        // arahkan otomatis ke dashboard sesuai role mereka.
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }

        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // Melakukan validasi email & password serta throttling (pembatasan percobaan login)
        $request->authenticate();

        // Mencegah Session Fixation Attack
        $request->session()->regenerate();

        // Mengarahkan user berdasarkan role menggunakan helper function
        return $this->redirectBasedOnRole($request->user());
    }

    /**
     * Helper function untuk menentukan arah redirect berdasarkan role.
     */
    protected function redirectBasedOnRole($user): RedirectResponse
    {
        // Jika role-nya admin, arahkan ke Admin Dashboard
        if ($user->role === 'admin') {
            return redirect()->intended(route('admin.dashboard', [], false));
        }

        // Jika user biasa/santri
        return redirect()->intended(route('dashboard', [], false));
    }

   public function destroy(Request $request): RedirectResponse
{
    Auth::guard('web')->logout();

    // 1. Matikan sesi yang ada
    $request->session()->invalidate();

    // 2. Buat token baru agar token lama tidak bisa dipakai lagi (Keamanan)
    $request->session()->regenerateToken();

    // 3. Paksa redirect ke halaman depan (bukan dashboard)
    return redirect('/');
}

}