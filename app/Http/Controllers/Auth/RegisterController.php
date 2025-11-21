<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RegistrationRequest; // ✅ DARI KODE ANDA

// 👇 DITAMBAHKAN UNTUK NOTIFIKASI ADMIN
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\NotifikasiUmum;


class RegisterController extends Controller
{
    /**
     * Menampilkan form registrasi.
     */
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    /**
     * Menyimpan permintaan pendaftaran untuk persetujuan Admin.
     */
    public function store(Request $request)
    {
        // Validasi input dari form
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            // ✅ PERBAIKAN: Aturan validasi 'unique' diubah untuk menunjuk ke tabel 'Pengguna'
            // karena tabel 'users' tidak ditemukan di database Anda.
            'email' => 'required|string|email|max:255|unique:Pengguna,email|unique:registration_requests,email',
            'no_wa' => 'required|string|min:10',
            'store_nama' => 'required|string|max:255',
        ]);

        // ✅ PERBAIKAN: Menyimpan data menggunakan Eloquent Model.
        // Ini lebih bersih dan secara otomatis mengisi `created_at` dan `updated_at`.
        RegistrationRequest::create($validatedData);

        // ==========================================================
        // 👇 BLOK NOTIFIKASI YANG HILANG (DILENGKAPI)
        // ==========================================================
        try {
            // 1. Cari semua admin
            $admins = User::where('role', 'admin')->get();
            
            if ($admins->count() > 0) {
                // 2. Buat payload notifikasi
                $dataNotifAdmin = [
                    'tipe'        => 'Registrasi',
                    'judul'       => 'Registrasi Pengguna Baru',
                    'pesan_utama' => 'Pengguna baru telah mendaftar: ' . $validatedData['name'], // Ambil dari data valid
                    'url'         => route('admin.registrations.index'), // Link ke halaman registrasi
                    'icon'        => 'fas fa-user-plus',
                ];
                
                // 3. Kirim notifikasi ke semua admin
                Notification::send($admins, new NotifikasiUmum($dataNotifAdmin));
            }
        } catch (\Exception $e) {
            // Jika gagal (misal rute belum ada), catat di log tapi jangan gagalkan registrasi
            Log::error('Gagal mengirim notifikasi registrasi baru: ' . $e->getMessage());
        }
        // ==========================================================
        // 👆 AKHIR BLOK TAMBAHAN
        // ==========================================================

        // Mengirim respons dengan pesan sukses
        return redirect()->route('register')
            ->with('success', 'Permintaan pendaftaran Anda telah berhasil dikirim. Mohon tunggu persetujuan dari Admin.');
    }
}