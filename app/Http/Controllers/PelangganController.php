<?php

namespace App\Http\Controllers;

use App\Models\Pelanggan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PelangganController extends Controller
{
    /**
     * Menampilkan daftar pelanggan dengan pencarian dan paginasi.
     */
    public function index(Request $request)
    {
        $query = Pelanggan::query();

        // Logika Pencarian
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama_pelanggan', 'like', '%' . $search . '%')
                  ->orWhere('nomor_wa', 'like', '%' . $search . '%')
                  ->orWhere('id_pelanggan', 'like', '%' . $search . '%');
            });
        }

        $pelanggans = $query->latest()->paginate(10); // Ambil 10 data per halaman

        return view('admin.pelanggan.index', compact('pelanggans'));
    }

    /**
     * Menyimpan data pelanggan baru.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_pelanggan' => 'required|string|max:50|unique:pelanggans,id_pelanggan',
            'nama_pelanggan' => 'required|string|max:255',
            'nomor_wa' => 'nullable|string|max:20',
            'alamat' => 'required|string',
            'keterangan' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.pelanggan.index')
                        ->withErrors($validator)
                        ->withInput();
        }

        Pelanggan::create($request->all());

        return redirect()->route('admin.pelanggan.index')->with('success', 'Pelanggan baru berhasil ditambahkan.');
    }

    /**
     * Mengambil data satu pelanggan untuk modal edit (JSON).
     */
    public function show($id)
    {
        $pelanggan = Pelanggan::find($id);
        if (!$pelanggan) {
            return response()->json(['error' => 'Pelanggan tidak ditemukan'], 404);
        }
        return response()->json($pelanggan);
    }


    /**
     * Memperbarui data pelanggan.
     */
    public function update(Request $request, $id)
    {
        $pelanggan = Pelanggan::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'id_pelanggan' => 'required|string|max:50|unique:pelanggans,id_pelanggan,' . $pelanggan->id,
            'nama_pelanggan' => 'required|string|max:255',
            'nomor_wa' => 'nullable|string|max:20',
            'alamat' => 'required|string',
            'keterangan' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.pelanggan.index')
                        ->withErrors($validator)
                        ->withInput();
        }

        $pelanggan->update($request->all());

        return redirect()->route('admin.pelanggan.index')->with('success', 'Data pelanggan berhasil diperbarui.');
    }

    /**
     * Menghapus data pelanggan.
     */
    public function destroy($id)
    {
        $pelanggan = Pelanggan::findOrFail($id);
        $pelanggan->delete();

        return redirect()->route('admin.pelanggan.index')->with('success', 'Data pelanggan berhasil dihapus.');
    }
}

