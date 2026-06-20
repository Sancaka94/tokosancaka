<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        Log::info('Akses halaman form login.');
        return view('auth.login');
    }

    /**
     * Normalisasi nomor HP (Hanya menyisakan angka).
     */
    protected function normalizePhoneNumber(string $phone)
    {
        $normalized = preg_replace('/[^0-9]/', '', $phone);
        Log::info('Normalisasi nomor HP.', ['original' => $phone, 'normalized' => $normalized]);
        return $normalized; 
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request): RedirectResponse
    {
        Log::info('Proses login (sebelum OTP) dimulai.');

        // 1. Validasi Input Formulir & Captcha
        Log::info('Validasi input login dimulai.', ['login_input' => $request->login]);
        $request->validate([
            'login'    => ['required', 'string'],
            'password' => ['required', 'string'],
            'captcha'  => ['required', 'captcha'],
        ], [
            'captcha.required' => 'Kode keamanan wajib diisi.',
            'captcha.captcha'  => 'Kode pada gambar salah, silakan coba lagi.',
        ]);

        // 2. Deteksi field login (Email atau Nomor WA)
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
            'status'     => 'Aktif'
        ]);

        // 3. Validasi kredensial keamanan tanpa langsung membuat sesi login aktif
        if (Auth::guard('web')->validate($credentials)) {
            Log::info('Kredensial valid. Melanjutkan ke proses OTP.');

            $user = User::where($loginField, $loginValue)->first();
            
            // Menggunakan id_pengguna sesuai struktur tabel primary key database
            $userId = $user->id_pengguna; 

            // Validasi Otorisasi Role Pengguna
            $allowedRoles = ['pelanggan', 'seller', 'admin', 'agent']; 
            if (!in_array(strtolower(trim($user->role)), $allowedRoles)) {
                Log::warning('Akses Ditolak: Peran tidak diizinkan.', [
                    'user_id' => $userId,
                    'role'    => $user->role
                ]);
                throw ValidationException::withMessages([
                    'login' => ['Akses Ditolak: Peran Anda tidak diizinkan masuk.'],
                ]);
            }

            // 4. Pembuatan kode acak OTP dan Link Verifikasi Otomatis
            $otpCode = strtoupper(Str::random(6));
            Log::info('OTP Code Generated.', ['user_id' => $userId]);

            // Pembuatan URL Halaman OTP lengkap dengan parameter ?otp=KODE
            $otpLink = route('customer.otp.form') . '?otp=' . $otpCode;

            // 5. Menyimpan data verifikasi ke Session Sementara (Masa Aktif 1 Menit)
            $request->session()->put('auth_otp_user_id', $userId);
            $request->session()->put('auth_otp_code', $otpCode);
            $request->session()->put('auth_otp_expires_at', now()->addMinutes(1));
            Log::info('Session sementara OTP disimpan.', ['user_id' => $userId, 'expires_at' => now()->addMinutes(1)]);

            // 6. Pengiriman OTP ke WhatsApp menggunakan FonnteService
            $noWa = preg_replace('/^0/', '62', $user->no_wa);
            $message = "Halo Kak {$user->nama_lengkap},\n\nSeseorang mencoba masuk ke akun Sancaka Anda. Berikut adalah kode verifikasi OTP Anda:\n\n*{$otpCode}*\n\nAtau klik link berikut untuk verifikasi otomatis:\n{$otpLink}\n\nKode ini berlaku selama 1 menit. *JANGAN berikan kode ini kepada siapa pun*, termasuk admin Sancaka, demi keamanan akun Anda.";
            
            Log::info('Mencoba mengirim OTP ke WhatsApp.', ['no_wa' => $noWa]);
            try {
                \App\Services\FonnteService::sendMessage($noWa, $message); 
                Log::info('OTP berhasil dikirim ke WhatsApp.', ['no_wa' => $noWa]);
            } catch (\Exception $e) {
                Log::error('FonnteService gagal kirim OTP: ' . $e->getMessage(), ['no_wa' => $noWa]);
            }

            // 7. Pengiriman OTP ke alamat Email (Apabila kolom email terisi di database)
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

            // 8. Mengarahkan Pengguna ke Halaman Pengisian OTP Kotak-Kotak
            Log::info('Redirecting user ke form OTP.', ['user_id' => $userId]);
            return redirect()->route('customer.otp.form')
                             ->with('info', 'Kode OTP telah dikirim ke WhatsApp dan Email Anda. Silakan cek pesan masuk.');
        }

        // Penanganan apabila kombinasi password/login salah
        Log::warning('Login attempt gagal. Kredensial tidak valid.', ['login_input' => $request->login]);
        throw ValidationException::withMessages([
            'login' => trans('auth.failed') ?? 'Kredensial yang Anda masukkan salah atau akun belum aktif.',
        ]);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $userId = Auth::check() ? Auth::user()->id_pengguna : 'Guest';
        Log::info('Proses logout dimulai.', ['user_id' => $userId]);

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Log::info('Logout berhasil.', ['user_id' => $userId]);
        return redirect('/');
 
    }
}