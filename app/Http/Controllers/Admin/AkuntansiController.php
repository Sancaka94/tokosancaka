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
     * ==============================================================================
     * 1. INDEX: MENAMPILKAN JURNAL UMUM (Buku Besar)
     * ==============================================================================
     */
    /**
     * 1. INDEX: MENAMPILKAN JURNAL UMUM (Buku Besar)
     */
    public function index(Request $request)
    {
        // A. QUERY DASAR
        $query = DB::table('keuangans')
            ->leftJoin('akun_keuangan', function($join) {
                $join->on('keuangans.kode_akun', '=', 'akun_keuangan.kode_akun')
                     ->on('keuangans.unit_usaha', '=', 'akun_keuangan.unit_usaha');
            })
            ->select(
                'keuangans.*', 
                DB::raw('COALESCE(akun_keuangan.nama_akun, keuangans.kategori) as nama_akun_final'),
                'akun_keuangan.kategori as kelompok_akun' 
            );

        // B. FILTER: RENTANG TANGGAL
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('keuangans.tanggal', [$request->start_date, $request->end_date]);
        }

        // C. FILTER: PENCARIAN
        if ($request->filled('search')) {
            $keyword = $request->search;
            $query->where(function($q) use ($keyword) {
                $q->where('keuangans.nomor_invoice', 'like', "%$keyword%")
                  ->orWhere('keuangans.keterangan', 'like', "%$keyword%")
                  ->orWhere('keuangans.unit_usaha', 'like', "%$keyword%") 
                  ->orWhere('keuangans.kode_akun', 'like', "%$keyword%") 
                  ->orWhere('akun_keuangan.nama_akun', 'like', "%$keyword%");
            });
        }

        // D. EKSEKUSI DATA (Pagination)
        // Clone query untuk tabel (agar order by tidak mengganggu perhitungan saldo)
        $jurnalQuery = clone $query;
        $jurnal = $jurnalQuery->orderBy('keuangans.tanggal', 'desc')
                        ->orderBy('keuangans.created_at', 'desc')
                        ->paginate(20)
                        ->withQueryString();

        // E. HITUNG SALDO (PERBAIKAN LOGIKA)
        // Gunakan (clone $query) di setiap baris agar filter 'jenis' tidak saling menumpuk/konflik
        $saldo = [
            'total_masuk'  => (clone $query)->where('keuangans.jenis', 'Pemasukan')->sum('keuangans.jumlah'),
            'total_keluar' => (clone $query)->where('keuangans.jenis', 'Pengeluaran')->sum('keuangans.jumlah'),
        ];

        return view('admin.akuntansi.index', compact('jurnal', 'saldo'));
    }

    /**
     * ==============================================================================
     * 2. CREATE: FORM INPUT TRANSAKSI MANUAL
     * ==============================================================================
     */
    public function create()
    {
        // Kirim SEMUA data akun ke view.
        // Nanti JavaScript di Blade yang akan memfilter mana milik Ekspedisi/Percetakan.
        $allAccounts = DB::table('akun_keuangan')
            ->orderBy('unit_usaha')
            ->orderBy('kode_akun')
            ->get();

        return view('admin.akuntansi.create', compact('allAccounts'));
    }

    /**
     * ==============================================================================
     * 3. STORE: SIMPAN TRANSAKSI MANUAL
     * ==============================================================================
     */
    public function store(Request $request)
    {
        $request->validate([
            'tanggal'     => 'required|date',
            'unit_usaha'  => 'required', // Wajib ada input unit usaha dari Radio Button
            'jenis'       => 'required|in:Pemasukan,Pengeluaran',
            'kode_akun'   => 'required', // Kode Akun Wajib
            'jumlah'      => 'required|numeric|min:0',
            'keterangan'  => 'required|string',
        ]);

        // 1. CARI DETAIL AKUN YANG VALID (STRICT LOOKUP)
        // Cari di tabel master: Kode Akun 1101 MILIK Unit yang dipilih user
        $masterAkun = DB::table('akun_keuangan')
                        ->where('kode_akun', $request->kode_akun)
                        ->where('unit_usaha', $request->unit_usaha) // <--- KUNCI PERBAIKAN: Filter Unit
                        ->first();

        // Validasi jika user iseng inspect element mengirim kode yang salah/tidak sesuai unit
        if (!$masterAkun) {
            return back()->with('error', "Kode Akun {$request->kode_akun} tidak ditemukan untuk Unit Usaha {$request->unit_usaha}.");
        }

        // 2. SIMPAN KE DATABASE
        DB::table('keuangans')->insert([
            'tanggal'       => $request->tanggal,
            'jenis'         => $request->jenis,
            
            // Simpan Data Valid dari Master Akun
            'kode_akun'     => $masterAkun->kode_akun,
            'kategori'      => $masterAkun->nama_akun, // Nama Akun disimpan di kolom kategori (backward compatibility)
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
     * ==============================================================================
     * 4. EDIT FORM
     * ==============================================================================
     */
    public function edit($id)
    {
        $data = DB::table('keuangans')->where('id', $id)->first();
        
        if(!$data) {
            return back()->with('error', 'Data tidak ditemukan.');
        }

        // Kirim semua akun untuk dropdown edit (filtering handled by JS)
        $allAccounts = DB::table('akun_keuangan')
            ->orderBy('unit_usaha')
            ->orderBy('kode_akun')
            ->get();

        return view('admin.akuntansi.edit', compact('data', 'allAccounts'));
    }

    /**
     * ==============================================================================
     * 5. UPDATE: SIMPAN PERUBAHAN
     * ==============================================================================
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
                        ->where('unit_usaha', $request->unit_usaha) // <--- Filter Unit Lagi
                        ->first();

        if (!$masterAkun) {
            return back()->with('error', "Kode Akun tidak valid untuk unit usaha ini.");
        }

        // 2. UPDATE DATABASE
        DB::table('keuangans')->where('id', $id)->update([
            'tanggal'       => $request->tanggal,
            'jenis'         => $request->jenis,
            
            // Update detail akun baru sesuai master
            'kode_akun'     => $masterAkun->kode_akun,
            'kategori'      => $masterAkun->nama_akun,
            'unit_usaha'    => $masterAkun->unit_usaha,
            
            // Update data lain
            // 'nomor_invoice' => $request->nomor_invoice, // Uncomment jika ingin update invoice
            'keterangan'    => $request->keterangan,
            'jumlah'        => $request->jumlah,
            'updated_at'    => now(),
        ]);

        return redirect()->route('admin.akuntansi.index')->with('success', 'Jurnal berhasil dikoreksi.');
    }

    /**
     * ==============================================================================
     * 6. HAPUS TRANSAKSI
     * ==============================================================================
     */
    public function destroy($id)
    {
        DB::table('keuangans')->where('id', $id)->delete();
        return back()->with('success', 'Jurnal dihapus.');
    }

    /**
     * ==============================================================================
     * 7. SYNC OTOMATIS (OPSIONAL)
     * Menarik data dari tabel operasional
     * ==============================================================================
     */
    public function syncData()
    {
        DB::beginTransaction();
        try {
            $totalSynced = 0;

            // --- A. SYNC EKSPEDISI (Unit: Ekspedisi, Akun: 4101 Pendapatan Jasa) ---
            $orders = DB::table('Pesanan')
                ->whereIn('status_pesanan', ['Selesai', 'Success', 'Delivered'])
                ->whereNotIn('nomor_invoice', function($q){ $q->select('nomor_invoice')->from('keuangans'); })
                ->get();

            foreach ($orders as $row) {
                DB::table('keuangans')->insert([
                    'tanggal'       => date('Y-m-d', strtotime($row->tanggal_pesanan)),
                    'jenis'         => 'Pemasukan',
                    'kategori'      => 'Pendapatan Jasa Pengiriman', // Sesuai Nama Akun di DB
                    'unit_usaha'    => 'Ekspedisi',
                    'kode_akun'     => '4101', 
                    'nomor_invoice' => $row->nomor_invoice,
                    'keterangan'    => "Pendapatan Resi: " . $row->resi . " (" . $row->expedition . ")",
                    'jumlah'        => $row->price,
                    'created_at'    => now(),
                    'updated_at'    => now()
                ]);
                $totalSynced++;
            }

            // --- B. SYNC PPOB (Unit: Ekspedisi/Konter, Akun: 4105 Pendapatan Tambahan) ---
            $ppob = DB::table('ppob_transactions')
                ->whereIn('status', ['Success', 'Berhasil'])
                ->whereNotIn('order_id', function($q){ $q->select('nomor_invoice')->from('keuangans'); })
                ->get();

            foreach ($ppob as $row) {
                DB::table('keuangans')->insert([
                    'tanggal'       => date('Y-m-d', strtotime($row->created_at)),
                    'jenis'         => 'Pemasukan',
                    'kategori'      => 'Pendapatan Tambahan (Packing, dll)', // Sesuai DB
                    'unit_usaha'    => 'Ekspedisi', 
                    'kode_akun'     => '4105', 
                    'nomor_invoice' => $row->order_id,
                    'keterangan'    => "Trx PPOB: " . ($row->buyer_sku_code ?? 'PPOB') . " - " . $row->customer_no,
                    'jumlah'        => $row->price + 50, // Margin
                    'created_at'    => now(),
                    'updated_at'    => now()
                ]);
                $totalSynced++;
            }

            // --- C. SYNC MARKETPLACE (Unit: Ekspedisi, Akun: 4102 Pendapatan Cargo - Contoh) ---
            $market = DB::table('order_marketplace')
                ->where('status', 'completed')
                ->whereNotIn('invoice_number', function($q){ $q->select('nomor_invoice')->from('keuangans'); })
                ->get();

            foreach ($market as $row) {
                DB::table('keuangans')->insert([
                    'tanggal'       => date('Y-m-d', strtotime($row->created_at)),
                    'jenis'         => 'Pemasukan',
                    'kategori'      => 'Pendapatan Cargo', // Asumsi masuk sini atau buat akun baru
                    'unit_usaha'    => 'Ekspedisi', 
                    'kode_akun'     => '4102', 
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