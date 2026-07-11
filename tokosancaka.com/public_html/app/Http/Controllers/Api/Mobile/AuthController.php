<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Pengguna;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;
use App\Notifications\NotifikasiUmum;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;
use Jenssegers\Agent\Agent;

class AuthController extends Controller
{
    // LOG LOG: Fungsi Login API Mobile (Password)
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required', // Email atau No WA
            'password' => 'required',
        ]);

        $user = User::where('email', $request->login)
                    ->orWhere('no_wa', $request->login)
                    ->first();

        if (!$user || !Hash::check($request->password, $user->password_hash)) {
            return response()->json([
                'success' => false,
                'message' => 'Kredensial tidak valid.',
            ], 401);
        }

        if ($user->status === 'Tidak Aktif') {
            return response()->json([
                'success' => false,
                'message' => 'Akun belum aktif.',
            ], 403);
        }

        // Kirim Notifikasi Keamanan Login
        $this->sendLoginNotification($user, $request, 'Akun Sancaka (Password)');

        // Cetak Token untuk HP
        $token = $user->createToken('sancaka-mobile')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login Berhasil',
            'data' => [
                'user' => $user,
                'token' => $token
            ]
        ], 200);
    }

    // LOG LOG: Fungsi Login API Mobile (PIN 6 Digit)
    public function loginPin(Request $request)
    {
        $request->validate([
            'login' => 'required', // Email atau No WA
            'pin'   => 'required|digits:6',
        ], [
            'login.required' => 'Email atau Nomor WA wajib diisi.',
            'pin.required'   => 'PIN wajib diisi.',
            'pin.digits'     => 'PIN harus 6 digit angka.'
        ]);

        $user = User::where('email', $request->login)
                    ->orWhere('no_wa', $request->login)
                    ->first();

        // 1. Cek apakah user ditemukan
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Akun tidak ditemukan.',
            ], 404);
        }

        // 2. Cek apakah user aktif
        if ($user->status === 'Tidak Aktif') {
            return response()->json([
                'success' => false,
                'message' => 'Akun belum aktif.',
            ], 403);
        }

        // 3. Cek apakah user sudah set PIN sebelumnya
        if (empty($user->pin)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda belum mengatur PIN. Silakan login menggunakan Password terlebih dahulu, lalu atur PIN di menu pengaturan.',
            ], 400);
        }

        // 4. Cocokkan PIN
        if (!Hash::check($request->pin, $user->pin)) {
            return response()->json([
                'success' => false,
                'message' => 'PIN yang Anda masukkan salah.',
            ], 401);
        }

        // Kirim Notifikasi Keamanan Login
        $this->sendLoginNotification($user, $request, 'Akun Sancaka (PIN)');

        // 5. Cetak Token untuk HP
        $token = $user->createToken('sancaka-mobile')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login Berhasil',
            'data' => [
                'user' => $user,
                'token' => $token
            ]
        ], 200);
    }

    // LOG LOG: Fungsi Get Profile
    public function user(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $request->user()
        ]);
    }

    // LOG LOG: Fungsi Logout API
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil'
        ]);
    }

    public function register(Request $request)
    {
        // 1. Validasi Input
        $validator = Validator::make($request->all(), [
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'email'        => ['required', 'string', 'email', 'max:255', 'unique:Pengguna,email'],
            'password'     => ['required', 'string', 'min:8', 'confirmed'],
            'no_wa'        => ['required', 'string', 'max:15', 'unique:Pengguna,no_wa'],
            'store_name'   => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        // 2. Buat User Baru
        $user = User::create([
            'store_name'   => $request->store_name,
            'nama_lengkap' => $request->nama_lengkap,
            'email'        => $request->email,
            'no_wa'        => $request->no_wa,
            'password'     => $request->password,
            'role'         => 'Pelanggan',
            'is_verified'  => 1,
            'status'       => 'Tidak Aktif',
        ]);

        $token = strtoupper(Str::random(6));
        $user->setup_token = $token;
        $user->save();

        // 3. Kirim OTP (Hanya Email)
        $this->sendDualOtp($user, $token);
        
        // 4. Kirim Notifikasi Registrasi ke Admin dan CC ke Pendaftar
        $this->sendRegistrationNotification($user, $request);

        return response()->json([
            'success' => true,
            'message' => 'Registrasi berhasil. Kode verifikasi telah dikirim ke Email Anda.',
            'data'    => $user
        ], 201);
    }

    // Fungsi Pembantu untuk mengirim Email OTP
    private function sendDualOtp($user, $token)
    {
        try {
            // URL verifikasi yang mengarah ke route web kita
            $verifyUrl = url('/verifikasi-email?token=' . $token . '&email=' . urlencode($user->email));

            $html = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; background-color: #ffffff;'>
                <div style='background-color: #ffffff; padding: 20px; text-align: center; border-bottom: 2px solid #dc2626;'>
                    <img src='https://tokosancaka.com/storage/uploads/sancaka.png' width='180'>
                </div>
                <div style='padding: 30px; color: #334155;'>
                    <h3>Halo {$user->nama_lengkap},</h3>
                    <p>Selamat bergabung di Sancaka Express! Masukkan kode ini untuk memverifikasi akun Anda:</p>

                    <div style='background-color: #f8fafc; padding: 25px; text-align: center; border-radius: 12px; border: 1px solid #e2e8f0; margin: 25px 0;'>
                        <div style='font-size: 32px; font-weight: 800; color: #dc2626; letter-spacing: 5px; margin-bottom: 20px;'>
                            {$token}
                        </div>

                        <a href='{$verifyUrl}'
                           style='display: inline-block; background-color: #dc2626; color: #ffffff; padding: 12px 24px; border-radius: 8px; font-size: 14px; font-weight: bold; text-decoration: none;'>
                            KLIK UNTUK VERIFIKASI
                        </a>
                    </div>

                    <p style='font-size: 13px; color: #64748b;'>
                        *Jika tombol tidak berfungsi, Anda bisa menggunakan kode di atas secara manual di aplikasi.
                    </p>
                </div>
                <div style='padding: 20px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #f1f5f9;'>
                    Toko Sancaka - Solusi Pengiriman Terpercaya
                </div>
            </div>";

            Mail::send([], [], function ($message) use ($user, $html) {
                $message->to($user->email)
                        ->subject('Verifikasi Akun Sancaka Express')
                        ->html($html);
            });
        } catch (\Exception $e) {
            Log::error('Gagal kirim Email saat registrasi: ' . $e->getMessage());
        }
    }

    public function resendToken(Request $request)
    {
        $request->validate(['identifier' => 'required']);
        $user = User::where('no_wa', $request->identifier)->orWhere('email', $request->identifier)->first();

        if (!$user) return response()->json(['success' => false, 'message' => 'Pengguna tidak ditemukan.'], 404);

        $newToken = strtoupper(Str::random(6));
        $user->setup_token = $newToken;
        $user->save();

        // Panggil fungsi kirim OTP
        $this->sendDualOtp($user, $newToken);

        return response()->json(['success' => true, 'message' => 'Kode verifikasi baru telah dikirim ke Email Anda.'], 200);
    }

     // LOG LOG: Fungsi Verifikasi Token (Mobile)
    public function verifyToken(Request $request)
    {
        $request->validate([
            'identifier' => 'required', // Ini bisa berupa no_wa, email, nama_lengkap, atau store_name
            'token' => 'required|string|size:6'
        ]);

        // Cari user berdasarkan no_wa, email, nama_lengkap, atau store_name
        $user = User::where('no_wa', $request->identifier)
                    ->orWhere('email', $request->identifier)
                    ->orWhere('nama_lengkap', $request->identifier)
                    ->orWhere('store_name', $request->identifier)
                    ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Pengguna tidak ditemukan.'
            ], 404);
        }

        // Loloskan otomatis jika setup_token sudah kosong (sudah disetujui manual oleh Admin)
        if (empty($user->setup_token)) {

            // LOG LOG: Token Valid, Aktifkan User
            $user->status = 'Aktif';
            $user->save();

            $authToken = $user->createToken('sancaka-mobile')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Verifikasi berhasil.',
                'data' => [
                    'token' => $authToken,
                    'user' => $user,
                    'is_profile_completed' => !empty($user->pin) ? true : false
                ]
            ], 200);
        }

        // Jalur normal jika setup_token masih ada
        if (strtoupper($user->setup_token) === strtoupper($request->token)) {

            // LOG LOG: Token Valid, Aktifkan User
            $user->status = 'Aktif';
            $user->setup_token = null; // Kosongkan token agar tidak bisa dipakai lagi
            $user->save();

            // Buat token otentikasi (Sanctum) agar user langsung login di aplikasi
            $authToken = $user->createToken('sancaka-mobile')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Verifikasi berhasil.',
                'data' => [
                    'token' => $authToken,
                    'user' => $user,
                    'is_profile_completed' => !empty($user->pin) ? true : false
                ]
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Token tidak valid atau salah.'
        ], 400);
    }

   public function verifyEmailFromLink(Request $request)
   {
        $token = $request->query('token');
        $email = $request->query('email');

        $user = User::where('email', $email)->first();
        if (!$user) return view('verifikasi-email', ['status' => 'error', 'message' => 'Akun tidak ditemukan.']);

        // Jika sudah terverifikasi, langsung lempar ke halaman sukses
        if ($user->is_verified == 1) {
            return view('verifikasi-email', ['status' => 'success', 'message' => 'Akun sudah aktif.']);
        }

        $cacheKey = 'otp_reset_pin_' . $user->id_pengguna;
        $savedOtp = \Illuminate\Support\Facades\Cache::get($cacheKey);

        if ($savedOtp && strtoupper($token) === strtoupper($savedOtp)) {
            $user->status = 'Aktif';
            $user->is_verified = 1;
            $user->save();
            \Illuminate\Support\Facades\Cache::forget($cacheKey);

            return view('verifikasi-email', ['status' => 'success', 'message' => 'Verifikasi berhasil!']);
        }

        return view('verifikasi-email', ['status' => 'error', 'message' => 'Kode salah atau kedaluwarsa.']);
    }

    // ====================================================================
    // LOG LOG: FUNGSI LOGIN GOOGLE VIA API MOBILE
    // ====================================================================
    public function loginGoogle(Request $request)
    {
        $request->validate([
            'token' => 'required|string', // Token yang didapat dari SDK Google di Android
        ]);

        try {
            // Memverifikasi token ke server Google secara stateless
            $googleUser = Socialite::driver('google')->stateless()->userFromToken($request->token);

            Log::info('API Mobile Google Login: Data diterima.', ['email' => $googleUser->getEmail()]);

            // Cari user berdasarkan email
            $user = User::where('email', $googleUser->getEmail())->first();

            $isNewUser = false;
            if (!$user) {
                Log::info('API Mobile Google Login: Mendaftarkan user baru.');
                $user = User::create([
                    'nama_lengkap' => $googleUser->getName(),
                    'email'        => $googleUser->getEmail(),
                    'role'         => 'Pelanggan',
                    'status'       => 'Aktif',
                    'password'     => bcrypt(Str::random(16)),
                    'is_verified'  => 1,
                ]);
                $isNewUser = true;
            }

            if ($user->status === 'Tidak Aktif') {
                return response()->json([
                    'success' => false,
                    'message' => 'Akun belum aktif.',
                ], 403);
            }
            
            // Jika user baru daftar via Google, kirim notif registrasi
            if ($isNewUser) {
                $this->sendRegistrationNotification($user, $request);
            }

            // Kirim Notifikasi Keamanan Login
            $this->sendLoginNotification($user, $request, 'Google Auth');

            // Cetak Token Sanctum untuk aplikasi Android
            $token = $user->createToken('sancaka-mobile')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login Google Berhasil',
                'data' => [
                    'user'  => $user,
                    'token' => $token
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('API Mobile Google Auth Gagal: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Otentikasi Google gagal atau token kedaluwarsa.',
            ], 401);
        }
    }

    // ====================================================================
    // LOG LOG: FUNGSI LOGIN FACEBOOK VIA API MOBILE
    // ====================================================================
    public function loginFacebook(Request $request)
    {
        $request->validate([
            'token' => 'required|string', // Token yang didapat dari SDK Facebook di Android
        ]);

        try {
            // Memverifikasi token ke server Facebook secara stateless
            $facebookUser = Socialite::driver('facebook')->stateless()->userFromToken($request->token);

            $email = $facebookUser->getEmail() ?? $facebookUser->getId() . '@facebook.sancaka.com';
            Log::info('API Mobile Facebook Login: Data diterima.', ['email' => $email]);

            // Cari user berdasarkan facebook_id atau email
            $user = User::where('facebook_id', $facebookUser->getId())
                        ->orWhere('email', $email)
                        ->first();

            $isNewUser = false;
            if (!$user) {
                Log::info('API Mobile Facebook Login: Mendaftarkan user baru.');
                $user = User::create([
                    'nama_lengkap' => $facebookUser->getName(),
                    'email'        => $email,
                    'facebook_id'  => $facebookUser->getId(),
                    'role'         => 'Pelanggan',
                    'status'       => 'Aktif',
                    'password'     => bcrypt(Str::random(16)),
                    'is_verified'  => 1,
                ]);
                $isNewUser = true;
            }

            if ($user->status === 'Tidak Aktif') {
                return response()->json([
                    'success' => false,
                    'message' => 'Akun belum aktif.',
                ], 403);
            }
            
            // Jika user baru daftar via Facebook, kirim notif registrasi
            if ($isNewUser) {
                $this->sendRegistrationNotification($user, $request);
            }

            // Kirim Notifikasi Keamanan Login
            $this->sendLoginNotification($user, $request, 'Facebook Auth');

            // Cetak Token Sanctum untuk aplikasi Android
            $token = $user->createToken('sancaka-mobile')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login Facebook Berhasil',
                'data' => [
                    'user'  => $user,
                    'token' => $token
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('API Mobile Facebook Auth Gagal: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Otentikasi Facebook gagal atau token kedaluwarsa.',
            ], 401);
        }
    }

    // ====================================================================
    // FUNGSI PEMBANTU: KIRIM NOTIFIKASI LOGIN
    // ====================================================================
    private function sendLoginNotification($user, Request $request, $loginMethod)
    {
        try {
            $ip = $request->ip();
            $userAgent = $request->header('User-Agent');
            $latitude = $request->input('latitude', 'Tidak diketahui');
            $longitude = $request->input('longitude', 'Tidak diketahui');

            // Deteksi jenis perangkat menggunakan Jenssegers\Agent\Agent
            $deviceInfo = $userAgent;
            if (class_exists(\Jenssegers\Agent\Agent::class)) {
                $agent = new Agent();
                $agent->setUserAgent($userAgent);
                $device = $agent->device() ?: 'Tidak diketahui';
                $platform = $agent->platform() ?: 'Tidak diketahui';
                $browser = $agent->browser() ?: 'Tidak diketahui';
                $deviceInfo = "{$device} ({$platform} - {$browser})";
            }

            $waktu = now()->timezone('Asia/Jakarta')->format('d M Y, H:i:s T');
            $emailTujuan = !empty($user->email) ? $user->email : 'tidak-ada-email@sancaka.com';

            $html = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; background-color: #ffffff;'>
                <div style='background-color: #dc2626; padding: 20px; text-align: center; color: white;'>
                    <h2 style='margin:0;'>Peringatan Keamanan Login</h2>
                </div>
                <div style='padding: 30px; color: #334155;'>
                    <p>Halo,</p>
                    <p>Akun atas nama <strong>{$user->nama_lengkap}</strong> baru saja melakukan login ke aplikasi Sancaka Express. Berikut adalah rincian aktivitas tersebut:</p>
                    
                    <table style='width: 100%; border-collapse: collapse; margin-top: 20px; text-align: left;'>
                        <tr>
                            <td style='padding: 10px; border: 1px solid #e2e8f0; font-weight: bold; background-color: #f8fafc; width: 35%;'>Metode Login</td>
                            <td style='padding: 10px; border: 1px solid #e2e8f0;'>{$loginMethod}</td>
                        </tr>
                        <tr>
                            <td style='padding: 10px; border: 1px solid #e2e8f0; font-weight: bold; background-color: #f8fafc;'>Waktu</td>
                            <td style='padding: 10px; border: 1px solid #e2e8f0;'>{$waktu}</td>
                        </tr>
                        <tr>
                            <td style='padding: 10px; border: 1px solid #e2e8f0; font-weight: bold; background-color: #f8fafc;'>IP Address</td>
                            <td style='padding: 10px; border: 1px solid #e2e8f0;'>{$ip}</td>
                        </tr>
                        <tr>
                            <td style='padding: 10px; border: 1px solid #e2e8f0; font-weight: bold; background-color: #f8fafc;'>Perangkat</td>
                            <td style='padding: 10px; border: 1px solid #e2e8f0;'>{$deviceInfo}</td>
                        </tr>
                        <tr>
                            <td style='padding: 10px; border: 1px solid #e2e8f0; font-weight: bold; background-color: #f8fafc;'>Lokasi (Lat, Lng)</td>
                            <td style='padding: 10px; border: 1px solid #e2e8f0;'>
                                <a href='https://www.google.com/maps/search/?api=1&query={$latitude},{$longitude}' target='_blank' style='color: #dc2626;'>{$latitude}, {$longitude}</a>
                            </td>
                        </tr>
                    </table>

                    <p style='margin-top: 25px; font-size: 14px; color: #64748b;'>
                        Jika ini adalah Anda, abaikan email ini. Namun, jika Anda tidak merasa melakukan login ini, segera hubungi Admin Sancaka untuk mengamankan akun Anda.
                    </p>
                </div>
            </div>";

            Mail::send([], [], function ($message) use ($emailTujuan, $user, $html) {
                // Email ditujukan ke Admin (salafy94@gmail.com) dan di-CC ke user
                $message->to('salafy94@gmail.com')
                        ->subject("Notifikasi Keamanan: Login Baru ({$user->nama_lengkap})")
                        ->html($html);
                
                // Pastikan user punya email yang valid sebelum di CC
                if (filter_var($emailTujuan, FILTER_VALIDATE_EMAIL) && !str_contains($emailTujuan, '@facebook.sancaka.com')) {
                    $message->cc($emailTujuan);
                }
            });

            Log::info("Notifikasi login dikirim ke Admin & CC User: {$emailTujuan}");
        } catch (\Exception $e) {
            Log::error('Gagal mengirim notifikasi login: ' . $e->getMessage());
        }
    }

    // ====================================================================
    // FUNGSI PEMBANTU: KIRIM NOTIFIKASI REGISTRASI BARU
    // ====================================================================
    private function sendRegistrationNotification($user, Request $request)
    {
        try {
            $ip = $request->ip();
            $userAgent = $request->header('User-Agent');
            $latitude = $request->input('latitude', 'Tidak diketahui');
            $longitude = $request->input('longitude', 'Tidak diketahui');

            // Deteksi jenis perangkat
            $deviceInfo = $userAgent;
            if (class_exists(\Jenssegers\Agent\Agent::class)) {
                $agent = new Agent();
                $agent->setUserAgent($userAgent);
                $device = $agent->device() ?: 'Tidak diketahui';
                $platform = $agent->platform() ?: 'Tidak diketahui';
                $browser = $agent->browser() ?: 'Tidak diketahui';
                $deviceInfo = "{$device} ({$platform} - {$browser})";
            }

            $waktu = now()->timezone('Asia/Jakarta')->format('d M Y, H:i:s T');
            $emailTujuan = !empty($user->email) ? $user->email : 'tidak-ada-email@sancaka.com';
            
            // Penyesuaian tampilan agar admin tau jika ada data yang kosong (khusus login sosial media)
            $storeName = !empty($user->store_name) ? $user->store_name : '-';
            $noWa = !empty($user->no_wa) ? $user->no_wa : '-';

            $html = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; background-color: #ffffff;'>
                <div style='background-color: #0284c7; padding: 20px; text-align: center; color: white;'>
                    <h2 style='margin:0;'>Pendaftaran Pengguna Baru</h2>
                </div>
                <div style='padding: 30px; color: #334155;'>
                    <p>Halo,</p>
                    <p>Terdapat pendaftaran akun baru di aplikasi Sancaka Express. Berikut adalah rincian pendaftar tersebut:</p>
                    
                    <table style='width: 100%; border-collapse: collapse; margin-top: 20px; text-align: left;'>
                        <tr>
                            <td style='padding: 10px; border: 1px solid #e2e8f0; font-weight: bold; background-color: #f8fafc; width: 35%;'>Nama Lengkap</td>
                            <td style='padding: 10px; border: 1px solid #e2e8f0;'>{$user->nama_lengkap}</td>
                        </tr>
                        <tr>
                            <td style='padding: 10px; border: 1px solid #e2e8f0; font-weight: bold; background-color: #f8fafc;'>Nama Toko</td>
                            <td style='padding: 10px; border: 1px solid #e2e8f0;'>{$storeName}</td>
                        </tr>
                        <tr>
                            <td style='padding: 10px; border: 1px solid #e2e8f0; font-weight: bold; background-color: #f8fafc;'>Email</td>
                            <td style='padding: 10px; border: 1px solid #e2e8f0;'>{$user->email}</td>
                        </tr>
                        <tr>
                            <td style='padding: 10px; border: 1px solid #e2e8f0; font-weight: bold; background-color: #f8fafc;'>Nomor WA</td>
                            <td style='padding: 10px; border: 1px solid #e2e8f0;'>{$noWa}</td>
                        </tr>
                        <tr>
                            <td style='padding: 10px; border: 1px solid #e2e8f0; font-weight: bold; background-color: #f8fafc;'>Waktu Daftar</td>
                            <td style='padding: 10px; border: 1px solid #e2e8f0;'>{$waktu}</td>
                        </tr>
                        <tr>
                            <td style='padding: 10px; border: 1px solid #e2e8f0; font-weight: bold; background-color: #f8fafc;'>IP Address</td>
                            <td style='padding: 10px; border: 1px solid #e2e8f0;'>{$ip}</td>
                        </tr>
                        <tr>
                            <td style='padding: 10px; border: 1px solid #e2e8f0; font-weight: bold; background-color: #f8fafc;'>Perangkat</td>
                            <td style='padding: 10px; border: 1px solid #e2e8f0;'>{$deviceInfo}</td>
                        </tr>
                        <tr>
                            <td style='padding: 10px; border: 1px solid #e2e8f0; font-weight: bold; background-color: #f8fafc;'>Lokasi (Lat, Lng)</td>
                            <td style='padding: 10px; border: 1px solid #e2e8f0;'>
                                <a href='https://www.google.com/maps/search/?api=1&query={$latitude},{$longitude}' target='_blank' style='color: #0284c7;'>{$latitude}, {$longitude}</a>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>";

            Mail::send([], [], function ($message) use ($emailTujuan, $user, $html) {
                // Email ditujukan ke Admin (salafy94@gmail.com) dan di-CC ke user
                $message->to('salafy94@gmail.com')
                        ->subject("Pendaftaran Baru: {$user->nama_lengkap}")
                        ->html($html);
                
                // Pastikan user punya email yang valid sebelum di CC
                if (filter_var($emailTujuan, FILTER_VALIDATE_EMAIL) && !str_contains($emailTujuan, '@facebook.sancaka.com')) {
                    $message->cc($emailTujuan);
                }
            });

            Log::info("Notifikasi registrasi dikirim ke Admin & CC User: {$emailTujuan}");
        } catch (\Exception $e) {
            Log::error('Gagal mengirim notifikasi registrasi: ' . $e->getMessage());
        }
    }
}