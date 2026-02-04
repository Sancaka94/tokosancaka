<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\KiriminAjaService; // ðŸ”‘ Ditambahkan untuk service API
use Carbon\Carbon; // Ditambahkan untuk timestamp


class ProfileController extends Controller
{
    /**
     * Menampilkan halaman untuk melihat profil pengguna.
     */
    public function show(Request $request)
    {
        return view('customer.profile.show', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Menampilkan form untuk mengedit profil pengguna.
     */
    public function edit(Request $request) { 
        return view('customer.profile.edit', [ 'user' => $request->user(), ]); 
    }
    
    /**
     * Memperbarui informasi profil pengguna yang sudah login (Regular Update).
     */
    public function update(Request $request)
    {
        $user = $request->user();
        
        try {
            // --- VALIDASI DENGAN EXCEPTION UNTUK 'unique:Pengguna' ---
            $validated = $request->validate([
                'nama_lengkap'          => ['required', 'string', 'max:255'],
                // ðŸ”‘ PERBAIKAN: Menambahkan Rule::unique untuk No. WA yang diperbarui
                'no_wa'                 => ['required', 'string', 'max:20', Rule::unique('Pengguna', 'no_wa')->ignore($user->id_pengguna, 'id_pengguna')],
                'store_name'            => ['nullable', 'string', 'max:255'],
                'store_logo'            => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
                'bank_name'             => ['nullable', 'string', 'max:255'],
                'bank_account_name'     => ['nullable', 'string', 'max:255'],
                'bank_account_number'   => ['nullable', 'string', 'max:255'],
                'province'              => ['required', 'string', 'max:255'],
                'regency'               => ['required', 'string', 'max:255'],
                'district'              => ['required', 'string', 'max:255'],
                'village'               => ['required', 'string', 'max:255'],
                'postal_code'           => ['nullable', 'string', 'max:10'],
                'address_detail'        => ['required', 'string'],
                'latitude'              => ['required', 'numeric', 'between:-90,90'],
                'longitude'             => ['required', 'numeric', 'between:-180,180'],
            ], [
                'latitude.required'     => 'Latitude wajib diisi. Gunakan tombol "Cari Koordinat".',
                'longitude.required'    => 'Longitude wajib diisi. Gunakan tombol "Cari Koordinat".',
                'no_wa.unique'          => 'Nomor WhatsApp sudah digunakan oleh pengguna lain.',
            ]);
        
            if ($request->hasFile('store_logo')) {
                // Hapus logo lama jika ada
                if ($user->store_logo_path) {
                    Storage::disk('public')->delete($user->store_logo_path);
                }
                $path = $request->file('store_logo')->store('uploads/store-logos', 'public');
                $user->store_logo_path = $path;
            }
        
            $user->fill($validated);
            $user->save();
        
            return redirect()
                ->route('customer.profile.show')
                ->with('success', 'Profil Anda berhasil diperbarui.');

        } catch (\Throwable $e) {
            Log::error('Update profile gagal: '.$e->getMessage());
        
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan saat memperbarui profil. Silakan coba lagi.');
        }
    }
    
    /**
     * Menampilkan form setup profil menggunakan token (setelah registrasi).
     * Route: customer/profile/setup/{token}
     */
    public function setup(Request $request, $token)
    {
        $user = User::where('setup_token', $token)->firstOrFail();
    
        // Perbaikan: Cek jika user yang login ID-nya tidak sama dengan user pemilik token
        if (!auth()->check() || $user->id_pengguna !== auth()->id()) {
            Auth::logout();
            // Simpan token di session agar bisa login dan langsung redirect (logic harus ada di LoginController)
            session(['setup_token_pending' => $token]); 
            return redirect()->route('login')->with('info', 'Sesi Anda tidak valid. Silakan login ulang untuk melanjutkan aktivasi akun.');
        }
    
        return view('customer.profile.setup', [
            'user' => $user
        ]);
    }


    /**
     * Memperbarui informasi profil dari form setup token.
     * Route: customer/profile/update-setup/{token} (Asumsi Route Name: customer.profile.update.setup)
     */
    public function updateSetup(Request $request, $token)
    {
        $user = User::where('setup_token', $token)->firstOrFail();

        // ðŸ”‘ PENTING: Validasi kepemilikan sesi sebelum update
        if ($user->id_pengguna !== auth()->id()) {
            return redirect()->route('login')->with('error', 'Sesi tidak valid.');
        }

        // --- VALIDASI SAMA DENGAN UPDATE BIASA ---
        $validated = $request->validate([
            'nama_lengkap'          => ['required', 'string', 'max:255'],
            // Mengabaikan user saat ini (token memastikan kita memilikinya)
            'no_wa'                 => ['required', 'string', 'max:20', Rule::unique('Pengguna', 'no_wa')->ignore($user->id_pengguna, 'id_pengguna')],
            'store_name'            => ['nullable', 'string', 'max:255'],
            'store_logo'            => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'bank_name'             => ['nullable', 'string', 'max:255'],
            'bank_account_name'     => ['nullable', 'string', 'max:255'],
            'bank_account_number'   => ['nullable', 'string', 'max:255'],
            'province'              => ['required', 'string', 'max:255'],
            'regency'               => ['required', 'string', 'max:255'],
            'district'              => ['required', 'string', 'max:255'],
            'village'               => ['required', 'string', 'max:255'],
            'postal_code'           => ['nullable', 'string', 'max:10'],
            'address_detail'        => ['required', 'string'],
            'latitude'              => ['required', 'numeric', 'between:-90,90'],
            'longitude'             => ['required', 'numeric', 'between:-180,180'],
        ], [
            'latitude.required'     => 'Latitude wajib diisi. Gunakan tombol "Cari Koordinat".',
            'longitude.required'    => 'Longitude wajib diisi. Gunakan tombol "Cari Koordinat".',
            'no_wa.unique'          => 'Nomor WhatsApp sudah digunakan oleh pengguna lain.',
        ]);
        
        if ($request->hasFile('store_logo')) {
            if ($user->store_logo_path) {
                Storage::disk('public')->delete($user->store_logo_path);
            }
            $path = $request->file('store_logo')->store('uploads/store-logos', 'public');
            $user->store_logo_path = $path;
        }
        
        // ðŸ”‘ Proses Akhir Setup
        $user->fill($validated);
        $user->profile_setup_at = Carbon::now(); // Tandai waktu setup selesai
        $user->status = 'Aktif'; // Ubah status menjadi Aktif
        $user->setup_token = null; // Hapus token agar tidak bisa digunakan lagi
        
        $user->save();
        
        return redirect()->route('customer.profile.show')
                        ->with('success', 'Aktivasi dan Profil Anda berhasil diselesaikan!');
    }


    public function searchKiriminAjaAddress(Request $request, KiriminAjaService $kiriminAja)
    {
        $query = $request->get('q');
        
        if (empty($query) || strlen($query) < 3) {
            return response()->json([]);
        }

        try {
            $apiResponse = $kiriminAja->searchAddress($query);
            
            if (isset($apiResponse['data']) && !empty($apiResponse['data'])) {
                
                // 🔑 KUNCI PERBAIKAN: Memetakan data dari API
                $processedData = collect($apiResponse['data'])->map(function ($item) {
                    
                    // Memecah string full_address (asumsi format: Kelurahan, Kecamatan, Kota, Provinsi, Kode Pos)
                    $addressParts = array_map('trim', explode(',', $item['full_address'] ?? ''));
                    
                    // Asumsi: Kita hanya perlu nama wilayahnya. Kita akan menggunakan data
                    // dari API untuk mengisi field yang diperlukan JavaScript.
                    
                    // Kita asumsikan urutan di full_address adalah:
                    // 0: Village (Kelurahan)
                    // 1: District (Kecamatan)
                    // 2: Regency/City (Kota)
                    // 3: Province (Provinsi)
                    // 4: Postal Code (Kode Pos)
                    
                    return [
                        // Nilai-nilai ini HARUS persis seperti yang diminta di JavaScript (item.province, item.regency, dst.)
                        'province' => $addressParts[3] ?? 'N/A', 
                        'regency' => $addressParts[2] ?? 'N/A', 
                        'district' => $addressParts[1] ?? 'N/A', 
                        'village' => $addressParts[0] ?? 'N/A', 
                        'postal_code' => $addressParts[4] ?? 'N/A', 
                        // Tambahkan full_address untuk tampilan di hasil pencarian
                        'full_address_display' => $item['full_address'] ?? 'Alamat Tidak Terstruktur', 
                    ];
                });

                return response()->json($processedData);
            }
            
            return response()->json([]);

        } catch (\Exception $e) {
            Log::error("KiriminAja API Error in ProfileController: " . $e->getMessage());
            return response()->json([], 500);
        }
    }
}
