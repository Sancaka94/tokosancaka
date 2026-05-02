<?php

namespace App\Http\Controllers;

use App\Models\City;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class CityController extends Controller
{
    public function index(Request $request)
    {
        // LOG LOG - Inisialisasi Query
        $query = City::query();

        // 1. Filter Pencarian (Nama Kota atau Keterangan)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama_kota', 'like', "%{$search}%")
                  ->orWhere('keterangan', 'like', "%{$search}%");
            });
        }

        // 2. Filter Tanggal Mulai
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        // 3. Filter Tanggal Sampai
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // LOG LOG - Paginate 20 Data
        $cities = $query->orderBy('id', 'desc')->paginate(10);
        
        $cities->appends($request->all());

        return view('cities.index', compact('cities'));
    }

    // --- METODE BARU: create() ---
    // Berfungsi menampilkan form tambah data manual
    public function create()
    {
        return view('cities.create');
    }

    // --- METODE BARU: store() ---
    // Berfungsi menyimpan data manual dari form ke database
    public function store(Request $request)
    {
        $request->validate([
            'nama_kota'  => 'required|string|max:255',
            'keterangan' => 'nullable|string'
        ]);

        City::create([
            'nama_kota'  => $request->nama_kota,
            'keterangan' => $request->keterangan
        ]);

        return redirect()->route('cities.index')->with('success', 'Data kota berhasil ditambahkan secara manual.');
    }

    public function destroy($id)
    {
        City::findOrFail($id)->delete();
        return redirect()->route('cities.index')->with('success', 'Data berhasil dihapus.');
    }

    public function downloadExample()
    {
        $headers = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="contoh_format_kota.csv"',
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Nama Kota', 'Keterangan']);
            fputcsv($file, ['Jakarta Selatan', 'Area pengiriman VIP']);
            fputcsv($file, ['Surabaya', 'Cabang Jawa Timur']);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:5120',
        ]);

        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();

        if (in_array(strtolower($extension), ['csv', 'txt'])) {
            $fileHandle = fopen($file->getPathname(), 'r');
            fgetcsv($fileHandle); 

            while (($row = fgetcsv($fileHandle, 1000, ',')) !== false) {
                if(!empty($row[0]) && !empty($row[1])) {
                    City::create([
                        'nama_kota' => $row[0],
                        'keterangan' => $row[1]
                    ]);
                }
            }
            fclose($fileHandle);

        } else {
            $dataArray = Excel::toArray([], $file);

            if (!empty($dataArray) && isset($dataArray[0])) {
                $sheet = $dataArray[0]; 
                array_shift($sheet); 

                foreach ($sheet as $row) {
                    if(!empty($row[0]) && !empty($row[1])) {
                        City::create([
                            'nama_kota' => $row[0],
                            'keterangan' => $row[1]
                        ]);
                    }
                }
            }
        }

        if ($request->ajax()) {
            session()->flash('success', 'File Spreadsheet (CSV/Excel) berhasil diunggah dan diproses!');
            return response()->json(['status' => 'success']);
        }

        return redirect()->route('cities.index')->with('success', 'File Spreadsheet (CSV/Excel) berhasil diunggah dan diproses!');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nama_kota' => 'required|string|max:255',
            'keterangan' => 'nullable|string'
        ]);

        $city = City::findOrFail($id);
        $city->update([
            'nama_kota' => $request->nama_kota,
            'keterangan' => $request->keterangan
        ]);

        return redirect()->route('cities.index')->with('success', 'Data kota berhasil diperbarui.');
    }
}