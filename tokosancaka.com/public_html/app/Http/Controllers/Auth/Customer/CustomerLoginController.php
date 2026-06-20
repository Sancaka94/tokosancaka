<?php

namespace App\Http\Controllers\Auth\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB; // <-- SEKARANG SUDAH DITAMBAHKAN FACADE DB

class CustomerLoginController extends Controller
{
    use AuthenticatesUsers;

    protected function redirectTo()
    {
        $user = Auth::user();
        $role = strtolower(trim($user->role));

        Log::info('Redirecting user post-login.', [
            'user_id' => $user->id_pengguna, 
            'role' => $role
        ]);

        if ($role === 'admin') {
            return route('admin.dashboard');
        }

        if ($role === 'agent') {
            return route('customer.dashboard'); 
        }
        
        return route('customer.dashboard');
    }

    public function showLoginForm()
    {
        Log::info('Akses halaman form login.');
        return view('auth.login');
    }

    protected function guard()
    {
        return Auth::guard('web');
    }

    protected function validateLogin(Request $request)
    {
        Log::info('Validasi input login dimulai.', ['login_input' => $request->login]);
        $request->validate([
            $this->username() => 'required|string',
            'password' => 'required|string',
            'captcha' => 'required|captcha', 
        ], [
            'captcha.required' => 'Kode keamanan wajib diisi.',
            'captcha.captcha' => 'Kode pada gambar salah, silakan coba lagi.',
        ]);
    }

    protected function credentials(Request $request)
    {
        $loginValue = $request->login;
        $loginField = str_contains($loginValue, '@') ? 'email' : 'no_wa';

        if ($loginField === 'no_wa') {
            $loginValue = $this->normalizePhoneNumber($loginValue); 
        }

        $credentials = [
            $loginField => $loginValue,
            'password'  => $request->password,
            'status'    => 'Aktif',    
        ];

        Log::info('LOGIN ATTEMPT - FINAL CREDENTIALS:', [
            'loginField' => $loginField,
            'loginValue' => $loginValue,
            'status' => 'Aktif'
        ]);

        return $credentials;
    }
    
    protected function normalizePhoneNumber(string $phone)
    {
        $normalized = preg_replace('/[^0-9]/', '', $phone);
        Log::info('Normalisasi nomor HP.', ['original' => $phone, 'normalized' => $normalized]);
        return $normalized; 
    }

    public function login(Request $request)
    {
        Log::info('Proses login (sebelum OTP) dimulai.');
        $this->validateLogin($request);
        $credentials = $this->credentials($request);

        if ($this->guard()->validate($credentials)) {
            Log::info('Kredensial valid. Melanjutkan ke proses OTP.');
            
            // ====================================================================
            // PERBAIKAN UTAMA: Menggunakan DB::table agar langsung tembus ke database
            // ====================================================================
            $loginField = isset($credentials['email']) ? 'email' : 'no_wa';
            $user = DB::table('Pengguna')->where($loginField, $credentials[$loginField])->first();
            
            $userId = $user->id_pengguna;

            // Pengecekan Role
            $allowedRoles = ['pelanggan', 'seller', 'admin', 'agent']; 
            if (!in_array(strtolower(trim($user->role)), $allowedRoles)) {
                Log::warning('Akses Ditolak: Peran tidak diizinkan.', [
                    'user_id' => $userId,
                    'role' => $user->role
                ]);
                throw ValidationException::withMessages([
                    'login' => ['Akses Ditolak: Peran Anda tidak diizinkan masuk.'],
                ]);
            }

            // 2. GENERATE KODE OTP & LINK
            $otpCode = strtoupper(Str::random(6)); 
            Log::info('OTP Code Generated.', ['user_id' => $userId]);
            
            $otpLink = route('customer.otp.form') . '?otp=' . $otpCode;

            // 3. SIMPAN KE SESSION SEMENTARA
            $request->session()->put('auth_otp_user_id', $userId);
            $request->session()->put('auth_otp_code', $otpCode);
            $request->session()->put('auth_otp_expires_at', now()->addMinutes(1)); 
            Log::info('Session sementara OTP disimpan.', ['user_id' => $userId, 'expires_at' => now()->addMinutes(1)]);

            // 4. KIRIM OTP KE WHATSAPP
            $noWa = preg_replace('/^0/', '62', $user->no_wa);
            $message = "Halo Kak {$user->nama_lengkap},\n\nSeseorang mencoba masuk ke akun Sancaka Anda. Berikut adalah kode verifikasi OTP Anda:\n\n*{$otpCode}*\n\nAtau klik link berikut untuk verifikasi otomatis:\n{$otpLink}\n\nKode ini berlaku selama 1 menit. *JANGAN berikan kode ini kepada siapa pun*, termasuk admin Sancaka, demi keamanan akun Anda.";
            
            Log::info('Mencoba mengirim OTP ke WhatsApp.', ['no_wa' => $noWa]);
            try {
                \App\Services\FonnteService::sendMessage($noWa, $message); 
                Log::info('OTP berhasil dikirim ke WhatsApp.', ['no_wa' => $noWa]);
            } catch (\Exception $e) {
                Log::error('FonnteService gagal kirim OTP: ' . $e->getMessage(), ['no_wa' => $noWa]);
            }

            // 5. KIRIM OTP KE EMAIL JIKA USER MEMILIKI EMAIL
            if (!empty($user->email)) {
                Log::info('Mencoba mengirim OTP ke Email.', ['email' => $user->email]);
                try {
                    $emailBody = "Halo Kak {$user->nama_lengkap},\n\nSeseorang mencoba masuk ke akun Sancaka Anda. Berikut adalah kode verifikasi OTP Anda:\n\n{$otpCode}\n\nAtau klik link berikut untuk verifikasi otomatis:\n{$otpLink}\n\nKode ini berlaku selama 1 menit. JANGAN berikan kode ini kepada siapa pun demi keamanan akun Anda.\n\nHormat kami,\nManajemen Sancaka";
                    
                    Mail::raw($emailBody, function ($mail) use ($user) {
                        $mail->to($user->email)
                             ->subject('Kode Verifikasi (OTP) Login Sancaka');
                    });
                    
                    Log::info('OTP berhasil dikirim ke Email: ' . $user->email);
                } catch (\Exception $e) {
                    Log::error('Gagal kirim OTP ke Email: ' . $e->getMessage(), ['email' => $user->email]);
                }
            } else {
                Log::info('User tidak memiliki email, skip pengiriman OTP via email.', ['user_id' => $userId]);
            }

            // 6. REDIRECT KE HALAMAN INPUT OTP
            Log::info('Redirecting user ke form OTP.', ['user_id' => $userId]);
            return redirect()->route('customer.otp.form')
                             ->with('info', 'Kode OTP telah dikirim ke WhatsApp dan Email Anda. Silakan cek pesan masuk.');
        }

        Log::warning('Login attempt gagal. Kredensial tidak valid.', ['login_input' => $request->login]);
        return $this->sendFailedLoginResponse($request);
    }
    
    public function username()
    {
        return 'login';
    }

    public function logout(Request $request)
    {
        $userId = Auth::check() ? Auth::user()->id_pengguna : 'Guest';
        Log::info('Proses logout dimulai.', ['user_id' => $userId]);

        $this->guard()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        Log::info('Logout berhasil.', ['user_id' => $userId]);
        return redirect()->route('login')->with('success', 'Anda telah berhasil logout.');
    }
}