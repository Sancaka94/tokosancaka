<?php

namespace App\Http\Controllers;

use App\Models\City;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel; // Tambahan library Excel

class CityController extends Controller
{
    public function index()
    {
        $cities = City::orderBy('id', 'desc')->get();
        return view('cities.index', compact('cities'));
    }

    public function destroy($id)
    {
        City::findOrFail($id)->delete();
        return redirect()->route('cities.index')->with('success', 'Data berhasil dihapus.');
    }

    // LOG LOG - Fungsi untuk mengunduh template CSV
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
        // Validasi diperluas untuk menerima Excel (.xlsx, .xls)
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:5120',
        ]);

        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();

        // LOG LOG - Percabangan deteksi format file
        if (in_array(strtolower($extension), ['csv', 'txt'])) {
            
            // --- LOGIKA 1: Jika file adalah CSV mentah (Super Cepat) ---
            $fileHandle = fopen($file->getPathname(), 'r');
            fgetcsv($fileHandle); // Lewati baris 1 (Header)

            while (($row = fgetcsv($fileHandle, 1000, ',')) !== false) {
                // Pastikan kolom 0 (Nama) dan 1 (Keterangan) ada isinya
                if(!empty($row[0]) && !empty($row[1])) {
                    City::create([
                        'nama_kota' => $row[0],
                        'keterangan' => $row[1]
                    ]);
                }
            }
            fclose($fileHandle);

        } else {
            
            // --- LOGIKA 2: Jika file adalah XLSX / XLS asli (Menggunakan Library) ---
            // Excel::toArray otomatis membaca seluruh isi file menjadi Array PHP
            $dataArray = Excel::toArray([], $file);

            if (!empty($dataArray) && isset($dataArray[0])) {
                $sheet = $dataArray[0]; // Ambil Sheet Pertama
                
                array_shift($sheet); // Buang baris 1 (Header)

                foreach ($sheet as $row) {
                    // Cek apakah data Excel di kolom A dan B tidak kosong
                    if(!empty($row[0]) && !empty($row[1])) {
                        City::create([
                            'nama_kota' => $row[0],
                            'keterangan' => $row[1]
                        ]);
                    }
                }
            }
        }

        // LOG LOG - Respons untuk AJAX Progress Bar
        if ($request->ajax()) {
            session()->flash('success', 'File Spreadsheet (CSV/Excel) berhasil diunggah dan diproses!');
            return response()->json(['status' => 'success']);
        }

        return redirect()->route('cities.index')->with('success', 'File Spreadsheet (CSV/Excel) berhasil diunggah dan diproses!');

    }

    // LOG LOG - Fungsi untuk memproses update data dari Modal
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