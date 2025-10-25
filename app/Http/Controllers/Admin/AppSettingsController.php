<?php

namespace App\Http\Controllers\Admin; // Pastikan namespace sesuai

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;
use App\Events\SliderUpdated; // Pastikan event ini ada
use App\Models\User; // <-- Import model User
use App\Services\KiriminAjaService; // <-- Import KiriminAjaService
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log; // Tambahkan Log
use Illuminate\Support\Facades\Http; // <-- Import Http facade
use Illuminate\Validation\ValidationException; // <-- Import ValidationException
use Illuminate\Support\Facades\Route; // <-- Import Route facade

class AppSettingsController extends Controller
{
    /**
     * Menampilkan halaman pengaturan aplikasi.
     */
    public function index()
    {
        // Ganti nama variabel $admin menjadi $user agar konsisten
        $user = Auth::user();
        if (!$user) {
             return redirect()->route('login')->with('error', 'Silakan login.');
        }

        $sliderData = Setting::where('key', 'dashboard_slider')->value('value');
        $slides = [];
        if ($sliderData) {
            try {
                $decodedSlides = json_decode($sliderData, true);
                $slides = is_array($decodedSlides) ? $decodedSlides : [];
            } catch (\Exception $e) {
                Log::error('Invalid JSON format for dashboard_slider setting: ' . $e->getMessage());
            }
        }

        $freezeSetting = Setting::where('key', 'auto_freeze_account')->value('value');
        $autoFreeze = filter_var($freezeSetting, FILTER_VALIDATE_BOOLEAN);

        // Kirim data ke view admin.settings
        // [PERBAIKAN] Pastikan view 'admin.settings' ada atau ganti ke 'admin.settings.index'
        // Saya asumsikan viewnya adalah 'admin.settings.index' sesuai file Blade sebelumnya
        return view('admin.settings.index', [
            // 'admin' => $user, // Tidak perlu lagi jika view menggunakan $user
            'user' => $user,  // Kirim sebagai 'user'
            'slides' => $slides, // Data slider (jika tab slider ada)
            'autoFreeze' => $autoFreeze, // Data auto freeze (jika tab customer ada)
            // Data banner dan settings gambar (jika tab banner ada)
            'banners' => \App\Models\BannerEtalase::orderBy('id', 'desc')->get(),
            'settings' => \App\Models\Setting::whereIn('key', ['logo','banner_2','banner_3'])->pluck('value','key')->all(),
        ]);
    }

    /**
     * Memperbarui profil admin/pengguna.
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user(); // Ganti $admin menjadi $user
        if (!$user) return back()->with('error', 'User tidak ditemukan.');

        $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'email' => [
                'required','email','max:255',
                 Rule::unique('Pengguna', 'email')->ignore($user->id_pengguna, 'id_pengguna')
            ],
            'no_wa' => [ // Gunakan no_wa sesuai form
                'nullable','string','max:20',
                 Rule::unique('Pengguna', 'no_wa')->ignore($user->id_pengguna, 'id_pengguna')
            ],
            'photo_profile' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $updateData = [
            'nama_lengkap' => $request->nama_lengkap,
            'email' => $request->email,
            'no_wa' => $request->no_wa,
        ];

        if ($request->hasFile('photo_profile')) {
            // Tentukan kolom mana yang akan digunakan
            $photoColumn = 'store_logo_path'; // Atau 'photo_profile' jika kolom itu ada di tabel Pengguna

            // Hapus foto lama
            if ($user->$photoColumn && Storage::disk('public')->exists($user->$photoColumn)) {
                Storage::disk('public')->delete($user->$photoColumn);
                Log::info("Deleted old profile photo for user {$user->id_pengguna}: " . $user->$photoColumn);
            }
            // Simpan foto baru
            $path = $request->file('photo_profile')->store('profile-photos', 'public'); // Simpan ke profile-photos
            $updateData[$photoColumn] = $path;
            Log::info("Updated profile photo for user {$user->id_pengguna}: " . $path);
        }

        try {
            $user->update($updateData);
            return back()->with('success', 'Profil berhasil diperbarui.');
        } catch (\Exception $e) {
             Log::error("Error updating profile for user {$user->id_pengguna}: " . $e->getMessage());
             return back()->with('error', 'Gagal memperbarui profil: ' . $e->getMessage());
        }
    }


    /**
     * Memperbarui password admin/pengguna.
     */
    public function updatePassword(Request $request)
    {
         $user = Auth::user(); // Ganti $admin menjadi $user
         if (!$user) return back()->with('error', 'User tidak ditemukan.');

        $request->validate([
            'current_password' => ['required', function ($attribute, $value, $fail) use ($user) {
                // Sesuaikan nama kolom password hash
                if (!Hash::check($value, $user->password_hash)) {
                    $fail('Password saat ini tidak cocok.');
                }
            }],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
             'password_confirmation' => 'required',
        ]);

        try {
             $user->update([
                 'password_hash' => Hash::make($request->password), // Sesuaikan nama kolom password hash
             ]);
            return back()->with('success', 'Password berhasil diubah.');
        } catch (\Exception $e) {
            Log::error("Error updating password for user {$user->id_pengguna}: " . $e->getMessage());
            return back()->with('error', 'Gagal mengubah password: ' . $e->getMessage());
        }
    }

