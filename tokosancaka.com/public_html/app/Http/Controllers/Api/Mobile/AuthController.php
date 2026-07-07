<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Pengguna;
use Illuminate\Support\Str;
use App\Services\FonnteService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;
use App\Notifications\NotifikasiUmum;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;

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
        // 1. Validasi Input (Tetap sama)
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

        // 3. Kirim OTP Dobel (WhatsApp + Email)
        $this->sendDualOtp($user, $token);

        return response()->json([
            'success' => true,
            'message' => 'Registrasi berhasil. Kode verifikasi telah dikirim ke WhatsApp dan Email Anda.',
            'data'    => $user
        ], 201);
    }

    // Fungsi Pembantu untuk mengirim WA & Email
    private function sendDualOtp($user, $token)
    {
        // A. Kirim ke WhatsApp (Fonnte)
        try {
            $message = "*Sancaka Express*\n\nHalo Kak {$user->nama_lengkap},\n\nKode Verifikasi Anda: *{$token}*\n\nJangan berikan kode ini kepada siapapun.";
            $noWa = preg_replace('/^0/', '62', $user->no_wa);
            FonnteService::sendMessage($noWa, $message);
        } catch (\Exception $e) {
            Log::error('Gagal kirim WA saat registrasi: ' . $e->getMessage());
        }

        // B. Kirim ke Email (Template Keren dengan Link Verifikasi)
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

        // Panggil fungsi kirim dobel
        $this->sendDualOtp($user, $newToken);

        return response()->json(['success' => true, 'message' => 'Kode verifikasi baru telah dikirim ke WA dan Email Anda.'], 200);
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
    // FUNGSI LOGIN GOOGLE VIA API MOBILE
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
            }

            if ($user->status === 'Tidak Aktif') {
                return response()->json([
                    'success' => false,
                    'message' => 'Akun belum aktif.',
                ], 403);
            }

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
    // FUNGSI LOGIN FACEBOOK VIA API MOBILE
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
            }

            if ($user->status === 'Tidak Aktif') {
                return response()->json([
                    'success' => false,
                    'message' => 'Akun belum aktif.',
                ], 403);
            }

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

}
