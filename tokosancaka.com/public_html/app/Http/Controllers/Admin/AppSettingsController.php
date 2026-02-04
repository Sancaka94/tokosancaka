<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;
use App\Models\User; // Model 'Pengguna' Anda
use App\Models\BannerEtalase; // Asumsi dari kode baru
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;  // Untuk Geocoding
use App\Services\KiriminAjaService; // Asumsi ini ada di app/Services/
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Models\Slide;
use Exception;


class AppSettingsController extends Controller
{

    /*
    |--------------------------------------------------------------------------
    | FUNGSI PENGATURAN UTAMA & PROFIL
    |--------------------------------------------------------------------------
    */

    /**
     * Menampilkan halaman pengaturan utama.
     * [DIGABUNG] Mengambil data untuk tabel pengguna DAN pengaturan baru.
     */
    public function index()
    {
        try {
            $admin = Auth::user();
            
            if (!$admin) {
                return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
            }

            // === Data untuk Tab Profil/Pengguna (dari AppSettingsController) ===

            // Data Slider Informasi (slider lama)
            $sliderData = Setting::where('key', 'slider_informasi')->first();
            $slides = $sliderData ? json_decode($sliderData->value, true) : [['img' => '', 'title' => 'Slide 1', 'desc' => '']];

            // Data Pengaturan Umum (auto_freeze)
            $freezeSetting = Setting::where('key', 'auto_freeze')->first();
            $autoFreeze = $freezeSetting ? $freezeSetting->value : false;

            // Data untuk Tabel Manajemen Pengguna
            $semuaPengguna = User::all();
            $roles = $semuaPengguna->pluck('role')->filter()->unique()->values()->all();
            $statuses = $semuaPengguna->pluck('status')->filter()->unique()->values()->all();
            
            $penggunaListArray = $semuaPengguna->toArray();
            //$penggunaListJson = json_encode($penggunaListArray, JSON_INVALID_UTF8_SUBSTITUTE);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('JSON encode error di AppSettingsController: ' . json_last_error_msg());
                $penggunaListJson = '[]';
                session()->flash('error', 'Gagal memuat data pengguna karena ada masalah encoding karakter.');
            }
            
            // === Data dari SettingController (BARU) ===
            $banners = BannerEtalase::orderBy('id', 'desc')->get();
            $settings = Setting::whereIn('key', ['logo','banner_2','banner_3'])
                                 ->pluck('value','key')
                                 ->all();
                                 
           
           


            // Kirim SEMUA data ke view 'admin.settings'
            return view('admin.settings', [
                'admin' => $admin,
                'slides' => $slides,
                'autoFreeze' => (bool)$autoFreeze,
                'penggunaList' => $penggunaListArray, // <-- [FIX] Kirim array PHP-nya langsung
                'roles' => $roles,
                'statuses' => $statuses,
                'banners' => $banners,   // Data baru
                'settings' => $settings, // Data baru
                'user' => $admin, // Alias untuk 'admin', jika view baru membutuhkannya
            ]);

        } catch (Exception $e) {
            Log::error('Gagal memuat halaman pengaturan: '.$e->getMessage(). ' di Baris: ' . $e->getLine());
            return back()->with('error', 'Gagal memuat halaman pengaturan: ' . $e->getMessage());
        }
    }

    /**
     * Memperbarui profil admin (Nama, Email, HP, Foto).
     */
    public function updateProfile(Request $request)
    {
        $admin = Auth::user();
        
        $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'store_name' => 'nullable|string|max:255', // [PERBAIKAN] Tambahkan validasi
            'email' => ['required', 'email', 'max:255', Rule::unique('Pengguna', 'email')->ignore($admin->id_pengguna, 'id_pengguna')],
            'no_hp' => ['nullable', 'string', 'max:20', Rule::unique('Pengguna', 'no_wa')->ignore($admin->id_pengguna, 'id_pengguna')],
            'photo_profile' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            
            // [PERBAIKAN] Tambahkan validasi bank
'bank_name' => 'nullable|string|max:100',
'bank_account_name' => 'nullable|string|max:255',
'bank_account_number' => 'nullable|string|max:50',
        ]);

        $updateData = [
            'nama_lengkap' => $request->nama_lengkap,
            'store_name' => $request->store_name, // [PERBAIKAN] Tambahkan ke data update
            'email' => $request->email,
            'no_wa' => $request->no_hp,
            // [PERBAIKAN] Tambahkan data bank ke array update
'bank_name' => $request->bank_name,
'bank_account_name' => $request->bank_account_name,
'bank_account_number' => $request->bank_account_number,

        ];
        
        if ($request->hasFile('photo_profile')) {
            $db_photo_column = 'store_logo_path'; 

            // Hapus file lama dari disk 'public'
            if ($admin->$db_photo_column && Storage::disk('public')->exists($admin->$db_photo_column)) {
                Storage::disk('public')->delete($admin->$db_photo_column);
            }
            
            // Simpan file baru ke disk 'public'
            $path = $request->file('photo_profile')->store('profile-photos', 'public');
            
            $updateData[$db_photo_column] = $path;
        }

        $admin->update($updateData);

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    /**
     * Memperbarui password admin.
     */
    public function updatePassword(Request $request)
    {
        $admin = Auth::user();

        $validated = $request->validate([
            'current_password' => ['required', 'string', function ($attribute, $value, $fail) use ($admin) {
                if (!Hash::check($value, $admin->password_hash)) {
                    $fail('Password saat ini salah.');
                }
            }],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        try {
            $admin->password = $validated['password']; 
            $admin->save();
            return back()->with('success', 'Password berhasil diubah.');
        } catch (Exception $e) {
            Log::error('Gagal memperbarui password: '.$e->getMessage());
            return back()->with('error', 'Gagal memperbarui password: '.$e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | FUNGSI PENGATURAN ALAMAT (KIRIMINAJA)
    |--------------------------------------------------------------------------
    */

  /**
     * Memperbarui alamat pengguna (yang sedang login).
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
            
            // Kita biarkan validasi ini, tidak masalah. 
            // Form tetap mengirimnya, jadi lebih baik divalidasi (sebagai nullable)
            // agar tidak ada error validasi, meskipun datanya tidak kita simpan.
            'kiriminaja_district_id' => 'nullable|integer', 
            'kiriminaja_subdistrict_id' => 'nullable|integer', 
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
            
            // [PERBAIKAN]
            // Baris-baris ini diberi komentar (dinonaktifkan) 
            // karena kolomnya tidak ada di tabel 'Pengguna' Anda.
            // $user->kiriminaja_district_id = $validated['kiriminaja_district_id'] ?? $user->kiriminaja_district_id;
            // $user->kiriminaja_subdistrict_id = $validated['kiriminaja_subdistrict_id'] ?? $user->kiriminaja_subdistrict_id;

            // Jika lat/long kosong, coba geocode sekali lagi sebelum menyimpan
            if (empty($user->latitude) || empty($user->longitude)) {
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
            $user->refresh();


            return back()->with('success', 'Alamat berhasil diperbarui.');

        } catch (ValidationException $e) {
             return back()->withErrors($e->errors())->withInput(); // Kembali dengan error validasi
        } catch (Exception $e) {
            Log::error('Failed to update user address: '.$e->getMessage());
            return back()->with('error', 'Gagal memperbarui alamat: '.$e->getMessage());
        }
    }

   /**
     * Mencari alamat menggunakan KiriminAja API.
     * [FIX] Memperbaiki parsing 'full_address' agar sesuai data API
     */
    public function searchAddressKiriminAja(Request $request, KiriminAjaService $kiriminAja)
    {
        $request->validate(['query' => 'required|string|min:3']);
        $query = $request->input('query');

        try {
            $result = $kiriminAja->searchAddress($query);
            // JANGAN LUPA HAPUS dd($result); DARI SINI

            if ($result['status'] && !empty($result['data'])) {
                
                // [FIX] Ini adalah logika parsing yang BENAR
                $simplifiedResults = collect($result['data'])->map(function ($addr) {
                    
                    // Pecah string 'full_address'
                    // Contoh: "Beran, Ngawi, Ngawi, Jawa Timur, 63216"
                    $parts = explode(', ', $addr['full_address'] ?? '');
                    
                    $village = null;
                    $district = null; // Ini akan jadi 'subdistrict' di form
                    $city = null;
                    $province = null;
                    $zip_code = null;

                    // Cek jumlah bagian untuk menentukan format alamat
                    if (count($parts) == 5) {
                        // Format: [Village], [District], [City], [Province], [ZIP]
                        $village = $parts[0];
                        $district = $parts[1];
                        $city = $parts[2];
                        $province = $parts[3];
                        $zip_code = $parts[4];
                    } elseif (count($parts) == 4) {
                        // Format: [District], [City], [Province], [ZIP] (Tanpa village)
                        $district = $parts[0];
                        $city = $parts[1];
                        $province = $parts[2];
                        $zip_code = $parts[3];
                    }

                    return [
                        // 'text' adalah apa yang dilihat pengguna di dropdown
                        'text' => $addr['full_address'], // Ini akan menampilkan alamat lengkap
                        
                        // Ini adalah data yang akan diisi ke form
                        'village' => $village,
                        'subdistrict' => $district, // JS Anda mengharapkan 'subdistrict'
                        'city' => $city,
                        'province' => $province,
                        'zip_code' => $zip_code,

                        // Ini adalah ID yang disimpan di hidden input
                        'district_id' => $addr['district_id'] ?? null,
                        'subdistrict_id' => $addr['subdistrict_id'] ?? null,
                    ];
                })->take(10); // Ambil 10 hasil saja

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
 * Melakukan geocoding string alamat menjadi koordinat Lat/Lng menggunakan Nominatim.
 *
 * @param string $address Alamat lengkap yang akan di-geocode.
 * @return array|null Mengembalikan array ['lat' => float, 'lng' => float] jika sukses, atau null jika gagal.
 */
private function geocode($address)
{
    $url = "https://nominatim.openstreetmap.org/search";
    
    try {
        // 1. Melakukan panggilan API ke Nominatim
        // Menyertakan User-Agent adalah WAJIB untuk kebijakan Nominatim.
        $response = Http::timeout(10)->withHeaders([
            'User-Agent' => 'Tokosancaka/1.0 (admin@tokosancaka.com)', // Ganti dengan email/domain Anda
            'Accept'     => 'application/json',
        ])->get($url, [
            'q'              => $address,
            'format'         => 'json',
            'limit'          => 1,      // Hanya ambil 1 hasil terbaik
            'countrycodes'   => 'id',   // Batasi pencarian hanya di Indonesia
            'addressdetails' => 1
        ]);
        
        // 2. Mencatat (Log) alamat yang sedang dicoba (Baik untuk debugging)
        // Ini adalah perbaikan dari error sebelumnya (menggunakan $address, bukan $request)
        Log::info('Mencoba geocode alamat:', [
            'alamat_dikirim' => $address
        ]);

        // 3. Memeriksa apakah respons sukses DAN memiliki data hasil
        if ($response->successful() && $response->json() && !empty($response->json()[0])) {
            
            // Ambil hasil pertama
            $result = $response->json()[0];
            
            // Kembalikan koordinat yang ditemukan
            return [
                'lat' => (float) $result['lat'],
                'lng' => (float) $result['lon'],
            ];
        } else {
            // 4. Jika API merespons (sukses) TAPI tidak menemukan alamat (body: "[]")
            Log::warning('Geocoding failed or returned empty (Alamat tidak ditemukan).', [
                'address' => $address, 
                'status'  => $response->status(), 
                'body'    => $response->body()
            ]);
        }

    } catch (\Illuminate\Http\Client\ConnectionException $e) {
        // 5. Menangani error koneksi (misal: timeout, DNS gagal, server down)
        Log::error('Geocoding connection failed: ' . $e->getMessage(), ['address' => $address]);
    
    } catch (\Exception $e) {
        // 6. Menangani error umum lainnya (misal: kesalahan logika di dalam 'try')
        Log::error('Geocoding general exception: ' . $e->getMessage(), [
            'address'   => $address, 
            'file'      => $e->getFile(),
            'line'      => $e->getLine(),
        ]);
    }
    
    // 7. Jika ada error (catch) atau alamat tidak ditemukan (else), kembalikan null
    return null;
}

/**
 * Mendapatkan koordinat (latitude, longitude) dari alamat.
 * [UPDATED] Ditambahkan logika fallback jika alamat lengkap gagal.
 */
public function geocodeAddress(Request $request)
{
    $request->validate(['address' => 'required|string|min:10']);
    $fullAddress = $request->input('address');

    // Percobaan 1: alamat lengkap
    $coordinates = $this->geocode($fullAddress);

    // Percobaan 2: alamat disederhanakan jika gagal
    if (!$coordinates) {
        Log::info("Geocode (Attempt 1) gagal. Mencoba Attempt 2 (Simplified).");

        // Pecah string hanya pada koma pertama
        $parts = explode(',', $fullAddress, 2);

        // Jika $parts[1] ada (artinya ada koma), gunakan sisanya
        if (isset($parts[1])) {
            $simplifiedAddress = trim($parts[1]); // contoh: "Ketanggi, Ngawi, Ngawi, Jawa Timur, 63211"
            $coordinates = $this->geocode($simplifiedAddress);
        }
    }

    // Hasil akhir
    if ($coordinates) {
        return response()->json([
            'success' => true,
            'data' => $coordinates
        ]);
    } else {
        Log::warning("Geocode gagal total", ['address' => $fullAddress]);
        return response()->json([
            'success' => false,
            'message' => 'Gagal mendapatkan koordinat. Alamat mungkin tidak ditemukan.'
        ], 404);
    }
}



    /*
    |--------------------------------------------------------------------------
    | FUNGSI PENGATURAN GAMBAR & BANNER
    |--------------------------------------------------------------------------
    */

    /**
     * Memperbarui pengaturan umum aplikasi (Auto-Freeze).
     */
    public function updateGeneral(Request $request)
    {
        try {
            Setting::updateOrCreate(
                ['key' => 'auto_freeze'],
                ['value' => $request->has('auto_freeze')]
            );
            return back()->with('success', 'Pengaturan umum berhasil disimpan.');
        } catch (Exception $e) {
            Log::error('Gagal memperbarui pengaturan umum: '.$e->getMessage());
            return back()->with('error', 'Gagal memperbarui pengaturan umum: '.$e->getMessage());
        }
    }

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
                    $path = $file->store('settings', 'public'); // Menggunakan disk 'public'

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

    /**
     * Menyimpan banner etalase baru.
     */
    public function storeBanner(Request $request)
    {
        try {
            $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            ]);
            $path = $request->file('image')->store('banners', 'public'); // Menggunakan disk 'public'
            BannerEtalase::create(['image' => $path]);
            Log::info("Stored new banner image: " . $path);
            return back()->with('success', 'Banner berhasil ditambahkan');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
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
                $path = $request->file('image')->store('banners', 'public'); // Menggunakan disk 'public'
                $banner->update(['image' => $path]);
                 Log::info("Updated banner ID {$banner->id} with image: " . $path);
            }
            return back()->with('success', 'Banner berhasil diperbarui');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput()->with('edit_banner_id', $banner->id);
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
    
    /**
     * Memperbarui pengaturan slider informasi (Slider Lama/DEPRECATED).
     * Saya membiarkannya di sini jika Anda masih menggunakannya.
     */
    public function updateSlider(Request $request)
    {
         $validated = $request->validate([
            'slides' => 'present|array',
            'slides.*.img' => 'nullable|url|max:2048',
            'slides.*.title' => 'nullable|string|max:255',
            'slides.*.desc' => 'nullable|string|max:500',
        ]);
        try {
            $slidesData = array_filter($validated['slides'], function($slide) {
                return !empty($slide['img']) || !empty($slide['title']) || !empty($slide['desc']);
            });
            Setting::updateOrCreate(
                ['key' => 'slider_informasi'],
                ['value' => json_encode(array_values($slidesData))]
            );
            return back()->with('success', 'Pengaturan slider berhasil disimpan.');
        } catch (Exception $e) {
            Log::error('Gagal memperbarui slider: '.$e->getMessage());
            return back()->with('error', 'Gagal memperbarui slider: '.$e->getMessage());
        }
    }
}