<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
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
            ->with('success', 'Pengguna ' . e($user->nama_lengkap) . ' berhasil diaktifkan!')
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
