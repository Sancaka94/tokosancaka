<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\KiriminAjaService; // Pastikan Service ini di-import
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Exception;

class ProfileController extends Controller
{
    /**
     * [FIX] Menampilkan Halaman Profil (Read Only)
     */
    public function index(Request $request): View
    {
        return view('profile.index', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Menampilkan Form Edit Profil.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update Informasi Dasar (Nama, Email, HP, Foto)
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        // Update No HP (Sanitasi)
        if ($request->has('phone')) {
            $phone = preg_replace('/[^0-9]/', '', $request->phone);
            if (substr($phone, 0, 2) === '62') {
                $phone = '0' . substr($phone, 2);
            }
            $request->user()->phone = $phone;
        }

        // Upload Logo
        if ($request->hasFile('logo')) {
            if ($request->user()->logo && Storage::disk('public')->exists($request->user()->logo)) {
                Storage::disk('public')->delete($request->user()->logo);
            }
            $path = $request->file('logo')->store('profile-photos', 'public');
            $request->user()->logo = $path;
        }

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Update Alamat Lengkap & Koordinat
     */
    public function updateAddress(Request $request): RedirectResponse
    {
        $request->validate([
            'address_detail' => 'required|string|max:500',
            'province'       => 'required|string|max:100',
            'regency'        => 'required|string|max:100',
            'district'       => 'required|string|max:100',
            'village'        => 'required|string|max:100',
            'postal_code'    => 'required|string|max:10',
            'district_id'    => 'required|integer',
            'subdistrict_id' => 'nullable|integer',
            'latitude'       => 'nullable|numeric',
            'longitude'      => 'nullable|numeric',
        ]);

        $user = $request->user();
        $lat = $request->latitude;
        $lng = $request->longitude;

        // Fallback Geocoding jika Lat/Lng kosong
        if (empty($lat) || empty($lng) || $lat == 0 || $lng == 0) {
            $queryAddress = implode(', ', [
                $request->village,
                $request->district,
                $request->regency,
                $request->province
            ]);

            try {
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
                }
            } catch (\Exception $e) {
                Log::error("Geocoding Failed: " . $e->getMessage());
            }
        }

        $user->update([
            'address_detail' => $request->address_detail,
            'province'       => $request->province,
            'regency'        => $request->regency,
            'district'       => $request->district,
            'village'        => $request->village,
            'postal_code'    => $request->postal_code,
            'district_id'    => $request->district_id,
            'subdistrict_id' => $request->subdistrict_id ?? 0,
            'latitude'       => $lat,
            'longitude'      => $lng,
        ]);

        return Redirect::route('profile.edit')->with('status', 'address-updated');
    }

    /**
     * API Pencarian Alamat (Dipanggil AJAX)
     */
    public function searchAddressApi(Request $request, KiriminAjaService $kirimaja)
    {
        $request->validate(['search' => 'required|string|min:3']);

        try {
            $results = $kirimaja->searchAddress($request->input('search'));
            return response()->json($results['data'] ?? []);
        } catch (Exception $e) {
            Log::error('Profile Address Search Failed: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal mengambil data alamat.'], 500);
        }
    }

    /**
     * Hapus Akun
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        if ($user->logo) {
            Storage::disk('public')->delete($user->logo);
        }

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

}
