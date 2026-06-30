<?php

namespace App\Http\Controllers\Toko;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Http; // <-- Tambahan untuk Tembak API
use Illuminate\Support\Facades\Log;  // <-- Tambahan untuk Log Error
use App\Models\Store;
use App\Models\User;

class ProfilTokoController extends Controller
{
    /**
     * Helper Function: Tembak API untuk mengubah Alamat menjadi Koordinat
     */
    private function geocodeAddress($address)
    {
        $url = "https://nominatim.openstreetmap.org/search";

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'SancakaApp/1.0 (support@tokosancaka.com)',
                'Accept'     => 'application/json',
            ])->timeout(10)->get($url, [
                'q'          => $address,
                'format'     => 'json',
                'limit'      => 1,
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

    public function edit()
    {
        $user = Auth::user();
        $store = $user->store;

        if (!$store) {
            return redirect()->route('seller.dashboard')->with('error', 'Profil toko Anda tidak ditemukan.');
        }

        return view('toko.profil.edit', compact('store'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $store = $user->store;

        if (!$store) {
            return redirect()->route('seller.dashboard')->with('error', 'Toko tidak ditemukan.');
        }

        // 1. Validasi semua input (Perhatikan: Latitude & Longitude sekarang nullable)
        $validatedData = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('stores')->ignore($store->id),
            ],
            'description'     => 'nullable|string|max:1000',
            'province'        => 'required|string|max:100',
            'regency'         => 'required|string|max:100',
            'district'        => 'required|string|max:100',
            'village'         => 'required|string|max:100',
            'address_detail'  => 'required|string|max:500',
            'zip_code'        => 'required|string|max:10',
            'latitude'        => 'nullable|numeric|between:-90,90',   // <-- Ubah jadi nullable
            'longitude'       => 'nullable|numeric|between:-180,180', // <-- Ubah jadi nullable
            'logo'            => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ], [
            'name.unique' => 'Nama toko ini sudah digunakan oleh toko lain.',
        ]);

        $lat = $request->latitude;
        $lng = $request->longitude;

        // 2. LOGIKA OTOMATIS: Jika GPS HP/PC gagal (kosong), Tembak API berdasarkan Alamat!
        if (empty($lat) || empty($lng)) {
            // Gabungkan alamat untuk ditembak ke API
            $fullAddress = "{$request->village}, {$request->district}, {$request->regency}, {$request->province}";

            $geoData = $this->geocodeAddress($fullAddress);

            if ($geoData) {
                $lat = $geoData['lat'];
                $lng = $geoData['lng'];
                Log::info("Koordinat toko {$store->name} diisi otomatis via API: {$lat}, {$lng}");
            } else {
                return redirect()->back()->withInput()->with('error', 'Sistem gagal menemukan koordinat dari alamat Anda. Mohon izinkan akses GPS di browser atau isi koordinat secara manual.');
            }
        }

        // 3. Handle File Upload (Logo)
        if ($request->hasFile('logo')) {
            if ($store->seller_logo) {
                Storage::disk('public')->delete($store->seller_logo);
            }
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
        $store->latitude = $lat;  // <-- Masukkan hasil kordinat final
        $store->longitude = $lng; // <-- Masukkan hasil kordinat final
        $store->save();

        // 5. Sinkronisasi data ke User
        $user->store_name = $validatedData['name'];
        $user->province = $validatedData['province'];
        $user->regency = $validatedData['regency'];
        $user->district = $validatedData['district'];
        $user->village = $validatedData['village'];
        $user->address_detail = $validatedData['address_detail'];
        $user->postal_code = $validatedData['zip_code'];
        $user->latitude = $lat;  // <-- Masukkan hasil kordinat final
        $user->longitude = $lng; // <-- Masukkan hasil kordinat final

        if (isset($path)) {
             $user->store_logo_path = $path;
        }
        $user->save();

        return redirect()->back()->with('success', 'Profil toko dan koordinat lokasi berhasil diperbarui.');
    }
}
