<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Tenant; // Pastikan Model Tenant di-import
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

        if ($user->role === 'super_admin') {
            // A. SUPER ADMIN: Lihat semua pegawai, urutkan per toko
            $employees = User::with('tenant')
                             ->where('id', '!=', $user->id)
                             ->orderBy('tenant_id', 'asc')
                             ->orderBy('role', 'asc')
                             ->get();
        } else {
            // B. ADMIN TOKO: HANYA lihat pegawai tokonya sendiri
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
        $tenants = [];
        // Jika Super Admin, kirim daftar toko agar dia bisa mendaftarkan pegawai untuk klien
        if (Auth::user()->role === 'super_admin') {
            $tenants = Tenant::orderBy('name', 'asc')->get();
        }

        return view('employees.create', compact('tenants'));
    }

    // 3. SIMPAN DATA (STORE)
    public function store(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'in:admin,staff,finance,operator,kasir'], // Tambahkan kasir jika ada
            'permissions' => ['array'],
        ]);

        // LOGIKA PINTAR PENENTUAN TENANT:
        $tenantId = $user->tenant_id; // Default: Kunci pakai ID yang login

        // Jika Super Admin dan dia memilih toko di form (dropdown), gunakan ID toko klien tersebut
        if ($user->role === 'super_admin' && $request->filled('tenant_id')) {
            $tenantId = $request->tenant_id;
        }

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'tenant_id' => $tenantId, // KUNCI KEAMANAN (Dinamis untuk Super Admin, Statis untuk Admin)
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
        $tenants = [];

        if ($currentUser->role === 'super_admin') {
            $employee = User::findOrFail($id);
            $tenants = Tenant::orderBy('name', 'asc')->get(); // Kirim data toko untuk form edit
        } else {
            $employee = User::where('id', $id)
                            ->where('tenant_id', $currentUser->tenant_id)
                            ->firstOrFail();
        }

        return view('employees.edit', compact('employee', 'tenants'));
    }

    // 5. UPDATE DATA (UPDATE)
    public function update(Request $request, $id)
    {
        $currentUser = Auth::user();

        if ($currentUser->role === 'super_admin') {
            $employee = User::findOrFail($id);
        } else {
            $employee = User::where('id', $id)
                            ->where('tenant_id', $currentUser->tenant_id)
                            ->firstOrFail();
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$employee->id],
            'role' => ['required'],
            'permissions' => ['array'],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'permissions' => $request->permissions ?? [],
        ];

        // Super admin bisa memindahkan pegawai ke toko lain jika salah input
        if ($currentUser->role === 'super_admin' && $request->filled('tenant_id')) {
            $data['tenant_id'] = $request->tenant_id;
        }

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
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

        if ($employee->id === $currentUser->id) {
            return back()->with('error', 'Tidak bisa menghapus akun sendiri!');
        }

        $employee->delete();

        return redirect()->route('employees.index')->with('success', 'Pegawai telah dihapus.');
    }
}
