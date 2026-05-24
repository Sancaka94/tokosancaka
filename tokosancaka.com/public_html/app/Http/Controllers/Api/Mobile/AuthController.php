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

        // B. Kirim ke Email (Template Keren)
        try {
            Mail::send([], [], function ($message) use ($user, $token) {
                $html = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden;'>
                    <div style='background-color: #ffffff; padding: 20px; text-align: center; border-bottom: 2px solid #dc2626;'>
                        <img src='https://tokosancaka.com/storage/uploads/sancaka.png' width='180'>
                    </div>
                    <div style='padding: 30px; color: #334155;'>
                        <h3>Halo {$user->nama_lengkap},</h3>
                        <p>Selamat bergabung di Sancaka Express! Masukkan kode ini untuk memverifikasi akun Anda:</p>
                        <div style='background-color: #f1f5f9; padding: 20px; text-align: center; border-radius: 8px; border: 2px dashed #dc2626; margin: 25px 0;'>
                            <span style='font-size: 32px; font-weight: 800; color: #dc2626; letter-spacing: 5px;'>{$token}</span>
                        </div>
                        <p>Tekan lama pada kode di atas untuk menyalin.</p>
                    </div>
                </div>";
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
            'identifier' => 'required', // Ini adalah no_wa yang dikirim dari HP
            'token' => 'required|string|size:6'
        ]);

        // Cari user berdasarkan no_wa
        $user = User::where('no_wa', $request->identifier)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Pengguna tidak ditemukan.'
            ], 404);
        }

        // Cek token (memastikan tidak sensitif terhadap huruf besar/kecil)
        if ($user->setup_token && strtoupper($user->setup_token) === strtoupper($request->token)) {

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
    $savedOtp = \Illuminate\Support\Facades\Cache::get('otp_reset_pin_' . $user->id_pengguna ?? 0);

    if ($user && $savedOtp && strtoupper($token) === strtoupper($savedOtp)) {
        $user->status = 'Aktif';
        $user->is_verified = 1;
        $user->save();
        \Illuminate\Support\Facades\Cache::forget('otp_reset_pin_' . $user->id_pengguna);

        // Arahkan ke view dengan pesan sukses
        return view('verifikasi-email')->with('success', 'Akun Anda berhasil diaktifkan. Sekarang Anda bisa login.');
    }

    // Arahkan ke view dengan status gagal
    return view('verifikasi-email');
}

}
