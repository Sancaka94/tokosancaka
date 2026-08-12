<?php

namespace App\Http\Controllers;

use App\Models\Proyek;
use Illuminate\Http\Request;

class ProyekController extends Controller
{
    // LOG LOG
    public function index()
    {
        $proyek = Proyek::latest()->get();
        return view('proyek.index', compact('proyek'));
    }

    public function create()
    {
        return view('proyek.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_proyek' => 'required|string|max:255',
            'lokasi_proyek' => 'required|string',
            'nomor_hp' => 'required|string|max:20',
        ]);

        Proyek::create($validated);
        return redirect()->route('proyek.index')->with('success', 'Data Proyek berhasil ditambahkan.');
    }

    public function show(Proyek $proyek)
    {
        // Menampilkan halaman detail RAB khusus untuk proyek ini
        $items = $proyek->rabItems()->orderBy('kategori')->get();
        return view('proyek.show', compact('proyek', 'items'));
    }

    public function edit(Proyek $proyek)
    {
        return view('proyek.edit', compact('proyek'));
    }

    public function update(Request $request, Proyek $proyek)
    {
        $validated = $request->validate([
            'nama_proyek' => 'required|string|max:255',
            'lokasi_proyek' => 'required|string',
            'nomor_hp' => 'required|string|max:20',
        ]);

        $proyek->update($validated);
        return redirect()->route('proyek.index')->with('success', 'Data Proyek berhasil diperbarui.');
    }

    public function destroy(Proyek $proyek)
    {
        $proyek->delete();
        return redirect()->route('proyek.index')->with('success', 'Data Proyek berhasil dihapus.');
    }

    // LOG LOG
    public function simpanCatatan(Request $request, Proyek $proyek)
    {
        $request->validate(['catatan' => 'nullable|string']);
        $proyek->update(['catatan' => $request->catatan]);
        return redirect()->route('proyek.show', $proyek->id)->with('success', 'Catatan proyek berhasil disimpan.');
    }
}