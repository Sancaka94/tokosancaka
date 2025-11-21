<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\User; // Pastikan model User di-import
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SellerRegisterController extends Controller
{
    /**
     * Menampilkan halaman formulir pendaftaran seller.
     */
    public function create()
    {
        // Jika user sudah punya toko, redirect
        if (Auth::user()->store) {
            return redirect()->route('seller.dashboard')->with('info', 'Anda sudah terdaftar sebagai seller.');
        }

        // Tampilkan view formulir pendaftaran
        return view('customer.seller-register');
    }

    /**
     * Menyimpan data pendaftaran toko dari formulir.
     */
    public function store(Request $request)
    {
        // 1. Validasi input dari form
        $request->validate([
            'name' => 'required|string|max:255|unique:stores',
            'description' => 'required|string|min:20'
        ],
        [
            'name.unique' => 'Nama toko ini sudah digunakan, silakan pilih nama lain.'
        ]);

        // 2. Ambil ID pengguna yang sedang login
        $userId = Auth::id();
        if (!$userId) {
            return redirect()->back()->with('error', 'Gagal mendapatkan data pengguna. Silakan coba lagi.');
        }

        // 3. Buat entitas toko baru di database
        Store::create([
            'user_id' => $userId, // Menghubungkan toko dengan pengguna yang login
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
        ]);

        // 4. Update role pengguna menjadi 'Seller'
        $user = User::find($userId);
        $user->role = 'Seller';
        $user->save();

        // 5. Redirect ke dashboard seller dengan pesan sukses
        return redirect()->route('seller.dashboard')->with('success', 'Selamat! Toko Anda berhasil dibuat.');
    }
}
