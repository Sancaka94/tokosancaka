<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http; // Wajib untuk Geocoding
use Illuminate\Support\Facades\Log;  // Wajib untuk Debugging
use Illuminate\View\View;

use App\Services\KiriminAjaService; // <--- WAJIB IMPORT INI

class ProfileController extends Controller
{
    /**
     * Menampilkan halaman edit profil.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * UPDATE 1: INFORMASI DASAR (Nama, Email, HP, Logo)
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        // 1. Fill data standar (Name, Email)
        $request->user()->fill($request->validated());

        // 2. Logic Update No WhatsApp (Phone)
        if ($request->has('phone')) {
            // Sanitasi nomor HP (hapus karakter aneh, ubah 62 jadi 0)
            $phone = preg_replace('/[^0-9]/', '', $request->phone);
            if (substr($phone, 0, 2) === '62') {
                $phone = '0' . substr($phone, 2);
            }
            $request->user()->phone = $phone;
        }

        // 3. Logic Upload Logo / Foto Profil
        if ($request->hasFile('logo')) {
            // Hapus foto lama jika ada (dan bukan foto default sistem jika ada logic itu)
            if ($request->user()->logo && Storage::disk('public')->exists($request->user()->logo)) {
                Storage::disk('public')->delete($request->user()->logo);
            }

            // Simpan foto baru
            $path = $request->file('logo')->store('profile-photos', 'public');
            $request->user()->logo = $path;
        }

        // 4. Reset verifikasi email jika email berubah
        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * UPDATE 2: ALAMAT LENGKAP & KOORDINAT (Integrasi KiriminAja)
     */
    public function updateAddress(Request $request): RedirectResponse
    {
        // 1. Validasi Input
        $validated = $request->validate([
            'address_detail' => 'required|string|max:500',
            'province'       => 'required|string|max:100',
            'regency'        => 'required|string|max:100',
            'district'       => 'required|string|max:100', // Kecamatan
            'village'        => 'required|string|max:100', // Kelurahan
            'postal_code'    => 'required|string|max:10',
            'district_id'    => 'required|integer',        // ID Kecamatan KiriminAja
            'subdistrict_id' => 'nullable|integer',        // ID Kelurahan KiriminAja
            'latitude'       => 'nullable|numeric',
            'longitude'      => 'nullable|numeric',
        ]);

        $user = $request->user();

        // 2. Logika Koordinat (Fallback Geocoding)
        // Jika frontend (JS) gagal mendapatkan lat/lng, kita cari manual via Nominatim API
        $lat = $request->latitude;
        $lng = $request->longitude;

        if (empty($lat) || empty($lng) || $lat == 0 || $lng == 0) {
            // Gabungkan alamat untuk pencarian
            $queryAddress = implode(', ', [
                $request->village,
                $request->district,
                $request->regency,
                $request->province
            ]);

            try {
                // Panggil API OpenStreetMap (Gratis)
                $response = Http::timeout(5)
                    ->withHeaders(['User-Agent' => 'AplikasiSancaka/1.0'])
                    ->get("https://nominatim.openstreetmap.org/search", [
                        'q' => $queryAddress,
                        'format' => 'json',
                        'limit' => 1,
                        'countrycodes' => 'id'
                    ]);

                if ($response->successful() && !empty($response[0])) {
                    $lat = $response[0]['lat'];
                    $lng = $response[0]['lon'];
                    Log::info("Geocoding Success for User {$user->id}: $lat, $lng");
                }
            } catch (\Exception $e) {
                Log::error("Geocoding Failed for User {$user->id}: " . $e->getMessage());
                // Biarkan null jika gagal total
            }
        }

        // 3. Simpan ke Database
        $user->update([
            'address_detail' => $request->address_detail,
            'province'       => $request->province,
            'regency'        => $request->regency,
            'district'       => $request->district, // Nama Kecamatan
            'village'        => $request->village,  // Nama Kelurahan
            'postal_code'    => $request->postal_code,
            'district_id'    => $request->district_id,    // ID Kecamatan (Penting utk Ongkir)
            'subdistrict_id' => $request->subdistrict_id ?? 0, // ID Kelurahan (Penting utk Ongkir)
            'latitude'       => $lat,
            'longitude'      => $lng,
        ]);

        return Redirect::route('profile.edit')->with('status', 'address-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * [BARU] API Pencarian Alamat untuk Form Profil
     * Dipanggil via AJAX dari view profile.partials.update-profile-information-form
     */
    public function searchAddressApi(Request $request, KiriminAjaService $kirimaja)
    {
        $request->validate([
            'search' => 'required|string|min:3'
        ]);

        try {
            // Memanggil service KiriminAja untuk mencari kelurahan/kecamatan
            $results = $kirimaja->searchAddress($request->input('search'));

            // Mengembalikan response JSON untuk AlpineJS
            return response()->json($results['data'] ?? []);

        } catch (Exception $e) {
            Log::error('Profile Address Search Failed: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal mengambil data alamat.'], 500);
        }
    }
}
