<?php

namespace App\Http\Controllers;

use App\Models\KasLaporan;
use App\Models\KasPengeluaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class KasController extends Controller
{
    // Menampilkan halaman riwayat (Index)
    public function index()
    {
        // Mengambil data beserta rincian pengeluarannya (Eager Loading)
        $laporanKas = KasLaporan::with('pengeluaran')->orderBy('tanggal', 'desc')->get();
        return view('kas.index', compact('laporanKas'));
    }

    // Menampilkan form input baru
    public function create()
    {
        // Contoh: Mengambil pemasukan dari transaksi parkir hari ini
        // $totalPemasukanParkir = Transaction::whereDate('exit_time', date('Y-m-d'))->sum(\DB::raw('fee + IFNULL(toilet_fee, 0)'));
        
        $totalPemasukanParkir = 150000; // HAPUS INI JIKA SUDAH TERHUBUNG KE DATABASE TRANSAKSI ASLI
        return view('kas.create', compact('totalPemasukanParkir'));
    }

    // Menyimpan data ke database
    public function store(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'pengeluaran.*.keterangan' => 'required|string',
            'pengeluaran.*.nominal' => 'required|numeric',
        ]);

        DB::beginTransaction();
        try {
            // Hitung ulang dari backend untuk keamanan
            $totalPengeluaran = 0;
            foreach ($request->pengeluaran as $item) {
                $totalPengeluaran += $item['nominal'];
            }
            
            $pemasukan = $request->pemasukan_sistem ?? 0;
            $saldoBersih = $pemasukan - $totalPengeluaran;

            // Handle Upload Tanda Tangan
            $pathPembuat = $request->hasFile('ttd_pembuat') ? $request->file('ttd_pembuat')->store('ttd_kas', 'public') : null;
            $pathPimpinan = $request->hasFile('ttd_pimpinan') ? $request->file('ttd_pimpinan')->store('ttd_kas', 'public') : null;

            // Simpan Induk Laporan
            $kas = KasLaporan::create([
                'tanggal' => $request->tanggal,
                'pemasukan_sistem' => $pemasukan,
                'total_pengeluaran' => $totalPengeluaran,
                'saldo_bersih' => $saldoBersih,
                'nama_pembuat' => $request->nama_pembuat,
                'ttd_pembuat' => $pathPembuat,
                'nama_pimpinan' => $request->nama_pimpinan,
                'ttd_pimpinan' => $pathPimpinan,
            ]);

            // Simpan Rincian Pengeluaran Dinamis
            foreach ($request->pengeluaran as $item) {
                KasPengeluaran::create([
                    'kas_laporan_id' => $kas->id,
                    'keterangan' => $item['keterangan'],
                    'nominal' => $item['nominal'],
                ]);
            }

            DB::commit();
            return redirect()->route('kas.index')->with('success', 'Laporan Kas berhasil disimpan!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    // Menghapus Data
    public function destroy($id)
    {
        $kas = KasLaporan::findOrFail($id);
        
        // Hapus file gambar dari storage jika ada
        if ($kas->ttd_pembuat) Storage::disk('public')->delete($kas->ttd_pembuat);
        if ($kas->ttd_pimpinan) Storage::disk('public')->delete($kas->ttd_pimpinan);
        
        $kas->delete(); // Pengeluaran akan otomatis terhapus karena ON DELETE CASCADE di database

        return redirect()->route('kas.index')->with('success', 'Laporan Kas berhasil dihapus.');
    }

    // Untuk fitur Export PDF Single
    public function exportPdfSingle($id)
    {
        $kas = KasLaporan::with('pengeluaran')->findOrFail($id);
        // Buat view pdf_single (nanti kita buat di tahap selanjutnya jika dibutuhkan)
        // $pdf = Pdf::loadView('kas.pdf_single', compact('kas'));
        // return $pdf->stream('Laporan_Kas_'.$kas->tanggal.'.pdf');
        return "Fungsi PDF untuk ID {$id} siap! Silakan siapkan view blade-nya.";
    }

    // Menampilkan halaman Edit
    public function edit($id)
    {
        // Ambil data laporan beserta rincian pengeluarannya
        $kas = KasLaporan::with('pengeluaran')->findOrFail($id);
        
        return view('kas.edit', compact('kas'));
    }

    // Memproses update data ke database
    public function update(Request $request, $id)
    {
        $kas = KasLaporan::findOrFail($id);

        $request->validate([
            'tanggal' => 'required|date',
            'pengeluaran.*.keterangan' => 'required|string',
            'pengeluaran.*.nominal' => 'required|numeric',
        ]);

        DB::beginTransaction();
        try {
            // 1. Hitung ulang pengeluaran
            $totalPengeluaran = 0;
            if ($request->has('pengeluaran')) {
                foreach ($request->pengeluaran as $item) {
                    $totalPengeluaran += $item['nominal'];
                }
            }
            
            // Pemasukan tetap mengambil dari data lama yang sudah tersimpan
            $pemasukan = $request->pemasukan_sistem ?? $kas->pemasukan_sistem;
            $saldoBersih = $pemasukan - $totalPengeluaran;

            // 2. Handle Update TTD Pembuat (Hapus lama, simpan baru)
            if ($request->hasFile('ttd_pembuat')) {
                if ($kas->ttd_pembuat) Storage::disk('public')->delete($kas->ttd_pembuat);
                $kas->ttd_pembuat = $request->file('ttd_pembuat')->store('ttd_kas', 'public');
            }

            // 3. Handle Update TTD Pimpinan
            if ($request->hasFile('ttd_pimpinan')) {
                if ($kas->ttd_pimpinan) Storage::disk('public')->delete($kas->ttd_pimpinan);
                $kas->ttd_pimpinan = $request->file('ttd_pimpinan')->store('ttd_kas', 'public');
            }

            // 4. Update data Induk
            $kas->update([
                'tanggal' => $request->tanggal,
                'pemasukan_sistem' => $pemasukan,
                'total_pengeluaran' => $totalPengeluaran,
                'saldo_bersih' => $saldoBersih,
                'nama_pembuat' => $request->nama_pembuat,
                'nama_pimpinan' => $request->nama_pimpinan,
            ]);

            // 5. Hapus rincian pengeluaran lama, dan masukkan yang baru
            $kas->pengeluaran()->delete();
            if ($request->has('pengeluaran')) {
                foreach ($request->pengeluaran as $item) {
                    KasPengeluaran::create([
                        'kas_laporan_id' => $kas->id,
                        'keterangan' => $item['keterangan'],
                        'nominal' => $item['nominal'],
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('kas.index')->with('success', 'Laporan Kas berhasil diperbarui!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    // Menghapus Data
    public function destroy($id)
    {
        $kas = KasLaporan::findOrFail($id);
        
        // Hapus file gambar dari storage server agar tidak penuh
        if ($kas->ttd_pembuat) Storage::disk('public')->delete($kas->ttd_pembuat);
        if ($kas->ttd_pimpinan) Storage::disk('public')->delete($kas->ttd_pimpinan);
        
        $kas->delete(); // Pengeluaran akan otomatis terhapus karena efek CASCADE di database

        return redirect()->route('kas.index')->with('success', 'Laporan Kas berhasil dihapus.');
    }
}