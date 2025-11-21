<?php

// ✅ PERBAIKAN: Namespace disesuaikan dengan nama folder (Admin)
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller; // ✅ PERBAIKAN: Menambahkan 'use' statement untuk Controller dasar
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Redirect;

class ProfileController extends Controller
{
    /**
     * Menampilkan form untuk mengedit profil pengguna.
     */
    public function edit(Request $request)
    {
        // DIUBAH: Mengambil data langsung dari tabel 'reg_provinces'
        $provinces = DB::table('reg_provinces')->get();

        return view('customer.profile.edit', [
            'user' => $request->user(),
            'provinces' => $provinces,
        ]);
    }

    /**
     * Memperbarui informasi profil pengguna.
     */
    public function update(Request $request)
    {
        $user = $request->user();

        // Validasi semua input dari form
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'no_wa' => ['required', 'string', 'max:15'],
            'store_name' => ['nullable', 'string', 'max:255'],
            'store_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:255'],
            // DIUBAH: Sesuaikan nama tabel validasi
            'province_id' => ['required', 'exists:reg_provinces,id'],
            'regency_id' => ['required', 'exists:reg_regencies,id'],
            'district_id' => ['required', 'exists:reg_districts,id'],
            'village_id' => ['required', 'exists:reg_villages,id'],
            'address_detail' => ['required', 'string'],
        ]);

        // Proses upload logo toko jika ada
        if ($request->hasFile('store_logo')) {
            $path = $request->file('store_logo')->store('uploads/store-logos', 'public');
            $user->store_logo_path = $path;
        }
        
        // Update data user
        $user->fill($validated);
        $user->profile_setup_at = now();
        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }
}
