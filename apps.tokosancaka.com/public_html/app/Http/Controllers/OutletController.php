<?php

namespace App\Http\Controllers;

use App\Models\Outlet;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OutletController extends Controller
{
    /**
     * Menampilkan Daftar Cabang / Outlet
     */
    public function index()
    {
        $user = Auth::user();

        if ($user->role === 'super_admin') {
            // Super Admin melihat SEMUA cabang dari SEMUA toko
            $outlets = Outlet::with('tenant')
                             ->orderBy('tenant_id', 'asc')
                             ->orderBy('name', 'asc')
                             ->get();
        } else {
            // Admin Toko HANYA melihat cabang dari tokonya sendiri
            $outlets = Outlet::where('tenant_id', $user->tenant_id)
                             ->orderBy('name', 'asc')
                             ->get();
        }

        return view('outlets.index', compact('outlets'));
    }

    /**
     * Form Tambah Cabang Baru
     */
    public function create()
    {
        $user = Auth::user();
        $tenants = [];

        // Jika Super Admin, berikan daftar toko agar dia bisa pilih cabang ini untuk klien mana
        if ($user->role === 'super_admin') {
            $tenants = Tenant::orderBy('name', 'asc')->get();
        }

        return view('outlets.create', compact('tenants'));
    }

    /**
     * Menyimpan Cabang Baru
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $rules = [
            'name'    => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'phone'   => ['nullable', 'string', 'max:20'],
        ];

        // Jika Super Admin, maka kolom tenant_id wajib dipilih dari form
        if ($user->role === 'super_admin') {
            $rules['tenant_id'] = ['required', 'exists:tenants,id'];
        }

        $request->validate($rules);

        // Penentuan Pemilik Cabang (Tenant)
        $tenantId = $user->tenant_id; // Default: Milik Admin yang sedang login

        if ($user->role === 'super_admin' && $request->filled('tenant_id')) {
            $tenantId = $request->tenant_id; // Timpa dengan pilihan Super Admin
        }

        Outlet::create([
            'tenant_id' => $tenantId,
            'name'      => $request->name,
            'address'   => $request->address,
            'phone'     => $request->phone,
        ]);

        return redirect()->route('outlets.index')->with('success', 'Cabang / Outlet berhasil ditambahkan!');
    }

    /**
     * Form Edit Cabang
     */
    public function edit($id)
    {
        $user = Auth::user();
        $tenants = [];

        if ($user->role === 'super_admin') {
            $outlet = Outlet::findOrFail($id);
            $tenants = Tenant::orderBy('name', 'asc')->get();
        } else {
            // Kunci keamanan: Pastikan hanya bisa buka edit cabangnya sendiri
            $outlet = Outlet::where('id', $id)
                            ->where('tenant_id', $user->tenant_id)
                            ->firstOrFail();
        }

        return view('outlets.edit', compact('outlet', 'tenants'));
    }

    /**
     * Menyimpan Perubahan Cabang
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();

        if ($user->role === 'super_admin') {
            $outlet = Outlet::findOrFail($id);
        } else {
            $outlet = Outlet::where('id', $id)
                            ->where('tenant_id', $user->tenant_id)
                            ->firstOrFail();
        }

        $rules = [
            'name'    => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'phone'   => ['nullable', 'string', 'max:20'],
        ];

        if ($user->role === 'super_admin') {
            $rules['tenant_id'] = ['required', 'exists:tenants,id'];
        }

        $request->validate($rules);

        $data = [
            'name'    => $request->name,
            'address' => $request->address,
            'phone'   => $request->phone,
        ];

        // Super Admin bisa memindahkan kepemilikan cabang ke toko lain
        if ($user->role === 'super_admin' && $request->filled('tenant_id')) {
            $data['tenant_id'] = $request->tenant_id;
        }

        $outlet->update($data);

        return redirect()->route('outlets.index')->with('success', 'Data Cabang / Outlet berhasil diperbarui!');
    }

    /**
     * Menghapus Cabang
     */
    public function destroy($id)
    {
        $user = Auth::user();

        if ($user->role === 'super_admin') {
            $outlet = Outlet::findOrFail($id);
        } else {
            $outlet = Outlet::where('id', $id)
                            ->where('tenant_id', $user->tenant_id)
                            ->firstOrFail();
        }

        $outlet->delete();

        return redirect()->route('outlets.index')->with('success', 'Cabang / Outlet berhasil dihapus.');
    }
}
