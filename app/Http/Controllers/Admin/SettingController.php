<?php

// [CATATAN] Pastikan namespace ini benar sesuai lokasi file Anda
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;
use App\Models\BannerEtalase; // Pastikan model ini ada dan benar
use App\Models\User; // Import model User
use Illuminate\Support\Facades\Storage;
use Exception;
use Illuminate\Support\Facades\Log; // Ditambahkan untuk logging
use Illuminate\Support\Facades\Auth; // Untuk mendapatkan user yang login
use Illuminate\Support\Facades\Http; // Untuk geocoding
use App\Services\KiriminAjaService; // Import KiriminAjaService
use Illuminate\Validation\ValidationException; // Untuk error validasi

class SettingController extends Controller
{
    /**
     * Menampilkan halaman pengaturan.
     */
    public function index()
    {
        try {
            $user = Auth::user(); // Ambil data user yang sedang login
            if (!$user) {
                // Handle jika user tidak terautentikasi (seharusnya tidak terjadi jika ada middleware)
                 return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
            }

            // Ambil banner etalase
            $banners = BannerEtalase::orderBy('id', 'desc')->get();

            // Ambil settings logo dan banner utama
            $settings = Setting::whereIn('key', ['logo','banner_2','banner_3'])
                                ->pluck('value','key')
                                ->all();

            // Kirim data user ke view
            return view('admin.settings.index', compact('banners','settings', 'user'));

        } catch (Exception $e) {
            Log::error('Failed to load settings page: '.$e->getMessage());
            return back()->with('error', 'Gagal memuat halaman pengaturan. Silakan coba lagi.');
        }
    }

     // --- PENGATURAN LOGO & BANNER BAWAAN ---

    /**
     * Memperbarui pengaturan utama (logo, banner_2, banner_3).
     */
    public function updateSettings(Request $request)
    {
         $request->validate([
             'logo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
             'banner_2' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
             'banner_3' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
         ]);

        try {
            foreach (['logo','banner_2','banner_3'] as $key) {
                if ($request->hasFile($key)) {
                    $file = $request->file($key);
                    $path = $file->store('settings', 'public');

                    $oldSetting = Setting::where('key', $key)->first();
                    if ($oldSetting && $oldSetting->value && Storage::disk('public')->exists($oldSetting->value)) {
                        Storage::disk('public')->delete($oldSetting->value);
                        Log::info("Deleted old setting image for key '{$key}': " . $oldSetting->value);
                    }

                    Setting::updateOrCreate(
                        ['key' => $key],
                        ['value' => $path]
                    );
                    Log::info("Updated setting '{$key}' with image: " . $path);
                }
            }
            return back()->with('success', 'Pengaturan gambar berhasil diperbarui');
        } catch (Exception $e) {
             Log::error('Failed to update settings images: '.$e->getMessage());
            return back()->with('error', 'Gagal memperbarui pengaturan gambar: '.$e->getMessage());
        }
    }

    // --- PENGATURAN BANNER SLIDER (ETALASE) ---

