<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DataAutoKirim;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel; 
use App\Imports\DataAutoKirimImport;
use App\Exports\DataAutoKirimExport;
use PDF; 

class DataAutoKirimController extends Controller
{
    public function index()
    {
        $data = DataAutoKirim::latest()->get();
        
        // Mengarah ke resources/views/admin/autokirim/data/index.blade.php
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
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        // Fungsi Import Excel Diaktifkan
        Excel::import(new DataAutoKirimImport, $request->file('file'));
        
        return redirect()->back()->with('success', 'Data Excel berhasil diimport.');
    }

    public function exportExcel()
    {
        // Fungsi Export Excel Diaktifkan
        return Excel::download(new DataAutoKirimExport, 'data-autokirim.xlsx');
    }

    public function exportPdf()
    {
        $data = DataAutoKirim::all();
        
        // Fungsi PDF Diaktifkan
        $pdf = PDF::loadView('admin.autokirim.data.pdf', compact('data'));
        return $pdf->download('data-autokirim.pdf');
    }
}