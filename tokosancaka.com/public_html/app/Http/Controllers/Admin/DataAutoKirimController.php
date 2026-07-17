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
    // LOG LOG
    public function index()
    {
        $data = DataAutoKirim::latest()->get();
        return view('admin.autokirim.data.index', compact('data'));
    }

    // LOG LOG
    public function store(Request $request)
    {
        // Validasi dilonggarkan menjadi nullable agar bisa simpan meski kosong
        $request->validate([
            'brand_logistik' => 'nullable|string',
            'service'        => 'nullable|string',
            'cashback'       => 'nullable|numeric',
            'admin_cod'      => 'nullable|numeric',
            'komisi_agen'    => 'nullable|numeric',
        ]);

        DataAutoKirim::create([
            'brand_logistik' => $request->brand_logistik,
            'service'        => $request->service,
            'satuan'         => $request->satuan ?? '%',
            'cashback'       => $request->cashback ?? 0,
            'admin_cod'      => $request->admin_cod ?? 0,
            'komisi_agen'    => $request->komisi_agen, 
        ]);

        return redirect()->back()->with('success', 'Data berhasil ditambahkan.');
    }

    // LOG LOG
    public function update(Request $request, $id)
    {
        $item = DataAutoKirim::findOrFail($id);
        
        $request->validate([
            'brand_logistik' => 'nullable|string',
            'service'        => 'nullable|string',
            'cashback'       => 'nullable|numeric',
            'admin_cod'      => 'nullable|numeric',
            'komisi_agen'    => 'nullable|numeric',
        ]);

        $item->update($request->all());
        
        return redirect()->back()->with('success', 'Data berhasil diperbarui.');
    }

    // LOG LOG
    public function destroy($id)
    {
        DataAutoKirim::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Data berhasil dihapus.');
    }

    // LOG LOG
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        Excel::import(new DataAutoKirimImport, $request->file('file'));
        
        return redirect()->back()->with('success', 'Data Excel berhasil diimport.');
    }

    // LOG LOG
    public function exportExcel()
    {
        return Excel::download(new DataAutoKirimExport, 'data-autokirim.xlsx');
    }

    // LOG LOG
    public function exportPdf()
    {
        $data = DataAutoKirim::all();
        $pdf = PDF::loadView('admin.autokirim.data.pdf', compact('data'));
        return $pdf->download('data-autokirim.pdf');
    }

    // LOG LOG
    public function downloadTemplate()
    {
        return Excel::download(new \App\Exports\DataAutoKirimTemplateExport, 'Template_Import_Komisi_Agent.xlsx');
    }
}