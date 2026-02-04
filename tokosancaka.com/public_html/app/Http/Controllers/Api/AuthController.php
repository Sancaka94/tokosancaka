<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator; // Diimpor untuk validasi yang lebih baik

class AuthController extends Controller
{
    /**
     * Menangani permintaan registrasi dari aplikasi.
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'nama_lengkap' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'Pelanggan',
            'status' => 'Aktif',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Registrasi berhasil!',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ], 201);
    }

    /**
     * Menangani permintaan login dari aplikasi.
     * DIPERBAIKI: Menggunakan Auth::attempt() agar konsisten dengan login web.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Menggunakan Auth::attempt() untuk otentikasi
        if (!Auth::attempt($request->only('email', 'password'))) {
            // Jika otentikasi gagal, kirim response error
            return response()->json([
                'message' => 'Kredensial yang diberikan salah.',
                'errors' => [
                    'email' => ['Kredensial yang diberikan salah.'],
                ]
            ], 422);
        }

        // Jika berhasil, ambil data user
        $user = User::where('email', $request->email)->firstOrFail();

        // Buat token Sanctum
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ]);
    }

    /**
     * Menangani permintaan logout dari aplikasi.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        
        return response()->json(['message' => 'Logout berhasil']);
    }
}
