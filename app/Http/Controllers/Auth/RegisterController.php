<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RegistrationRequest; // ✅ PERBAIKAN: Menggunakan Model

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

        // Mengirim respons dengan pesan sukses
        return redirect()->route('register')
            ->with('success', 'Permintaan pendaftaran Anda telah berhasil dikirim. Mohon tunggu persetujuan dari Admin.');
    }
}
