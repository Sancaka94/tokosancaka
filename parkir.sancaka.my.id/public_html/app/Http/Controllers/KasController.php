<?php

namespace App\Http\Controllers;

use App\Models\KasLaporan;
use App\Models\KasPengeluaran;
use App\Models\Transaction; // Pastikan model Transaction Anda di-import
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class KasController extends Controller
{
    /**
     * ==========================================
     * 1. INDEX: Menampilkan Riwayat & Filter
     * ==========================================
     */
    public function index(Request $request)
    {
        $query = KasLaporan::with('pengeluaran')->orderBy('tanggal_mulai', 'desc');

        // Jika user melakukan filter rentang tanggal
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->where('tanggal_mulai', '>=', $request->start_date)
                  ->where('tanggal_akhir', '<=', $request->end_date);
        }

        $laporanKas = $query->get();

        return view('kas.index', compact('laporanKas'));
    }

    /**
     * ==========================================
     * 2. AJAX: Ambil Pemasukan dari Sistem Parkir
     * ==========================================
     */
    public function getPemasukan(Request $request)
    {
        $request->validate([
            'tanggal_mulai' => 'required|date',
            'tanggal_akhir' => 'required|date',
        ]);

        // Menjumlahkan kolom tarif dari tabel parkir sesuai rentang tanggal
        $totalPemasukan = Transaction::whereDate('exit_time', '>=', $request->tanggal_mulai)
                            ->whereDate('exit_time', '<=', $request->tanggal_akhir)
                            ->sum(DB::raw('fee + IFNULL(toilet_fee, 0)'));

        return response()->json(['total' => (float) $totalPemasukan]);
    }

    /**
     * ==========================================
     * 3. CREATE & STORE: Input Kas Baru
     * ==========================================
     */
    public function create()
    {
        // Tampilan form (pemasukan di-load otomatis via AJAX saat tanggal dipilih)
        return view('kas.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'tanggal_mulai' => 'required|date',
            'tanggal_akhir' => 'required|date',
            'pengeluaran.*.keterangan' => 'required|string',
            'pengeluaran.*.nominal' => 'required|numeric',
            'pemasukan_sistem' => 'required|numeric',
        ]);

        DB::beginTransaction();
        try {
            // Hitung ulang total pengeluaran untuk keamanan backend
            $totalPengeluaran = 0;
            if ($request->has('pengeluaran')) {
                foreach ($request->pengeluaran as $item) {
                    $totalPengeluaran += $item['nominal'];
                }
            }
            
            $pemasukan = $request->pemasukan_sistem ?? 0;
            $saldoBersih = $pemasukan - $totalPengeluaran;

            // Proses Upload Tanda Tangan
            $pathPembuat = $request->hasFile('ttd_pembuat') ? $request->file('ttd_pembuat')->store('ttd_kas', 'public') : null;
            $pathPimpinan = $request->hasFile('ttd_pimpinan') ? $request->file('ttd_pimpinan')->store('ttd_kas', 'public') : null;

            // Simpan Data Induk
            $kas = KasLaporan::create([
                'tanggal_mulai' => $request->tanggal_mulai,
                'tanggal_akhir' => $request->tanggal_akhir,
                'pemasukan_sistem' => $pemasukan,
                'total_pengeluaran' => $totalPengeluaran,
                'saldo_bersih' => $saldoBersih,
                'nama_pembuat' => $request->nama_pembuat,
                'ttd_pembuat' => $pathPembuat,
                'nama_pimpinan' => $request->nama_pimpinan,
                'ttd_pimpinan' => $pathPimpinan,
            ]);

            // Simpan Rincian Pengeluaran Dinamis
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
            return redirect()->route('kas.index')->with('success', 'Laporan Kas berhasil disimpan!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * ==========================================
     * 4. EDIT & UPDATE: Revisi Kas
     * ==========================================
     */
    public function edit($id)
    {
        $kas = KasLaporan::with('pengeluaran')->findOrFail($id);
        return view('kas.edit', compact('kas'));
    }

    public function update(Request $request, $id)
    {
        $kas = KasLaporan::findOrFail($id);

        $request->validate([
            'tanggal_mulai' => 'required|date',
            'tanggal_akhir' => 'required|date',
            'pengeluaran.*.keterangan' => 'required|string',
            'pengeluaran.*.nominal' => 'required|numeric',
            'pemasukan_sistem' => 'required|numeric',
        ]);

        DB::beginTransaction();
        try {
            // Kalkulasi ulang
            $totalPengeluaran = 0;
            if ($request->has('pengeluaran')) {
                foreach ($request->pengeluaran as $item) {
                    $totalPengeluaran += $item['nominal'];
                }
            }
            
            $pemasukan = $request->pemasukan_sistem;
            $saldoBersih = $pemasukan - $totalPengeluaran;

            // Handle Update TTD Pembuat
            if ($request->hasFile('ttd_pembuat')) {
                if ($kas->ttd_pembuat) Storage::disk('public')->delete($kas->ttd_pembuat);
                $kas->ttd_pembuat = $request->file('ttd_pembuat')->store('ttd_kas', 'public');
            }

            // Handle Update TTD Pimpinan
            if ($request->hasFile('ttd_pimpinan')) {
                if ($kas->ttd_pimpinan) Storage::disk('public')->delete($kas->ttd_pimpinan);
                $kas->ttd_pimpinan = $request->file('ttd_pimpinan')->store('ttd_kas', 'public');
            }

            // Update Data Induk
            $kas->update([
                'tanggal_mulai' => $request->tanggal_mulai,
                'tanggal_akhir' => $request->tanggal_akhir,
                'pemasukan_sistem' => $pemasukan,
                'total_pengeluaran' => $totalPengeluaran,
                'saldo_bersih' => $saldoBersih,
                'nama_pembuat' => $request->nama_pembuat,
                'nama_pimpinan' => $request->nama_pimpinan,
            ]);

            // Hapus rincian pengeluaran lama, lalu simpan yang baru
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

    /**
     * ==========================================
     * 5. DESTROY: Hapus Data
     * ==========================================
     */
    public function destroy($id)
    {
        $kas = KasLaporan::findOrFail($id);
        
        // Bersihkan foto tanda tangan di storage
        if ($kas->ttd_pembuat) Storage::disk('public')->delete($kas->ttd_pembuat);
        if ($kas->ttd_pimpinan) Storage::disk('public')->delete($kas->ttd_pimpinan);
        
        $kas->delete(); // Pengeluaran otomatis terhapus jika pakai cascade

        return redirect()->route('kas.index')->with('success', 'Laporan Kas berhasil dihapus.');
    }

    /**
     * ==========================================
     * 6. EXPORT PDF: Cetak Satu Laporan
     * ==========================================
     */
    public function exportPdfSingle($id)
    {
        $kas = KasLaporan::with('pengeluaran')->findOrFail($id);
        
        $pdf = Pdf::loadView('kas.pdf_single', compact('kas'));
        return $pdf->setPaper('a4', 'portrait')->stream('Laporan_Kas_'.$kas->tanggal_mulai.'_sd_'.$kas->tanggal_akhir.'.pdf');
    }
}