<?php

namespace App\Http\Controllers\Pondok\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User; // Gunakan Model User bawaan Laravel
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class PenggunaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Ambil data dari tabel 'users'
        $pengguna = User::paginate(15);
        return view('pondok.admin.pengguna.index', compact('pengguna'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('pondok.admin.pengguna.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users', // Cek unik ke tabel users
            'password' => 'required|confirmed|min:8',
            'role' => 'required|string',
        ]);

        // Simpan ke tabel 'users'
        User::create([
            'name' => $request->nama_lengkap, // Mapping: input nama_lengkap -> kolom name
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            // 'username' => $request->username, // HAPUS baris ini jika di database tidak ada kolom username
        ]);

        return redirect()->route('admin.pengguna.index')->with('success', 'Pengguna berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $pengguna = User::findOrFail($id);
        return view('pondok.admin.pengguna.show', compact('pengguna'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $pengguna = User::findOrFail($id);
        return view('pondok.admin.pengguna.edit', compact('pengguna'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'role' => 'required|string',
        ]);

        // Update data
        $user->name = $request->nama_lengkap;
        $user->email = $request->email;
        $user->role = $request->role;

        // Cek jika password diisi baru
        if ($request->filled('password')) {
            $request->validate([
                'password' => 'confirmed|min:8',
            ]);
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return redirect()->route('admin.pengguna.index')->with('success', 'Data pengguna berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return redirect()->route('admin.pengguna.index')->with('success', 'Pengguna berhasil dihapus.');
    }
}