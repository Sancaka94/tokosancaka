<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User; // Sesuaikan dengan model Anda
use Illuminate\Support\Str;

class WhitelistController extends Controller
{
    /**
     * Menampilkan halaman whitelist
     */
    public function index()
    {
        // Ambil semua user yang memiliki status whitelist = 1
        $whitelistedUsers = DB::table('Pengguna')->where('is_whitelisted', 1)->get();

        return view('admin.whitelist', compact('whitelistedUsers'));
    }

    /**
     * Membuat akun dummy baru dan langsung di-whitelist
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'login_value'  => 'required|string|max:255',
            'password'     => 'required|string|min:6',
            'role'         => 'required|string|in:pelanggan,admin,agent',
        ]);

        // Cek apakah login_value berupa email atau nomor WA
        $isEmail = str_contains($request->login_value, '@');

        $email = $isEmail ? $request->login_value : null;

        // Bersihkan nomor WA jika bukan email
        $noWa = !$isEmail ? preg_replace('/[^0-9]/', '', $request->login_value) : null;

        // Cek apakah user sudah ada
        $existingUser = DB::table('Pengguna')
            ->where(function ($query) use ($email, $noWa) {
                if ($email) $query->where('email', $email);
                if ($noWa) $query->where('no_wa', $noWa);
            })->first();

        if ($existingUser) {
            return redirect()->back()->with('error', 'Akun dengan Email atau No WA tersebut sudah terdaftar.');
        }

        // Insert ke database (Gunakan DB Facade atau Eloquent sesuai standar Anda)
        DB::table('Pengguna')->insert([
            'nama_lengkap'   => $request->nama_lengkap,
            'email'          => $email,
            'no_wa'          => $noWa,
            'password'       => Hash::make($request->password),
            'role'           => $request->role,
            'status'         => 'Aktif',
            'is_whitelisted' => 1, // Langsung whitelist
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return redirect()->route('admin.whitelist.index')->with('success', 'Akun dummy berhasil dibuat dan di-whitelist.');
    }

    /**
     * Mencabut status whitelist dari pengguna
     */
    public function toggle($id)
    {
        $user = DB::table('Pengguna')->where('id_pengguna', $id)->first();

        if (!$user) {
            return redirect()->back()->with('error', 'Pengguna tidak ditemukan.');
        }

        // Ubah is_whitelisted menjadi 0
        DB::table('Pengguna')->where('id_pengguna', $id)->update([
            'is_whitelisted' => 0,
            'updated_at'     => now(),
        ]);

        return redirect()->route('admin.whitelist.index')->with('success', 'Akses whitelist berhasil dicabut dari ' . $user->nama_lengkap);
    }
}
