<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EkspedisiController extends Controller
{
    public function index()
    {
        $ekspedisi = DB::table('Ekspedisi')->get();
        return view('admin.ekspedisi.index', compact('ekspedisi'));
    }

    public function store(Request $request)
    {
        $data = [
            'nama_ekspedisi' => $request->nama_ekspedisi,
            'keyword' => strtolower($request->keyword),
            'diskon_rules' => $request->diskon_rules,
        ];

        // Logic Upload Logo Sederhana
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('logos'), $filename);
            $data['logo_path'] = '/logos/' . $filename;
        }

        DB::table('Ekspedisi')->insert($data);
        return back()->with('success', 'Ekspedisi berhasil ditambahkan');
    }

    public function update(Request $request, $id)
    {
        $data = [
            'nama_ekspedisi' => $request->nama_ekspedisi,
            'keyword' => strtolower($request->keyword),
            'diskon_rules' => $request->diskon_rules,
        ];

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('logos'), $filename);
            $data['logo_path'] = '/logos/' . $filename;
        }

        DB::table('Ekspedisi')->where('id_ekspedisi', $id)->update($data);
        return back()->with('success', 'Data Ekspedisi berhasil diupdate');
    }

    public function destroy($id)
    {
        DB::table('Ekspedisi')->where('id_ekspedisi', $id)->delete();
        return back()->with('success', 'Data Ekspedisi dihapus');
    }
}