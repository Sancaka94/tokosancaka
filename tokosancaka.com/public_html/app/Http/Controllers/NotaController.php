<?php

namespace App\Http\Controllers;

use App\Models\Nota;
use App\Models\NotaItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotaController extends Controller
{
    // Menampilkan Riwayat Nota (Admin)
    public function index()
    {
        $notas = Nota::orderBy('created_at', 'desc')->paginate(10);
        return view('nota.index', compact('notas'));
    }

    // Menampilkan Form Pembuatan Nota (App)
    public function create()
    {
        // Generate No Nota otomatis (opsional, bisa disesuaikan)
        $no_nota = 'NOTA-' . date('Ymd') . '-' . rand(100, 999);
        return view('nota.create', compact('no_nota'));
    }

    // Menyimpan Nota dan Item ke Database
    public function store(Request $request)
    {
        $request->validate([
            'no_nota' => 'required|unique:notas',
            'kepada' => 'required',
            'tanggal' => 'required|date',
            'barang.*.nama' => 'required',
            'barang.*.banyaknya' => 'required|numeric|min:1',
            'barang.*.harga' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            // 1. Simpan Header Nota
            $nota = Nota::create([
                'no_nota' => $request->no_nota,
                'kepada' => $request->kepada,
                'tanggal' => $request->tanggal,
                'total_harga' => 0, // Akan diupdate di bawah
            ]);

            $total_harga = 0;

            // 2. Simpan Detail Item Barang
            foreach ($request->barang as $item) {
                $jumlah = $item['banyaknya'] * $item['harga'];
                $total_harga += $jumlah;

                NotaItem::create([
                    'nota_id' => $nota->id,
                    'nama_barang' => $item['nama'],
                    'banyaknya' => $item['banyaknya'],
                    'harga' => $item['harga'],
                    'jumlah' => $jumlah,
                ]);
            }

            // 3. Update Grand Total
            $nota->update(['total_harga' => $total_harga]);

            DB::commit();
            return redirect()->route('nota.index')->with('success', 'Nota berhasil dibuat!');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    // Menghapus Nota (Otomatis menghapus item karena cascade)
    public function destroy($id)
    {
        Nota::findOrFail($id)->delete();
        return redirect()->route('nota.index')->with('success', 'Nota berhasil dihapus!');
    }
}