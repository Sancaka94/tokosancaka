<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Str;
use App\Services\FonnteService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;
use App\Notifications\NotifikasiUmum;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    // LOG LOG: Fungsi Login API Mobile
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

    // LOG LOG: Fungsi Register API Mobile
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
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        // 2. Buat User Baru (Status Tidak Aktif)
        $user = User::create([
            'store_name'   => $request->store_name,
            'nama_lengkap' => $request->nama_lengkap,
            'email'        => $request->email,
            'no_wa'        => $request->no_wa,
            'password'     => $request->password, // hash di mutator User
            'role'         => 'Pelanggan',
            'is_verified'  => 1,
            'status'       => 'Tidak Aktif',
        ]);

        // 3. Generate token setup (6 Karakter Huruf Besar dan Angka)
        $token = strtoupper(Str::random(6)); // PERUBAHAN DI SINI
        $user->setup_token = $token;
        $user->save();

        // 4. Notifikasi ke Admin
        try {
            $admins = User::where('role', 'Admin')->get();

            if ($admins->isNotEmpty()) {
                $dataNotifAdmin = [
                    'tipe'        => 'Registrasi',
                    'judul'       => 'Pendaftaran Pelanggan Baru (Mobile)',
                    'pesan_utama' => $user->nama_lengkap . ' telah mendaftar (Status: Tidak Aktif).',
                    'url'         => route('admin.customers.index'),
                    'icon'        => 'fas fa-user-plus',
                ];
                Notification::send($admins, new NotifikasiUmum($dataNotifAdmin));
            }
        } catch (\Exception $e) {
            Log::error('Gagal mengirim notifikasi pendaftaran via API: ' . $e->getMessage());
        }

        // 5. Kirim Pesan WhatsApp via Fonnte
        // Pastikan nama route 'customer.profile.setup' terdaftar di web.php
        $setup_url = route('customer.profile.setup', ['token' => $token]);

        $message = <<<TEXT
*Selamat Datang di Aplikasi Sancaka Express, Kak {$user->nama_lengkap}*

Apabila Anda mengalami kendala atau memiliki pertanyaan, silakan hubungi Admin Sancaka melalui nomor +628819435180.

Berikut adalah *KODE VERIFIKASI* dan Link Pendaftaran Kakak {$user->nama_lengkap}:

KODE VERIFIKASI APLIKASI: *{$token}*

Atau Lanjutkan Pendaftaran Web Dengan Klik Link Dibawah ini:

Link Setup Profile: {$setup_url}

Hormat kami,
*Manajemen Sancaka* CV Sancaka Karya Hutama
*Jl.Dr.Wahidin No.18A RT.22 RW.05 Ketanggi Ngawi Jawa Timur 63211* Website: tokosancaka.com
TEXT;

        $noWa = preg_replace('/^0/', '62', $user->no_wa);

        try {
            FonnteService::sendMessage($noWa, $message);
            FonnteService::sendMessage('085745808809', $message);
        } catch (\Exception $e) {
            Log::error('Gagal mengirim WA pendaftaran via API: ' . $e->getMessage());
        }

        // 6. Return Response JSON
        // Karena statusnya masih 'Tidak Aktif', kita tidak mencetak token Sanctum di sini
        return response()->json([
            'success' => true,
            'message' => 'Registrasi berhasil. Silakan cek WhatsApp Anda untuk menyelesaikan pendaftaran.',
            'data'    => $user
        ], 201);
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
                    'user' => $user
                ]
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Token tidak valid atau salah.'
        ], 400);
    }

    // LOG LOG: Fungsi Kirim Ulang Token (Mobile)
    public function resendToken(Request $request)
    {
        $request->validate([
            'identifier' => 'required'
        ]);

        $user = User::where('no_wa', $request->identifier)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Pengguna tidak ditemukan.'
            ], 404);
        }

        // Generate ulang token 6 karakter
        $newToken = strtoupper(Str::random(6));
        $user->setup_token = $newToken;
        $user->save();

        // Pesan WA yang baru
        $message = "*Sancaka Express*\n\nHalo Kak {$user->nama_lengkap},\nIni adalah KODE VERIFIKASI baru Anda:\n\n*{$newToken}*\n\nKode ini bersifat rahasia.";

        $noWa = preg_replace('/^0/', '62', $user->no_wa);

        try {
            FonnteService::sendMessage($noWa, $message);
        } catch (\Exception $e) {
            Log::error('Gagal mengirim ulang WA via API: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Kode verifikasi baru telah dikirim ulang ke WhatsApp Anda.'
        ], 200);
    }
}
