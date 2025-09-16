<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Menangani permintaan registrasi dari aplikasi Android.
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users', // 'users' adalah nama tabel pengguna Anda
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'nama_lengkap' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'Pelanggan', // Atur role default untuk pengguna dari aplikasi
            'status' => 'Aktif',   // Atur status default
        ]);

        // Membuat token otentikasi setelah registrasi berhasil
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Registrasi berhasil!',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ], 201);
    }

    /**
     * Menangani permintaan login dari aplikasi Android.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Kredensial yang diberikan salah.'],
            ]);
        }

        // Membuat token keamanan menggunakan Sanctum
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ]);
    }

    /**
     * Menangani permintaan logout dari aplikasi Android.
     */
    public function logout(Request $request)
    {
        // Menghapus token yang sedang digunakan untuk logout
        $request->user()->currentAccessToken()->delete();
        
        return response()->json(['message' => 'Logout berhasil']);
    }
}
