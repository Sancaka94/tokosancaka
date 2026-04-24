<?php

namespace App\Http\Controllers;

use App\Models\Nota;
use App\Models\NotaItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\NotaExport;

class NotaController extends Controller
{
    /**
     * 1. INDEX: Menampilkan riwayat nota
     */
    public function index()
    {
        /* Gunakan eager loading 'items' agar query lebih cepat */
        $notas = Nota::with('items')->orderBy('created_at', 'desc')->paginate(10);
        return view('nota.index', compact('notas'));
    }

    /**
     * 2. CREATE: Menampilkan form tambah nota
     */
    public function create()
    {
        $no_nota = 'NOTA-' . date('Ymd') . '-' . rand(1000, 9999);
        return view('nota.create', compact('no_nota'));
    }

    /**
     * 3. STORE: Menyimpan nota baru ke database
     */
    public function store(Request $request)
    {
        $request->validate([
            'no_nota'      => 'required|unique:notas',
            'kepada'       => 'required|string|max:255',
            'tanggal'      => 'required|date',
            'nama_pembeli' => 'required|string|max:255',
            'nama_penjual' => 'required|string|max:255',
            'ttd_pembeli'  => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'ttd_penjual'  => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'barang.*.nama'      => 'required|string',
            'barang.*.banyaknya' => 'required|numeric|min:1',
            'barang.*.harga'     => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            /* Handle Upload TTD */
            $path_ttd_pembeli = $request->hasFile('ttd_pembeli') ? $request->file('ttd_pembeli')->store('uploads/ttd', 'public') : null;
            $path_ttd_penjual = $request->hasFile('ttd_penjual') ? $request->file('ttd_penjual')->store('uploads/ttd', 'public') : null;

            /* Simpan Header Nota */
            $nota = Nota::create([
                'no_nota'      => $request->no_nota,
                'kepada'       => $request->kepada,
                'tanggal'      => $request->tanggal,
                'nama_pembeli' => $request->nama_pembeli,
                'nama_penjual' => $request->nama_penjual,
                'ttd_pembeli'  => $path_ttd_pembeli,
                'ttd_penjual'  => $path_ttd_penjual,
                'total_harga'  => 0, 
            ]);

            $total_harga = 0;

            /* Simpan Detail Item */
            foreach ($request->barang as $item) {
                $jumlah = $item['banyaknya'] * $item['harga'];
                $total_harga += $jumlah;

                NotaItem::create([
                    'nota_id'     => $nota->id,
                    'nama_barang' => $item['nama'],
                    'banyaknya'   => $item['banyaknya'],
                    'harga'       => $item['harga'],
                    'jumlah'      => $jumlah,
                ]);
            }

            /* Update Grand Total */
            $nota->update(['total_harga' => $total_harga]);

            DB::commit();
            // Redirect kembali ke halaman create, sambil membawa ID nota yang baru saja dibuat
            return back()
                ->with('success', 'Nota berhasil dibuat dan disimpan!')
                ->with('success_nota_id', $nota->id);

        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Terjadi kesalahan sistem: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * 4. SHOW: Menampilkan detail nota
     */
    public function show($id)
    {
        $nota = Nota::with('items')->findOrFail($id);
        return view('nota.show', compact('nota')); 
    }

    /**
     * 5. EDIT: Menampilkan form edit nota
     */
    public function edit($id)
    {
        $nota = Nota::with('items')->findOrFail($id);
        return view('nota.edit', compact('nota'));
    }

    /**
     * 6. UPDATE: Menyimpan perubahan nota
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'kepada'       => 'required|string|max:255',
            'tanggal'      => 'required|date',
            'nama_pembeli' => 'required|string|max:255',
            'nama_penjual' => 'required|string|max:255',
            'ttd_pembeli'  => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'ttd_penjual'  => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'barang.*.nama'      => 'required|string',
            'barang.*.banyaknya' => 'required|numeric|min:1',
            'barang.*.harga'     => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();
            $nota = Nota::findOrFail($id);

            /* Handle Update TTD Pembeli */
            if ($request->hasFile('ttd_pembeli')) {
                if ($nota->ttd_pembeli) Storage::disk('public')->delete($nota->ttd_pembeli);
                $nota->ttd_pembeli = $request->file('ttd_pembeli')->store('uploads/ttd', 'public');
            }

            /* Handle Update TTD Penjual */
            if ($request->hasFile('ttd_penjual')) {
                if ($nota->ttd_penjual) Storage::disk('public')->delete($nota->ttd_penjual);
                $nota->ttd_penjual = $request->file('ttd_penjual')->store('uploads/ttd', 'public');
            }

            /* Update Field Header */
            $nota->kepada = $request->kepada;
            $nota->tanggal = $request->tanggal;
            $nota->nama_pembeli = $request->nama_pembeli;
            $nota->nama_penjual = $request->nama_penjual;
            $nota->save();

            /* Reset dan Masukkan Item Baru */
            $nota->items()->delete();

            $total_harga = 0;
            foreach ($request->barang as $item) {
                $jumlah = $item['banyaknya'] * $item['harga'];
                $total_harga += $jumlah;

                NotaItem::create([
                    'nota_id'     => $nota->id,
                    'nama_barang' => $item['nama'],
                    'banyaknya'   => $item['banyaknya'],
                    'harga'       => $item['harga'],
                    'jumlah'      => $jumlah,
                ]);
            }

            $nota->update(['total_harga' => $total_harga]);

            DB::commit();
            return redirect()->route('nota.index')->with('success', 'Nota berhasil diperbarui!');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Gagal update: ' . $e->getMessage());
        }
    }

    /**
     * 7. DESTROY: Menghapus nota beserta gambarnya
     */
    public function destroy($id)
    {
        $nota = Nota::findOrFail($id);

        /* Hapus file gambar secara fisik dari storage */
        if ($nota->ttd_pembeli) Storage::disk('public')->delete($nota->ttd_pembeli);
        if ($nota->ttd_penjual) Storage::disk('public')->delete($nota->ttd_penjual);

        $nota->delete();

        return redirect()->route('nota.index')->with('success', 'Nota beserta data gambarnya berhasil dihapus!');
    }

    /**
     * =========================================
     * BAGIAN EXPORT (PDF & EXCEL)
     * =========================================
     */

    public function exportPdf()
    {
        $notas = Nota::with('items')->orderBy('tanggal', 'desc')->get();
        $pdf = Pdf::loadView('nota.pdf', compact('notas'));
        return $pdf->download('Laporan_Riwayat_Nota.pdf');
    }

    public function exportExcel()
    {
        return Excel::download(new NotaExport, 'Laporan_Riwayat_Nota.xlsx');
    }

    /**
     * DOWNLOAD: Mengunduh PDF untuk 1 Nota Spesifik (Lengkap dengan TTD)
     */
    public function downloadNota($id)
    {
        $nota = Nota::with('items')->findOrFail($id);
        
        // Memuat view khusus untuk cetak 1 nota
        $pdf = Pdf::loadView('nota.receipt_pdf', compact('nota'));
        
        // Mengatur ukuran kertas (A5 portrait biasanya cocok untuk nota)
        $pdf->setPaper('A5', 'portrait');
        
        return $pdf->download('Nota_' . $nota->no_nota . '.pdf');
    }
}