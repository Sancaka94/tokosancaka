<?php

namespace App\Http\Controllers\Auth\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use App\Models\User;
use App\Services\FonnteService; // Pastikan Service ini ada sesuai contoh Anda

/**
 * Class CustomerForgotPasswordController
 *
 * Controller ini menangani permintaan reset password untuk User (Tabel Pengguna) via WhatsApp (Fonnte).
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
     * Menangani pengiriman token reset via WhatsApp.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sendResetLinkRequest(Request $request)
    {
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
        }

        if (!$user) {
            return back()->withErrors(['phone' => 'Nomor WhatsApp ini tidak terdaftar di sistem kami.']);
        }

        // 4. Generate Token Reset Password
        // Token ini memerlukan email user untuk verifikasi hash saat reset
        $token = Password::broker()->createToken($user);

        // 5. Siapkan Link Reset
        $resetLink = url(route('password.reset', [
            'token' => $token,
            'email' => $user->email 
        ], false));

        // 6. Siapkan Pesan
        $message = "Halo *$user->nama_lengkap*,\n\n";
        $message .= "Kami menerima permintaan reset password untuk akun Anda.\n";
        $message .= "Silakan klik link berikut untuk membuat password baru:\n\n";
        $message .= "$resetLink\n\n";
        $message .= "Link ini akan kadaluarsa dalam 60 menit.\n";
        $message .= "Jika Anda tidak meminta ini, abaikan saja.";

        // 7. Kirim Pesan via FonnteService (Sesuai Standar PesananController)
        try {
            // Menggunakan FonnteService::sendMessage seperti pada contoh sendResiViaWhatsappApi
            // Pastikan FonnteService sudah terimport
            $response = FonnteService::sendMessage($sanitizedPhone, $message);

            // Kita asumsikan Service menghandle return value, tapi kita cek dasar saja
            if (isset($response['status']) && $response['status'] == false) {
                 // Jika service mengembalikan status false (misal device disconnected)
                 return back()->withErrors(['phone' => 'Gagal mengirim WA: ' . ($response['message'] ?? 'Device tidak terhubung.')]);
            }

            return back()->with('status', 'Link reset password telah dikirim ke WhatsApp Anda!');

        } catch (\Exception $e) {
            return back()->withErrors(['phone' => 'Terjadi kesalahan sistem: ' . $e->getMessage()]);
        }
    }

    /**
     * Membersihkan dan memformat nomor telepon agar seragam (08xxx).
     * Diambil dari referensi PesananController agar konsisten.
     * * @param string $phone
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