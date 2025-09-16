<?php

namespace App\Http\Controllers;

use App\Models\Kontak;
use App\Http\Controllers\KontakController;
use Illuminate\Http\Request;
use App\Exports\KontaksExport; // <-- Menggunakan kelas Export
use App\Imports\KontaksImport; // <-- Menggunakan kelas Import
use Maatwebsite\Excel\Facades\Excel; // <-- Menggunakan fasad Excel
use Barryvdh\DomPDF\Facade\Pdf;       // <-- Menggunakan fasad PDF

class KontakController extends Controller
{
    /**
     * Menampilkan daftar kontak dengan fitur pencarian dan filter.
     */
    public function index(Request $request)
    {
        $query = Kontak::query();

        // Logika Pencarian
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('no_hp', 'like', "%{$search}%");
            });
        }

        // Logika Filter
        if ($request->filled('filter') && $request->input('filter') !== 'Semua') {
            $query->where('tipe', $request->input('filter'));
        }

        $kontaks = $query->latest()->paginate(10); // Menampilkan 10 data per halaman

        return view('admin.kontak.index', compact('kontaks'));
    }

    /**
     * Menyimpan kontak baru dari modal.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'nama' => 'required|string|max:255',
            'no_hp' => 'required|string|max:20|unique:kontaks,no_hp',
            'alamat' => 'required|string',
            'tipe' => 'required|string',
        ]);

        Kontak::create($validatedData);

        return redirect()->route('admin.kontak.index')->with('success', 'Kontak baru berhasil disimpan.');
    }

    /**
     * Menampilkan data kontak untuk diedit (biasanya dalam format JSON untuk modal).
     */
    public function show(Kontak $kontak)
    {
        return response()->json($kontak);
    }
    
    /**
     * PERBAIKAN: Fungsi untuk mencari kontak secara live (AJAX).
     * Fungsi ini akan dipanggil oleh JavaScript.
     */
    public function search(Request $request)
    {
        $query = $request->input('query');
        
        // Pastikan query tidak kosong
        if(empty($query)) {
            return response()->json([]);
        }

        // PERBAIKAN: Menggunakan kolom 'no_hp' sesuai dengan database Anda
        // dan menyertakan 'id' di dalam hasil
        $kontaks = Kontak::where('nama', 'LIKE', "%{$query}%")
                         ->orWhere('no_hp', 'LIKE', "%{$query}%") // Diubah dari 'telepon' menjadi 'no_hp'
                         ->limit(10) // Batasi hasil agar tidak terlalu banyak
                         ->get(['id', 'nama', 'no_hp', 'alamat']); // Ambil kolom yang dibutuhkan

        return response()->json($kontaks);
    }
   
    /**
     * Memperbarui data kontak.
     */
    public function update(Request $request, Kontak $kontak)
    {
        $validatedData = $request->validate([
            'nama' => 'required|string|max:255',
            'no_hp' => 'required|string|max:20|unique:kontaks,no_hp,' . $kontak->id,
            'alamat' => 'required|string',
            'tipe' => 'required|string',
        ]);

        $kontak->update($validatedData);

        return redirect()->route('admin.kontak.index')->with('success', 'Kontak berhasil diperbarui.');
    }

    /**
     * Menghapus kontak.
     */
    public function destroy(Kontak $kontak)
    {
        $kontak->delete();

        return redirect()->route('admin.kontak.index')->with('success', 'Kontak berhasil dihapus.');
    }
    
    // --- FUNGSI BARU UNTUK EXPORT & IMPORT ---

    /**
     * Menangani export data ke Excel.
     */
    public function exportExcel() 
    {
        return Excel::download(new KontaksExport, 'data-kontak.xlsx');
    }

    /**
     * Menangani export data ke PDF.
     */
    public function exportPdf() 
    {
        $kontaks = Kontak::all();
        $pdf = PDF::loadView('admin.kontak.pdf', compact('kontaks'));
        return $pdf->download('data-kontak.pdf');
    }

    /**
     * Menangani import data dari Excel.
     */
    public function importExcel(Request $request) 
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls'
        ]);
        
        try {
            Excel::import(new KontaksImport, $request->file('file'));
            return redirect()->route('admin.kontak.index')->with('success', 'Data kontak berhasil diimport.');
        } catch (\Exception $e) {
            return redirect()->route('admin.kontak.index')->with('error', 'Gagal mengimport data. Pastikan format file Excel sudah benar.');
        }
    }
}
