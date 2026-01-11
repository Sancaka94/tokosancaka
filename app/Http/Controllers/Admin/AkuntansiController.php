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
     * Versi Awal (Original)
     */
    public function index(Request $request)
    {
        // 1. QUERY DASAR (JOIN YANG DIPERBAIKI)
        $query = DB::table('keuangans')
            // Join menggunakan 2 kondisi (Kode & Unit) agar tidak duplikat
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

        // 3. FILTER: PENCARIAN
        if ($request->filled('search')) {
            $keyword = $request->search;
            $query->where(function($q) use ($keyword) {
                $q->where('keuangans.nomor_invoice', 'like', "%$keyword%")
                  ->orWhere('keuangans.keterangan', 'like', "%$keyword%")
                  ->orWhere('akun_keuangan.nama_akun', 'like', "%$keyword%");
            });
        }

        // 4. EKSEKUSI DATA & PAGINATION
        $jurnal = $query->orderBy('keuangans.tanggal', 'desc')
                        ->orderBy('keuangans.created_at', 'desc')
                        ->paginate(20)
                        ->withQueryString();

        // 5. HITUNG SALDO (TOTAL GLOBAL - Tanpa Filter Halaman)
        $saldo = [
            'total_masuk'  => DB::table('keuangans')->where('jenis', 'Pemasukan')->sum('jumlah'),
            'total_keluar' => DB::table('keuangans')->where('jenis', 'Pengeluaran')->sum('jumlah'),
        ];

        return view('admin.akuntansi.index', compact('jurnal', 'saldo'));
    }

    /**
     * 2. CREATE: FORM INPUT TRANSAKSI MANUAL
     */
    public function create()
    {
        // Kirim SEMUA data akun ke view
        // Nanti JavaScript di Blade yang akan memfilter mana milik Ekspedisi/Percetakan
        $allAccounts = DB::table('akun_keuangan')
            ->orderBy('unit_usaha')
            ->orderBy('kode_akun')
            ->get();

        return view('admin.akuntansi.create', compact('allAccounts'));
    }

    /**
     * 3. STORE: SIMPAN TRANSAKSI MANUAL (PERBAIKAN LOGIKA)
     */
    public function store(Request $request)
    {
        $request->validate([
            'tanggal'     => 'required|date',
            'unit_usaha'  => 'required', // Wajib ada input unit usaha
            'jenis'       => 'required|in:Pemasukan,Pengeluaran',
            'kode_akun'   => 'required', // Kode Akun Wajib
            'jumlah'      => 'required|numeric|min:0',
            'keterangan'  => 'required|string',
        ]);

        // 1. CARI DETAIL AKUN YANG VALID
        // Cari di tabel master: Kode Akun 1101 MILIK Percetakan (contohnya)
        $masterAkun = DB::table('akun_keuangan')
                        ->where('kode_akun', $request->kode_akun)
                        ->where('unit_usaha', $request->unit_usaha) // <--- KUNCI PERBAIKAN
                        ->first();

        // Validasi jika user iseng inspect element mengirim kode yang salah
        if (!$masterAkun) {
            return back()->with('error', "Kode Akun {$request->kode_akun} tidak ditemukan untuk Unit Usaha {$request->unit_usaha}.");
        }

        // 2. SIMPAN KE DATABASE
        DB::table('keuangans')->insert([
            'tanggal'       => $request->tanggal,
            'jenis'         => $request->jenis,
            
            // Simpan Data Valid dari Master
            'kode_akun'     => $masterAkun->kode_akun,
            'kategori'      => $masterAkun->nama_akun, // Nama Akun disimpan di kolom kategori
            'unit_usaha'    => $masterAkun->unit_usaha,
            
            'nomor_invoice' => $request->nomor_invoice ?? 'MAN-' . time(),
            'keterangan'    => $request->keterangan,
            'jumlah'        => $request->jumlah,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        return redirect()->route('admin.akuntansi.index')
                         ->with('success', "Transaksi berhasil dicatat untuk unit {$masterAkun->unit_usaha}.");
    }

    /**
     * 4. EDIT FORM
     */
    public function edit($id)
    {
        $data = DB::table('keuangans')->where('id', $id)->first();
        
        if(!$data) {
            return back()->with('error', 'Data tidak ditemukan.');
        }

        // Kirim semua akun untuk dropdown edit
        $allAccounts = DB::table('akun_keuangan')
            ->orderBy('unit_usaha')
            ->orderBy('kode_akun')
            ->get();

        return view('admin.akuntansi.edit', compact('data', 'allAccounts'));
    }

    /**
     * 5. UPDATE: SIMPAN PERUBAHAN (PERBAIKAN LOGIKA)
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'tanggal'     => 'required|date',
            'unit_usaha'  => 'required', 
            'jenis'       => 'required|in:Pemasukan,Pengeluaran',
            'kode_akun'   => 'required',
            'jumlah'      => 'required|numeric|min:0',
            'keterangan'  => 'required|string',
        ]);

        // 1. CARI LAGI DETAIL AKUN (Jaga-jaga user ganti unit/akun saat edit)
        $masterAkun = DB::table('akun_keuangan')
                        ->where('kode_akun', $request->kode_akun)
                        ->where('unit_usaha', $request->unit_usaha) // <--- KUNCI PERBAIKAN
                        ->first();

        if (!$masterAkun) {
            return back()->with('error', "Kode Akun tidak valid untuk unit usaha ini.");
        }

        // 2. UPDATE DATABASE
        DB::table('keuangans')->where('id', $id)->update([
            'tanggal'       => $request->tanggal,
            'jenis'         => $request->jenis,
            
            // Update detail akun baru
            'kode_akun'     => $masterAkun->kode_akun,
            'kategori'      => $masterAkun->nama_akun,
            'unit_usaha'    => $masterAkun->unit_usaha,
            
            'nomor_invoice' => $request->nomor_invoice, // Jika ada input invoice di edit
            'keterangan'    => $request->keterangan,
            'jumlah'        => $request->jumlah,
            'updated_at'    => now(),
        ]);

        return redirect()->route('admin.akuntansi.index')->with('success', 'Jurnal berhasil dikoreksi.');
    }

    /**
     * 6. HAPUS TRANSAKSI
     */
    public function destroy($id)
    {
        DB::table('keuangans')->where('id', $id)->delete();
        return back()->with('success', 'Jurnal dihapus.');
    }

    /**
     * 7. SYNC OTOMATIS (OPSIONAL - LOGIKA LAMA)
     * Tetap dipertahankan untuk menarik data otomatis dari tabel pesanan/ppob
     */
    public function syncData()
    {
        DB::beginTransaction();
        try {
            $totalSynced = 0;

            // A. SYNC EKSPEDISI (4101 - Pendapatan Jasa)
            // Asumsi: Unit Usaha = Ekspedisi
            $orders = DB::table('Pesanan')
                ->whereIn('status_pesanan', ['Selesai', 'Success', 'Delivered'])
                ->whereNotIn('nomor_invoice', function($q){ $q->select('nomor_invoice')->from('keuangans'); })
                ->get();

            foreach ($orders as $row) {
                DB::table('keuangans')->insert([
                    'tanggal'       => date('Y-m-d', strtotime($row->tanggal_pesanan)),
                    'jenis'         => 'Pemasukan',
                    'kategori'      => 'Pendapatan Jasa', // Nama Akun Default
                    'unit_usaha'    => 'Ekspedisi',       // Unit Default
                    'kode_akun'     => '4101',            // Kode Default Pendapatan
                    'nomor_invoice' => $row->nomor_invoice,
                    'keterangan'    => "Pendapatan Resi: " . $row->resi . " (" . $row->expedition . ")",
                    'jumlah'        => $row->price,
                    'created_at'    => now(),
                    'updated_at'    => now()
                ]);
                $totalSynced++;
            }

            // B. SYNC PPOB (4105 - Pendapatan Lain)
            $ppob = DB::table('ppob_transactions')
                ->whereIn('status', ['Success', 'Berhasil'])
                ->whereNotIn('order_id', function($q){ $q->select('nomor_invoice')->from('keuangans'); })
                ->get();

            foreach ($ppob as $row) {
                DB::table('keuangans')->insert([
                    'tanggal'       => date('Y-m-d', strtotime($row->created_at)),
                    'jenis'         => 'Pemasukan',
                    'kategori'      => 'Pendapatan Lain-lain',
                    'unit_usaha'    => 'Ekspedisi', // Asumsi PPOB masuk unit Ekspedisi/Pusat
                    'kode_akun'     => '4105', 
                    'nomor_invoice' => $row->order_id,
                    'keterangan'    => "Trx PPOB: " . ($row->buyer_sku_code ?? 'PPOB') . " - " . $row->customer_no,
                    'jumlah'        => $row->price + 50, // Margin contoh
                    'created_at'    => now(),
                    'updated_at'    => now()
                ]);
                $totalSynced++;
            }

            // C. SYNC MARKETPLACE
            $market = DB::table('order_marketplace')
                ->where('status', 'completed')
                ->whereNotIn('invoice_number', function($q){ $q->select('nomor_invoice')->from('keuangans'); })
                ->get();

            foreach ($market as $row) {
                DB::table('keuangans')->insert([
                    'tanggal'       => date('Y-m-d', strtotime($row->created_at)),
                    'jenis'         => 'Pemasukan',
                    'kategori'      => 'Pendapatan Jasa',
                    'unit_usaha'    => 'Ekspedisi', // Atau Marketplace jika ada unit khusus
                    'kode_akun'     => '4101', 
                    'nomor_invoice' => $row->invoice_number,
                    'keterangan'    => "Order MP: " . $row->customer_name,
                    'jumlah'        => $row->total_amount,
                    'created_at'    => now(),
                    'updated_at'    => now()
                ]);
                $totalSynced++;
            }

            DB::commit();
            return redirect()->back()->with('success', "Sinkronisasi Selesai! $totalSynced transaksi baru telah dijurnal.");

        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan sinkronisasi: ' . $e->getMessage());
        }
    }
}