<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User; // <-- BARU: Import model User
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // <-- BARU: Import Fassad Auth

class ResetPasswordController extends Controller
{
    use ResetsPasswords;

    /**
     * CATATAN: Properti ini tidak akan digunakan lagi karena kita akan
     * membuat logika redirect sendiri yang lebih dinamis.
     */
    // protected $redirectTo = '/password/reset/success'; // Tidak lagi digunakan

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Menampilkan formulir reset password.
     * (Tidak ada perubahan di sini)
     */
    public function showResetForm(Request $request)
    {
        $token = $request->route()->parameter('token');
        return view('auth.passwords.reset')->with(
            ['token' => $token, 'email' => $request->email]
        );
    }

    /**
     * ✅ PERBAIKAN UTAMA:
     * Override method ini untuk melakukan login otomatis setelah reset
     * dan mengarahkan pengguna ke dashboard yang sesuai dengan rolenya.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function sendResetResponse(Request $request, $response)
    {
        // Dapatkan data user berdasarkan email dari request
        $user = User::where('email', $request->email)->first();

        // Jika user ditemukan, login-kan user tersebut
        if ($user) {
            Auth::login($user);

            // Tentukan tujuan redirect berdasarkan role user
            if ($user->role === 'Admin') {
                return redirect()->route('admin.dashboard')
                                 ->with('status', 'Password Anda telah berhasil diubah! Selamat datang kembali.');
            } elseif ($user->role === 'Pelanggan') {
                return redirect()->route('customer.dashboard')
                                 ->with('status', 'Password Anda telah berhasil diubah! Selamat datang kembali.');
            }
        }

        // Fallback jika terjadi sesuatu yang aneh atau user tidak punya role.
        // Arahkan ke halaman login dengan pesan sukses.
        return redirect()->route('login')
                         ->with('status', 'Password Anda telah berhasil diubah! Silakan login kembali.');
    }
}
