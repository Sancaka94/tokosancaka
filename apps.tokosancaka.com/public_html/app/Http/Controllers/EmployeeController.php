<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Tenant;
use App\Models\Outlet; // Tambahkan import Model Outlet
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
            // Load relasi 'tenant' dan 'outlet' agar query lebih ringan (Eager Loading)
            $employees = User::with(['tenant', 'outlet'])
                             ->where('id', '!=', $user->id)
                             ->orderBy('tenant_id', 'asc')
                             ->orderBy('role', 'asc')
                             ->get();
        } else {
            // B. ADMIN TOKO: HANYA lihat pegawai tokonya sendiri
            $employees = User::with('outlet')
                             ->where('tenant_id', $user->tenant_id)
                             ->where('id', '!=', $user->id)
                             ->latest()
                             ->get();
        }

        return view('employees.index', compact('employees'));
    }

    // 2. FORM TAMBAH (CREATE)
    public function create()
    {
        $user = Auth::user();
        $tenants = [];
        $outlets = [];

        if ($user->role === 'super_admin') {
            $tenants = Tenant::orderBy('name', 'asc')->get();
            // Super admin melihat semua outlet (nanti di Blade di-filter via JavaScript berdasarkan Tenant yg dipilih)
            $outlets = Outlet::orderBy('name', 'asc')->get();
        } else {
            // Admin Toko hanya mengambil outlet miliknya sendiri
            $outlets = Outlet::where('tenant_id', $user->tenant_id)->orderBy('name', 'asc')->get();
        }

        return view('employees.create', compact('tenants', 'outlets'));
    }

    // 3. SIMPAN DATA (STORE)
    public function store(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'in:admin,staff,finance,operator,kasir'],
            'permissions' => ['array'],
            'outlet_id' => ['nullable', 'exists:outlets,id'], // Validasi ID Outlet
        ]);

        // Tentukan ID Tenant
        $tenantId = $user->tenant_id;

        // Jika Super Admin memilih toko di form, gunakan ID toko klien tersebut
        if ($user->role === 'super_admin' && $request->filled('tenant_id')) {
            $tenantId = $request->tenant_id;
        }

        // --- SECURITY CHECK (PENTING) ---
        // Pastikan Outlet yang dipilih BENAR-BENAR milik Tenant tersebut
        if ($request->filled('outlet_id')) {
            $outlet = Outlet::find($request->outlet_id);
            if ($outlet && $outlet->tenant_id != $tenantId) {
                return back()->withErrors(['outlet_id' => 'Cabang/Outlet ini tidak valid atau bukan milik toko yang bersangkutan.'])->withInput();
            }
        }

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'tenant_id' => $tenantId,
            'outlet_id' => $request->outlet_id, // Simpan ID Cabang
            'role' => $request->role,
            'permissions' => $request->permissions ?? [],
            'email_verified_at' => now(),
        ]);

        return redirect()->route('employees.index')->with('success', 'Pegawai berhasil ditambahkan!');
    }

   public function edit($id)
{
    $currentUser = Auth::user();
    $tenants = [];
    $outlets = [];

    // Cek murni apakah ID 41 ada di database, terlepas dari dia tenant mana
    $cekUserMurni = User::find($id);
    if (!$cekUserMurni) {
        // Jika masuk ke sini, artinya ID 41 benar-benar tidak ada di database
        abort(404, 'Data pegawai dengan ID ini sudah tidak ada di database.');
    }

    if ($currentUser->role === 'super_admin') {
        $employee = $cekUserMurni;
        $tenants = Tenant::orderBy('name', 'asc')->get();
        $outlets = Outlet::orderBy('name', 'asc')->get();
    } else {
        // Cek apakah pegawai ini benar-benar milik toko (tenant) yang login
        $employee = User::where('id', $id)
                        ->where('tenant_id', $currentUser->tenant_id)
                        ->first();

        if (!$employee) {
            // Jika masuk ke sini, ID 41 ada, TAPI dia bukan milik toko ini
            dd('Akses Ditolak: Pegawai ID ' . $id . ' ini terdaftar di tenant_id: ' . $cekUserMurni->tenant_id . ', sedangkan tenant_id kamu adalah: ' . $currentUser->tenant_id);
        }

        $outlets = Outlet::where('tenant_id', $currentUser->tenant_id)->orderBy('name', 'asc')->get();
    }

    return view('employees.edit', compact('employee', 'tenants', 'outlets'));
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
            'outlet_id' => ['nullable', 'exists:outlets,id'], // Validasi ID Outlet
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'outlet_id' => $request->outlet_id, // Update Cabang
            'permissions' => $request->permissions ?? [],
        ];

        $tenantId = $employee->tenant_id;

        // Super admin memindahkan pegawai ke toko lain
        if ($currentUser->role === 'super_admin' && $request->filled('tenant_id')) {
            $tenantId = $request->tenant_id;
            $data['tenant_id'] = $tenantId;
        }

        // --- SECURITY CHECK ---
        // Pastikan Outlet baru yang dipilih valid untuk toko/tenant tersebut
        if ($request->filled('outlet_id')) {
            $outlet = Outlet::find($request->outlet_id);
            if ($outlet && $outlet->tenant_id != $tenantId) {
                return back()->withErrors(['outlet_id' => 'Cabang/Outlet ini bukan milik toko tersebut.'])->withInput();
            }
        } else {
            // Jika dikosongkan, berarti dia ditarik ke Pusat (null)
            $data['outlet_id'] = null;
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
