<?php

namespace App\Http\Controllers\Auth\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User; // <-- PENTING: Impor model User
use Illuminate\Support\Facades\Hash; // <-- PENTING: Impor Hash

class CustomerLoginController extends Controller
{
    use AuthenticatesUsers;

    protected function redirectTo()
    {
        $user = Auth::user();
        if ($user->role === 'Admin') {
            return '/admin/dashboard';
        }
        return '/customer/dashboard';
    }

    /**
     * ✅ PERBAIKAN: Konstruktor dan middleware dihapus.
     * Logika middleware sekarang dipindahkan ke file routes/web.php
     * untuk praktik yang lebih modern dan untuk memperbaiki error.
     */

    public function showLoginForm()
    {
        return view('auth.login');
    }

    protected function validateLogin(Request $request)
    {
        $request->validate([
            'login'    => 'required|string',
            'password' => 'required|string',
        ]);
    }

    protected function credentials(Request $request)
    {
        $loginField = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'no_wa';
        return [
            $loginField => $request->login,
            'password'  => $request->password,
        ];
    }

    /**
     * ✅ FUNGSI LOGIN DENGAN DEBUGGING
     * Fungsi ini menimpa fungsi login default untuk memberikan pesan error yang lebih jelas.
     */
    public function login(Request $request)
    {
        $this->validateLogin($request);

        // --- BLOK DEBUGGING MANUAL ---
        $loginField = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'no_wa';

        // 1. Coba cari user berdasarkan input (email/no_wa) dan HANYA untuk peran 'Pelanggan'
        $user = User::where($loginField, $request->login)
                    ->first();

        if (!$user) {
            // Jika user dengan kriteria di atas tidak ditemukan sama sekali.
            return back()->withInput($request->only('login', 'remember'))
                         ->withErrors(['login' => 'DEBUG: User dengan data tersebut dan peran "Pelanggan" tidak dapat ditemukan.']);
        }

        // 2. Jika user ditemukan, sekarang kita cek passwordnya secara manual.
        if (!Hash::check($request->password, $user->getAuthPassword())) {
            // Jika password yang diketik tidak cocok dengan hash di database.
            return back()->withInput($request->only('login', 'remember'))
                         ->withErrors(['login' => 'DEBUG: Password salah untuk user yang ditemukan. Coba reset password manual dengan Tinker.']);
        }
        // --- AKHIR BLOK DEBUGGING MANUAL ---


        // Jika lolos dari semua pengecekan manual, lanjutkan dengan proses login standar Laravel.
        if ($this->attemptLogin($request)) {
    if ($request->hasSession()) {
        $request->session()->regenerate();
    }

    // Kirim pesan via Fonnte
    $user = Auth::user();
    $noWa = preg_replace('/^0/', '62', $user->no_wa);
$message = <<<TEXT
*Selamat Datang di Aplikasi Sancaka Express, Kak {$user->nama_lengkap}*

Apabila Anda mengalami kendala atau memiliki pertanyaan, silakan hubungi Admin Sancaka melalui nomor *0881-9435-180*.

Hormat kami,  

*Manajemen Sancaka*  

CV Sancaka Karya Hutama  
*Jl.Dr.Wahidin No.18A RT.22 RW.05 Ketanggi Ngawi Jawa Timur 63211*  
Website: tokosancaka.com
TEXT;

if (!empty($user->setup_token) && empty($user->profile_setup_at)) {
    $link = url('/customer/profile/setup/' . $user->setup_token);

    $message .= <<<TEXT

---

Berikut adalah *Link Pendaftaran* Kakak {$user->id}.  
Agar pendaftaran Kakak berhasil, silakan klik link di bawah ini dan lengkapi datanya:  

{$link}
TEXT;
}

\App\Services\FonnteService::sendMessage($noWa, $message);

return $this->sendLoginResponse($request);

}

        // Jika karena alasan lain tetap gagal, kembalikan error default.
        return $this->sendFailedLoginResponse($request);
    }

    public function username()
    {
        return 'login';
    }

   public function logout(Request $request)
    {
        $this->guard()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    
        return redirect()->route('login')->with('success', 'Anda telah berhasil logout.');
    }

}
