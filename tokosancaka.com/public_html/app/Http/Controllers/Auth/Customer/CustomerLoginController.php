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
use Illuminate\Support\Facades\DB; // <-- FACADE DB UNTUK TABEL PENGGUNA
use Laravel\Socialite\Facades\Socialite; // <-- Import Socialite
use Illuminate\Http\RedirectResponse;
use Jenssegers\Agent\Agent; // <-- TAMBAHAN: Import library Agent untuk deteksi perangkat
use Illuminate\Support\Facades\Hash;

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

        // ====================================================================
        // CEK KELENGKAPAN PROFIL GOOGLE
        // Jika statusnya belum "Aktif" atau datanya kosong (misal baru daftar via Google),
        // paksa ke halaman setup profile.
        // ====================================================================
        if ($user->status !== 'Aktif' || empty($user->no_wa)) {
            Log::info('User belum melengkapi profil. Dialihkan ke halaman Setup Profile.');
            return route('customer.profile.setup');
        }

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

        // ====================================================================
        // BYPASS AKUN DUMMY/WHITELIST (DINAMIS via Database)
        // ====================================================================
        $loginValue = $request->login;
        $loginField = str_contains($loginValue, '@') ? 'email' : 'no_wa';
        if ($loginField === 'no_wa') {
            $loginValue = $this->normalizePhoneNumber($loginValue);
        }

        $dummyUser = DB::table('Pengguna')
            ->where($loginField, $loginValue)
            ->where('is_whitelisted', 1)
            ->first();

            if ($dummyUser && Hash::check($request->password, $dummyUser->password_hash)) { // <-- UBAH DI SINI
            Log::info('Bypass login dinamis: Akun whitelist terdeteksi. Melewati validasi captcha dan OTP.', ['user_id' => $dummyUser->id_pengguna]);


            $userModel = User::find($dummyUser->id_pengguna);
            if ($userModel) {
                $this->guard()->login($userModel);
                $request->session()->regenerate();

                try {
                    $agent = new Agent();
                    $deviceInfo = $agent->browser() . ' on ' . $agent->platform();
                    DB::table('Pengguna')->where('id_pengguna', $dummyUser->id_pengguna)->update([
                        'ip_address' => $request->ip(),
                        'user_agent' => $deviceInfo,
                        'latitude'   => $request->input('latitude'),
                        'longitude'  => $request->input('longitude'),
                        'updated_at' => now(),
                    ]);
                    Log::info('Data IP, Agent, dan Koordinat berhasil disimpan (Bypass Login).', ['user_id' => $dummyUser->id_pengguna, 'ip' => $request->ip()]);
                } catch (\Exception $e) {
                    Log::error('Gagal menyimpan data keamanan bypass login: ' . $e->getMessage());
                }

                return redirect()->intended($this->redirectTo());
            }
        }
        // ====================================================================

        $this->validateLogin($request);
        $credentials = $this->credentials($request);

        if ($this->guard()->validate($credentials)) {
            Log::info('Kredensial valid. Melanjutkan ke proses OTP.');

            // ====================================================================
            // Menggunakan DB::table agar langsung tembus ke database
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

            // ====================================================================
            // TAMBAHAN: UPDATE LOKASI, IP, & USER AGENT (LOGIN MANUAL)
            // ====================================================================
            try {
                $agent = new Agent();
                $deviceInfo = $agent->browser() . ' on ' . $agent->platform();

                DB::table('Pengguna')->where('id_pengguna', $userId)->update([
                    'ip_address' => $request->ip(),
                    'user_agent' => $deviceInfo,
                    'latitude'   => $request->input('latitude'),
                    'longitude'  => $request->input('longitude'),
                    'updated_at' => now(),
                ]);

                Log::info('Data IP, Agent, dan Koordinat berhasil disimpan (Manual Login).', [
                    'user_id' => $userId,
                    'ip' => $request->ip()
                ]);
            } catch (\Exception $e) {
                Log::error('Gagal menyimpan data keamanan login manual: ' . $e->getMessage());
            }
            // ====================================================================

            // 2. GENERATE KODE OTP & LINK
            $otpCode = strtoupper(Str::random(6));
            Log::info('OTP Code Generated.', ['user_id' => $userId]);

            // Ubah route menjadi login.otp.form agar tidak bentrok dengan OTP Registrasi
            $otpLink = route('login.otp.form') . '?otp=' . $otpCode;

            // 3. SIMPAN KE SESSION SEMENTARA
            $request->session()->put('auth_otp_user_id', $userId);
            $request->session()->put('auth_otp_code', $otpCode);
            $request->session()->put('auth_otp_expires_at', now()->addMinutes(1));
            $request->session()->save();
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

            return redirect()->route('login.otp.form')
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
                    'status'       => 'Menunggu Setup', // STATUS SEMENTARA
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

            // ====================================================================
            // TAMBAHAN: UPDATE LOKASI, IP, & USER AGENT (LOGIN GOOGLE)
            // ====================================================================
            try {
                $agent = new Agent();
                $deviceInfo = $agent->browser() . ' on ' . $agent->platform();

                // Menggunakan Eloquent karena $user di atas mengambil instance Model User
                $user->update([
                    'ip_address' => $request->ip(),
                    'user_agent' => $deviceInfo,
                    'latitude'   => $request->input('latitude'),
                    'longitude'  => $request->input('longitude'),
                ]);

                Log::info('Data IP, Agent, dan Koordinat berhasil disimpan (Google Login).', [
                    'user_id' => $user->id_pengguna,
                    'ip' => $request->ip()
                ]);
            } catch (\Exception $e) {
                Log::error('Gagal menyimpan data keamanan login Google: ' . $e->getMessage());
            }
            // ====================================================================

            // Bypass OTP dan langsung login ke dalam sistem
            $this->guard()->login($user);
            $request->session()->regenerate();

            Log::info('Login Google berhasil.', ['email' => $user->email]);

            // Arahkan ke rute berdasarkan kondisi profil (fungsi redirectTo di atas)
            return redirect()->intended($this->redirectTo());

        } catch (\Exception $e) {
            Log::error('Google Auth Gagal: ' . $e->getMessage());
            return redirect()->route('login')->withErrors([
                'login' => 'Terjadi kesalahan saat otentikasi menggunakan Google. Silakan coba lagi.'
            ]);
        }
    }
}
