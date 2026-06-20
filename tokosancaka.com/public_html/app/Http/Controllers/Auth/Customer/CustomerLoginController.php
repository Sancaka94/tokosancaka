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
use Illuminate\Support\Str; // <-- TAMBAHKAN INI UNTUK RANDOM STRING OTP


class CustomerLoginController extends Controller
{
    use AuthenticatesUsers;

    protected function redirectTo()
    {
        $user = Auth::user();
        $role = strtolower(trim($user->role));

        Log::info('Redirecting user post-login.', [
            'user_id' => $user->id,
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

        // LOGGING UNTUK DEBUGGING
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

    /**
     * PERBAIKAN: Fungsi Login dimodifikasi untuk menahan sesi dan melempar ke OTP
     */
    public function login(Request $request)
    {
        Log::info('Proses login (sebelum OTP) dimulai.');
        $this->validateLogin($request);
        $credentials = $this->credentials($request);

        // 1. VALIDASI KREDENSIAL TANPA LOGIN LANGSUNG
        // guard()->validate() mengecek password tanpa memasukkan user ke status 'Logged In'
        if ($this->guard()->validate($credentials)) {
            Log::info('Kredensial valid. Melanjutkan ke proses OTP.');
            
            // Ambil data user
            $loginField = isset($credentials['email']) ? 'email' : 'no_wa';
            $user = User::where($loginField, $credentials[$loginField])->first();

            // Pengecekan Role
            $allowedRoles = ['pelanggan', 'seller', 'admin', 'agent']; 
            if (!in_array(strtolower(trim($user->role)), $allowedRoles)) {
                Log::warning('Akses Ditolak: Peran tidak diizinkan.', [
                    'user_id' => $user->id,
                    'role' => $user->role
                ]);
                throw ValidationException::withMessages([
                    'login' => ['Akses Ditolak: Peran Anda tidak diizinkan masuk.'],
                ]);
            }

            // 2. GENERATE KODE OTP (6 Karakter)
            $otpCode = strtoupper(Str::random(6)); // Contoh output: X9B2A1
            Log::info('OTP Code Generated.', ['user_id' => $user->id]);

            // 3. SIMPAN KE SESSION SEMENTARA
            $request->session()->put('auth_otp_user_id', $user->id);
            $request->session()->put('auth_otp_code', $otpCode);
            $request->session()->put('auth_otp_expires_at', now()->addMinutes(1)); // Valid 1 Menit
            Log::info('Session sementara OTP disimpan.', ['user_id' => $user->id, 'expires_at' => now()->addMinutes(1)]);

            // 4. KIRIM OTP KE WHATSAPP
            $noWa = preg_replace('/^0/', '62', $user->no_wa);
            $message = "Halo Kak {$user->nama_lengkap},\n\nSeseorang mencoba masuk ke akun Sancaka Anda. Berikut adalah kode verifikasi OTP Anda:\n\n*{$otpCode}*\n\nKode ini berlaku selama 1 menit. *JANGAN berikan kode ini kepada siapa pun*, termasuk admin Sancaka, demi keamanan akun Anda.";
            
            Log::info('Mencoba mengirim OTP ke WhatsApp.', ['no_wa' => $noWa]);
            try {
                \App\Services\FonnteService::sendMessage($noWa, $message); 
                Log::info('OTP berhasil dikirim ke WhatsApp.', ['no_wa' => $noWa]);
            } catch (\Exception $e) {
                Log::error('FonnteService gagal kirim OTP: ' . $e->getMessage(), ['no_wa' => $noWa]);
            }

            // ====================================================================
            // 5. TAMBAHAN BARU: KIRIM OTP KE EMAIL JIKA USER MEMILIKI EMAIL
            // ====================================================================
            if (!empty($user->email)) {
                Log::info('Mencoba mengirim OTP ke Email.', ['email' => $user->email]);
                try {
                    $emailBody = "Halo Kak {$user->nama_lengkap},\n\nSeseorang mencoba masuk ke akun Sancaka Anda. Berikut adalah kode verifikasi OTP Anda:\n\n{$otpCode}\n\nKode ini berlaku selama 1 menit. JANGAN berikan kode ini kepada siapa pun demi keamanan akun Anda.\n\nHormat kami,\nManajemen Sancaka";
                    
                    \Illuminate\Support\Facades\Mail::raw($emailBody, function ($mail) use ($user) {
                        $mail->to($user->email)
                             ->subject('Kode Verifikasi (OTP) Login Sancaka');
                    });
                    
                    // Mempertahankan LOG pelacakan sistem
                    Log::info('OTP berhasil dikirim ke Email: ' . $user->email);
                } catch (\Exception $e) {
                    Log::error('Gagal kirim OTP ke Email: ' . $e->getMessage(), ['email' => $user->email]);
                }
            } else {
                Log::info('User tidak memiliki email, skip pengiriman OTP via email.', ['user_id' => $user->id]);
            }
            // ====================================================================

            // 6. REDIRECT KE HALAMAN INPUT OTP
            Log::info('Redirecting user ke form OTP.', ['user_id' => $user->id]);
            return redirect()->route('customer.otp.form')
                             ->with('info', 'Kode OTP telah dikirim ke WhatsApp dan Email Anda. Silakan cek pesan masuk.');
        }

        // Jika Password/Username salah
        Log::warning('Login attempt gagal. Kredensial tidak valid.', ['login_input' => $request->login]);
        return $this->sendFailedLoginResponse($request);
    }
    
    // (Pindahkan fungsi buildWelcomeMessage ke Controller OTP nantinya, karena user baru resmi login setelah OTP benar)
    
    public function username()
    {
        return 'login';
    }

    public function logout(Request $request)
    {
        $userId = Auth::id();
        Log::info('Proses logout dimulai.', ['user_id' => $userId]);

        $this->guard()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        Log::info('Logout berhasil.', ['user_id' => $userId]);
        return redirect()->route('login')->with('success', 'Anda telah berhasil logout.');
    }
}