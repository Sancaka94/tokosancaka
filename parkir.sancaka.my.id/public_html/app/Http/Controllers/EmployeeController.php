<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User; // Tambahkan Model User untuk pembuatan akun login
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash; // Tambahkan Hash untuk enkripsi kata sandi

class EmployeeController extends Controller
{
    public function index()
    {
        // Jika kamu menampilkan data dari tabel users, ubah Employee menjadi User
        $employees = User::whereIn('role', ['admin', 'operator'])->latest()->paginate(10);
        return view('employees.index', compact('employees'));
    }

    public function create()
    {
        return view('employees.create');
    }

    public function store(Request $request)
    {
        // 1. Sesuaikan validasi dengan inputan form (name, email, password, role)
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'role'     => 'required|in:operator,admin',
        ], [
            'email.unique' => 'Email ini sudah terdaftar. Silakan gunakan email lain.'
        ]);

        // 2. Simpan sebagai akun login (menggunakan model User)
        User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password), // Wajib dienkripsi agar bisa login
            'role'      => $request->role,
            'tenant_id' => auth()->user()->tenant_id, // Opsional: mengikat pegawai ke cabang yang sama
        ]);

        return redirect()->route('employees.index')->with('success', 'Akun pegawai berhasil ditambahkan.');
    }

    public function edit(User $employee) // Sesuaikan parameter dengan User jika pakai tabel users
    {
        return view('employees.edit', compact('employee'));
    }

    public function update(Request $request, User $employee) // Sesuaikan parameter dengan User
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $employee->id,
            'role'  => 'required|in:operator,admin',
        ]);

        // Siapkan data yang akan diupdate
        $data = [
            'name'  => $request->name,
            'email' => $request->email,
            'role'  => $request->role,
        ];

        // Jika form edit mengirimkan password baru, maka enkripsi dan update
        if ($request->filled('password')) {
            $request->validate(['password' => 'string|min:8']);
            $data['password'] = Hash::make($request->password);
        }

        $employee->update($data);

        return redirect()->route('employees.index')->with('success', 'Data pegawai diperbarui.');
    }

    public function destroy(User $employee) // Sesuaikan parameter dengan User
    {
        // Mencegah akun menghapus dirinya sendiri
        if (auth()->id() === $employee->id) {
            return redirect()->route('employees.index')->with('error', 'Kamu tidak bisa menghapus akunmu sendiri.');
        }

        $employee->delete();
        return redirect()->route('employees.index')->with('success', 'Pegawai dihapus.');
    }
}
