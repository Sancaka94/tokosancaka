<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

// === Impor yang Diperlukan ===
use Illuminate\Support\Facades\Http;
use Exception;
use Illuminate\Validation\ValidationException;
// =============================

class SellerRegisterController extends Controller
{
    /**
     * Menampilkan halaman formulir pendaftaran seller.
     */
    public function create()
    {
        $user = Auth::user(); // 1. Ambil data user
        $userId = $user->id_pengguna ?? $user->id;

        // 2. Cek apakah sudah punya toko di tabel 'stores'
        $store = Store::where('user_id', $userId)->first();
        if ($store) {
            return redirect()->route('seller.dashboard')->with('info', 'Anda sudah terdaftar sebagai seller.');
        }

        // 3. Ambil nama toko dari tabel 'Pengguna' (User)
        $currentStoreName = $user->store_name;

        // 4. Tampilkan view dan kirim data
        return view('customer.seller-register', [
            'currentStoreName' => $currentStoreName,
            'user'             => $user // Mengirimkan $user ke view
        ]);
    }

    /**
     * Menyimpan data pendaftaran toko dari formulir.
     */
    public function store(Request $request)
    {
        // 1. Validasi input dari form
        $validated = $request->validate([
            'store_name'    => 'required|string|max:255|unique:stores,name',
            'description'   => 'nullable|string|max:1000',
            
            // Validasi Alamat (Input Manual)
            'province'      => 'required|string|max:100',
            'regency'       => 'required|string|max:100', // Kabupaten/Kota
            'district'      => 'required|string|max:100', // Kecamatan
            'village'       => 'required|string|max:100',  // Desa/Kelurahan
            'postal_code'   => 'required|string|max:10',
            'address_detail'=> 'required|string|max:500',
            'latitude'      => 'required|numeric|between:-90,90',
            'longitude'     => 'required|numeric|between:-180,180',
        ],
        [
            'store_name.unique' => 'Nama toko ini sudah digunakan, silakan pilih nama lain.',
            'latitude.required' => 'Koordinat Latitude (Lat) wajib diisi. Klik "Dapatkan Koordinat".',
            'longitude.required' => 'Koordinat Longitude (Long) wajib diisi. Klik "Dapatkan Koordinat".',
        ]);

        $user = Auth::user();
        $userId = $user->id_pengguna ?? $user->id;

        DB::beginTransaction(); // Mulai transaksi
        try {

            // 1. Buat entitas toko baru di database 'stores'
            $store = Store::create([
                'user_id'       => $userId,
                'name'          => $validated['store_name'],
                'slug'          => Str::slug($validated['store_name']),
                'description'   => $validated['description'],
                'province'      => $validated['province'],
                'regency'       => $validated['regency'],
                'district'      => $validated['district'],
                'village'       => $validated['village'],
                'address_detail'=> $validated['address_detail'],
                'zip_code'      => $validated['postal_code'],
                'latitude'      => $validated['latitude'],
                'longitude'     => $validated['longitude'],
            ]);

            // 2. Update data di tabel 'Pengguna' (User) agar SINKRON
            $user->role = 'Seller';
            $user->store_name = $validated['store_name']; // Sinkronkan nama
            $user->province = $validated['province'];
            $user->regency = $validated['regency'];
            $user->district = $validated['district'];
            $user->village = $validated['village'];
            $user->address_detail = $validated['address_detail'];
            $user->postal_code = $validated['postal_code'];
            $user->latitude = $validated['latitude'];
            $user->longitude = $validated['longitude'];
            $user->save();
            
            DB::commit(); // Selesaikan transaksi

            // 3. Redirect ke dashboard seller dengan pesan sukses
            return redirect()->route('seller.dashboard')->with('success', 'Selamat! Toko Anda berhasil dibuat.');

        } catch (\Exception $e) {
            DB::rollBack(); // Batalkan jika gagal
            Log::error('Gagal membuat toko: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Gagal membuat toko. Silakan coba lagi.');
        }
    }

    // ==========================================================
    // FUNGSI HELPER HANYA UNTUK GEOCoding
    // ==========================================================

    /**
     * Mendapatkan koordinat (latitude, longitude) dari alamat.
     */
    public function geocodeAddress(Request $request)
    {
        $request->validate(['address' => 'required|string|min:10']);
        $address = $request->input('address');

        $coordinates = $this->geocode($address);

        if ($coordinates) {
            return response()->json(['success' => true, 'data' => $coordinates]);
        } else {
             return response()->json(['success' => false, 'message' => 'Gagal mendapatkan koordinat untuk alamat tersebut.'], 404);
        }
    }

    /**
     * Fungsi helper untuk geocoding menggunakan Nominatim.
     */
    private function geocode($address) {
        $url = "https://nominatim.openstreetmap.org/search";
        try {
            $response = Http::timeout(10)->withHeaders([
                'User-Agent' => config('app.name', 'MyLaravelApp') . '/1.0 (' . config('mail.from.address', 'support@example.com') . ')',
                'Accept'     => 'application/json',
            ])->get($url, [
                'q'        => $address,
                'format'   => 'json',
                'limit'    => 1,
                'countrycodes' => 'id',
                'addressdetails' => 1
            ]);

            if ($response->successful() && $response->json() && !empty($response->json()[0])) {
                 $result = $response->json()[0];
                return [
                    'lat' => (float) $result['lat'],
                    'lng' => (float) $result['lon'],
                ];
            } else {
                 Log::warning('Geocoding failed or returned empty.', ['address' => $address, 'status' => $response->status(), 'body' => $response->body()]);
            }
        } catch (\Exception $e) {
            Log::error('Geocoding exception: ' . $e->getMessage(), ['address' => $address]);
        }
        return null;
    }
}