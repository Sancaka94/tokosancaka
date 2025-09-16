<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    /**
     * Menampilkan form untuk mengedit profil pengguna.
     */
    public function edit(Request $request)
    {
        // Mengambil data provinsi untuk dropdown di form
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
            // ✅ PERBAIKAN: Menggunakan 'nama_lengkap' sesuai nama kolom database
            'nama_lengkap' => ['required', 'string', 'max:255'],
            
            // ✅ PERBAIKAN UTAMA: Mengarahkan validasi ke tabel 'Pengguna' dan menggunakan primary key 'id_pengguna'
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('Pengguna', 'email')->ignore($user->id_pengguna, 'id_pengguna')],
            
            'no_wa' => ['required', 'string', 'max:15'],
            'store_name' => ['nullable', 'string', 'max:255'],
            'store_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:255'],
            'province_id' => ['required', 'exists:reg_provinces,id'],
            'regency_id' => ['required', 'exists:reg_regencies,id'],
            'district_id' => ['required', 'exists:reg_districts,id'],
            'village_id' => ['required', 'exists:reg_villages,id'],
            'address_detail' => ['required', 'string'],
        ]);

        // Proses upload logo toko jika ada
        if ($request->hasFile('store_logo')) {
            $path = $request->file('store_logo')->store('uploads/store-logos', 'public');
            $validated['store_logo_path'] = $path;
        }
        
        // Hapus store_logo dari array karena kita sudah menangani path-nya
        unset($validated['store_logo']);

        // Update data user
        $user->fill($validated);
        $user->profile_setup_at = now(); // Menandai profil sudah di-update
        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }
}
