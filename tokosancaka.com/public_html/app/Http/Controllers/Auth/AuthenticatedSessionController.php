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
use Illuminate\Support\Facades\DB; // <-- SEKARANG SUDAH DITAMBAHKAN FACADE DB
use Laravel\Socialite\Facades\Socialite; // <-- TAMBAHAN: Import Socialite

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        Log::info('Akses halaman form login.');
        return view('auth.login');
    }

    protected function normalizePhoneNumber(string $phone)
    {
        $normalized = preg_replace('/[^0-9]/', '', $phone);
        Log::info('Normalisasi nomor HP.', ['original' => $phone, 'normalized' => $normalized]);
        return $normalized; 
    }

    public function store(Request $request): RedirectResponse
    {
        Log::info('Proses login (sebelum OTP) dimulai.');

        // 1. Validasi Input
        Log::info('Validasi input login dimulai.', ['login_input' => $request->login]);
        $request->validate([
            'login'    => ['required', 'string'],
            'password' => ['required', 'string'],
            'captcha'  => ['required', 'captcha'],
        ], [
            'captcha.required' => 'Kode keamanan wajib diisi.',
            'captcha.captcha'  => 'Kode pada gambar salah, silakan coba lagi.',
        ]);

        // 2. Deteksi field login
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

        // 3. Cek Kredensial
        if (Auth::guard('web')->validate($credentials)) {
            Log::info('Kredensial valid. Melanjutkan ke proses OTP.');

            // ====================================================================
            // PERBAIKAN UTAMA: Menggunakan DB::table agar langsung tembus ke database
            // ====================================================================
            $user = DB::table('Pengguna')->where($loginField, $loginValue)->first();
            
            $userId = $user->id_pengguna; 

            // Validasi Otorisasi Role
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

            // 4. Generate OTP & Link
            $otpCode = strtoupper(Str::random(6));
            Log::info('OTP Code Generated.', ['user_id' => $userId]);

            $otpLink = route('login.otp.form') . '?otp=' . $otpCode;

            // 5. Simpan ke Session Sementara
            $request->session()->put('auth_otp_user_id', $userId);
            $request->session()->put('auth_otp_code', $otpCode);
            $request->session()->put('auth_otp_expires_at', now()->addMinutes(1));
            $request->session()->save();
            Log::info('Session sementara OTP disimpan.', ['user_id' => $userId, 'expires_at' => now()->addMinutes(1)]);

            // 6. Kirim OTP ke WhatsApp
            $noWa = preg_replace('/^0/', '62', $user->no_wa);
            $message = "Halo Kak {$user->nama_lengkap},\n\nSeseorang mencoba masuk ke akun Sancaka Anda. Berikut adalah kode verifikasi OTP Anda:\n\n*{$otpCode}*\n\nAtau klik link berikut untuk verifikasi otomatis:\n{$otpLink}\n\nKode ini berlaku selama 1 menit. *JANGAN berikan kode ini kepada siapa pun*, termasuk admin Sancaka, demi keamanan akun Anda.";
            
            Log::info('Mencoba mengirim OTP ke WhatsApp.', ['no_wa' => $noWa]);
            try {
                \App\Services\FonnteService::sendMessage($noWa, $message); 
                Log::info('OTP berhasil dikirim ke WhatsApp.', ['no_wa' => $noWa]);
            } catch (\Exception $e) {
                Log::error('FonnteService gagal kirim OTP: ' . $e->getMessage(), ['no_wa' => $noWa]);
            }

            // 7. Kirim OTP ke Email
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

            // 8. Redirect ke Form OTP
            Log::info('Redirecting user ke form OTP.', ['user_id' => $userId]);
            return redirect()->route('login.otp.form')
                 ->with('info', 'Kode OTP telah dikirim ke WhatsApp dan Email Anda. Silakan cek pesan masuk.');
        }

        Log::warning('Login attempt gagal. Kredensial tidak valid.', ['login_input' => $request->login]);
        throw ValidationException::withMessages([
            'login' => trans('auth.failed') ?? 'Kredensial yang Anda masukkan salah atau akun belum aktif.',
        ]);
    }

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

    // ====================================================================
    // TAMBAHAN: FUNGSI LOGIN GOOGLE (SOCIALITE)
    // ====================================================================

    public function redirectToGoogle(): RedirectResponse
    {
        Log::info('Redirecting user ke Google Auth.');
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        try {
            Log::info('Proses callback Google Auth dimulai.');
            
            $googleUser = Socialite::driver('google')->user();
            Log::info('Data Google diterima.', ['email' => $googleUser->getEmail()]);

            // Cari user di database berdasarkan email dari Google
            $user = User::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                // Jika tidak ada, buat akun baru secara otomatis
                Log::info('Email tidak ditemukan, membuat user baru dari Google.', ['email' => $googleUser->getEmail()]);
                
                $user = User::create([
                    'nama_lengkap' => $googleUser->getName(),
                    'email'        => $googleUser->getEmail(),
                    'role'         => 'pelanggan', // Role default
                    'status'       => 'Aktif',
                    'password'     => bcrypt(Str::random(16)), // Generate password acak
                ]);
            }

            // Validasi Otorisasi Role sama seperti login manual
            $allowedRoles = ['pelanggan', 'seller', 'admin', 'agent']; 
            if (!in_array(strtolower(trim($user->role)), $allowedRoles)) {
                Log::warning('Akses Ditolak: Peran tidak diizinkan (Via Google).', [
                    'email' => $user->email,
                    'role'  => $user->role
                ]);
                throw ValidationException::withMessages([
                    'login' => ['Akses Ditolak: Peran Anda tidak diizinkan masuk.'],
                ]);
            }

            // Bypass OTP dan langsung login ke dalam sistem
            Auth::guard('web')->login($user);
            $request->session()->regenerate();

            Log::info('Login Google berhasil.', ['email' => $user->email]);

            // Arahkan ke dashboard utama
            return redirect()->intended('/dashboard');

        } catch (\Exception $e) {
            Log::error('Google Auth Gagal: ' . $e->getMessage());
            return redirect()->route('login')->withErrors([
                'login' => 'Terjadi kesalahan saat otentikasi menggunakan Google. Silakan coba lagi.'
            ]);
        }
    }
}