<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Keuangan;
use App\Models\Pesanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Exports\KeuanganExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;


class KeuanganController extends Controller
{
    /**
     * FUNGSI KHUSUS: PENGOLAH DATA PUSAT
     * Fungsi ini memuat SEMUA logika lama Anda (Query, Filter Search, Filter Tanggal, Hitung JSON).
     * Dipisahkan agar bisa dipakai oleh Index (Tabel), Excel, dan PDF sekaligus.
     */
    private function getDataLengkap(Request $request)
    {
        // ==================================================================================
        // 1. QUERY DASAR (SAMA PERSIS SEPERTI KODE LAMA)
        // ==================================================================================

        // A. Manual Query
        $manualQuery = DB::table('keuangans')
            ->select('id', 'tanggal', 'jenis', 'kategori', 'nomor_invoice', 'keterangan',
            DB::raw("CASE WHEN jenis = 'Pemasukan' THEN jumlah ELSE 0 END as omzet"),
            DB::raw("CASE WHEN jenis = 'Pengeluaran' THEN jumlah ELSE 0 END as modal"),
            DB::raw("CASE WHEN jenis = 'Pemasukan' THEN jumlah ELSE -jumlah END as profit"));

        // B. PPOB Query
        $ppobQuery = DB::table('ppob_transactions')
            ->whereIn('status', ['Success', 'Lunas', 'Berhasil', 'success'])
            ->select('id', DB::raw('DATE(created_at) as tanggal'), DB::raw("'Pemasukan' as jenis"), DB::raw("'PPOB' as kategori"),
            'order_id as nomor_invoice', DB::raw("CONCAT(buyer_sku_code, ' - ', customer_no) as keterangan"),
            DB::raw('(price + 50) as omzet'), 'price as modal', DB::raw('50 as profit'));

        // C. Top Up Saldo Query
        $topupQuery = DB::table('transactions')
            ->where('type', 'topup')->whereIn('status', ['success', 'paid', 'lunas', 'berhasil'])
            ->select('id', DB::raw('DATE(created_at) as tanggal'), DB::raw("'Pemasukan' as jenis"), DB::raw("'Top Up Saldo' as kategori"),
            'reference_id as nomor_invoice', 'description as keterangan', 'amount as omzet', 'amount as modal', DB::raw('0 as profit'));

        // D. Marketplace Query
        $marketplaceQuery = DB::table('order_marketplace')
            ->whereIn('status', ['completed', 'success', 'delivered', 'selesai', 'terkirim', 'lunas'])
            ->select('id', DB::raw('DATE(created_at) as tanggal'), DB::raw("'Pemasukan' as jenis"), DB::raw("'Marketplace' as kategori"),
            'invoice_number as nomor_invoice', DB::raw("CONCAT(shipping_method, ' - ', COALESCE(shipping_resi, '-')) as keterangan"),
            'total_amount as omzet', DB::raw('(shipping_cost + insurance_cost) as modal'), DB::raw('(total_amount - (shipping_cost + insurance_cost)) as profit'));

        // E. EKSPEDISI QUERY (Ambil Data Mentah)
        $ekspedisiQuery = DB::table('Pesanan')
            ->whereIn('status_pesanan', ['Selesai', 'Terkirim', 'Lunas', 'Delivered', 'Success', 'success'])
            ->select(
                DB::raw("id_pesanan as id"),
                DB::raw('DATE(tanggal_pesanan) as tanggal'),
                DB::raw("'Pemasukan' as jenis"),
                DB::raw("'Ekspedisi' as kategori"),
                'nomor_invoice',
                DB::raw("CONCAT(resi, ' (', expedition, ')') as keterangan"),
                'price as omzet',
                'shipping_cost',
                'insurance_cost',
                'expedition',
                'service_type',
                DB::raw('0 as modal'),
                DB::raw('0 as profit')
            );


        if ($request->filled('search')) {
            $keyword = $request->search;

            $manualQuery->where(function($q) use ($keyword) {
                 $q->where('nomor_invoice', 'like', "%$keyword%")->orWhere('keterangan', 'like', "%$keyword%")->orWhere('kategori', 'like', "%$keyword%");
            });
            $ppobQuery->where(function($q) use ($keyword) {
                 $q->where('order_id', 'like', "%$keyword%")->orWhere('customer_no', 'like', "%$keyword%")->orWhereRaw("'PPOB' LIKE ?", ["%$keyword%"]);
            });
            $topupQuery->where(function($q) use ($keyword) {
                 $q->where('reference_id', 'like', "%$keyword%")->orWhereRaw("'Top Up Saldo' LIKE ?", ["%$keyword%"]);
            });
            $marketplaceQuery->where(function($q) use ($keyword) {
                 $q->where('invoice_number', 'like', "%$keyword%")->orWhereRaw("'Marketplace' LIKE ?", ["%$keyword%"]);
            });
            $ekspedisiQuery->where(function($q) use ($keyword) {
                $q->where('nomor_invoice', 'like', "%$keyword%")
                  ->orWhere('resi', 'like', "%$keyword%")
                  ->orWhere('expedition', 'like', "%$keyword%")
                  ->orWhereRaw("'Ekspedisi' LIKE ?", ["%$keyword%"]);
            });
        }

        if ($request->filled('date_range')) {
            $rawDate = str_replace([' - ', ' s.d. '], ' to ', $request->date_range);
            $dates = explode(' to ', $rawDate);
            if (count($dates) >= 2) {
                $start = $dates[0]; $end = $dates[1];
                $manualQuery->whereBetween('tanggal', [$start, $end]);
                $ppobQuery->whereBetween(DB::raw('DATE(created_at)'), [$start, $end]);
                $topupQuery->whereBetween(DB::raw('DATE(created_at)'), [$start, $end]);
                $marketplaceQuery->whereBetween(DB::raw('DATE(created_at)'), [$start, $end]);
                $ekspedisiQuery->whereBetween(DB::raw('DATE(tanggal_pesanan)'), [$start, $end]);
            }
        }

        // ==================================================================================
        // 3. PROSES HITUNG PROFIT EKSPEDISI / JSON (SAMA PERSIS SEPERTI KODE LAMA)
        // ==================================================================================

        $diskonRules = DB::table('Ekspedisi')->whereNotNull('keyword')->get();
        $ekspedisiData = $ekspedisiQuery->get();

        $processedEkspedisi = $ekspedisiData->map(function($row) use ($diskonRules) {
            $diskonPersen = 0;
            $expStr = strtolower($row->expedition);

            foreach ($diskonRules as $rule) {
                if (str_contains($expStr, strtolower($rule->keyword))) {
                    $rules = json_decode($rule->diskon_rules, true);
                    if (is_array($rules)) {
                        foreach ($rules as $key => $val) {
                            if ($key !== 'default' && str_contains($expStr, $key)) {
                                $diskonPersen = $val;
                                break 2;
                            }
                        }
                        if (isset($rules['default'])) {
                            $diskonPersen = $rules['default'];
                        }
                    }
                    break;
                }
            }

            $ongkirPublish = $row->shipping_cost;
            $ongkirReal    = $ongkirPublish - ($ongkirPublish * $diskonPersen);

            $row->modal  = $ongkirReal + $row->insurance_cost;
            $row->profit = $row->omzet - $row->modal;

            return $row;
        });

        // ==================================================================================
        // 4. GABUNGKAN DATA (MERGE)
        // ==================================================================================

        $othersData = $manualQuery
            ->unionAll($ppobQuery)
            ->unionAll($topupQuery)
            ->unionAll($marketplaceQuery)
            ->get();

        $allData = $othersData->merge($processedEkspedisi);
        $sortedData = $allData->sortByDesc('tanggal');

        return $sortedData; // Mengembalikan Collection Data Lengkap
    }

