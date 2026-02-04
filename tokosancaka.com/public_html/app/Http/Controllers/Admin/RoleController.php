<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role; // PASTIKAN BARIS INI ADA
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    /**
     * Menampilkan daftar semua role.
     */
    public function index()
    {
        $roles = Role::orderBy('name')->paginate(10);
        return view('admin.roles.index', compact('roles'));
    }

    /**
     * Menampilkan form untuk membuat role baru.
     */
    public function create()
    {
        return view('admin.roles.form');
    }

    /**
     * Menyimpan role baru ke database.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
        ], [
            'name.required' => 'Nama role tidak boleh kosong.',
            'name.unique' => 'Nama role ini sudah ada.',
        ]);

        Role::create(['name' => $request->name, 'guard_name' => 'web']);

        return redirect()->route('admin.roles.index')
                         ->with('success', 'Role baru berhasil ditambahkan.');
    }

    /**
     * Menampilkan form untuk mengedit role.
     */
    public function edit(Role $role)
    {
        return view('admin.roles.form', compact('role'));
    }

    /**
     * Memperbarui role di database.
     */
    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles')->ignore($role->id),
            ],
        ], [
            'name.required' => 'Nama role tidak boleh kosong.',
            'name.unique' => 'Nama role ini sudah ada.',
        ]);

        // Mencegah role krusial diubah namanya
        if (in_array($role->name, ['Super Admin', 'Admin'])) {
            return redirect()->route('admin.roles.edit', $role)
                             ->with('error', 'Role "' . $role->name . '" tidak dapat diubah.');
        }

        $role->update(['name' => $request->name]);

        return redirect()->route('admin.roles.index')
                         ->with('success', 'Role berhasil diperbarui.');
    }

    /**
     * Menghapus role dari database.
     */
    public function destroy(Role $role)
    {
        // Mencegah role krusial dihapus
        if (in_array($role->name, ['Super Admin', 'Admin'])) {
            return redirect()->route('admin.roles.index')
                             ->with('error', 'Role "' . $role->name . '" tidak dapat dihapus.');
        }

        $role->delete();

        return redirect()->route('admin.roles.index')
                         ->with('success', 'Role berhasil dihapus.');
    }
}
