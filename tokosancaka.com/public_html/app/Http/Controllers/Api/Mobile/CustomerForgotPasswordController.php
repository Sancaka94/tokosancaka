<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Services\FonnteService; // Pastikan namespace service ini sesuai dengan aplikasimu

/**
 * Class CustomerForgotPasswordController (API Mobile)
 *
 * Controller ini menangani permintaan reset password untuk User via WhatsApp (Fonnte).
 * Dikhususkan untuk menerima request dari aplikasi React Native (TSX).
 */
class CustomerForgotPasswordController extends Controller
{
    /**
     * Menangani pengiriman token reset via WhatsApp (JSON Response).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendResetLinkApi(Request $request)
    {
        // 1. Validasi Input dari Mobile (Menerima input dengan key 'phone' atau 'email')
        // Karena form TSX awal kamu menggunakan email, kita beri fleksibilitas
        $inputField = $request->has('phone') ? 'phone' : 'email';

        try {
            $request->validate([
                $inputField => 'required|string',
            ]);

            $user = null;

            // 2. Jika Input Berupa Nomor HP
            if ($inputField === 'phone') {
                $sanitizedPhone = $this->_sanitizePhoneNumber($request->input('phone'));

                // Cari dengan format 08...
                $user = User::where('no_wa', $sanitizedPhone)->first();

                // Cari dengan format 62... jika belum ketemu
                if (!$user) {
                    $phone62 = preg_replace('/^0/', '62', $sanitizedPhone);
                    $user = User::where('no_wa', $phone62)->first();
                }
            }
            // 3. Jika Input Berupa Email
            else {
                $user = User::where('email', $request->input('email'))->first();
            }

            // 4. Jika User Tidak Ditemukan
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akun dengan ' . ($inputField === 'phone' ? 'nomor WhatsApp' : 'email') . ' tersebut tidak ditemukan di sistem kami.'
                ], 404);
            }

            // Validasi apakah user punya nomor WA jika request via email
            if (empty($user->no_wa)) {
                 return response()->json([
                    'success' => false,
                    'message' => 'Akun ini tidak memiliki nomor WhatsApp yang terdaftar untuk menerima link reset.'
                ], 400);
            }

            // 5. Generate Token Reset Password Bawaan Laravel
            // Broker password akan otomatis mencatat token ini di tabel password_resets
            $token = Password::broker()->createToken($user);

            // 6. Siapkan Link Reset (Diarahkan ke web untuk proses ganti password)
            // Pastikan URL base ini mengarah ke tampilan web frontend kamu untuk mereset password
            $resetLink = url(route('password.reset', [
                'token' => $token,
                'email' => $user->email // Email dibutuhkan oleh Laravel standar untuk verifikasi
            ], false));

            // 7. Siapkan Pesan WA
            $message = "Halo *$user->nama_lengkap*,\n\n";
            $message .= "Kami menerima permintaan reset password untuk akun Sancaka Express Anda.\n";
            $message .= "Silakan klik link berikut untuk membuat password baru:\n\n";
            $message .= "$resetLink\n\n";
            $message .= "Link ini akan kadaluarsa dalam 60 menit.\n";
            $message .= "Jika Anda tidak meminta reset password, abaikan saja pesan ini.";

            // 8. Kirim Pesan via FonnteService
            // Format ulang nomor tujuan ke format internasional (62) untuk Fonnte
            $targetWa = preg_replace('/^0/', '62', $this->_sanitizePhoneNumber($user->no_wa));

            $response = FonnteService::sendMessage($targetWa, $message);

            // Jika Fonnte Service mengembalikan status
            if (isset($response['status']) && $response['status'] == false) {
                 Log::error('[API MOBILE] Gagal mengirim WA Reset Password: ', $response);
                 return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengirim pesan WhatsApp. Pastikan layanan notifikasi aktif.'
                ], 500);
            }

            // 9. Return JSON Sukses ke React Native
            return response()->json([
                'success' => true,
                'message' => 'Tautan reset password telah dikirim ke WhatsApp Anda (' . $this->_maskPhoneNumber($user->no_wa) . ').'
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
             return response()->json([
                'success' => false,
                'message' => 'Input tidak valid.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('[API MOBILE] Customer Forgot Password Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper: Sanitasi nomor HP (Mengembalikan format 08xxx)
     */
    private function _sanitizePhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (Str::startsWith($phone, '62')) {
            if (Str::startsWith(substr($phone, 2), '0')) {
                return '0' . substr($phone, 3);
            }
            return '0' . substr($phone, 2);
        }

        if (!Str::startsWith($phone, '0') && Str::startsWith($phone, '8')) {
            return '0' . $phone;
        }

        return $phone;
    }

    /**
     * Helper: Menyamarkan nomor HP untuk ditampilkan di JSON response (Contoh: 0812***890)
     */
    private function _maskPhoneNumber(string $phone): string
    {
        if (strlen($phone) < 8) return $phone;
        $first = substr($phone, 0, 4);
        $last = substr($phone, -3);
        $stars = str_repeat('*', strlen($phone) - 7);
        return $first . $stars . $last;
    }
}
