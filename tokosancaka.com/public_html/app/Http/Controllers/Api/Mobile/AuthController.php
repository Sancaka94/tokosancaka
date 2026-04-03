<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    // LOG LOG: Fungsi Login API Mobile
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required', // Email atau No WA
            'password' => 'required',
        ]);

        $user = User::where('email', $request->login)
                    ->orWhere('no_wa', $request->login)
                    ->first();

        if (!$user || !Hash::check($request->password, $user->password_hash)) {
            return response()->json([
                'success' => false,
                'message' => 'Kredensial tidak valid.',
            ], 401);
        }

        if ($user->status === 'Tidak Aktif') {
            return response()->json([
                'success' => false,
                'message' => 'Akun belum aktif.',
            ], 403);
        }

        // Cetak Token untuk HP
        $token = $user->createToken('sancaka-mobile')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login Berhasil',
            'data' => [
                'user' => $user,
                'token' => $token
            ]
        ], 200);
    }

    // LOG LOG: Fungsi Get Profile
    public function user(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $request->user()
        ]);
    }

    // LOG LOG: Fungsi Logout API
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil'
        ]);
    }
}
