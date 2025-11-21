<?php

namespace App\Http\Controllers;

use App\Models\Pelanggan;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\PelangganImport;
use Barryvdh\DomPDF\Facade\Pdf;
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
                $q->where('id_pelanggan', 'like', '%' . $search . '%')
                  ->orWhere('nama_pelanggan', 'like', '%' . $search . '%')
                  ->orWhere('nomor_wa', 'like', '%' . $search . '%');
            });
        }

        $pelanggans = $query->latest()->paginate(10);
        return view('admin.pelanggan.index', compact('pelanggans'));
    }

    /**
     * Menyimpan data pelanggan baru ke database.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_pelanggan' => 'required|string|max:255|unique:pelanggans,id_pelanggan',
            'nama_pelanggan' => 'required|string|max:255',
            'alamat' => 'required|string',
            'nomor_wa' => 'nullable|string|max:20',
            'keterangan' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $pelanggan = Pelanggan::create($validator->validated());
        return response()->json($pelanggan, 201);
    }

    /**
     * Mengambil data satu pelanggan untuk form edit.
     */
    public function show(Pelanggan $pelanggan)
    {
        return response()->json($pelanggan);
    }

    /**
     * Memperbarui data pelanggan di database.
     */
    public function update(Request $request, Pelanggan $pelanggan)
    {
        $validator = Validator::make($request->all(), [
            'id_pelanggan' => 'required|string|max:255|unique:pelanggans,id_pelanggan,' . $pelanggan->id,
            'nama_pelanggan' => 'required|string|max:255',
            'alamat' => 'required|string',
            'nomor_wa' => 'nullable|string|max:20',
            'keterangan' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $pelanggan->update($validator->validated());
        return response()->json($pelanggan);
    }

    /**
     * Menghapus data pelanggan dari database.
     */
    public function destroy(Pelanggan $pelanggan)
    {
        $pelanggan->delete();
        return response()->json(['success' => 'Data pelanggan berhasil dihapus.']);
    }

    /**
     * Mengimpor data pelanggan dari file Excel.
     */
    public function importExcel(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        try {
            Excel::import(new PelangganImport, $request->file('file'));
            return redirect()->route('admin.pelanggan.index')->with('success', 'Data pelanggan berhasil diimpor!');
        } catch (\Exception $e) {
            return redirect()->route('admin.pelanggan.index')->withErrors(['error' => 'Gagal mengimpor file. Pesan: ' . $e->getMessage()]);
        }
    }

    /**
     * Mengekspor data pelanggan ke file Excel.
     */
    public function exportExcel()
    {
        // Logika untuk export Excel (membutuhkan class PelangganExport)
        // Jika belum ada, Anda bisa membuatnya dengan: php artisan make:export PelangganExport --model=Pelanggan
        // Untuk sementara kita kembalikan redirect dengan pesan.
        return redirect()->back()->with('success', 'Fitur Export Excel akan segera tersedia.');
    }

    /**
     * Mengekspor data pelanggan ke file PDF.
     */
    public function exportPdf()
    {
        $pelanggans = Pelanggan::all();
        $pdf = Pdf::loadView('admin.pelanggan.pdf', ['pelanggans' => $pelanggans]);
        return $pdf->download('daftar-pelanggan-' . date('Y-m-d') . '.pdf');
    }
}

