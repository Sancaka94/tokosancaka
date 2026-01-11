<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Keuangan;
use Illuminate\Http\Request;
use Carbon\Carbon;

class KeuanganController extends Controller
{
    /**
     * Menampilkan daftar keuangan + Ringkasan Saldo
     */
    public function index(Request $request)
    {
        $query = Keuangan::query();

        // Filter Sederhana (Opsional)
        if ($request->filled('search')) {
            $query->where('nomor_invoice', 'like', '%' . $request->search . '%')
                  ->orWhere('keterangan', 'like', '%' . $request->search . '%');
        }

        // Urutkan dari yang terbaru
        $transaksi = $query->orderBy('tanggal', 'desc')->paginate(10);

        // Hitung Ringkasan untuk Card Atas
        $totalPemasukan = Keuangan::where('jenis', 'Pemasukan')->sum('jumlah');
        $totalPengeluaran = Keuangan::where('jenis', 'Pengeluaran')->sum('jumlah');
        $saldo = $totalPemasukan - $totalPengeluaran;

        return view('admin.keuangan.index', compact('transaksi', 'totalPemasukan', 'totalPengeluaran', 'saldo'));
    }

    /**
     * Simpan Transaksi Baru
     */
    public function store(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'jenis' => 'required|in:Pemasukan,Pengeluaran',
            'kategori' => 'required|string',
            'jumlah' => 'required|numeric|min:0',
            'nomor_invoice' => 'nullable|string',
            'keterangan' => 'nullable|string',
        ]);

        Keuangan::create($request->all());

        return redirect()->back()->with('success', 'Data keuangan berhasil disimpan.');
    }

    /**
     * Update Transaksi
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'jenis' => 'required|in:Pemasukan,Pengeluaran',
            'kategori' => 'required|string',
            'jumlah' => 'required|numeric|min:0',
        ]);

        $keuangan = Keuangan::findOrFail($id);
        $keuangan->update($request->all());

        return redirect()->back()->with('success', 'Data keuangan berhasil diperbarui.');
    }

    /**
     * Hapus Transaksi
     */
    public function destroy($id)
    {
        $keuangan = Keuangan::findOrFail($id);
        $keuangan->delete();

        return redirect()->back()->with('success', 'Data keuangan berhasil dihapus.');
    }
}