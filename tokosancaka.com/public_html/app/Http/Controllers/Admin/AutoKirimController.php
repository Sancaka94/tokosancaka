<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AutoKirim;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\AutoKirimImport;

class AutoKirimController extends Controller
{
    public function index()
    {
        $data = AutoKirim::latest()->paginate(15);
        return view('admin.autokirim.index', compact('data'));
    }

    public function create()
    {
        return view('admin.autokirim.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'zip' => 'required',
            'district_id' => 'required',
            'district_name' => 'required',
            'regency_name' => 'required',
            'province_name' => 'required',
        ]);

        AutoKirim::create($request->all());

        return redirect()->route('admin.autokirim.index')->with('success', 'Data area berhasil ditambahkan.');
    }

    public function edit(AutoKirim $autokirim)
    {
        return view('admin.autokirim.edit', compact('autokirim'));
    }

    public function update(Request $request, AutoKirim $autokirim)
    {
        $request->validate([
            'zip' => 'required',
            'district_id' => 'required',
            'district_name' => 'required',
            'regency_name' => 'required',
            'province_name' => 'required',
        ]);

        $autokirim->update($request->all());

        return redirect()->route('admin.autokirim.index')->with('success', 'Data area berhasil diperbarui.');
    }

    public function destroy(AutoKirim $autokirim)
    {
        $autokirim->delete();
        return redirect()->route('admin.autokirim.index')->with('success', 'Data area berhasil dihapus.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        Excel::import(new AutoKirimImport, $request->file('file'));

        // LOG LOG: AutoKirim Imported (Menjaga kebiasaan log-mu)
        \Log::info('LOG LOG: Import Excel Area AutoKirim sukses oleh user ID: ' . auth()->id());

        return redirect()->route('admin.autokirim.index')->with('success', 'File Excel berhasil diimport ke database.');
    }
}