<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AutoKirim;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\AutoKirimImport;

class AutoKirimController extends Controller
{
   public function index(Request $request)
    {
        $query = AutoKirim::query();

        // 1. FITUR PENCARIAN TEKS (Kodepos, Desa, Kota, Provinsi)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('zip', 'like', "%{$search}%")
                  ->orWhere('district_name', 'like', "%{$search}%")
                  ->orWhere('regency_name', 'like', "%{$search}%")
                  ->orWhere('province_name', 'like', "%{$search}%");
            });
        }

        // 2. FITUR FILTER DROPDOWN PROVINSI
        if ($request->filled('province')) {
            $query->where('province_name', $request->province);
        }

        // 3. FITUR FILTER DROPDOWN KOTA/KABUPATEN
        if ($request->filled('regency')) {
            $query->where('regency_name', $request->regency);
        }

        // 4. PENGURUTAN A-Z (Mulai dari Aceh seperti di Excel)
        $query->orderBy('province_name', 'asc')
              ->orderBy('regency_name', 'asc')
              ->orderBy('district_name', 'asc');

        // 5. HITUNG STATISTIK (Dihitung berdasarkan filter yang aktif)
        $statsQuery = clone $query;
        $totalDesa = $statsQuery->count();
        
        $kotaQuery = clone $query;
        $totalKota = $kotaQuery->distinct('regency_name')->count('regency_name');
        
        $provQuery = clone $query;
        $totalProvinsi = $provQuery->distinct('province_name')->count('province_name');

        // 6. AMBIL DATA UNTUK ISI DROPDOWN (Data unik dari tabel)
        $provinces = AutoKirim::select('province_name')->distinct()->whereNotNull('province_name')->orderBy('province_name')->pluck('province_name');
        $regencies = AutoKirim::select('regency_name')->distinct()->whereNotNull('regency_name')->orderBy('regency_name')->pluck('regency_name');

        // 7. PAGINASI (withQueryString agar filter tidak hilang saat pindah halaman)
        $data = $query->paginate(15)->withQueryString();

        return view('admin.autokirim.index', compact(
            'data', 'totalDesa', 'totalKota', 'totalProvinsi', 'provinces', 'regencies'
        ));
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