    /**
     * Menyimpan banner etalase baru.
     */
    public function storeBanner(Request $request)
    {
        try {
            $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            ]);
            $path = $request->file('image')->store('banners', 'public');
            BannerEtalase::create(['image' => $path]);
            Log::info("Stored new banner image: " . $path);
            return back()->with('success', 'Banner berhasil ditambahkan');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput(); // Kembali dengan error validasi
        } catch (Exception $e) {
             Log::error('Failed to add banner: '.$e->getMessage());
            return back()->with('error', 'Gagal menambahkan banner: '.$e->getMessage());
        }
    }

    /**
     * Memperbarui banner etalase yang sudah ada.
     */
    public function updateBanner(Request $request, BannerEtalase $banner)
    {
        try {
            $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            ]);

            if ($request->hasFile('image')) {
                if ($banner->image && Storage::disk('public')->exists($banner->image)) {
                    Storage::disk('public')->delete($banner->image);
                     Log::info("Deleted old banner image for banner ID {$banner->id}: " . $banner->image);
                }
                $path = $request->file('image')->store('banners', 'public');
                $banner->update(['image' => $path]);
                 Log::info("Updated banner ID {$banner->id} with image: " . $path);
            }
            return back()->with('success', 'Banner berhasil diperbarui');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput()->with('edit_banner_id', $banner->id); // Kirim ID banner yg error
        } catch (Exception $e) {
             Log::error('Failed to update banner: '.$e->getMessage());
            return back()->with('error', 'Gagal memperbarui banner: '.$e->getMessage())->with('edit_banner_id', $banner->id);
        }
    }

    /**
     * Menghapus banner etalase.
     */
    public function destroyBanner(BannerEtalase $banner)
    {
        try {
            $oldImagePath = $banner->image;
            if ($oldImagePath && Storage::disk('public')->exists($oldImagePath)) {
                Storage::disk('public')->delete($oldImagePath);
                 Log::info("Deleted banner image file for banner ID {$banner->id}: " . $oldImagePath);
            }
            $banner->delete();
             Log::info("Deleted banner record with ID {$banner->id}");
            return back()->with('success', 'Banner berhasil dihapus');
        } catch (Exception $e) {
             Log::error('Failed to delete banner: '.$e->getMessage());
            return back()->with('error', 'Gagal menghapus banner: '.$e->getMessage());
        }
    }


    // --- [BARU] FUNGSI UNTUK ALAMAT ---

    /**
      * Mencari alamat menggunakan KiriminAja API.
      * Dipanggil via AJAX dari view.
      */
    public function searchAddressKiriminAja(Request $request, KiriminAjaService $kiriminAja)
    {
        $request->validate(['query' => 'required|string|min:3']);
        $query = $request->input('query');

        try {
            $result = $kiriminAja->searchAddress($query);
            // Log::info('KiriminAja Search Result:', ['query' => $query, 'result' => $result]); // Debugging

            if ($result['status'] && !empty($result['data'])) {
                // Ambil hanya data yang relevan untuk ditampilkan
                $simplifiedResults = collect($result['data'])->map(function ($addr) {
                    return [
                        'text' => $addr['label'] ?? implode(', ', array_filter([$addr['village'], $addr['subdistrict'], $addr['city'], $addr['province']])), // Teks tampilan
                        'village' => $addr['village'],
                        'subdistrict' => $addr['subdistrict'], // Kecamatan KiriminAja
                        'city' => $addr['city'],             // Kabupaten/Kota KiriminAja
                        'province' => $addr['province'],
                        'zip_code' => $addr['zip_code'],
                        'district_id' => $addr['district_id'],    // ID Kecamatan KiriminAja
                        'subdistrict_id' => $addr['subdistrict_id'],// ID Kelurahan KiriminAja
                    ];
                })->take(10); // Batasi hasil

                return response()->json(['success' => true, 'data' => $simplifiedResults]);
            } else {
                 return response()->json(['success' => false, 'message' => $result['message'] ?? 'Alamat tidak ditemukan.']);
            }
        } catch (Exception $e) {
             Log::error('KiriminAja searchAddress failed: '.$e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal mencari alamat: ' . $e->getMessage()], 500);
        }
    }

     /**
      * Mendapatkan koordinat (latitude, longitude) dari alamat.
      * Dipanggil via AJAX dari view.
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
     private function geocode($address) { // Ubah jadi private
         $url = "https://nominatim.openstreetmap.org/search";
         try {
             $response = Http::timeout(10)->withHeaders([
                 'User-Agent' => config('app.name', 'MyLaravelApp') . '/1.0 (' . config('mail.from.address', 'support@example.com') . ')',
                 'Accept'     => 'application/json',
             ])->get($url, [
                 'q'      => $address,
                 'format' => 'json',
                 'limit'  => 1,
                 'countrycodes' => 'id', // Fokus ke Indonesia
                 'addressdetails' => 1 // Minta detail alamat
             ]);

             if ($response->successful() && $response->json() && !empty($response->json()[0])) {
                  $result = $response->json()[0];
                  // Coba ambil nama desa/kec/kab/prov dari hasil geocoding jika diperlukan
                  // $addressDetails = $result['address'] ?? [];
                  // $village = $addressDetails['village'] ?? $addressDetails['suburb'] ?? null;
                  // $district = $addressDetails['city_district'] ?? $addressDetails['county'] ?? null;
                  // $regency = $addressDetails['city'] ?? $addressDetails['state_district'] ?? null;
                  // $province = $addressDetails['state'] ?? null;
                  // $postcode = $addressDetails['postcode'] ?? null;

                 return [
                     'lat' => (float) $result['lat'],
                     'lng' => (float) $result['lon'],
                     // 'address' => $addressDetails // Kirim detail jika perlu konfirmasi
                 ];
             } else {
                  Log::warning('Geocoding failed or returned empty.', ['address' => $address, 'status' => $response->status(), 'body' => $response->body()]);
             }
         } catch (\Exception $e) {
             Log::error('Geocoding exception: ' . $e->getMessage(), ['address' => $address]);
         }
         return null;
     }

    /**
     * Memperbarui alamat pengguna (yang sedang login).
     * [CATATAN] Pastikan route yang mengarah ke sini (misal: admin.settings.address.update) menggunakan method POST/PUT/PATCH dan ada di web.php
     */
    public function updateAddress(Request $request)
    {
        $validated = $request->validate([
            'province' => 'required|string|max:100',
            'regency' => 'required|string|max:100', // Kabupaten/Kota
            'district' => 'required|string|max:100', // Kecamatan
            'village' => 'required|string|max:100',  // Desa/Kelurahan
            'postal_code' => 'required|string|max:10',
            'address_detail' => 'required|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'kiriminaja_district_id' => 'nullable|integer', // ID Kecamatan KiriminAja
            'kiriminaja_subdistrict_id' => 'nullable|integer', // ID Kelurahan KiriminAja
        ]);

        try {
            $user = Auth::user();
            if (!$user) {
                throw new Exception("User not authenticated.");
            }

            // Update data user
            $user->province = $validated['province'];
            $user->regency = $validated['regency'];
            $user->district = $validated['district'];
            $user->village = $validated['village'];
            $user->postal_code = $validated['postal_code'];
            $user->address_detail = $validated['address_detail'];
            $user->latitude = $validated['latitude'];
            $user->longitude = $validated['longitude'];
            $user->kiriminaja_district_id = $validated['kiriminaja_district_id'];
            $user->kiriminaja_subdistrict_id = $validated['kiriminaja_subdistrict_id'];

            // Jika lat/long kosong, coba geocode sekali lagi sebelum menyimpan
            if (empty($user->latitude) || empty($user->longitude)) {
                 $fullAddress = implode(', ', array_filter([$validated['address_detail'], $validated['village'], $validated['district'], $validated['regency'], $validated['province'], $validated['postal_code']]));
                 $coordinates = $this->geocode($fullAddress);
                 if ($coordinates) {
                     $user->latitude = $coordinates['lat'];
                     $user->longitude = $coordinates['lng'];
                     Log::info("Geocoded address during update for user {$user->id}.");
                 } else {
                     Log::warning("Failed to geocode address during update for user {$user->id}. Saving without coordinates.", ['address' => $fullAddress]);
                 }
            }


            $user->save();

            return back()->with('success', 'Alamat berhasil diperbarui.');

        } catch (ValidationException $e) {
             return back()->withErrors($e->errors())->withInput(); // Kembali dengan error validasi
        } catch (Exception $e) {
            Log::error('Failed to update user address: '.$e->getMessage());
            return back()->with('error', 'Gagal memperbarui alamat: '.$e->getMessage());
        }
    }

}

