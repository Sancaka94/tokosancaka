<?php

namespace App\Http\Controllers\Auth\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User; // Impor model User
use Illuminate\Validation\ValidationException; // Diperlukan untuk error custom
use Illuminate\Support\Facades\Log; // Import Log Facade
use Illuminate\Support\Facades\Route; // Tambahkan ini agar helper route() dikenali

class CustomerLoginController extends Controller
{
    use AuthenticatesUsers;

    /**
 * Tentukan kemana user akan dialihkan setelah login berhasil.
 *
 * @return string
 */
protected function redirectTo()
{
    $user = Auth::user();
    $role = strtolower(trim($user->role)); // Tambahkan trim() di sini juga

    // 1. Admin
    if ($role === 'admin') {
        return route('admin.dashboard');
    }

    // 2. **PERBAIKAN: Tambahkan role 'agent'**
    if ($role === 'agent') {
        return route('customer.dashboard'); // Ganti dengan nama route yang benar untuk Agent
    }
    
    // 3. Pelanggan/Seller/Default (catch-all)
    return route('customer.dashboard');
}

    /**
     * Tampilkan form login.
     *
     * @return \Illuminate\View\View
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Dapatkan guard yang digunakan untuk autentikasi.
     * Secara default, ini menggunakan guard 'web' atau guard default aplikasi.
     *
     * @return \Illuminate\Contracts\Auth\Guard
     */
    protected function guard()
    {
        return Auth::guard('web'); // Asumsikan Anda menggunakan guard 'web' untuk customer
    }

    /**
     * Validasi input request login.
     *
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    protected function validateLogin(Request $request)
    {
        $request->validate([
            $this->username() => 'required|string',
            'password' => 'required|string',
        ]);
    }

    /**
     * Dapatkan kredensial yang akan digunakan untuk attempt login.
     *
     * PERBAIKAN: Menghapus batasan role dan logika strtolower() yang bermasalah.
     * * @param \Illuminate\Http\Request $request
     * @return array
     */
    protected function credentials(Request $request)
    {
        // 1. Ambil nilai login tanpa perubahan case (biarkan database/collation yang menangani)
        $loginValue = $request->login;

        // 2. Tentukan field yang digunakan
        $loginField = str_contains($loginValue, '@') ? 'email' : 'no_wa';

        // 3. Normalisasi Nomor HP (hanya hapus non-angka)
        if ($loginField === 'no_wa') {
            // Gunakan normalizePhoneNumber untuk membersihkan input no_wa
            $loginValue = $this->normalizePhoneNumber($loginValue); 
        }

        // Kredensial tanpa batasan role (dapat login dengan role apapun asalkan status Aktif)
        $credentials = [
            $loginField => $loginValue,
            'password'  => $request->password,
            // 'role'      => 'Pelanggan', // Dihapus
            'status'    => 'Aktif',    
        ];
        
        // LOGGING UNTUK DEBUGGING - Hapus baris ini setelah masalah teratasi
        Log::info('LOGIN ATTEMPT - FINAL CREDENTIALS:', $credentials);

        return $credentials;
    }
    
    /**
     * Normalisasi nomor HP untuk login.
     * METHOD INI HANYA MENGHILANGKAN KARAKTER NON-ANGKA (sesuai format database 08xx...).
     *
     * @param string $phone
     * @return string
     */
    protected function normalizePhoneNumber(string $phone)
    {
        // Hanya hapus semua karakter non-angka
        return preg_replace('/[^0-9]/', '', $phone); 
    }


    /**
     * Handle the POST request for login.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request)
    {
        $this->validateLogin($request);

        // 1. Cek apakah user bisa diautentikasi dengan kredensial
        if ($this->attemptLogin($request)) {
            
            if ($request->hasSession()) {
                $request->session()->regenerate();
            }

            // --- Logika Sesi dan Pengiriman Pesan ---
            $user = Auth::user();
            
            // Pengecekan terakhir: Pastikan user yang berhasil login adalah salah satu role yang diizinkan
            // Pengecekan ini harusnya mencegah user selain yang diizinkan masuk
            $allowedRoles = ['pelanggan', 'seller', 'admin', 'agent']; 
            if (!in_array(strtolower($user->role), $allowedRoles)) {
                $this->guard()->logout();
                throw ValidationException::withMessages([
                    'login' => ['Akses Ditolak: Peran Anda tidak diizinkan masuk melalui halaman ini.'],
                ]);
            }

            $noWa = preg_replace('/^0/', '62', $user->no_wa);
            
            try {
                $message = $this->buildWelcomeMessage($user);
                \App\Services\FonnteService::sendMessage($noWa, $message); 
            } catch (\Exception $e) {
                \Log::error('FonnteService failed to send message on login: ' . $e->getMessage());
            }

            return $this->sendLoginResponse($request);
        }

        // Jika attemptLogin gagal, kembalikan error default.
        return $this->sendFailedLoginResponse($request);
    }
    
    /**
     * Membangun konten pesan WhatsApp
     *
     * @param \App\Models\User $user
     * @return string
     */
    protected function buildWelcomeMessage($user)
    {
        $message = <<<TEXT
*Selamat Datang di Aplikasi Sancaka Express, Kak {$user->nama_lengkap}*

Apabila Anda mengalami kendala atau memiliki pertanyaan, silakan hubungi Admin Sancaka melalui nomor *0881-9435-180*.

Hormat kami,  

*Manajemen Sancaka* CV Sancaka Karya Hutama  
*Jl.Dr.Wahidin No.18A RT.22 RW.05 Ketanggi Ngawi Jawa Timur 63211* Website: tokosancaka.com
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
        return $message;
    }


    /**
     * Dapatkan field yang digunakan untuk login (email/no_wa)
     *
     * @return string
     */
    public function username()
    {
        return 'login';
    }

    /**
     * Handle logout request.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        $this->guard()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('login')->with('success', 'Anda telah berhasil logout.');
    }

}