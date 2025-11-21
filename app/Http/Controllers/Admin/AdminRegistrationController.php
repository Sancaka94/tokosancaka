<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User; // Pastikan model User Anda di-import
use Illuminate\Http\Request;

class AdminRegistrationController extends Controller
{
    /**
     * ✅ FUNGSI UNTUK DEBUGGING
     * Fungsi ini akan dijalankan pertama kali saat controller ini dipanggil.
     */
    public function __construct()
    {
        // Hapus komentar di bawah ini untuk menguji apakah file ini dimuat.
        // dd('File AdminRegistrationController.php BERHASIL dimuat!');
    }

    /**
     * Menampilkan daftar semua pendaftaran yang menunggu persetujuan.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Mengambil semua pengguna dengan peran 'Pelanggan' yang belum diverifikasi
        $registrations = User::where('role', 'Pelanggan')
                             ->where('is_verified', 0) // Asumsi 0 = belum diverifikasi
                             ->latest('created_at') // Urutkan dari yang terbaru
                             ->paginate(10);

        return view('admin.registrations.index', compact('registrations'));
    }

    /**
     * ✅ FUNGSI YANG HILANG: Menghitung jumlah pendaftaran baru.
     * Fungsi ini akan dipanggil oleh AJAX untuk memperbarui badge notifikasi.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function count()
    {
        // Menghitung semua pengguna dengan peran 'Pelanggan' yang belum diverifikasi
        $count = User::where('role', 'Pelanggan')
                     ->where('is_verified', 0)
                     ->count();

        // Mengembalikan jumlah dalam format JSON
        return response()->json(['count' => $count]);
    }

    /**
     * Menyetujui pendaftaran seorang pengguna.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function approve($id)
    {
        $user = User::findOrFail($id);

        // Ubah status verifikasi menjadi 'disetujui'
        $user->is_verified = 1; // Asumsi 1 = sudah diverifikasi
        $user->save();

        // Di sini Anda bisa menambahkan logika untuk mengirim email notifikasi
        // bahwa akun pengguna telah disetujui.

        return redirect()->route('admin.registrations.index')
                         ->with('success', 'Pendaftaran untuk ' . $user->nama_lengkap . ' telah disetujui.');
    }
}
