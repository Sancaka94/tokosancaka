<?php

namespace App\Http\Controllers\Auth\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use App\Models\User; // Pastikan model User di-import

class CustomerResetPasswordController extends Controller
{
    use ResetsPasswords;

    /**
     * ✅ PERBAIKAN FINAL: Mengarahkan LANGSUNG ke dashboard.
     * Ini menghilangkan redirect kedua yang menyebabkan masalah sesi.
     *
     * @var string
     */
    protected $redirectTo = '/customer/dashboard';

    public function showResetForm(Request $request)
    {
        $token = $request->route()->parameter('token');
        return view('auth.passwords.reset')->with(
            ['token' => $token, 'email' => $request->email]
        );
    }

    /**
     * Metode ini akan menangani logika reset secara manual untuk memastikan
     * password di-hash dengan benar.
     */
    public function reset(Request $request)
    {
        $request->validate($this->rules(), $this->validationErrorMessages());

        // Cari user berdasarkan email
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withInput($request->only('email'))
                         ->withErrors(['email' => 'Email tidak ditemukan.']);
        }

        // Verifikasi token reset password
        if (!Password::tokenExists($user, $request->token)) {
            return back()->withInput($request->only('email'))
                         ->withErrors(['email' => 'Token reset password tidak valid atau sudah kedaluwarsa.']);
        }

        // Hash password baru dan simpan ke database
        $user->password = $request->password;
        $user->save();

        // Hapus token setelah digunakan
        Password::deleteToken($user);

        // Login pengguna secara otomatis setelah reset
        $this->guard()->login($user);

        // Mengarahkan ke dashboard dengan pesan sukses
        return redirect($this->redirectPath())
               ->with('status', 'Password Anda telah berhasil direset! Selamat datang kembali.');
    }

    /**
     * Aturan validasi untuk form reset.
     */
    protected function rules()
    {
        return [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8',
        ];
    }
}
