<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ekspedisi;
use Illuminate\Http\Request;

class EkspedisiController extends Controller
{
    /**
     * Menampilkan daftar semua ekspedisi.
     */
    public function index()
    {
        $ekspedisis = Ekspedisi::latest()->paginate(10);
        // Anda perlu membuat view ini: 'resources/views/admin/ekspedisi/index.blade.php'
        return view('admin.ekspedisi.index', compact('ekspedisis'));
    }

    /**
     * Menampilkan form untuk membuat ekspedisi baru.
     */
    public function create()
    {
        // Anda perlu membuat view ini: 'resources/views/admin/ekspedisi/create.blade.php'
        return view('admin.ekspedisi.create');
    }

    /**
     * Menyimpan ekspedisi baru ke database.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255|unique:ekspedisis',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $data = $request->only('nama');

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('logos', 'public');
        }

        Ekspedisi::create($data);

        return redirect()->route('admin.ekspedisi.index')
                         ->with('success', 'Ekspedisi berhasil ditambahkan.');
    }

    /**
     * Menampilkan form untuk mengedit ekspedisi.
     */
    public function edit(Ekspedisi $ekspedisi)
    {
        // Anda perlu membuat view ini: 'resources/views/admin/ekspedisi/edit.blade.php'
        return view('admin.ekspedisi.edit', compact('ekspedisi'));
    }

    /**
     * Memperbarui data ekspedisi di database.
     */
    public function update(Request $request, Ekspedisi $ekspedisi)
    {
        $request->validate([
            'nama' => 'required|string|max:255|unique:ekspedisis,nama,' . $ekspedisi->id,
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $data = $request->only('nama');

        if ($request->hasFile('logo')) {
            // Hapus logo lama jika ada
            // Storage::disk('public')->delete($ekspedisi->logo);
            $data['logo'] = $request->file('logo')->store('logos', 'public');
        }

        $ekspedisi->update($data);

        return redirect()->route('admin.ekspedisi.index')
                         ->with('success', 'Ekspedisi berhasil diperbarui.');
    }

    /**
     * Menghapus ekspedisi dari database.
     */
    public function destroy(Ekspedisi $ekspedisi)
    {
        // Hapus logo dari storage
        // Storage::disk('public')->delete($ekspedisi->logo);
        $ekspedisi->delete();

        return redirect()->route('admin.ekspedisi.index')
                         ->with('success', 'Ekspedisi berhasil dihapus.');
    }
}
