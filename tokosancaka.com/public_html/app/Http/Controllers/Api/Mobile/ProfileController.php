<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator; // <-- Tambahkan ini

class ProfileController extends Controller
{
    /**
     * Mengambil data profil user saat ini
     */
    public function show(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $request->user()
        ]);
    }

    /**
     * Memperbarui profil via Mobile API
     */
    public function update(Request $request)
    {
        $user = $request->user();

        // 1. Menggunakan Validator::make agar bisa mengembalikan response JSON manual jika gagal
        $validator = Validator::make($request->all(), [
            'nama_lengkap'          => ['required', 'string', 'max:255'],
            // Menggunakan ignore berdasarkan id user yang sedang login
            'no_wa'                 => ['required', 'string', 'max:20', Rule::unique('Pengguna', 'no_wa')->ignore($user->id_pengguna, 'id_pengguna')],
            'store_name'            => ['nullable', 'string', 'max:255'],
            'store_logo'            => ['nullable', 'image', 'max:4096'], // Diperbesar menjadi 4MB untuk berjaga-jaga dari kamera HP
            'bank_name'             => ['nullable', 'string', 'max:255'],
            'bank_account_name'     => ['nullable', 'string', 'max:255'],
            'bank_account_number'   => ['nullable', 'string', 'max:255'],
            'province'              => ['required', 'string', 'max:255'],
            'regency'               => ['required', 'string', 'max:255'],
            'district'              => ['required', 'string', 'max:255'],
            'village'               => ['required', 'string', 'max:255'], // Ini yang sebelumnya bikin error karena tidak dikirim
            'postal_code'           => ['nullable', 'string', 'max:10'],
            'address_detail'        => ['required', 'string'],
            // Menjadikan latitude dan longitude nullable (atau beri default) karena kadang GPS gagal
            'latitude'              => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'             => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid. Cek kembali form Anda.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $validated = $validator->validated();

            // Proses Upload Gambar Logo Toko
            if ($request->hasFile('store_logo')) {
                if ($user->store_logo_path) {
                    Storage::disk('public')->delete($user->store_logo_path);
                }
                $path = $request->file('store_logo')->store('uploads/store-logos', 'public');
                $user->store_logo_path = $path;

                // Hapus key store_logo dari $validated agar tidak ikut di $user->fill()
                unset($validated['store_logo']);
            }

            $user->fill($validated);
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Profil berhasil diperbarui!',
                'data' => $user
            ]);

        } catch (\Exception $e) {
            Log::error('API Profile Update Error: '.$e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
            ], 500);
        }
    }
}
