<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\User; // Sesuaikan jika menggunakan App\Models\Pengguna
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class EditPenggunaController extends Controller
{
    /**
     * Middleware check: Pastikan hanya User ID 4 yang bisa mengakses
     */
    private function checkIsAdmin()
    {
        $user = Auth::user();
        $userId = $user->id_pengguna ?? $user->id;

        if ($userId != 4) {
            return false;
        }
        return true;
    }

    /**
     * GET: Mengambil data pengguna berdasarkan ID
     */
    public function show($id)
    {
        if (!$this->checkIsAdmin()) {
            return response()->json(['success' => false, 'message' => 'Akses Ditolak. Hanya Admin Utama yang diizinkan.'], 403);
        }

        // Sesuaikan primary key jika namaya id_pengguna. Gunakan where('id_pengguna', $id)->first() jika perlu
        $targetUser = User::find($id);

        if (!$targetUser) {
            return response()->json(['success' => false, 'message' => 'Pengguna tidak ditemukan.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $targetUser
        ]);
    }

    /**
     * PUT: Memperbarui data pengguna berdasarkan ID
     */
    public function update(Request $request, $id)
    {
        if (!$this->checkIsAdmin()) {
            return response()->json(['success' => false, 'message' => 'Akses Ditolak. Hanya Admin Utama yang diizinkan.'], 403);
        }

        $targetUser = User::find($id);

        if (!$targetUser) {
            return response()->json(['success' => false, 'message' => 'Pengguna tidak ditemukan.'], 404);
        }

        try {
            // --- Aturan Validasi ---
            $request->validate([
                'nama_lengkap' => 'required|string|max:255',
                'email' => [
                    'required',
                    'email',
                    Rule::unique('users', 'email')->ignore($targetUser->id) // Sesuaikan nama tabel 'users' atau 'Pengguna'
                ],
                'no_wa' => 'nullable|string|max:15',
                'role' => 'required|string|in:Admin,Seller,Pelanggan',
                'status' => 'required|string|in:Aktif,Beku,Nonaktif',

                // Opsional: Password & PIN
                'password' => 'nullable|string|min:6',
                'pin' => 'nullable|digits:6',

                // Opsional: Data Toko & Bank
                'store_name' => 'nullable|string|max:255',
                'bank_name' => 'nullable|string|max:255',
                'bank_account_number' => 'nullable|string|max:255',
                'bank_account_name' => 'nullable|string|max:255',
            ]);

            // Exclude password dan pin dari mass update
            $updateData = $request->except(['password', 'pin']);

            // --- Logika Update Password Menjadi HASH ---
            if ($request->filled('password')) {
                // Sesuaikan nama kolom di database, misalnya 'password' atau 'password_hash'
                $updateData['password'] = Hash::make($request->password);
            }

            // --- Logika Update PIN Menjadi HASH ---
            if ($request->filled('pin')) {
                $updateData['pin'] = Hash::make($request->pin);
            }

            $targetUser->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Data pengguna berhasil diperbarui.'
            ]);

        } catch (\Exception $e) {
            Log::error('API Edit Pengguna Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui data: ' . $e->getMessage()
            ], 500);
        }
    }
}
