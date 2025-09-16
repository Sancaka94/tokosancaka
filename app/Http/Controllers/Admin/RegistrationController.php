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
        $requests = User::orderBy('created_at', 'desc')->where('status', 'Tidak Aktif')->get();
        
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
        $requestData = DB::table('Pengguna')->where('id_pengguna',$id)->first();

        if (!$requestData) {
            return redirect()->route('admin.registrations.index')->with('error', 'Permintaan tidak ditemukan.');
        }
        
        $requestData->update(['status' => 'Aktif']);

        $setupUrl = url("/customer/profile/setup/{$requestData->setup_token}");


        // Siapkan pesan WhatsApp baru
        $message  = "🥳 *Pendaftaran Disetujui!*\n\n";
        $message .= "Selamat, akun Sancaka Express Anda telah aktif.\n\n";
        $message .= "Silakan klik link di bawah ini untuk melengkapi data diri dan mengatur password Anda. Link ini hanya berlaku selama 7 hari.\n\n";
        $message .= $setupUrl;

        // Gunakan nomor telepon yang sudah diformat
        $whatsappUrl = "https://wa.me/" . $phoneNumber . "?text=" . urlencode($message);

        return redirect()->route('admin.registrations.index')
            // ✅ PERBAIKAN: Menggunakan 'nama_lengkap' sesuai dengan kolom di database.
            ->with('success', 'Pengguna ' . e($user->nama_lengkap) . ' berhasil dibuat!')
            ->with('whatsapp_url', $whatsappUrl);
    }
    
    public function reject($id)
    {
        $user = DB::table('Pengguna')->where('id_pengguna', $id)->first();
    
        if (!$user) {
            return redirect()->route('admin.registrations.index')->with('error', 'Pengguna tidak ditemukan.');
        }
    
        DB::table('Pengguna')->where('id_pengguna', $id)->update([
            'status' => 'Lengkapi Data'
        ]);
    
        return redirect()->route('admin.registrations.index')->with('warning', 'Pengguna ' . e($user->nama_lengkap) . ' harus melengkapi data.');
    }
    
    public function destroy($id)
    {
        $user = DB::table('Pengguna')->where('id_pengguna', $id)->first();
    
        if (!$user) {
            return redirect()->route('admin.registrations.index')->with('error', 'Pengguna tidak ditemukan.');
        }
    
        DB::table('Pengguna')->where('id_pengguna', $id)->delete();
    
        return redirect()->route('admin.registrations.index')->with('success', 'Pengguna ' . e($user->nama_lengkap) . ' berhasil dihapus.');
    }
}
