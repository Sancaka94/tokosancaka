<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log; // Optional: Jika Anda ingin mencatat log pengiriman email
use Illuminate\Support\Str;

class RegistrationController extends Controller
{
    /**
     * Menampilkan daftar permintaan pendaftaran dan pengguna terdaftar.
     */
    public function index()
    {
        // Mengambil permintaan pendaftaran (status 'Tidak Aktif')
        $requests = User::orderBy('created_at', 'desc')->where('status', 'Tidak Aktif')->get();

        // Mengambil semua pengguna terdaftar dengan paginasi
        $registeredUsers = User::orderBy('created_at', 'desc')->paginate(10);

        return view('admin.registrations.index', [
            'requests' => $requests,
            'registeredUsers' => $registeredUsers
        ]);
    }

    /**
     * Menyetujui permintaan pendaftaran.
     */
    public function approve($id)
    {
        // Gunakan Eloquent Model untuk menemukan pengguna
        $user = User::find($id);

        if (!$user) {
            return redirect()->route('admin.registrations.index')->with('error', 'Permintaan tidak ditemukan.');
        }

        // Perbarui status menggunakan metode Eloquent
        $user->update(['status' => 'Aktif']);

        // Dapatkan URL untuk setup profil
        $setupUrl = url("/customer/profile/setup/{$user->setup_token}");

        // =========================================================
        // TAMBAHAN: KIRIM EMAIL KE USER
        // =========================================================
        try {
            $emailSubject = 'Pendaftaran Sancaka Express Disetujui';
            $userEmail = $user->email; // Pastikan kolom email di tabel User adalah 'email'

            // Format HTML sederhana untuk email
            $emailBody = "
            <div style='font-family: Arial, sans-serif; padding: 20px; background-color: #f9f9f9;'>
                <div style='background-color: #ffffff; padding: 20px; border-radius: 8px; border: 1px solid #ddd; max-width: 600px; margin: auto;'>
                    <h2 style='color: #28a745; text-align: center;'>🥳 Pendaftaran Disetujui!</h2>
                    <p>Halo <strong>{$user->nama_lengkap}</strong>,</p>
                    <p>Selamat, akun Sancaka Express Anda telah aktif.</p>
                    <p>Silakan klik tombol di bawah ini untuk melengkapi data diri dan mengatur password Anda. <strong>Link ini hanya berlaku selama 7 hari.</strong></p>

                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='{$setupUrl}' style='background-color: #dc2626; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;'>Lengkapi Data Profil</a>
                    </div>

                    <p style='color: #666; font-size: 14px;'>Jika tombol tidak berfungsi, salin dan tempel tautan berikut di browser Anda:</p>
                    <p style='color: #2563eb; font-size: 14px; word-break: break-all;'>{$setupUrl}</p>

                    <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'/>
                    <p style='font-size: 12px; color: #999; text-align: center;'>Email ini dikirim otomatis oleh sistem Sancaka Express. Harap tidak membalas email ini.</p>
                </div>
            </div>";

            // Gunakan Mail::html untuk mengirim string HTML langsung
            Mail::html($emailBody, function ($message) use ($userEmail, $emailSubject) {
                $message->to($userEmail)
                        ->subject($emailSubject)
                        ->from(config('mail.from.address', 'admin@tokosancaka.com'), config('mail.from.name', 'Admin Sancaka'));
            });

        } catch (\Exception $e) {
            // Jika email gagal, jangan sampai mengganggu proses approve. Cukup log errornya.
            \Illuminate\Support\Facades\Log::error('Gagal kirim email approval ke ' . $user->email . ': ' . $e->getMessage());
        }
        // =========================================================

        // Siapkan pesan WhatsApp baru
        $message  = "🥳 *Pendaftaran Disetujui!*\n\n";
        $message .= "Selamat, akun Sancaka Express Anda telah aktif.\n\n";
        $message .= "Silakan klik link di bawah ini untuk melengkapi data diri dan mengatur password Anda. Link ini hanya berlaku selama 7 hari.\n\n";
        $message .= $setupUrl;

        // Pastikan nama kolom nomor telepon sudah benar (misal: 'no_hp' atau 'phone_number')
        $phoneNumber = $user->no_hp;

        // Buat URL WhatsApp
        $whatsappUrl = "https://wa.me/" . $phoneNumber . "?text=" . urlencode($message);

        return redirect()->route('admin.registrations.index')
            ->with('success', 'Pengguna ' . e($user->nama_lengkap) . ' berhasil diaktifkan dan Notifikasi Email Telah Terkirim!')
            ->with('whatsapp_url', $whatsappUrl);
    }

    /**
     * Menolak atau meminta pengguna melengkapi data.
     */
    public function reject($id)
    {
        // Gunakan Eloquent Model untuk konsistensi
        $user = User::find($id);

        if (!$user) {
            return redirect()->route('admin.registrations.index')->with('error', 'Pengguna tidak ditemukan.');
        }

        // Perbarui status pengguna
        $user->update([
            'status' => 'Lengkapi Data'
        ]);

        return redirect()->route('admin.registrations.index')->with('warning', 'Status pengguna ' . e($user->nama_lengkap) . ' diubah menjadi "Lengkapi Data".');
    }

    /**
     * Menghapus pengguna.
     */
    public function destroy($id)
    {
        // Gunakan Eloquent Model untuk konsistensi
        $user = User::find($id);

        if (!$user) {
            return redirect()->route('admin.registrations.index')->with('error', 'Pengguna tidak ditemukan.');
        }

        // Hapus pengguna
        $user->delete();

        return redirect()->route('admin.registrations.index')->with('success', 'Pengguna ' . e($user->nama_lengkap) . ' berhasil dihapus.');
    }
}
