<?php

namespace App\Http\Controllers\Toko;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    /**
     * Helper Function: Tembak API Nominatim OpenStreetMap untuk Geocoding
     */
    private function geocodeAddress($address)
    {
        $url = "https://nominatim.openstreetmap.org/search";

        try {
            $response = Http::withHeaders([
                // Wajib menggunakan User-Agent yang jelas sesuai aturan Nominatim
                'User-Agent' => 'SancakaMarketplace/1.0 (support@tokosancaka.com)',
                'Accept'     => 'application/json',
            ])->timeout(10)->get($url, [
                'q'            => $address,
                'format'       => 'json',
                'limit'        => 1,
                'countrycodes' => 'id'
            ]);

            $data = $response->json();

            if ($response->successful() && !empty($data) && isset($data[0])) {
                return [
                    'lat' => (float) $data[0]['lat'],
                    'lng' => (float) $data[0]['lon'],
                ];
            }
            return null;
        } catch (\Exception $e) {
            Log::error('Geocoding Profil Toko Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Menampilkan form untuk mengedit profil toko.
     */
    public function edit()
    {
        $store = Auth::user()->store;

        if (!$store) {
            return redirect()->route('seller.dashboard')->with('error', 'Data profil toko Anda tidak ditemukan.');
        }

        // Sesuaikan dengan letak file blade Anda, misalnya 'toko.profil.edit' atau 'seller.profile.edit'
        return view('toko.profil.edit', compact('store'));
    }

    /**
     * Mengupdate data profil toko di database.
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        $store = $user->store;

        if (!$store) {
            return redirect()->route('seller.dashboard')->with('error', 'Toko tidak ditemukan.');
        }

        // 1. Validasi Semua Input (Bukan hanya name & description)
        $validatedData = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('stores')->ignore($store->id),
            ],
            'description'    => 'nullable|string|max:1000',
            'province'       => 'required|string|max:100',
            'regency'        => 'required|string|max:100',
            'district'       => 'required|string|max:100',
            'village'        => 'required|string|max:100',
            'address_detail' => 'required|string|max:500',
            'zip_code'       => 'required|string|max:10',
            'latitude'       => 'nullable|numeric|between:-90,90',
            'longitude'      => 'nullable|numeric|between:-180,180',
            'logo'           => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ], [
            'name.unique' => 'Nama toko ini sudah digunakan oleh toko lain.',
            'latitude.numeric' => 'Format latitude tidak valid.',
            'longitude.numeric' => 'Format longitude tidak valid.',
        ]);

        $lat = $request->input('latitude');
        $lng = $request->input('longitude');

        // 2. LOGIKA OTOMATIS: Jika Latitude/Longitude kosong, tembak dari backend
        if (blank($lat) || blank($lng)) {
            // Urutan pencarian dari spesifik ke umum untuk akurasi Nominatim
            $fullAddress = "{$request->village}, {$request->district}, {$request->regency}, {$request->province}";

            $geoData = $this->geocodeAddress($fullAddress);

            if ($geoData) {
                $lat = $geoData['lat'];
                $lng = $geoData['lng'];
                Log::info("Koordinat toko {$store->name} diisi otomatis via API: {$lat}, {$lng}");
            } else {
                return redirect()->back()->withInput()->with('error', 'Sistem gagal menemukan koordinat dari alamat Anda. Pastikan nama wilayah sudah benar atau isi koordinat secara manual.');
            }
        }

        // 3. Handle File Upload (Logo)
        if ($request->hasFile('logo')) {
            // Hapus logo lama jika ada (menyesuaikan format path yang benar)
            if ($store->seller_logo) {
                Storage::disk('public')->delete($store->seller_logo);
            }
            // Simpan logo baru di folder 'store_logos'
            $path = $request->file('logo')->store('store_logos', 'public');
            $store->seller_logo = $path;
        }

        // 4. Update data Toko (Store)
        $store->name = $validatedData['name'];
        $store->slug = Str::slug($validatedData['name']);
        $store->description = $validatedData['description'];
        $store->province = $validatedData['province'];
        $store->regency = $validatedData['regency'];
        $store->district = $validatedData['district'];
        $store->village = $validatedData['village'];
        $store->address_detail = $validatedData['address_detail'];
        $store->zip_code = $validatedData['zip_code'];
        $store->latitude = $lat;
        $store->longitude = $lng;
        $store->save();

        // 5. Sinkronisasi data alamat ke tabel User (Opsional tapi disarankan agar data sinkron)
        $user->store_name = $validatedData['name'];
        $user->province = $validatedData['province'];
        $user->regency = $validatedData['regency'];
        $user->district = $validatedData['district'];
        $user->village = $validatedData['village'];
        $user->address_detail = $validatedData['address_detail'];
        $user->postal_code = $validatedData['zip_code'];
        $user->latitude = $lat;
        $user->longitude = $lng;

        if (isset($path)) {
             $user->store_logo_path = $path;
        }
        $user->save();

        // Redirect kembali dengan pesan sukses
        return redirect()->back()->with('success', 'Profil toko dan lokasi berhasil diperbarui.');
    }
}
