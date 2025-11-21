<?php

namespace App\Http\Controllers\Toko;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Models\Store; // <-- Impor model Store
use App\Models\User;  // <-- Impor model User

class ProfilTokoController extends Controller
{
    /**
     * Menampilkan halaman formulir edit profil toko.
     * Halaman ini akan memuat data toko yang sedang login.
     */
    public function edit()
    {
        $user = Auth::user();
        
        // Ambil data toko yang terkait dengan user ini
        // Asumsi relasi di model User: public function store() { return $this->hasOne(Store::class, 'user_id'); }
        $store = $user->store;

        if (!$store) {
            // Jika user seller tapi data tokonya tidak ada, ini aneh.
            // Arahkan ke dashboard seller dengan error.
            return redirect()->route('seller.dashboard')->with('error', 'Profil toko Anda tidak ditemukan.'); 
        }

        // Tampilkan view edit profil toko
        // Pastikan nama view ini sesuai dengan file yang Anda buat
        return view('toko.profil.edit', compact('store'));
    }

    /**
     * Memperbarui data profil toko di database.
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        $store = $user->store;

        if (!$store) {
            return redirect()->route('seller.dashboard')->with('error', 'Toko tidak ditemukan.');
        }

        // 1. Validasi semua input dari form
        $validatedData = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('stores')->ignore($store->id), // Pastikan nama unik, tapi abaikan toko ini
            ],
            'description'     => 'nullable|string|max:1000',
            'province'        => 'required|string|max:100',
            'regency'         => 'required|string|max:100',
            'district'        => 'required|string|max:100',
            'village'         => 'required|string|max:100',
            'address_detail'  => 'required|string|max:500',
            'zip_code'        => 'required|string|max:10', // Sesuai nama form
            'latitude'        => 'required|numeric|between:-90,90',
            'longitude'       => 'required|numeric|between:-180,180',
            'logo'            => 'nullable|image|mimes:jpeg,png,jpg|max:2048', // 'logo' sesuai nama form
        ], [
            // Pesan error khusus
            'name.unique' => 'Nama toko ini sudah digunakan oleh toko lain.',
            'latitude.required' => 'Latitude wajib diisi. Gunakan tombol "Cari Koordinat".',
            'longitude.required' => 'Longitude wajib diisi. Gunakan tombol "Cari Koordinat".',
        ]);

        // 2. Handle File Upload (Logo)
        if ($request->hasFile('logo')) {
            // Hapus logo lama jika ada
            // Menggunakan 'seller_logo' sesuai dengan yang ada di view Anda
            if ($store->seller_logo) { 
                Storage::disk('public')->delete($store->seller_logo);
            }
            // Simpan logo baru
            $path = $request->file('logo')->store('store_logos', 'public');
            $store->seller_logo = $path; // 'seller_logo' adalah kolom di DB
        }

        // 3. Update data Toko (Store)
        $store->name = $validatedData['name'];
        $store->slug = Str::slug($validatedData['name']);
        $store->description = $validatedData['description'];
        $store->province = $validatedData['province'];
        $store->regency = $validatedData['regency'];
        $store->district = $validatedData['district'];
        $store->village = $validatedData['village'];
        $store->address_detail = $validatedData['address_detail'];
        $store->zip_code = $validatedData['zip_code']; // Menggunakan 'zip_code'
        $store->latitude = $validatedData['latitude'];
        $store->longitude = $validatedData['longitude'];
        $store->save();

        // 4. Sinkronisasi data ke User (PENTING untuk konsistensi)
        // Ini agar data di tabel 'users' juga terupdate,
        // sama seperti saat registrasi seller.
        $user->store_name = $validatedData['name']; 
        $user->province = $validatedData['province'];
        $user->regency = $validatedData['regency'];
        $user->district = $validatedData['district'];
        $user->village = $validatedData['village'];
        $user->address_detail = $validatedData['address_detail'];
        
        // Sesuaikan nama kolom di tabel 'users' jika berbeda
        $user->postal_code = $validatedData['zip_code']; 
        $user->latitude = $validatedData['latitude'];
        $user->longitude = $validatedData['longitude'];
        
        // Jika Anda juga menyimpan path logo di tabel user
        if (isset($path)) {
             // Sesuaikan nama kolom 'store_logo_path' di tabel 'users'
             $user->store_logo_path = $path; 
        }
        $user->save();

        // 5. Redirect kembali dengan pesan sukses
        return redirect()->back()->with('success', 'Profil toko berhasil diperbarui.');
    }
}