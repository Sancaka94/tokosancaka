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
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;
use Jenssegers\Agent\Agent;
use Illuminate\Support\Facades\Hash;

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

        if ($dummyUser && Hash::check($request->password, $dummyUser->password_hash)) {
            Log::info('Bypass login dinamis: Akun whitelist terdeteksi. Melewati validasi captcha dan OTP.', ['user_id' => $dummyUser->id_pengguna]);

            $userModel = User::find($dummyUser->id_pengguna);
            if ($userModel) {
                Auth::guard('web')->login($userModel);
                $request->session()->regenerate();

                try {
                    $agent = new Agent();
                    $deviceInfo = $agent->browser() . ' on ' . $agent->platform();
                    DB::table('Pengguna')->where('id_pengguna', $dummyUser->id_pengguna)->update([
                        'ip_address' => $request->ip(),
                        'user_agent' => $deviceInfo,
                        'latitude'   => $request->input('latitude'),
                        'longitude'  => $request->input('longitude'),
                    ]);
                    Log::info('Data IP dan Agent berhasil disimpan (Bypass Login).', ['user_id' => $dummyUser->id_pengguna]);
                } catch (\Exception $e) {
                    Log::error('Gagal menyimpan data keamanan bypass login: ' . $e->getMessage());
                }

                $role = strtolower(trim($userModel->role));
                if ($role === 'admin') {
                    return redirect()->intended(route('admin.dashboard'));
                }

                // Pelanggan, Agent, Seller, maupun Driver diarahkan ke dashboard customer
                return redirect()->intended(route('customer.dashboard'));
            }
        }
        // ====================================================================

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

            $user = DB::table('Pengguna')->where($loginField, $loginValue)->first();
            $userId = $user->id_pengguna;

            // PERBAIKAN: Menambahkan 'driver' agar akun yang ber-role driver tidak terblokir
            $allowedRoles = ['pelanggan', 'seller', 'admin', 'agent', 'driver'];
            if (!in_array(strtolower(trim($user->role)), $allowedRoles)) {
                Log::warning('Akses Ditolak: Peran tidak diizinkan.', [
                    'user_id' => $userId,
                    'role'    => $user->role
                ]);
                throw ValidationException::withMessages([
                    'login' => ['Akses Ditolak: Peran Anda tidak diizinkan masuk.'],
                ]);
            }

            try {
                $agent = new Agent();
                $deviceInfo = $agent->browser() . ' on ' . $agent->platform();

                DB::table('Pengguna')->where('id_pengguna', $userId)->update([
                    'ip_address' => $request->ip(),
                    'user_agent' => $deviceInfo,
                    'latitude'   => $request->input('latitude'),
                    'longitude'  => $request->input('longitude'),
                ]);

                Log::info('Data IP, Agent, dan Koordinat berhasil disimpan (Manual Login).', [
                    'user_id' => $userId,
                    'ip' => $request->ip()
                ]);
            } catch (\Exception $e) {
                Log::error('Gagal menyimpan data keamanan login manual: ' . $e->getMessage());
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
    // FUNGSI LOGIN GOOGLE (SOCIALITE)
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

            $user = User::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                Log::info('Email tidak ditemukan, membuat user baru dari Google.', ['email' => $googleUser->getEmail()]);

                $user = User::create([
                    'nama_lengkap' => $googleUser->getName(),
                    'email'        => $googleUser->getEmail(),
                    'role'         => 'pelanggan',
                    'status'       => 'Menunggu Setup',
                    'password'     => bcrypt(Str::random(16)),
                ]);
            }

            // PERBAIKAN: Menambahkan 'driver' pada pengecekan role Google Auth
            $allowedRoles = ['pelanggan', 'seller', 'admin', 'agent', 'driver'];
            if (!in_array(strtolower(trim($user->role)), $allowedRoles)) {
                Log::warning('Akses Ditolak: Peran tidak diizinkan (Via Google).', [
                    'email' => $user->email,
                    'role'  => $user->role
                ]);
                throw ValidationException::withMessages([
                    'login' => ['Akses Ditolak: Peran Anda tidak diizinkan masuk.'],
                ]);
            }

            try {
                $agent = new Agent();
                $deviceInfo = $agent->browser() . ' on ' . $agent->platform();

                $user->update([
                    'ip_address' => $request->ip(),
                    'user_agent' => $deviceInfo,
                    'latitude'   => $request->input('latitude'),
                    'longitude'  => $request->input('longitude'),
                ]);

                Log::info('Data IP, Agent, dan Koordinat berhasil disimpan (Google Login).', [
                    'user_id' => $user->id_pengguna ?? $user->id,
                    'ip' => $request->ip()
                ]);
            } catch (\Exception $e) {
                Log::error('Gagal menyimpan data keamanan login Google: ' . $e->getMessage());
            }

            Auth::guard('web')->login($user);
            $request->session()->regenerate();

            Log::info('Login Google berhasil.', ['email' => $user->email]);

            if ($user->status !== 'Aktif' || empty($user->no_wa)) {
                Log::info('User belum melengkapi profil. Dialihkan ke halaman Setup Profile.', ['user_id' => $user->id_pengguna]);
                return redirect()->route('customer.profile.setup');
            }

            $role = strtolower(trim($user->role));
            if ($role === 'admin') {
                return redirect()->route('admin.dashboard');
            }

            // Arahkan semua role non-admin (pelanggan, agent, seller, driver) ke dashboard customer
            return redirect()->route('customer.dashboard');

        } catch (\Exception $e) {
            Log::error('Google Auth Gagal: ' . $e->getMessage());
            return redirect()->route('login')->withErrors([
                'login' => 'Terjadi kesalahan saat otentikasi menggunakan Google. Silakan coba lagi.'
            ]);
        }
    }

    // ====================================================================
    // FUNGSI LOGIN FACEBOOK (SOCIALITE)
    // ====================================================================

    public function redirectToFacebook(): RedirectResponse
    {
        Log::info('Redirecting user ke Facebook Auth.');
        return Socialite::driver('facebook')->redirect();
    }

    public function handleFacebookCallback(Request $request): RedirectResponse
    {
        try {
            Log::info('Proses callback Facebook Auth dimulai.');

            $facebookUser = Socialite::driver('facebook')->user();
            
            // Fallback email: Jika akun FB daftar pakai nomor HP, email bisa null.
            $email = $facebookUser->getEmail() ?? $facebookUser->getId() . '@facebook.sancaka.com';
            Log::info('Data Facebook diterima.', ['email' => $email]);

            $user = User::where('email', $email)->first();

            if (!$user) {
                Log::info('Email tidak ditemukan, membuat user baru dari Facebook.', ['email' => $email]);

                $user = User::create([
                    'nama_lengkap' => $facebookUser->getName(),
                    'email'        => $email,
                    'role'         => 'pelanggan',
                    'status'       => 'Menunggu Setup',
                    'password'     => bcrypt(Str::random(16)),
                ]);
            }

            $allowedRoles = ['pelanggan', 'seller', 'admin', 'agent', 'driver'];
            if (!in_array(strtolower(trim($user->role)), $allowedRoles)) {
                Log::warning('Akses Ditolak: Peran tidak diizinkan (Via Facebook).', [
                    'email' => $user->email,
                    'role'  => $user->role
                ]);
                throw ValidationException::withMessages([
                    'login' => ['Akses Ditolak: Peran Anda tidak diizinkan masuk.'],
                ]);
            }

            try {
                $agent = new Agent();
                $deviceInfo = $agent->browser() . ' on ' . $agent->platform();

                $user->update([
                    'ip_address' => $request->ip(),
                    'user_agent' => $deviceInfo,
                    'latitude'   => $request->input('latitude'),
                    'longitude'  => $request->input('longitude'),
                ]);

                Log::info('Data IP, Agent, dan Koordinat berhasil disimpan (Facebook Login).', [
                    'user_id' => $user->id_pengguna ?? $user->id,
                    'ip' => $request->ip()
                ]);
            } catch (\Exception $e) {
                Log::error('Gagal menyimpan data keamanan login Facebook: ' . $e->getMessage());
            }

            Auth::guard('web')->login($user);
            $request->session()->regenerate();

            Log::info('Login Facebook berhasil.', ['email' => $user->email]);

            if ($user->status !== 'Aktif' || empty($user->no_wa)) {
                Log::info('User belum melengkapi profil. Dialihkan ke halaman Setup Profile.', ['user_id' => $user->id_pengguna]);
                return redirect()->route('customer.profile.setup');
            }

            $role = strtolower(trim($user->role));
            if ($role === 'admin') {
                return redirect()->route('admin.dashboard');
            }

            return redirect()->route('customer.dashboard');

                } catch (\Exception $e) {
                // Menambahkan array konteks ['exception' => $e] agar Laravel mencatat detail error secara lengkap
                Log::error('Facebook Auth Gagal: ' . $e->getMessage(), ['exception' => $e]);
                
                return redirect()->route('login')->withErrors([
                    'login' => 'Terjadi kesalahan saat otentikasi menggunakan Facebook. Silakan coba lagi.'
                ]);
            }
    }
}
