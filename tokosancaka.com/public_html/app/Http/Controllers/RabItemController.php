<?php

namespace App\Http\Controllers;

use App\Models\RabItem;
use App\Models\Proyek;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\RabImport;
use Barryvdh\DomPDF\Facade\Pdf;

class RabItemController extends Controller
{
    // LOG LOG
    public function create(Request $request)
    {
        // Menangkap proyek_id dari URL untuk form tambah
        $proyek = Proyek::findOrFail($request->proyek_id);
        return view('rab.create', compact('proyek'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'proyek_id'        => 'required|exists:proyek,id',
            'kategori'         => 'nullable|string|max:255',
            'uraian_pekerjaan' => 'required|string',
            'volume'           => 'required|numeric|min:0',
            'satuan'           => 'required|string|max:50',
            'harga_satuan'     => 'required|numeric|min:0',
        ]);

        $validated['total'] = $validated['volume'] * $validated['harga_satuan'];
        RabItem::create($validated);

        return redirect()->route('proyek.show', $validated['proyek_id'])->with('success', 'Item RAB berhasil ditambahkan.');
    }

    public function edit(RabItem $rab)
    {
        $proyek = $rab->proyek;
        return view('rab.edit', compact('rab', 'proyek'));
    }

    public function update(Request $request, RabItem $rab)
    {
        $validated = $request->validate([
            'kategori'         => 'nullable|string|max:255',
            'uraian_pekerjaan' => 'required|string',
            'volume'           => 'required|numeric|min:0',
            'satuan'           => 'required|string|max:50',
            'harga_satuan'     => 'required|numeric|min:0',
        ]);

        $validated['total'] = $validated['volume'] * $validated['harga_satuan'];
        $rab->update($validated);

        return redirect()->route('proyek.show', $rab->proyek_id)->with('success', 'Item RAB berhasil diperbarui.');
    }

    public function destroy(RabItem $rab)
    {
        $proyek_id = $rab->proyek_id;
        $rab->delete();
        return redirect()->route('proyek.show', $proyek_id)->with('success', 'Item RAB berhasil dihapus.');
    }

    public function import(Request $request, $proyek_id)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:2048'
        ]);

        Excel::import(new RabImport($proyek_id), $request->file('file'));

        return redirect()->route('proyek.show', $proyek_id)->with('success', 'Data RAB dari Excel berhasil diunggah.');
    }

    public function exportPdf(Request $request, $proyek_id)
    {
        $proyek = Proyek::findOrFail($proyek_id);

        // Memulai query ke tabel rabItems
        $query = $proyek->rabItems()->orderBy('kategori');

        // Mengecek apakah ada parameter request 'kategori'
        if ($request->filled('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        // Mengeksekusi query
        $items = $query->get();
        $totalKeseluruhan = $items->sum('total');

        // Mengaktifkan remote URL agar gambar logo dari HTTPS bisa di-load oleh PDF
        $pdf = Pdf::setOptions(['isRemoteEnabled' => true])
                  ->loadView('rab.pdf', compact('proyek', 'items', 'totalKeseluruhan'));

        // Penamaan file dinamis mengikuti kategori jika ada
        $namaFile = 'RAB_' . str_replace(' ', '_', $proyek->nama_proyek);
        if ($request->filled('kategori')) {
            $namaFile .= '_' . str_replace(' ', '_', $request->kategori);
        }
        $namaFile .= '.pdf';

        return $pdf->download($namaFile);
    }
}
