<?php

namespace App\Http\Controllers;

use App\Models\City;
use Illuminate\Http\Request;

class CityController extends Controller
{
    public function index()
    {
        // Mengambil semua data diurutkan dari yang terbaru
        $cities = City::orderBy('id', 'desc')->get();
        return view('cities.index', compact('cities'));
    }

    public function destroy($id)
    {
        City::findOrFail($id)->delete();
        return redirect()->route('cities.index')->with('success', 'Data berhasil dihapus.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('file');
        $fileHandle = fopen($file->getPathname(), 'r');
        
        // Lewati baris pertama jika itu adalah Header (Nama Kota, Keterangan)
        fgetcsv($fileHandle); 

        // LOG LOG - Looping data CSV dan masukkan ke database
        while (($row = fgetcsv($fileHandle, 1000, ',')) !== false) {
            if(isset($row[0]) && isset($row[1])) {
                City::create([
                    'nama_kota' => $row[0],
                    'keterangan' => $row[1]
                ]);
            }
        }
        fclose($fileHandle);

        return redirect()->route('cities.index')->with('success', 'Data CSV berhasil diunggah!');
    }
}