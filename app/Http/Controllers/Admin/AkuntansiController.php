<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class AkuntansiController extends Controller
{
    /**
     * 1. INDEX: MENAMPILKAN JURNAL UMUM (Buku Besar)
     * Mengambil data dari tabel 'keuangans' yang sudah di-join dengan 'akun_keuangan'
     */
    public function index(Request $request)
    {
        // 1. QUERY DASAR (JOIN YANG DIPERBAIKI)
        $query = DB::table('keuangans')
            // KUNCI PERBAIKAN: Join menggunakan 2 kondisi (Kode & Unit)
            // Agar data tidak ganda (duplikat) saat kode akun sama beda unit
            ->leftJoin('akun_keuangan', function($join) {
                $join->on('keuangans.kode_akun', '=', 'akun_keuangan.kode_akun')
                     ->on('keuangans.unit_usaha', '=', 'akun_keuangan.unit_usaha');
            })
            ->select(
                'keuangans.*', 
                'akun_keuangan.nama_akun', 
                'akun_keuangan.kategori as kategori_akun'
            );

        // 2. FILTER: RENTANG TANGGAL
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('keuangans.tanggal', [$request->start_date, $request->end_date]);
        }

        // 3. FILTER: PENCARIAN (Invoice / Keterangan / Nama Akun)
        if ($request->filled('search')) {
            $keyword = $request->search;
            $query->where(function($q) use ($keyword) {
                $q->where('keuangans.nomor_invoice', 'like', "%$keyword%")
                  ->orWhere('keuangans.keterangan', 'like', "%$keyword%")
                  ->orWhere('akun_keuangan.nama_akun', 'like', "%$keyword%");
            });
        }

        // 4. EKSEKUSI DATA & PAGINATION
        $jurnal = $query->orderBy('keuangans.tanggal', 'desc')     // Urutkan Tanggal Terbaru
                        ->orderBy('keuangans.created_at', 'desc')  // Urutkan Jam Input Terbaru (jika tgl sama)
                        ->paginate(20)
                        ->withQueryString(); // PENTING: Agar filter tidak hilang saat pindah halaman

        // 5. HITUNG SALDO (TOTAL GLOBAL)
        // Hitung total keseluruhan (tanpa filter halaman) untuk ditampilkan di Card atas
        $saldo = [
            'total_masuk'  => DB::table('keuangans')->where('jenis', 'Pemasukan')->sum('jumlah'),
            'total_keluar' => DB::table('keuangans')->where('jenis', 'Pengeluaran')->sum('jumlah'),
        ];

        return view('admin.akuntansi.index', compact('jurnal', 'saldo'));
    }

    /**
     * 2. CREATE: FORM INPUT TRANSAKSI MANUAL
     * Mengambil Master Data COA untuk Dropdown
     */
    public function create()
    {
        // Ambil SEMUA akun, nanti difilter pakai JS di frontend
        $allAccounts = DB::table('akun_keuangan')
            ->orderBy('kode_akun')
            ->get();

        return view('admin.akuntansi.create', compact('allAccounts'));
    }

    /**
     * 3. STORE: SIMPAN TRANSAKSI MANUAL
     * Mencatat pengeluaran operasional (Gaji, Listrik, Sewa) atau Pemasukan Lainnya
     */
    public function store(Request $request)
    {
        $request->validate([
            'tanggal'     => 'required|date',
            'jenis'       => 'required|in:Pemasukan,Pengeluaran',
            'kode_akun'   => 'required|exists:akun_keuangan,kode_akun', // Wajib ada di master
            'jumlah'      => 'required|numeric|min:0',
            'keterangan'  => 'required|string',
        ]);

        // Ambil data detail akun untuk melengkapi kolom kategori & unit_usaha
        $masterAkun = DB::table('akun_keuangan')->where('kode_akun', $request->kode_akun)->first();

        DB::table('keuangans')->insert([
            'tanggal'       => $request->tanggal,
            'jenis'         => $request->jenis,
            'kategori'      => $masterAkun->kategori, // Otomatis dari Master
            'unit_usaha'    => $masterAkun->unit_usaha, // Otomatis dari Master
            'kode_akun'     => $masterAkun->kode_akun,
            'nomor_invoice' => 'MAN-' . time(), // Invoice Manual
            'keterangan'    => $request->keterangan . ' (' . $masterAkun->nama_akun . ')',
            'jumlah'        => $request->jumlah,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        return redirect()->route('admin.akuntansi.index')->with('success', 'Transaksi berhasil dicatat.');
    }

    /**
     * 4. SYNC OTOMATIS (THE ENGINE)
     * Menarik data dari tabel operasional ke tabel keuangan
     * Tombol ini ditekan user untuk "Posting Jurnal"
     */
    public function syncData()
    {
        DB::beginTransaction(); // Safety First: Jika error, batalkan semua

        try {
            $totalSynced = 0;

            // =========================================================================
            // A. SYNC EKSPEDISI (Tabel: Pesanan) -> Akun 4101 (Pendapatan Jasa Pengiriman)
            // =========================================================================
            $orders = DB::table('Pesanan')
                ->whereIn('status_pesanan', ['Selesai', 'Success', 'Delivered'])
                ->whereNotIn('nomor_invoice', function($q){
                    $q->select('nomor_invoice')->from('keuangans'); // Cek Duplikat
                })
                ->get();

            foreach ($orders as $row) {
                DB::table('keuangans')->insert([
                    'tanggal'       => date('Y-m-d', strtotime($row->tanggal_pesanan)),
                    'jenis'         => 'Pemasukan',
                    'kategori'      => 'Pendapatan Usaha',
                    'unit_usaha'    => 'Ekspedisi',
                    'kode_akun'     => '4101', // Hardcode sesuai COA Pendapatan Ekspedisi
                    'nomor_invoice' => $row->nomor_invoice,
                    'keterangan'    => "Pendapatan Resi: " . $row->resi . " (" . $row->expedition . ")",
                    'jumlah'        => $row->price, // Omzet Bruto
                    'created_at'    => now(),
                    'updated_at'    => now()
                ]);
                $totalSynced++;
            }

            // =========================================================================
            // B. SYNC PPOB (Tabel: ppob_transactions) -> Akun 4105 (Pendapatan Lain/PPOB)
            // =========================================================================
            // Asumsi: Kita buatkan akun bayangan atau pakai 4105 (Pendapatan Tambahan)
            // atau tambahkan kode 4106 khusus PPOB di master jika belum ada.
            
            $ppob = DB::table('ppob_transactions')
                ->whereIn('status', ['Success', 'Berhasil'])
                ->whereNotIn('order_id', function($q){
                    $q->select('nomor_invoice')->from('keuangans');
                })
                ->get();

            foreach ($ppob as $row) {
                // Logic Markup Profit: Harga Jual (Price + Margin)
                // Disini kita catat Omzet Penuh (Price + 50 perak margin contohnya)
                $omzet = $row->price + 50; 

                DB::table('keuangans')->insert([
                    'tanggal'       => date('Y-m-d', strtotime($row->created_at)),
                    'jenis'         => 'Pemasukan',
                    'kategori'      => 'Pendapatan Usaha',
                    'unit_usaha'    => 'Ekspedisi', // Atau Unit Lain
                    'kode_akun'     => '4105', // Masuk ke Pendapatan Tambahan/PPOB
                    'nomor_invoice' => $row->order_id,
                    // Gunakan 'buyer_sku_code' sesuai struktur database Anda
                    'keterangan' => "Trx PPOB: " . ($row->buyer_sku_code ?? 'PPOB') . " - " . $row->customer_no,
                    'jumlah'        => $omzet,
                    'created_at'    => now(),
                    'updated_at'    => now()
                ]);
                $totalSynced++;
            }

            // =========================================================================
            // C. SYNC MARKETPLACE (Tabel: order_marketplace) -> Akun 4102/4103
            // =========================================================================
            $market = DB::table('order_marketplace')
                ->where('status', 'completed')
                ->whereNotIn('invoice_number', function($q){
                    $q->select('nomor_invoice')->from('keuangans');
                })
                ->get();

            foreach ($market as $row) {
                // Tentukan Akun berdasarkan jenis produk jika perlu
                // Disini kita pukul rata ke 4101 (Pendapatan Usaha) atau akun khusus MP
                
                DB::table('keuangans')->insert([
                    'tanggal'       => date('Y-m-d', strtotime($row->created_at)),
                    'jenis'         => 'Pemasukan',
                    'kategori'      => 'Pendapatan Usaha',
                    'unit_usaha'    => 'Ekspedisi', // Asumsi usaha utama
                    'kode_akun'     => '4101', 
                    'nomor_invoice' => $row->invoice_number,
                    'keterangan'    => "Order MP: " . $row->customer_name,
                    'jumlah'        => $row->total_amount,
                    'created_at'    => now(),
                    'updated_at'    => now()
                ]);
                $totalSynced++;
            }

            DB::commit(); // Simpan Perubahan Permanen

            return redirect()->back()->with('success', "Sinkronisasi Selesai! $totalSynced transaksi baru telah dijurnal.");

        } catch (Exception $e) {
            DB::rollBack(); // Batalkan jika ada error
            return redirect()->back()->with('error', 'Terjadi kesalahan sinkronisasi: ' . $e->getMessage());
        }
    }

    /**
     * 5. EDIT & UPDATE (Koreksi Jurnal)
     */
    public function edit($id)
    {
        $data = DB::table('keuangans')->where('id', $id)->first();
        
        // Change: Send ALL accounts for JS filtering
        $allAccounts = DB::table('akun_keuangan')
            ->orderBy('kode_akun')
            ->get();

        return view('admin.akuntansi.edit', compact('data', 'allAccounts'));
    }

    public function update(Request $request, $id)
    {
        // Validasi dan Update mirip store...
        // Ini berguna jika ada salah posting akun
        $masterAkun = DB::table('akun_keuangan')->where('kode_akun', $request->kode_akun)->first();

        DB::table('keuangans')->where('id', $id)->update([
            'tanggal'       => $request->tanggal,
            'jenis'         => $request->jenis,
            'kategori'      => $masterAkun->kategori,
            'unit_usaha'    => $masterAkun->unit_usaha,
            'kode_akun'     => $masterAkun->kode_akun,
            'keterangan'    => $request->keterangan,
            'jumlah'        => $request->jumlah,
            'updated_at'    => now(),
        ]);

        return redirect()->route('admin.akuntansi.index')->with('success', 'Jurnal berhasil dikoreksi.');
    }

    public function destroy($id)
    {
        DB::table('keuangans')->where('id', $id)->delete();
        return back()->with('success', 'Jurnal dihapus.');
    }
}