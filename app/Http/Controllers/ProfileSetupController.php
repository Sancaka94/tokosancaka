<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileSetupController extends Controller
{
    /**
     * Menampilkan halaman form untuk setup profil.
     * Method ini dipanggil saat pengguna mengklik link dari WhatsApp.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function show(User $user)
    {
        // Jika pengguna sudah pernah setup profil, langsung arahkan ke dashboard
        // Asumsi ada kolom 'profile_setup_at' di tabel users
        if ($user->profile_setup_at) {
            // Login otomatis dan redirect
            Auth::login($user);
            // Sesuaikan nama route jika berbeda
            return redirect()->route('customer.dashboard')->with('info', 'Anda sudah pernah mengatur profil. Selamat datang kembali!');
        }

        // Tampilkan view dengan data user, tidak perlu data provinsi untuk input manual
        return view('profile.setup', [
            'user' => $user,
        ]);
    }

    /**
     * Memvalidasi dan menyimpan data dari form setup profil.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, User $user)
    {
        // 1. Validasi input dari form
        $validated = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'no_wa' => ['required', 'string', 'max:20'],
            'store_name' => ['nullable', 'string', 'max:255'],
            
            // Aturan validasi untuk password baru
            'password' => ['required', 'confirmed', Password::min(8)],

            // Validasi untuk input alamat manual (string)
            'province' => ['required', 'string', 'max:255'],
            'regency' => ['required', 'string', 'max:255'],
            'district' => ['required', 'string', 'max:255'],
            'village' => ['required', 'string', 'max:255'],
            'address_detail' => ['required', 'string'],
        ]);

        // 2. Update data pengguna dengan data yang sudah divalidasi
        // Catatan: Ini mengasumsikan kolom alamat ada di tabel 'users'.
        // Jika Anda menggunakan tabel 'user_addresses', sesuaikan logikanya.
        $user->update([
            'nama_lengkap' => $validated['nama_lengkap'],
            'no_wa' => $validated['no_wa'],
            'store_name' => $validated['store_name'],
            'password' => Hash::make($validated['password']), // Hash password secara eksplisit
            'province' => $validated['province'],
            'regency' => $validated['regency'],
            'district' => $validated['district'],
            'village' => $validated['village'],
            'address_detail' => $validated['address_detail'],
            'profile_setup_at' => now(), // Tandai bahwa profil sudah selesai di-setup
        ]);

        // 3. Login pengguna secara otomatis setelah berhasil setup
        Auth::login($user);

        // 4. Arahkan ke dashboard yang sesuai dengan pesan sukses
        if ($user->role === 'Admin') {
            // Sesuaikan nama route jika berbeda
            return redirect()->route('admin.dashboard')->with('success', 'Profil berhasil diatur! Selamat datang, Admin.');
        }

        // Sesuaikan nama route jika berbeda
        return redirect()->route('customer.dashboard')->with('success', 'Profil Anda berhasil diatur! Selamat datang.');
    }
}
