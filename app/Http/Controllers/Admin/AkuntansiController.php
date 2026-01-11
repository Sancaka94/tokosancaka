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
     */
    public function index(Request $request)
    {
        $query = DB::table('keuangans')
            ->leftJoin('akun_keuangan', function($join) {
                $join->on('keuangans.kode_akun', '=', 'akun_keuangan.kode_akun')
                     ->on('keuangans.unit_usaha', '=', 'akun_keuangan.unit_usaha');
            })
            ->select(
                'keuangans.*', 
                'akun_keuangan.nama_akun', 
                'akun_keuangan.kategori as kategori_akun' // Kategori asli (Aset/Hutang)
            );

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('keuangans.tanggal', [$request->start_date, $request->end_date]);
        }

        if ($request->filled('search')) {
            $keyword = $request->search;
            $query->where(function($q) use ($keyword) {
                $q->where('keuangans.nomor_invoice', 'like', "%$keyword%")
                  ->orWhere('keuangans.keterangan', 'like', "%$keyword%")
                  ->orWhere('akun_keuangan.nama_akun', 'like', "%$keyword%");
            });
        }

        $jurnal = $query->orderBy('keuangans.tanggal', 'desc')
                        ->orderBy('keuangans.created_at', 'desc')
                        ->paginate(20)
                        ->withQueryString();

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
        $allAccounts = DB::table('akun_keuangan')
            ->orderBy('unit_usaha')
            ->orderBy('kode_akun')
            ->get();

        return view('admin.akuntansi.create', compact('allAccounts'));
    }

    /**
     * 3. STORE: SIMPAN TRANSAKSI MANUAL (PERBAIKAN)
     */
    public function store(Request $request)
    {
        $request->validate([
            'tanggal'     => 'required|date',
            'jenis'       => 'required|in:Pemasukan,Pengeluaran',
            'kode_akun'   => 'required|exists:akun_keuangan,kode_akun', // Pastikan kode ada di master
            'jumlah'      => 'required|numeric|min:0',
            'keterangan'  => 'required|string',
        ]);

        // 1. Cari Detail Akun berdasarkan Kode yang dikirim Blade
        $masterAkun = DB::table('akun_keuangan')
                        ->where('kode_akun', $request->kode_akun)
                        ->first();

        // 2. Simpan ke tabel Keuangans
        DB::table('keuangans')->insert([
            'tanggal'       => $request->tanggal,
            'jenis'         => $request->jenis,
            
            // PENTING: Simpan Kode Akun
            'kode_akun'     => $masterAkun->kode_akun, 
            
            // Simpan Nama Akun ke kolom 'kategori' (Agar konsisten dengan view laporan lama)
            'kategori'      => $masterAkun->nama_akun, 
            
            // Ambil Unit Usaha dari Master (Lebih aman daripada input user)
            'unit_usaha'    => $masterAkun->unit_usaha, 
            
            'nomor_invoice' => 'MAN-' . time(),
            'keterangan'    => $request->keterangan,
            'jumlah'        => $request->jumlah,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        return redirect()->route('admin.akuntansi.index')->with('success', 'Transaksi berhasil dicatat.');
    }

    /**
     * 4. SYNC OTOMATIS
     */
    public function syncData()
    {
        DB::beginTransaction();
        try {
            $totalSynced = 0;

            // A. SYNC EKSPEDISI (4101)
            $orders = DB::table('Pesanan')
                ->whereIn('status_pesanan', ['Selesai', 'Success', 'Delivered'])
                ->whereNotIn('nomor_invoice', function($q){ $q->select('nomor_invoice')->from('keuangans'); })
                ->get();

            foreach ($orders as $row) {
                DB::table('keuangans')->insert([
                    'tanggal'       => date('Y-m-d', strtotime($row->tanggal_pesanan)),
                    'jenis'         => 'Pemasukan',
                    'kategori'      => 'Pendapatan Jasa', // Nama Akun
                    'unit_usaha'    => 'Ekspedisi',
                    'kode_akun'     => '4101', // Kode Akun Pendapatan
                    'nomor_invoice' => $row->nomor_invoice,
                    'keterangan'    => "Pendapatan Resi: " . $row->resi . " (" . $row->expedition . ")",
                    'jumlah'        => $row->price,
                    'created_at'    => now(),
                    'updated_at'    => now()
                ]);
                $totalSynced++;
            }

            // B. SYNC PPOB (4105)
            $ppob = DB::table('ppob_transactions')
                ->whereIn('status', ['Success', 'Berhasil'])
                ->whereNotIn('order_id', function($q){ $q->select('nomor_invoice')->from('keuangans'); })
                ->get();

            foreach ($ppob as $row) {
                DB::table('keuangans')->insert([
                    'tanggal'       => date('Y-m-d', strtotime($row->created_at)),
                    'jenis'         => 'Pemasukan',
                    'kategori'      => 'Pendapatan Lain-lain',
                    'unit_usaha'    => 'Ekspedisi',
                    'kode_akun'     => '4105', 
                    'nomor_invoice' => $row->order_id,
                    'keterangan'    => "Trx PPOB: " . ($row->buyer_sku_code ?? 'PPOB') . " - " . $row->customer_no,
                    'jumlah'        => $row->price + 50,
                    'created_at'    => now(),
                    'updated_at'    => now()
                ]);
                $totalSynced++;
            }

            // C. SYNC MARKETPLACE (4101 / 4103)
            $market = DB::table('order_marketplace')
                ->where('status', 'completed')
                ->whereNotIn('invoice_number', function($q){ $q->select('nomor_invoice')->from('keuangans'); })
                ->get();

            foreach ($market as $row) {
                DB::table('keuangans')->insert([
                    'tanggal'       => date('Y-m-d', strtotime($row->created_at)),
                    'jenis'         => 'Pemasukan',
                    'kategori'      => 'Pendapatan Jasa',
                    'unit_usaha'    => 'Ekspedisi', 
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

    /**
     * 5. EDIT FORM
     */
    public function edit($id)
    {
        $data = DB::table('keuangans')->where('id', $id)->first();
        
        $allAccounts = DB::table('akun_keuangan')
            ->orderBy('unit_usaha')
            ->orderBy('kode_akun')
            ->get();

        return view('admin.akuntansi.edit', compact('data', 'allAccounts'));
    }

    /**
     * 6. UPDATE: SIMPAN PERUBAHAN (PERBAIKAN)
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'tanggal'     => 'required|date',
            'jenis'       => 'required|in:Pemasukan,Pengeluaran',
            'kode_akun'   => 'required|exists:akun_keuangan,kode_akun',
            'jumlah'      => 'required|numeric|min:0',
            'keterangan'  => 'required|string',
        ]);

        // 1. Cari Data Akun Baru (Jika user mengubah akun)
        $masterAkun = DB::table('akun_keuangan')
                        ->where('kode_akun', $request->kode_akun)
                        ->first();

        // 2. Update Database
        DB::table('keuangans')->where('id', $id)->update([
            'tanggal'       => $request->tanggal,
            'jenis'         => $request->jenis,
            
            // Update Kode Akun & Detailnya
            'kode_akun'     => $masterAkun->kode_akun,
            'kategori'      => $masterAkun->nama_akun, // Nama Akun disimpan di kategori
            'unit_usaha'    => $masterAkun->unit_usaha,
            
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