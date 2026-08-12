<?php

namespace App\Http\Controllers;

use App\Models\RabItem;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\RabImport;
use Barryvdh\DomPDF\Facade\Pdf;

class RabItemController extends Controller
{
    // LOG LOG
    public function index()
    {
        $items = RabItem::orderBy('kategori')->get();
        return view('rab.index', compact('items'));
    }

    public function create()
    {
        return view('rab.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kategori' => 'nullable|string|max:255',
            'uraian_pekerjaan' => 'required|string',
            'volume' => 'required|numeric|min:0',
            'satuan' => 'required|string|max:50',
            'harga_satuan' => 'required|numeric|min:0',
        ]);

        $validated['total'] = $validated['volume'] * $validated['harga_satuan'];

        RabItem::create($validated);

        return redirect()->route('rab.index')->with('success', 'Item RAB berhasil ditambahkan.');
    }

    public function edit(RabItem $rab)
    {
        return view('rab.edit', compact('rab'));
    }

    public function update(Request $request, RabItem $rab)
    {
        $validated = $request->validate([
            'kategori' => 'nullable|string|max:255',
            'uraian_pekerjaan' => 'required|string',
            'volume' => 'required|numeric|min:0',
            'satuan' => 'required|string|max:50',
            'harga_satuan' => 'required|numeric|min:0',
        ]);

        $validated['total'] = $validated['volume'] * $validated['harga_satuan'];

        $rab->update($validated);

        return redirect()->route('rab.index')->with('success', 'Item RAB berhasil diperbarui.');
    }

    public function destroy(RabItem $rab)
    {
        $rab->delete();
        return redirect()->route('rab.index')->with('success', 'Item RAB berhasil dihapus.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:2048'
        ]);

        Excel::import(new RabImport, $request->file('file'));

        return redirect()->route('rab.index')->with('success', 'Data RAB dari Excel berhasil diunggah dan disimpan.');
    }

    public function exportPdf()
    {
        // Mengelompokkan item berdasarkan kategori agar rapi di PDF
        $items = RabItem::orderBy('kategori')->get();
        $totalKeseluruhan = $items->sum('total');

        $pdf = Pdf::loadView('rab.pdf', compact('items', 'totalKeseluruhan'));
        
        return $pdf->download('RAB_Proyek_Sancaka.pdf');
    }
}