<?php

namespace App\Http\Controllers\Auth\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Log; // Import Log Facade

class OtpController extends Controller
{
    // Method untuk menampilkan view OTP yang desainnya kotak-kotak tadi
    public function showOtpForm(Request $request)
    {
        Log::info('Akses halaman form verifikasi OTP.');

        // Cegah akses jika tidak ada session OTP
        if (!$request->session()->has('auth_otp_user_id')) {
            Log::warning('Akses form OTP ditolak: Sesi tidak valid atau kosong.');
            return redirect()->route('login')->with('error', 'Sesi login tidak valid. Silakan login ulang.');
        }

        return view('customer.otp'); // Sesuaikan dengan nama file view Anda (misal: 'auth.otp' jika di dalam folder auth)
    }

    // Method aksi form ketika input OTP di submit
    public function processOtp(Request $request)
    {
        Log::info('Proses submit OTP dimulai.');

        $request->validate([
            'otp' => 'required|string|size:6'
        ], [
            'otp.required' => 'Kode OTP wajib diisi.',
            'otp.size' => 'Format kode OTP tidak valid (harus 6 karakter).'
        ]);

        $sessionOtp = session('auth_otp_code');
        $expiresAt = session('auth_otp_expires_at');
        $userId = session('auth_otp_user_id');

        Log::info('Pengecekan validitas sesi OTP.', ['user_id' => $userId, 'input_otp' => $request->otp]);

        // 1. Cek apakah sesi valid
        if (!$sessionOtp || !$userId) {
            Log::warning('Verifikasi OTP gagal: Session OTP kosong atau hilang.', ['user_id' => $userId]);
            return redirect()->route('login')->with('error', 'Sesi telah berakhir, silakan login ulang.');
        }

        // 2. Cek apakah waktu OTP habis
        if (now()->greaterThan($expiresAt)) {
            Log::warning('Verifikasi OTP gagal: Kode OTP telah kadaluarsa.', ['user_id' => $userId]);
            session()->forget(['auth_otp_code', 'auth_otp_expires_at', 'auth_otp_user_id']);
            return redirect()->route('login')->with('error', 'Kode OTP sudah kadaluarsa (lebih dari 1 menit). Silakan login ulang.');
        }

        // 3. Cek apakah OTP yang diinput cocok dengan Session
        if (strtoupper($request->otp) !== strtoupper($sessionOtp)) {
            Log::warning('Verifikasi OTP gagal: Kode OTP tidak cocok.', ['user_id' => $userId, 'input_otp' => $request->otp]);
            // Kembali ke halaman form OTP (SweetAlert error akan muncul karena ada with('error'))
            return back()->with('error', 'Kode OTP yang Anda masukkan salah. Silakan periksa kembali.');
        }

        // 4. JIKA OTP BENAR -> RESMIKAN LOGINNYA
        Log::info('Verifikasi OTP berhasil. Memulai proses login user.', ['user_id' => $userId]);

        // Cari user menggunakan id_pengguna untuk memastikan keamanan tabel kustom Anda
        $user = User::where('id_pengguna', $userId)->first();

        if (!$user) {
            Log::error('Verifikasi OTP gagal: Data user tidak ditemukan di database.', ['user_id' => $userId]);
            return redirect()->route('login')->with('error', 'Akun tidak ditemukan di sistem.');
        }

        // Resmikan Login
        Auth::login($user);

        // Bersihkan session OTP agar tidak disalahgunakan
        session()->forget(['auth_otp_code', 'auth_otp_expires_at', 'auth_otp_user_id']);
        $request->session()->regenerate();

        Log::info('Login berhasil dan sesi diregenerasi.', ['user_id' => $userId, 'role' => $user->role]);

        // 5. OPTIONAL: Kirim pesan Welcome
        try {
            Log::info('Mencoba mengirim pesan Welcome Sancaka ke WA.', ['user_id' => $userId]);
            $noWa = preg_replace('/^0/', '62', $user->no_wa);
            $message = $this->buildWelcomeMessage($user); 
            \App\Services\FonnteService::sendMessage($noWa, $message); 
            Log::info('Pesan Welcome berhasil dikirim.', ['no_wa' => $noWa]);
        } catch (\Exception $e) {
            Log::error('Gagal kirim pesan welcome: ' . $e->getMessage(), ['user_id' => $userId]);
        }

        // 6. Redirect ke dashboard
        $role = strtolower(trim($user->role));
        Log::info('Redirecting user post-login.', ['user_id' => $userId, 'role' => $role]);

        if ($role === 'admin') {
            return redirect()->route('admin.dashboard');
        } elseif ($role === 'seller') {
            return redirect()->route('seller.dashboard'); // Pastikan route ini ada jika Anda punya dashboard seller
        } elseif ($role === 'agent') {
            return redirect()->route('customer.dashboard'); 
        }
        
        return redirect()->route('customer.dashboard');
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

Berikut adalah *Link Pendaftaran* Kakak {$user->id_pengguna}.  
Agar pendaftaran Kakak berhasil, silakan klik link di bawah ini dan lengkapi datanya:  

{$link}
TEXT;
        }
        return $message;
    }
}