    /**
     * Memperbarui pengaturan slider informasi.
     */
    public function updateSlider(Request $request)
    {
        $validated = $request->validate([
            'slides' => 'present|array',
            'slides.*.img' => 'required|url|max:1024',
            'slides.*.title' => 'nullable|string|max:255',
            'slides.*.desc' => 'nullable|string|max:500',
        ]);

         $validSlides = array_filter($validated['slides'], fn($slide) => !empty($slide['img']) );

        try {
            Setting::updateOrCreate(
                ['key' => 'dashboard_slider'],
                ['value' => json_encode(array_values($validSlides))]
            );
            // event(new SliderUpdated(array_values($validSlides))); // Aktifkan jika perlu broadcast
            return back()->with('success', 'Pengaturan slider berhasil disimpan.');
        } catch (\Exception $e) {
             Log::error("Error updating slider settings: " . $e->getMessage());
             return back()->with('error', 'Gagal menyimpan pengaturan slider: ' . $e->getMessage());
        }
    }

    /**
     * Memperbarui pengaturan umum aplikasi.
     */
    public function updateGeneral(Request $request)
    {
        try {
            Setting::updateOrCreate(
                ['key' => 'auto_freeze_account'],
                ['value' => $request->has('auto_freeze') ? '1' : '0']
            );
            return back()->with('success', 'Pengaturan umum berhasil disimpan.');
        } catch (\Exception $e) {
            Log::error("Error updating general settings: " . $e->getMessage());
            return back()->with('error', 'Gagal menyimpan pengaturan umum: ' . $e->getMessage());
        }
    }

    // --- [BARU] METHOD UNTUK ALAMAT ---

    /**
      * Mencari alamat menggunakan KiriminAja API.
      * [CATATAN] Pastikan route 'admin.settings.address.search' mengarah ke sini.
      */
    public function searchAddressKiriminAja(Request $request, KiriminAjaService $kiriminAja)
    {
        $request->validate(['query' => 'required|string|min:3']);
        $query = $request->input('query');

        try {
            $result = $kiriminAja->searchAddress($query);

            if (isset($result['status']) && $result['status'] && !empty($result['data'])) {
                $simplifiedResults = collect($result['data'])->map(function ($addr) {
                    return [
                        'text' => $addr['label'] ?? implode(', ', array_filter([$addr['village'] ?? null, $addr['subdistrict'] ?? null, $addr['city'] ?? null, $addr['province'] ?? null])),
                        'village' => $addr['village'] ?? null,
                        'subdistrict' => $addr['subdistrict'] ?? null, // Kecamatan KiriminAja
                        'city' => $addr['city'] ?? null,             // Kabupaten/Kota KiriminAja
                        'province' => $addr['province'] ?? null,
                        'zip_code' => $addr['zip_code'] ?? null,
                        'district_id' => $addr['district_id'] ?? null,    // ID Kecamatan KiriminAja
                        'subdistrict_id' => $addr['subdistrict_id'] ?? null,// ID Kelurahan KiriminAja
                    ];
                })->take(10);

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
      * [CATATAN] Pastikan route 'admin.settings.address.geocode' mengarah ke sini.
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
                 'q'      => $address,
                 'format' => 'json',
                 'limit'  => 1,
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

    /**
     * Memperbarui alamat pengguna (yang sedang login).
     * [CATATAN] Pastikan route 'admin.settings.address.update' mengarah ke sini.
     */
    public function updateAddress(Request $request)
    {
        // Validasi input form alamat
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

            // Update data user sesuai nama kolom di tabel Pengguna
            $user->province = $validated['province'];
            $user->regency = $validated['regency'];
            $user->district = $validated['district'];
            $user->village = $validated['village'];
            $user->postal_code = $validated['postal_code'];
            $user->address_detail = $validated['address_detail'];
            $user->latitude = $validated['latitude'];
            $user->longitude = $validated['longitude'];
            $user->kiriminaja_district_id = $validated['kiriminaja_district_id']; // Asumsi nama kolom sama
            $user->kiriminaja_subdistrict_id = $validated['kiriminaja_subdistrict_id']; // Asumsi nama kolom sama

            // Jika lat/long kosong DAN alamat detail diisi, coba geocode
            if ((empty($user->latitude) || empty($user->longitude)) && !empty($validated['address_detail'])) {
                 $fullAddress = implode(', ', array_filter([$validated['address_detail'], $validated['village'], $validated['district'], $validated['regency'], $validated['province'], $validated['postal_code']]));
                 $coordinates = $this->geocode($fullAddress);
                 if ($coordinates) {
                     $user->latitude = $coordinates['lat'];
                     $user->longitude = $coordinates['lng'];
                     Log::info("Geocoded address during update for user {$user->id_pengguna}.");
                 } else {
                     Log::warning("Failed to geocode address during update for user {$user->id_pengguna}. Saving without coordinates.", ['address' => $fullAddress]);
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


} // End of Class

