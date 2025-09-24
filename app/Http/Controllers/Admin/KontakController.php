<?php

// 1. Namespace diperbaiki agar sesuai dengan folder Admin
namespace App\Http\Controllers\Admin;

use App\Models\Kontak;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exports\KontaksExport;
use App\Imports\KontaksImport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ApiKontakController extends Controller
{
    /**
     * Menampilkan daftar kontak dengan fitur pencarian dan filter.
     */
    public function index(Request $request)
    {
        $query = Kontak::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('no_hp', 'like', "%{$search}%");
            });
        }

        if ($request->filled('filter') && $request->input('filter') !== 'Semua') {
            $query->where('tipe', $request->input('filter'));
        }

        $kontaks = $query->latest()->paginate(10);

        return view('admin.kontak.index', compact('kontaks'));
    }

    /**
     * Menyimpan kontak baru dari modal.
     */
    public function store(Request $request)
    {
        // 3. Validasi untuk alamat terstruktur dilengkapi
        $validatedData = $request->validate([
            'nama' => 'required|string|max:255',
            'no_hp' => 'required|string|max:20|unique:kontaks,no_hp',
            'alamat' => 'required|string',
            'tipe' => 'required|string',
            'province' => 'nullable|string|max:255',
            'regency' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'village' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:255',
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
     * 2. Fungsi search disesuaikan untuk kebutuhan form pesanan (AJAX).
     * Menerima parameter 'search' dan 'tipe' dari JavaScript.
     */
    public function search(Request $request)
    {
        $request->validate([
            'search' => 'required|string|min:3',
            'tipe'   => 'nullable|in:Pengirim,Penerima,Keduanya',
        ]);

        $query = Kontak::query();

        $searchTerm = $request->input('search');
        $query->where(function ($q) use ($searchTerm) {
            $q->where('nama', 'LIKE', "%{$searchTerm}%")
              ->orWhere('no_hp', 'LIKE', "%{$searchTerm}%");
        });

        $tipe = $request->input('tipe');
        if ($tipe) {
            $query->where('tipe', $tipe)->orWhere('tipe', 'Keduanya');
        }

        $kontaks = $query->limit(10)->get();

        return response()->json($kontaks);
    }
    
    /**
     * Memperbarui data kontak.
     */
    public function update(Request $request, Kontak $kontak)
    {
        // 3. Validasi untuk alamat terstruktur dilengkapi
        $validatedData = $request->validate([
            'nama' => 'required|string|max:255',
            'no_hp' => 'required|string|max:20|unique:kontaks,no_hp,' . $kontak->id,
            'alamat' => 'required|string',
            'tipe' => 'required|string',
            'province' => 'nullable|string|max:255',
            'regency' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'village' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:255',
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
    
    // --- FUNGSI UNTUK EXPORT & IMPORT ---

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

