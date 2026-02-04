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
            $today = date('Y-m-d');
            $totalSynced = 0;

            // =========================================================================
            // 1. SYNC EKSPEDISI (Pesanan)
            // =========================================================================
            // Logic: Pemasukan (Omzet) & Pengeluaran (Modal) berdasarkan Diskon
            // =========================================================================

            // Ambil data Pesanan yang berubah HARI INI
            $orders = DB::table('Pesanan')
                ->whereDate('updated_at', $today) // Hanya data hari ini biar ringan
                ->whereIn('status_pesanan', ['Selesai', 'Success', 'Delivered', 'Terkirim'])
                ->get();

            // Ambil rules diskon untuk hitung profit
            $ekspedisiRules = DB::table('Ekspedisi')->get();

            foreach ($orders as $row) {
                // --- LOGIC HITUNG DISKON (Sama seperti sebelumnya) ---
                $diskonPersen = 0;
                $expStr = strtolower($row->expedition);

                foreach ($ekspedisiRules as $rule) {
                    if (str_contains($expStr, strtolower($rule->keyword))) {
                        $rules = json_decode($rule->diskon_rules, true);
                        if (is_array($rules)) {
                            foreach ($rules as $key => $val) {
                                if ($key !== 'default' && str_contains($expStr, $key)) {
                                    $diskonPersen = $val;
                                    break 2;
                                }
                            }
                            if (isset($rules['default'])) $diskonPersen = $rules['default'];
                        }
                        break;
                    }
                }

                $ongkirPublish = (float) $row->price; // Total bayar customer
                // Jika shipping_cost ada isinya, gunakan itu sebagai basis hitung diskon
                $basisDiskon   = (float) ($row->shipping_cost ?? $row->price);
                $nilaiDiskon   = $basisDiskon * $diskonPersen;
                $modalReal     = $ongkirPublish - $nilaiDiskon;

                if ($ongkirPublish > 0) {
                    // A. Update/Create PEMASUKAN (Kode 4101)
                    \App\Models\Keuangan::updateOrCreate(
                        [
                            'nomor_invoice' => $row->nomor_invoice,
                            'jenis'         => 'Pemasukan',
                            'kategori'      => 'Pendapatan Jasa Pengiriman' // Key unik
                        ],
                        [
                            'tanggal'       => date('Y-m-d', strtotime($row->tanggal_pesanan)),
                            'unit_usaha'    => 'Ekspedisi',
                            'kode_akun'     => '4101',
                            'keterangan'    => "Pendapatan Resi: " . $row->resi . " (" . $row->expedition . ")",
                            'jumlah'        => $ongkirPublish,
                            'updated_at'    => now()
                        ]
                    );

                    // B. Update/Create PENGELUARAN/MODAL (Agar Profit Benar)
                    \App\Models\Keuangan::updateOrCreate(
                        [
                            'nomor_invoice' => $row->nomor_invoice,
                            'jenis'         => 'Pengeluaran',
                            'kategori'      => 'Beban Ekspedisi' // Key unik
                        ],
                        [
                            'tanggal'       => date('Y-m-d', strtotime($row->tanggal_pesanan)),
                            'unit_usaha'    => 'Ekspedisi',
                            'kode_akun'     => '5101', // Asumsi Kode Akun Beban
                            'keterangan'    => "Setor Modal ke Pusat: " . $row->resi,
                            'jumlah'        => $modalReal,
                            'updated_at'    => now()
                        ]
                    );

                    $totalSynced++;
                }
            }

            // =========================================================================
            // 2. SYNC PPOB
            // =========================================================================
            $ppob = DB::table('ppob_transactions')
                ->whereDate('created_at', $today) // Filter Hari Ini
                ->whereIn('status', ['Success', 'Berhasil'])
                ->get();

            foreach ($ppob as $row) {
                \App\Models\Keuangan::updateOrCreate(
                    [
                        'nomor_invoice' => $row->order_id,
                        'jenis'         => 'Pemasukan',
                        'kategori'      => 'Pendapatan Tambahan (Packing, dll)'
                    ],
                    [
                        'tanggal'       => date('Y-m-d', strtotime($row->created_at)),
                        'unit_usaha'    => 'PPOB', // Atau Konter
                        'kode_akun'     => '4105',
                        'keterangan'    => "Trx PPOB: " . ($row->buyer_sku_code ?? 'PPOB') . " - " . $row->customer_no,
                        'jumlah'        => $row->price + 50, // Margin Profit
                        'updated_at'    => now()
                    ]
                );
                $totalSynced++;
            }

            // =========================================================================
            // 3. SYNC MARKETPLACE
            // =========================================================================
            $market = DB::table('order_marketplace')
                ->whereDate('created_at', $today) // Filter Hari Ini
                ->whereIn('status', ['completed', 'success'])
                ->get();

            foreach ($market as $row) {
                \App\Models\Keuangan::updateOrCreate(
                    [
                        'nomor_invoice' => $row->invoice_number,
                        'jenis'         => 'Pemasukan',
                        'kategori'      => 'Marketplace'
                    ],
                    [
                        'tanggal'       => date('Y-m-d', strtotime($row->created_at)),
                        'unit_usaha'    => 'Ekspedisi',
                        'kode_akun'     => '4102',
                        'keterangan'    => "Order MP: " . $row->customer_name,
                        'jumlah'        => $row->total_amount,
                        'updated_at'    => now()
                    ]
                );
                $totalSynced++;
            }

            DB::commit();
            return redirect()->back()->with('success', "Sinkronisasi Hari Ini Selesai! Data telah diperbarui.");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan sinkronisasi: ' . $e->getMessage());
        }
    }
}