    /**
     * HALAMAN UTAMA (INDEX)
     * Menggunakan Logic Lama untuk Pagination & Summary Card
     */
    public function index(Request $request)
    {
        // 1. Ambil Data dari Fungsi Pusat (Logic Lama)
        $allData = $this->getDataLengkap($request);

        // 2. Pagination Manual (Logic Lama)
        $page = $request->input('page', 1);
        $perPage = 15;
        $offset = ($page * $perPage) - $perPage;

        $transaksi = new LengthAwarePaginator(
            $allData->slice($offset, $perPage)->values(),
            $allData->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // 3. Summary untuk Card (Logic Lama)
        $summary = [
            'omzet'  => $allData->sum('omzet'),
            'modal'  => $allData->sum('modal'),
            'profit' => $allData->sum('profit'),
            'ekspedisi' => ['omzet' => $allData->where('kategori', 'Ekspedisi')->sum('omzet'), 'count' => $allData->where('kategori', 'Ekspedisi')->count()],
            'ppob' => ['omzet' => $allData->where('kategori', 'PPOB')->sum('omzet'), 'count' => $allData->where('kategori', 'PPOB')->count()],
            'marketplace' => ['omzet' => $allData->where('kategori', 'Marketplace')->sum('omzet'), 'count' => $allData->where('kategori', 'Marketplace')->count()],
            'topup' => ['omzet' => $allData->where('kategori', 'Top Up Saldo')->sum('omzet'), 'count' => $allData->where('kategori', 'Top Up Saldo')->count()],
        ];

        // Pastikan baris ini ada:
        $allAccounts = DB::table('akun_keuangan')
                    ->orderBy('unit_usaha') // Urutkan biar grouping rapi
                    ->orderBy('kode_akun')
                    ->get();

        return view('admin.keuangan.index', compact('transaksi', 'summary', 'allAccounts'));
    }

    /**
     * FITUR BARU: EXPORT EXCEL
     * Menggunakan Logic yang SAMA dengan Index, tapi di-download
     */
    public function exportExcel(Request $request)
    {
        $data = $this->getDataLengkap($request);
        $fileName = 'Laporan_Keuangan_' . date('Y-m-d_H-i') . '.xlsx';
        return Excel::download(new KeuanganExport($data), $fileName);
    }

    /**
     * FITUR BARU: EXPORT PDF
     * Menggunakan Logic yang SAMA dengan Index, tapi di-download PDF
     */
    public function exportPdf(Request $request)
    {
        $data = $this->getDataLengkap($request);

        // Hitung ulang summary kecil untuk Header PDF
        $summary = [
            'omzet' => $data->sum('omzet'),
            'modal' => $data->sum('modal'),
            'profit' => $data->sum('profit'),
        ];

        $pdf = Pdf::loadView('admin.keuangan.pdf', compact('data', 'summary'));
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('Laporan_Keuangan_' . date('Y-m-d') . '.pdf');
    }

    /**
     * FITUR BARU: SYNC MANUAL (UPDATE OR CREATE)
     * Bisa diklik berkali-kali. Jika data sudah ada, akan di-update (memperbaiki NULL).
     */
    public function syncHariIni()
    {
        try {
            $today = date('Y-m-d');

            // 1. Ambil Pesanan Hari Ini yang sudah ada Resi
            $orders = Pesanan::whereDate('updated_at', $today)
                        ->whereNotNull('resi')
                        ->where('resi', '!=', '')
                        ->get();

            if ($orders->isEmpty()) {
                return back()->with('info', "Tidak ada transaksi ber-resi yang diproses pada tanggal $today.");
            }

            $ekspedisiRules = DB::table('Ekspedisi')->get();
            $count = 0;

            DB::beginTransaction();

            foreach ($orders as $pesanan) {

                // --- LOGIKA HITUNG DISKON (Sama seperti sebelumnya) ---
                $diskonPersen = 0;
                $expStr = strtolower($pesanan->expedition);

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

                $ongkirPublish = (float) $pesanan->shipping_cost;
                $nilaiDiskon   = $ongkirPublish * $diskonPersen;
                $modalReal     = $ongkirPublish - $nilaiDiskon;

                if ($ongkirPublish > 0) {

                    // ==========================================================
                    // LOGIKA UPDATE OR CREATE (AGAR BISA DI-KLIK BERKALI-KALI)
                    // ==========================================================

                    // 1. Handle PEMASUKAN (OMZET)
                    // Cari data berdasarkan array pertama, Update data berdasarkan array kedua
                    Keuangan::updateOrCreate(
                        [
                            'nomor_invoice' => $pesanan->nomor_invoice, // Kunci Unik 1
                            'jenis'         => 'Pemasukan',             // Kunci Unik 2
                            'kategori'      => 'Ekspedisi'              // Kunci Unik 3
                        ],
                        [
                            'kode_akun'     => '1101',          // Data yang akan di-update/insert
                            'unit_usaha'    => 'Ekspedisi',     // Data yang akan di-update/insert
                            'tanggal'       => $today,
                            'keterangan'    => "Sync: Omzet Order " . $pesanan->expedition . " - Resi: " . $pesanan->resi,
                            'jumlah'        => $ongkirPublish
                        ]
                    );

                    // 2. Handle PENGELUARAN (MODAL)
                    Keuangan::updateOrCreate(
                        [
                            'nomor_invoice' => $pesanan->nomor_invoice, // Kunci Unik 1
                            'jenis'         => 'Pengeluaran',           // Kunci Unik 2
                            'kategori'      => 'Ekspedisi'              // Kunci Unik 3
                        ],
                        [
                            'kode_akun'     => '1101',          // Data yang akan di-update/insert
                            'unit_usaha'    => 'Ekspedisi',     // Data yang akan di-update/insert
                            'tanggal'       => $today,
                            'keterangan'    => "Sync: Setor Modal " . $pesanan->expedition . " (Diskon " . ($diskonPersen * 100) . "%)",
                            'jumlah'        => $modalReal
                        ]
                    );

                    $count++;
                }
            }
            DB::commit();

            return back()->with('success', "Sinkronisasi Selesai! {$count} data pesanan hari ini telah diperbarui/ditambahkan.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal sinkronisasi: ' . $e->getMessage());
        }
    }

    // CRUD Manual (SAMA SEPERTI LAMA)
    // SIMPAN DATA (CREATE)
    public function store(Request $request)
    {
        // 1. Validasi Input
        $request->validate([
            'tanggal'   => 'required|date',
            'jenis'     => 'required|in:Pemasukan,Pengeluaran',
            'kategori'  => 'required|string',
            'jumlah'    => 'required|numeric|min:0',
            'keterangan'=> 'nullable|string',
        ]);

        // 2. Logic Simpan Dinamis
        // Kita gunakan 'updateOrCreate' agar jika ada ID, dia update. Jika tidak, dia create baru.
        // Ini trik agar satu fungsi bisa dipakai buat Tambah & Edit sekaligus.
        
        $id = $request->input('id'); // Ambil ID dari hidden input (jika ada)

        $data = \App\Models\Keuangan::updateOrCreate(
            ['id' => $id], // Kunci pencarian (jika null, buat baru)
            [
                'tanggal'       => $request->tanggal,
                'jenis'         => $request->jenis,     // Pemasukan/Pengeluaran
                'kategori'      => $request->kategori,  // Aset Tetap, Hutang, Modal, dll
                'keterangan'    => $request->keterangan,
                'jumlah'        => $request->jumlah,
                
                // DATA TAMBAHAN OTOMATIS
                'unit_usaha'    => 'Pusat', 
                'nomor_invoice' => 'NERACA-' . date('ymdHis'), // Invoice Unik
                'kode_akun'     => 'NERACA', // FLAG PENTING: Penanda ini data Neraca
            ]
        );

        return back()->with('success', 'Data Neraca berhasil disimpan/diperbarui.');
    }

    public function update(Request $request, $id) { Keuangan::find($id)->update($request->all()); return back()->with('success','Diupdate'); }

    public function destroy($id)
    {
        // Pakai forceDelete() biar data benar-benar musnah dari muka bumi
        \App\Models\Keuangan::where('id', $id)->forceDelete();

        return redirect()->back()->with('success', 'Data berhasil dihapus.');
    }

    public function neraca(Request $request)
{
    // 1. SETUP TANGGAL
    $startDate = $request->input('date_start', date('Y-01-01'));
    $endDate   = $request->input('date_end', date('Y-m-d'));

    // 2. AMBIL SEMUA DATA LENGKAP
    $allData = $this->getDataLengkap($request);

    // =========================================================================
    // 3. LOGIKA FILTER PROFIT (HANYA OPERASIONAL)
    // =========================================================================
    // Filter data agar Profit Berjalan HANYA menghitung transaksi bisnis.
    // Data dengan kode_akun 'NERACA' (inputan manual Anda) dibuang dari Profit.
    
    $dataProfit = $allData->filter(function ($item) {
        $kategoriOperasional = ['Ekspedisi', 'PPOB', 'Marketplace', 'Top Up Saldo'];
        $isManualNeraca = isset($item->kode_akun) && $item->kode_akun === 'NERACA';
        
        return in_array($item->kategori, $kategoriOperasional) && !$isManualNeraca;
    });

    $profitReal = $dataProfit->sum('profit');

    // =========================================================================
    // 4. LOGIKA FILTER NERACA (HARTA & MODAL)
    // =========================================================================
    // Ambil data yang khusus Anda input secara manual (kategori Kas, Bank, dll)
    
    $dataManual = $allData->filter(function ($item) {
        return isset($item->kode_akun) && $item->kode_akun === 'NERACA';
    });

    // Hitung Kelompok Aset
    $kasBank = $dataManual->whereIn('kategori', ['Kas Tunai', 'Kas', 'Bank BCA', 'Bank BRI', 'E-Wallet', 'Kas Besar'])->sum('jumlah');
    
    $asetTetap = $dataManual->whereIn('kategori', ['Aset Tetap', 'Investasi', 'Bangunan', 'Kendaraan'])
                            ->groupBy('keterangan')
                            ->map(fn($row) => $row->sum('jumlah'));

    // Hitung Kelompok Pasiva
    $hutang = $dataManual->whereIn('kategori', ['Hutang Bank', 'Hutang Usaha'])
                         ->groupBy('keterangan')
                         ->map(fn($row) => $row->sum('jumlah'));

    $modalDisetor = $dataManual->where('kategori', 'Modal Disetor')->sum('jumlah');
    $prive = $dataManual->where('kategori', 'Prive')->sum('jumlah');

    // =========================================================================
    // 5. SUSUN ARRAY FINAL UNTUK VIEW
    // =========================================================================
    $neraca = [
        'aset_lancar' => [
            'Kas & Bank (Total)' => $kasBank
        ],
        'aset_tetap'  => $asetTetap,
        'kewajiban'   => $hutang,
        'ekuitas'     => [
            'Modal Disetor'          => $modalDisetor,
            'Prive (Tarik Modal)'    => $prive * -1,
            'Profit Berjalan (Real)' => $profitReal 
        ],
        'total_aset'      => $kasBank + $asetTetap->sum(),
        'total_kewajiban' => $hutang->sum(),
    ];

    // Total Pasiva = Hutang + Modal - Prive + Profit
    $neraca['total_pasiva'] = $neraca['total_kewajiban'] + $modalDisetor - $prive + $profitReal;
    
    $selisih = $neraca['total_aset'] - $neraca['total_pasiva'];

    return view('admin.keuangan.neraca', compact('neraca', 'startDate', 'endDate', 'selisih', 'dataManual'));
}
    
}
