<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class SettingPrivacyController extends Controller
{
    /**
     * Memperbarui Alamat Email Pengguna
     */
    public function updateEmail(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            // Memastikan email unik, tetapi mengabaikan email milik user ini sendiri
            'email' => 'required|email|unique:Pengguna,email,' . $user->id_pengguna . ',id_pengguna',
        ], [
            'email.required' => 'Email tidak boleh kosong.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan oleh pengguna lain.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 400);
        }

        // Update dan Simpan
        $user->email = $request->email;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Alamat email berhasil diperbarui.',
            'data' => ['email' => $user->email]
        ]);
    }

    /**
     * Memperbarui Password Pengguna
     */
    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'old_password' => 'required',
            'new_password' => 'required|min:6',
        ], [
            'old_password.required' => 'Password lama wajib diisi.',
            'new_password.required' => 'Password baru wajib diisi.',
            'new_password.min' => 'Password baru minimal harus 6 karakter.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 400);
        }

        // Cek apakah password lama yang dimasukkan sesuai dengan di database (kolom password_hash)
        if (!Hash::check($request->old_password, $user->password_hash)) {
            return response()->json([
                'success' => false,
                'message' => 'Password lama yang Anda masukkan salah.'
            ], 400);
        }

        // Karena model User Anda menggunakan mutator setPasswordAttribute,
        // kita cukup assign ke 'password' dan otomatis akan di-hash ke 'password_hash'
        $user->password = $request->new_password;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diperbarui.'
        ]);
    }

    /**
     * Memperbarui PIN Transaksi Pengguna
     */
    public function updatePin(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'old_pin' => 'required|digits:6',
            'new_pin' => 'required|digits:6',
        ], [
            'old_pin.required' => 'PIN lama wajib diisi.',
            'old_pin.digits' => 'PIN lama harus berupa 6 digit angka.',
            'new_pin.required' => 'PIN baru wajib diisi.',
            'new_pin.digits' => 'PIN baru harus berupa 6 digit angka.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 400);
        }

        // Jika user sudah pernah mengatur PIN sebelumnya, validasi PIN lamanya
        if (!empty($user->pin)) {
            if (!Hash::check($request->old_pin, $user->pin)) {
                return response()->json([
                    'success' => false,
                    'message' => 'PIN lama yang Anda masukkan salah.'
                ], 400);
            }
        }
        // Note: Jika PIN di DB sebelumnya NULL, sistem akan langsung menimpa dengan PIN baru
        // (mengabaikan inputan old_pin dari frontend) karena ini dianggap pembuatan PIN pertama kali.

        // Enkripsi dan simpan PIN baru
        $user->pin = Hash::make($request->new_pin);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'PIN transaksi berhasil diperbarui.'
        ]);
    }
}
