<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log; // Tambahan Import Log

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        // Log saat halaman login dibuka
        Log::info('Login page visited.', [
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);

        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // Log percobaan login (sebelum otentikasi)
        Log::info('Login attempt initiated.', [
            'email' => $request->input('email'), // Jangan pernah log password!
            'ip' => $request->ip()
        ]);

        $request->authenticate();

        // Log jika otentikasi berhasil
        Log::info('User authenticated successfully.', [
            'email' => $request->input('email'),
            'user_id' => Auth::id() // Mencatat ID user yang berhasil masuk
        ]);

        $request->session()->regenerate();

        // Log regenerasi session
        Log::info('Session regenerated for user.', [
            'user_id' => Auth::id()
        ]);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $userId = Auth::id(); // Simpan ID sebelum logout untuk log

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        // Log aktivitas logout
        Log::info('User logged out.', [
            'user_id' => $userId,
            'ip' => $request->ip()
        ]);

        return redirect('/');
    }
}
