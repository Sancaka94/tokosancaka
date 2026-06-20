<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Carbon\Carbon;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    /**
     * Handle an incoming new password request using OTP Logic.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // 1. Validasi input, pastikan 'token' (yang kini berupa OTP) diisi
        $request->validate([
            'token'    => ['required'], // Input ini dari form OTP Anda
            'email'    => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'token.required' => 'Kode OTP wajib diisi.',
            'email.required' => 'Email wajib diisi.',
        ]);

        // 2. Cari User
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withInput($request->only('email'))
                         ->withErrors(['email' => 'Email tidak terdaftar di sistem.']);
        }

        // 3. Ambil data OTP dari tabel database
        $table = DB::getSchemaBuilder()->hasTable('password_reset_tokens') ? 'password_reset_tokens' : 'password_resets';
        $resetRecord = DB::table($table)->where('email', $request->email)->first();

        // 4. Verifikasi Kecocokan OTP (Tanpa Bcrypt Hasher)
        $inputOtp = preg_replace('/\s+/', '', $request->token); // Hilangkan spasi jika ada
        if (!$resetRecord || strtoupper($resetRecord->token) !== strtoupper($inputOtp)) {
            return back()->withInput($request->only('email'))
                         ->withErrors(['token' => 'Kode OTP salah. Silakan periksa kembali.']);
        }

        // 5. Cek Masa Berlaku OTP (Maksimal 60 Menit)
        if (Carbon::parse($resetRecord->created_at)->addMinutes(60)->isPast()) {
            DB::table($table)->where('email', $request->email)->delete(); // Bersihkan yang expired
            return back()->withInput($request->only('email'))
                         ->withErrors(['token' => 'Kode OTP sudah kedaluwarsa. Silakan minta ulang.']);
        }

        // 6. Update Password Baru & Reset Remember Token
        try {
            DB::transaction(function () use ($user, $request, $table) {
                $user->forceFill([
                    'password'       => Hash::make($request->password), // Enkripsi password baru
                    'remember_token' => Str::random(60),
                ])->save();

                // Hapus token setelah berhasil digunakan
                DB::table($table)->where('email', $request->email)->delete();
            });
        } catch (\Exception $e) {
            return back()->withErrors(['token' => 'Terjadi kesalahan sistem saat menyimpan password.']);
        }

        // 7. Picu event Password Reset bawaan Laravel (Opsional, berguna jika ada listener)
        event(new PasswordReset($user));

        // 8. Redirect ke halaman login dengan pesan sukses
        return redirect()->route('login')->with('status', 'Password berhasil direset! Silakan login dengan password baru.');
    }
}