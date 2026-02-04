<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rules\Password;

class UserProfileController extends Controller
{
    /**
     * Menampilkan halaman setup profil.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function setup(Request $request, User $user)
    {
        // Pengecekan dari kode Anda (Ini sudah bagus)
        if (!$request->hasValidSignature()) {
            abort(401, 'Link tidak valid atau sudah kedaluwarsa.');
        }

        if ($user->profile_setup_at) {
            return redirect()->route('login')->with('status', 'Profil Anda sudah pernah diatur. Silakan login.');
        }

        // DIUBAH: Menghapus pengambilan data provinsi dari database.
        // View 'customer.profile.setup' akan mengambil data ini secara dinamis.
        return view('admin.profile.setup', [
            'user' => $user,
        ]);
    }

    /**
     * Memperbarui profil pengguna, me-login-kan, dan mengarahkan ke dashboard.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, User $user)
    {
        // DIUBAH: Menggunakan validasi yang sesuai dengan arsitektur API eksternal.
        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
            'bank_name' => ['required', 'string', 'max:255'],
            'bank_account_name' => ['required', 'string', 'max:255'],
            'bank_account_number' => ['required', 'string', 'max:255'],
            
            // Aturan 'exists' dihapus untuk mencegah error validasi.
            'province_id' => ['required', 'string'],
            'regency_id' => ['required', 'string'],
            'district_id' => ['required', 'string'],
            'village_id' => ['required', 'string'],
            'address_detail' => ['required', 'string', 'max:255'],
        ]);

        // Update data user dengan data yang sudah divalidasi
        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'bank_name' => $validated['bank_name'],
            'bank_account_name' => $validated['bank_account_name'],
            'bank_account_number' => $validated['bank_account_number'],
            'province_id' => $validated['province_id'],
            'regency_id' => $validated['regency_id'],
            'district_id' => $validated['district_id'],
            'village_id' => $validated['village_id'],
            'address_detail' => $validated['address_detail'],
            'profile_setup_at' => now(), // Tandai bahwa profil sudah di-setup
            'email_verified_at' => $user->email_verified_at ?? now(), // Verifikasi email jika belum
        ])->save();

        // --- LOGIKA BARU DARI KODE ANDA (Ini sudah bagus) ---
        // 1. Login-kan pengguna secara otomatis
        Auth::login($user);

        // 2. Arahkan ke dashboard pelanggan yang baru
        return redirect()->route('customer.dashboard')
            ->with('success', 'Profil berhasil diperbarui! Selamat datang di dashboard Anda.');
    }
}
