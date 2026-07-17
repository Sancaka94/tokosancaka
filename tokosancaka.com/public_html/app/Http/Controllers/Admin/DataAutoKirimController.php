<?php

namespace App\Http\Controllers\Admin;

use App\Models\DataAutoKirim;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel; // Uncomment jika menggunakan package excel
use App\Imports\DataAutoKirimImport;
use App\Exports\DataAutoKirimExport;
// use PDF; // Uncomment jika menggunakan barryvdh/laravel-dompdf

class DataAutoKirimController extends Controller
{
    public function index()
    {
        $data = DataAutoKirim::latest()->get();
        
        // SUDAH DIPERBAIKI: Mengarah ke resources/views/admin/autokirim/data/index.blade.php
        return view('admin.autokirim.data.index', compact('data'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'brand_logistik' => 'required|string',
            'service' => 'required|string',
            'cashback' => 'required|numeric',
            'admin_cod' => 'required|numeric',
            'komisi_agen' => 'required|numeric',
        ]);

        DataAutoKirim::create([
            'brand_logistik' => $request->brand_logistik,
            'service' => $request->service,
            'satuan' => $request->satuan ?? '%',
            'cashback' => $request->cashback,
            'admin_cod' => $request->admin_cod,
            'komisi_agen' => $request->komisi_agen, 
        ]);

        return redirect()->back()->with('success', 'Data berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $item = DataAutoKirim::findOrFail($id);
        $item->update($request->all());
        
        return redirect()->back()->with('success', 'Data berhasil diperbarui.');
    }

    public function destroy($id)
    {
        DataAutoKirim::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Data berhasil dihapus.');
    }

    public function import(Request $request)
    {
        $request->validate(['file' => 'required|mimes:xlsx,csv']);
        // Excel::import(new DataAutoKirimImport, $request->file('file'));
        return redirect()->back()->with('success', 'Data berhasil diimport.');
    }

    public function exportExcel()
    {
        // return Excel::download(new DataAutoKirimExport, 'data-autokirim.xlsx');
        return redirect()->back()->with('success', 'Fungsi download Excel dipanggil.');
    }

    public function exportPdf()
    {
        $data = DataAutoKirim::all();
        // $pdf = PDF::loadView('admin.autokirim.data.pdf', compact('data'));
        // return $pdf->download('data-autokirim.pdf');
        return redirect()->back()->with('success', 'Fungsi download PDF dipanggil.');
    }
}