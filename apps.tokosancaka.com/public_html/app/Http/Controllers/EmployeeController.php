<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class EmployeeController extends Controller
{
    // 1. LIST PEGAWAI (READ)
    public function index()
    {
        $user = Auth::user();

        // LOGIKA PINTAR:
        if ($user->role === 'super_admin') {
            // A. JIKA SUPER ADMIN:
            // Ambil SEMUA user dari seluruh dunia, urutkan berdasarkan Toko (Tenant)
            // Kita load relasi 'tenant' agar nama toko muncul tanpa berat di query
            $employees = User::with('tenant')
                             ->where('id', '!=', $user->id) // Sembunyikan akun sendiri
                             ->orderBy('tenant_id', 'asc')  // Kelompokkan per toko
                             ->orderBy('role', 'asc')       // Urutkan role (Admin dulu, baru staff)
                             ->get();
        } else {
            // B. JIKA ADMIN BIASA:
            // KUNCI HANYA user milik tenant ini (Anti-Ketuker)
            $employees = User::where('tenant_id', $user->tenant_id)
                             ->where('id', '!=', $user->id)
                             ->latest()
                             ->get();
        }

        return view('employees.index', compact('employees'));
    }

    // 2. FORM TAMBAH (CREATE)
    public function create()
    {
        return view('employees.create');
    }

    // 3. SIMPAN DATA (STORE)
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'in:admin,staff,finance,operator'],
            'permissions' => ['array'],
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'tenant_id' => Auth::user()->tenant_id, // KUNCI KEAMANAN
            'role' => $request->role,
            'permissions' => $request->permissions ?? [],
            'email_verified_at' => now(),
        ]);

        return redirect()->route('employees.index')->with('success', 'Pegawai berhasil ditambahkan!');
    }

    // 4. FORM EDIT (EDIT)
    public function edit($id)
    {
        $currentUser = Auth::user();

        if ($currentUser->role === 'super_admin') {
            // Super Admin: Cari user berdasarkan ID saja (Bebas Edit Siapa Saja)
            $employee = User::findOrFail($id);
        } else {
            // Admin Toko: Hanya boleh edit pegawai tokonya sendiri
            $employee = User::where('id', $id)
                            ->where('tenant_id', $currentUser->tenant_id)
                            ->firstOrFail();
        }

        return view('employees.edit', compact('employee'));
    }

    // 5. UPDATE DATA (UPDATE)
    public function update(Request $request, $id)
    {
        $currentUser = Auth::user();

        // Logika pencarian user yang sama dengan Edit
        if ($currentUser->role === 'super_admin') {
            $employee = User::findOrFail($id);
        } else {
            $employee = User::where('id', $id)
                            ->where('tenant_id', $currentUser->tenant_id)
                            ->firstOrFail();
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            // Validasi email unique kecuali punya diri sendiri
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$employee->id],
            'role' => ['required'], // Validation rules disesuaikan
            'permissions' => ['array'],
            'password' => ['nullable', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'permissions' => $request->permissions ?? [],
        ];

        if ($request->filled('password')) {
            $data['password'] = \Illuminate\Support\Facades\Hash::make($request->password);
        }

        $employee->update($data);

        return redirect()->route('employees.index')->with('success', 'Data pegawai diperbarui!');
    }

    // 6. HAPUS DATA (DESTROY)
    public function destroy($id)
    {
        $currentUser = Auth::user();

        if ($currentUser->role === 'super_admin') {
            $employee = User::findOrFail($id);
        } else {
            $employee = User::where('id', $id)
                            ->where('tenant_id', $currentUser->tenant_id)
                            ->firstOrFail();
        }

        // Cegah menghapus diri sendiri
        if ($employee->id === $currentUser->id) {
            return back()->with('error', 'Tidak bisa menghapus akun sendiri!');
        }

        $employee->delete();

        return redirect()->route('employees.index')->with('success', 'Pegawai telah dihapus.');
    }

}
