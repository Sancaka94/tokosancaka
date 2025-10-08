<?php

namespace App\Http\Controllers;

use App\Models\Pelanggan; // Tambahkan baris ini
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
            $query->where('nama_pelanggan', 'like', '%' . $request->search . '%')
                  ->orWhere('nomor_wa', 'like', '%' . $request->search . '%');
        }

        $pelanggans = $query->latest()->paginate(10);
        return view('admin.pelanggan.index', compact('pelanggans'));
    }

    /**
     * Menyimpan data pelanggan baru.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_pelanggan' => 'required|unique:pelanggans,id_pelanggan',
            'nama_pelanggan' => 'required|string|max:255',
            'nomor_wa' => 'nullable|string|max:20',
            'alamat' => 'required|string',
            'keterangan' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $pelanggan = Pelanggan::create($request->all());
        return response()->json($pelanggan, 201);
    }

    /**
     * Mengambil data pelanggan untuk diedit.
     */
    public function show($id)
    {
        $pelanggan = Pelanggan::findOrFail($id);
        return response()->json($pelanggan);
    }

    /**
     * Memperbarui data pelanggan.
     */
    public function update(Request $request, $id)
    {
        $pelanggan = Pelanggan::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'id_pelanggan' => 'required|unique:pelanggans,id_pelanggan,' . $pelanggan->id,
            'nama_pelanggan' => 'required|string|max:255',
            'nomor_wa' => 'nullable|string|max:20',
            'alamat' => 'required|string',
            'keterangan' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $pelanggan->update($request->all());
        return response()->json($pelanggan);
    }

    /**
     * Menghapus data pelanggan.
     */
    public function destroy($id)
    {
        $pelanggan = Pelanggan::findOrFail($id);
        $pelanggan->delete();
        return response()->json(['success' => 'Data pelanggan berhasil dihapus.']);
    }
}

