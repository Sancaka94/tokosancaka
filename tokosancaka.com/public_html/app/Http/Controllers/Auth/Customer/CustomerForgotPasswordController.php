<?php

namespace App\Http\Controllers\Auth\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\User;
use App\Services\FonnteService; // Pastikan Service ini ada sesuai contoh Anda
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

/**
 * Class CustomerForgotPasswordController
 *
 * Controller ini menangani permintaan reset password untuk User (Tabel Pengguna) via WhatsApp (Fonnte) dan Email.
 * Menggunakan standar coding FonnteService dan sanitasi nomor telepon.
 */
class CustomerForgotPasswordController extends Controller
{
    /**
     * Menampilkan form untuk meminta token reset password (Input No HP).
     *
     * @return \Illuminate\View\View
     */
    public function showLinkRequestForm()
    {
        return view('auth.passwords.phone'); 
    }

    /**
     * Menangani pengiriman token reset via WhatsApp dan Email.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sendResetLinkRequest(Request $request)
    {
        Log::info('Permintaan reset password dimulai.', ['input' => $request->phone]);

        // 1. Validasi Input
        $request->validate([
            'phone' => 'required|string',
        ]);

        // 2. Sanitize Nomor HP (Menggunakan Helper "Baik dan Benar")
        // Ini akan mengubah input user menjadi format standar (misal: 0812...)
        $sanitizedPhone = $this->_sanitizePhoneNumber($request->phone);

        // 3. Cari User di Database
        // Kita cari berdasarkan nomor yang sudah disanitasi
        $user = User::where('no_wa', $sanitizedPhone)->first();

        // Optional: Jika di DB tersimpan dengan format 62, kita coba cari juga
        if (!$user) {
            // Ubah 08xx jadi 628xx untuk pencarian alternatif
            $phone62 = preg_replace('/^0/', '62', $sanitizedPhone);
            $user = User::where('no_wa', $phone62)->first();
            
            // Tambahan: Pencarian berdasarkan Email jika user ternyata memasukkan Email di form
            if (!$user && str_contains($request->phone, '@')) {
                $user = User::where('email', $request->phone)->first();
            }
        }

        if (!$user) {
            Log::warning('Reset password gagal: Data tidak ditemukan.', ['input' => $request->phone]);
            return back()->withErrors(['phone' => 'Data WhatsApp/Email ini tidak terdaftar di sistem kami.']);
        }

        // ====================================================================
        // 4. Generate KODE OTP (Bukan Link Panjang)
        // ====================================================================
        $otpCode = strtoupper(Str::random(6));

        $table = DB::getSchemaBuilder()->hasTable('password_reset_tokens') ? 'password_reset_tokens' : 'password_resets';
        DB::table($table)->updateOrInsert(
            ['email' => $user->email ?? $user->no_wa],
            ['token' => $otpCode, 'created_at' => now()]
        );

        // ====================================================================
        // 5. Siapkan & Kirim Pesan via FonnteService
        // ====================================================================
        $message = "Halo *$user->nama_lengkap*,\n\n";
        $message .= "Kami menerima permintaan reset password untuk akun Anda.\n";
        $message .= "Berikut adalah *KODE OTP* rahasia Anda:\n\n";
        $message .= "*{$otpCode}*\n\n";
        $message .= "Kode ini berlaku selama 60 menit. Jangan berikan kode ini kepada siapapun demi keamanan akun Anda.\n";
        $message .= "Jika Anda tidak meminta ini, abaikan saja.";

        $phoneTarget = preg_replace('/^0/', '62', $user->no_wa);

        try {
            // Menggunakan FonnteService::sendMessage seperti pada contoh sendResiViaWhatsappApi
            $response = FonnteService::sendMessage($phoneTarget, $message);
            Log::info('OTP Reset terkirim ke WhatsApp.', ['no_wa' => $phoneTarget]);

            // Kita asumsikan Service menghandle return value, tapi kita cek dasar saja
            if (isset($response['status']) && $response['status'] == false) {
                 return back()->withErrors(['phone' => 'Gagal mengirim WA: ' . ($response['message'] ?? 'Device tidak terhubung.')]);
            }

        } catch (\Exception $e) {
            Log::error('FonnteService gagal kirim OTP Reset: ' . $e->getMessage());
        }

        // ====================================================================
        // 6. Kirim OTP via Email (Jika Tersedia)
        // ====================================================================
        if (!empty($user->email)) {
            try {
                $emailBody = "Halo {$user->nama_lengkap},\n\nKami menerima permintaan reset password akun Sancaka Anda. Berikut adalah KODE OTP Anda:\n\n{$otpCode}\n\nKode ini berlaku 60 menit. Abaikan email ini jika Anda tidak merasa memintanya.";
                Mail::raw($emailBody, function ($mail) use ($user) {
                    $mail->to($user->email)->subject('Kode OTP Reset Password Sancaka');
                });
                Log::info('OTP Reset terkirim ke Email.', ['email' => $user->email]);
            } catch (\Exception $e) {
                Log::error('Gagal kirim OTP Reset via Email: ' . $e->getMessage());
            }
        }

        // Arahkan ke form OTP dengan membawa identifier (email atau no_wa)
        return redirect()->route('password.reset', ['identifier' => $user->email ?? $user->no_wa])
                         ->with('status', 'Kode OTP rahasia telah dikirim ke WhatsApp dan Email Anda.');
    }

    /**
     * Membersihkan dan memformat nomor telepon agar seragam (08xxx).
     * Diambil dari referensi PesananController agar konsisten.
     * @param string $phone
     * @return string
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
}