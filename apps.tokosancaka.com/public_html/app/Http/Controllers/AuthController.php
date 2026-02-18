<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    public function loginApi(Request $request)
    {
        // 1. Validasi Input
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // 2. Cek Login ke Database (Tabel Users)
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            // Ambil data Toko/Tenant user ini (Jika ada relasi)
            // Asumsi: User punya kolom 'tenant_id' atau relasi ke tabel tenants
            // Sesuaikan dengan struktur database Anda
            $tenantSubdomain = 'pusat'; // Default
            if ($user->tenant) {
                $tenantSubdomain = $user->tenant->subdomain;
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Login Berhasil',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role, // Jika ada role
                    'target_subdomain' => $tenantSubdomain, // Penting untuk Desktop
                ]
            ]);
        }

        // 3. Jika Gagal
        return response()->json([
            'status' => 'error',
            'message' => 'Email atau Password salah!'
        ], 401);
    }
}
