<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\CityTransaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    // Menampilkan halaman form
    public function create()
    {
        // Mengambil data kota untuk di dropdown
        // Menggunakan groupBy/unique jika ada nama kota duplikat (seperti Jakarta Selatan di DB Anda)
        $cities = City::select('id', 'nama_kota')->get()->unique('nama_kota'); 
        return view('transactions.create', compact('cities'));
    }

    // Menyimpan data ke database
    public function store(Request $request)
    {
        $request->validate([
            'city_id' => 'required|exists:cities,id',
            'jumlah'  => 'required|integer|min:1',
            'tanggal' => 'required|date',
        ]);

        CityTransaction::create([
            'city_id' => $request->city_id,
            'jumlah'  => $request->jumlah,
            'tanggal' => $request->tanggal,
        ]);

        return redirect()->back()->with('success', 'Data transaksi berhasil disimpan!');
    }
}