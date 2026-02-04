<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // WAJIB ADA UNTUK LOGGING
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf; // Pastikan package PDF ada

class FinanceController extends Controller
{
    protected $tenantId;

    public function __construct(Request $request)
    {
        try {
            // Deteksi Tenant
            $host = $request->getHost();
            $subdomain = explode('.', $host)[0];
            $tenant = DB::table('tenants')->where('subdomain', $subdomain)->first();
            $this->tenantId = $tenant ? $tenant->id : 1;

            // Log Tenant ID yang terdeteksi
            // Log::info("FinanceController Initialized for Tenant ID: {$this->tenantId} (Subdomain: {$subdomain})");
        } catch (\Exception $e) {
            Log::error("❌ Error Construct FinanceController: " . $e->getMessage());
        }
    }

    public function index(Request $request)
    {
        try {
            // Log::info("🔍 Mengakses Halaman Jurnal...");

            $query = DB::table('journals')
                ->join('accounts', 'journals.account_id', '=', 'accounts.id')
                ->where('journals.tenant_id', $this->tenantId)
                ->select('journals.*', 'accounts.name as account_name', 'accounts.code');

            // --- LOGIC FILTER BARU ---
            $filterType = $request->filter_type ?? 'monthly'; // Default Bulanan biar rapi

            switch ($filterType) {
                case 'daily':
                    $date = $request->date ?? date('Y-m-d');
                    $query->whereDate('journals.transaction_date', $date);
                    $periodeLabel = "Harian: " . date('d M Y', strtotime($date));
                    break;

                case 'monthly':
                    $month = $request->month ?? date('m');
                    $year  = $request->year ?? date('Y');
                    $query->whereMonth('journals.transaction_date', $month)
                          ->whereYear('journals.transaction_date', $year);
                    $periodeLabel = "Bulan: " . date('F Y', mktime(0, 0, 0, $month, 10, $year));
                    break;

                case 'yearly':
                    $year = $request->year ?? date('Y');
                    $query->whereYear('journals.transaction_date', $year);
                    $periodeLabel = "Tahun: " . $year;
                    break;

                case 'all':
                    // Tidak ada filter tanggal
                    $periodeLabel = "Semua Data";
                    break;

                default:
                    // Default fallback ke bulan ini
                    $query->whereMonth('journals.transaction_date', date('m'))
                          ->whereYear('journals.transaction_date', date('Y'));
                    $periodeLabel = "Bulan Ini";
            }

            // Export Logic (PDF / Excel)
            if ($request->has('export')) {
                $data = $query->orderBy('transaction_date', 'asc')->get(); // Export urut tanggal naik

                if ($request->export == 'pdf') {
                    // Pastikan kirim $periodeLabel biar judul PDF-nya jelas
                    return $this->_exportPdf($data, $periodeLabel);
                }
                if ($request->export == 'excel') {
                    return $this->_exportExcel($data, $periodeLabel);
                }
            }

            // Tampilan Web
            $journals = $query->orderBy('journals.transaction_date', 'desc')
                              ->orderBy('journals.id', 'desc')
                              ->paginate(20);

            $accounts = DB::table('accounts')->where('tenant_id', $this->tenantId)->get();

            return view('finance.index', compact('journals', 'accounts'));

        } catch (\Exception $e) {
            Log::error("❌ Error Index Jurnal: " . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan.');
        }
    }

    // =========================================================
    // 2. SIMPAN JURNAL MANUAL
    // =========================================================
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'transaction_date' => 'required|date',
                'description'      => 'required|string',
                'account_id'       => 'required',
                'amount'           => 'required|numeric',
                'type'             => 'required|in:debit,credit',
            ]);

            DB::table('journals')->insert([
                'tenant_id'        => $this->tenantId,
                'transaction_date' => $request->transaction_date,
                'account_id'       => $request->account_id,
                'description'      => $request->description,
                'debit'            => $request->type == 'debit' ? $request->amount : 0,
                'credit'           => $request->type == 'credit' ? $request->amount : 0,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            DB::commit();
            Log::info("✅ Sukses Input Jurnal Manual: {$request->description} - Rp {$request->amount}");
            return back()->with('success', 'Transaksi berhasil dicatat!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("❌ Gagal Input Jurnal Manual: " . $e->getMessage());
            return back()->with('error', 'Gagal menyimpan data: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            DB::table('journals')->where('id', $id)->where('tenant_id', $this->tenantId)->delete();
            Log::info("🗑️ Menghapus Jurnal ID: {$id}");
            return back()->with('success', 'Transaksi dihapus.');
        } catch (\Exception $e) {
            Log::error("❌ Gagal Hapus Jurnal: " . $e->getMessage());
            return back()->with('error', 'Gagal menghapus data.');
        }
    }

    // =========================================================
    // 🔥 FITUR SYNC (SMART CHECK & ANTI DUPLIKAT)
    // =========================================================
    public function syncData()
    {
        Log::info("🔄 MEMULAI SMART SYNC (Tenant: {$this->tenantId})");
        DB::beginTransaction(); // Mulai Transaksi Database (Safety)

        try {
            // 1. SIAPKAN AKUN-AKUN PENTING
            // Kita cari ID akun berdasarkan namanya
            $akunKas        = DB::table('accounts')->where('tenant_id', $this->tenantId)->where('name', 'like', '%Kas%')->first();
            $akunPendapatan = DB::table('accounts')->where('tenant_id', $this->tenantId)->where('type', 'revenue')->first();
            $akunHPP        = DB::table('accounts')->where('tenant_id', $this->tenantId)->where('name', 'like', '%HPP%')->orWhere('name', 'like', '%Beban Pembelian%')->first();
            $akunPersediaan = DB::table('accounts')->where('tenant_id', $this->tenantId)->where('name', 'like', '%Persediaan%')->first();

            // Validasi: Jika akun dasar tidak ada, batalkan.
            if (!$akunKas || !$akunPendapatan) {
                throw new \Exception("Akun 'Kas' atau 'Pendapatan' belum ditemukan. Harap buat akun dulu di menu Akun.");
            }

            // 2. AMBIL DATA ORDER YANG SUDAH LUNAS (PAID)
            // Kita ambil semua yang paid, nanti disaring di bawah
            $orders = DB::table('orders')
                ->where('tenant_id', $this->tenantId)
                ->where('payment_status', 'paid')
                ->orderBy('created_at', 'asc') // Urutkan dari yang terlama biar rapi masuknya
                ->get();

            $countInserted = 0;
            $countSkipped  = 0;

            foreach ($orders as $order) {
                // 3. CEK APAKAH SUDAH ADA DI JURNAL? (LOGIKA ANTI DUPLIKAT)
                // Kita cari di tabel journals: "Apakah ada deskripsi yang mengandung Order Number ini?"
                $isExists = DB::table('journals')
                    ->where('tenant_id', $this->tenantId)
                    ->where('description', 'like', "%{$order->order_number}%")
                    ->exists(); // Return true jika ada

                if ($isExists) {
                    $countSkipped++;
                    // Log::info("⏩ Skip Order {$order->order_number} (Sudah Ada)");
                    continue; // Lanjut ke order berikutnya, jangan diproses
                }

                // ========================================================
                // JIKA BELUM ADA, MAKA EKSEKUSI PENCATATAN JURNAL
                // ========================================================

                // A. JURNAL PENJUALAN (Debit Kas, Kredit Pendapatan)
                // --------------------------------------------------------

                // 1. Debit Kas (Uang Masuk)
                DB::table('journals')->insert([
                    'tenant_id'        => $this->tenantId,
                    'transaction_date' => date('Y-m-d', strtotime($order->created_at)),
                    'account_id'       => $akunKas->id,
                    'description'      => "Penjualan No: {$order->order_number} ({$order->customer_name})",
                    'debit'            => $order->final_price,
                    'credit'           => 0,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);

                // 2. Kredit Pendapatan (Omzet)
                DB::table('journals')->insert([
                    'tenant_id'        => $this->tenantId,
                    'transaction_date' => date('Y-m-d', strtotime($order->created_at)),
                    'account_id'       => $akunPendapatan->id,
                    'description'      => "Pendapatan No: {$order->order_number}",
                    'debit'            => 0,
                    'credit'           => $order->final_price,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);

                // B. JURNAL HPP (Debit Beban HPP, Kredit Persediaan)
                // --------------------------------------------------------
                // Hanya jalankan jika Akun HPP & Persediaan ada
                if ($akunHPP && $akunPersediaan) {
                    // Hitung total modal real-time dari tabel Products
                    $totalModal = DB::table('order_details')
                        ->join('products', 'order_details.product_id', '=', 'products.id')
                        ->where('order_details.order_id', $order->id)
                        ->sum(DB::raw('order_details.quantity * products.base_price'));

                    // Hanya catat HPP jika ada nilainya (Kalau jasa/nol tidak perlu jurnal HPP)
                    if ($totalModal > 0) {
                        // 3. Debit Beban HPP
                        DB::table('journals')->insert([
                            'tenant_id'        => $this->tenantId,
                            'transaction_date' => date('Y-m-d', strtotime($order->created_at)),
                            'account_id'       => $akunHPP->id,
                            'description'      => "HPP No: {$order->order_number}",
                            'debit'            => $totalModal,
                            'credit'           => 0,
                            'created_at'       => now(),
                            'updated_at'       => now(),
                        ]);

                        // 4. Kredit Persediaan (Stok Berkurang secara Nilai)
                        DB::table('journals')->insert([
                            'tenant_id'        => $this->tenantId,
                            'transaction_date' => date('Y-m-d', strtotime($order->created_at)),
                            'account_id'       => $akunPersediaan->id,
                            'description'      => "Stok Keluar No: {$order->order_number}",
                            'debit'            => 0,
                            'credit'           => $totalModal,
                            'created_at'       => now(),
                            'updated_at'       => now(),
                        ]);
                    }
                }

                $countInserted++;
                Log::info("✅ Inserted Order {$order->order_number} to Journal.");
            }

            DB::commit(); // Simpan Perubahan Permanen

            Log::info("🏁 Sync Selesai. Masuk: {$countInserted}, Skip: {$countSkipped}");

            // LOGIKA PESAN KE USER
            if ($countInserted > 0) {
                // Kasus: Ada data baru masuk
                return back()->with('success', "✅ Sukses! {$countInserted} transaksi baru berhasil ditambahkan.");
            } else {
                // Kasus: Masuk 0 (Data Sudah Terupdate semua)
                // Ini yang Bapak minta:
                return back()->with('success', "👌 Data Sudah Terupdate! (Semua transaksi sudah tercatat di jurnal)");
            }

        } catch (\Exception $e) {
            DB::rollBack(); // Batalkan semua jika ada error
            Log::error("❌ SYNC ERROR: " . $e->getMessage());
            return back()->with('error', 'Gagal Sinkronisasi: ' . $e->getMessage());
        }
    }

    // =========================================================
    // 🔥 FITUR RESET & RESYNC (SOLUSI "SKIP" DATA)
    // =========================================================
    public function resetAndSync()
    {
        Log::info("⚠️ MEMULAI RESET (HARD DELETE) JURNAL OTOMATIS (Tenant: {$this->tenantId})");

        DB::beginTransaction(); // Mulai transaksi biar aman

        try {
            // 1. HAPUS DULU DATA JURNAL OTOMATIS YANG LAMA
            // Kita gunakan 'WHERE' yang spesifik agar Input Manual (Gaji/Listrik) TIDAK HILANG.

            $deleted = DB::table('journals')
                ->where('tenant_id', $this->tenantId)
                ->where(function($query) {
                    // Filter berdasarkan kata kunci yang dihasilkan sistem otomatis
                    $query->where('description', 'like', 'Penjualan No:%')
                          ->orWhere('description', 'like', 'Pendapatan No:%')
                          ->orWhere('description', 'like', 'HPP No:%')
                          ->orWhere('description', 'like', 'Stok Keluar No:%');
                })
                ->delete();

            DB::commit(); // Commit penghapusan dulu

            Log::info("🗑️ DELETE SUKSES. Berhasil menghapus {$deleted} baris jurnal otomatis lama.");

            // 2. JALANKAN SYNC ULANG
            // Kita redirect user langsung ke route 'sync'.
            // Ini akan memicu fungsi syncData() untuk berjalan dari awal.
            // Karena data lama sudah dihapus, maka 'Skip' akan berubah menjadi 'Inserted'.

            return redirect()->route('finance.sync')
                ->with('success', "✅ RESET BERHASIL! {$deleted} data lama telah dibersihkan. Sistem sedang menarik ulang data terbaru...");

        } catch (\Exception $e) {
            DB::rollBack(); // Balikin data kalau ada error
            Log::error("❌ RESET ERROR: " . $e->getMessage());
            return back()->with('error', 'Gagal Reset Database: ' . $e->getMessage());
        }
    }

   // =========================================================
    // 3. LABA RUGI (PLUS EXPORT)
    // =========================================================
    public function labaRugi(Request $request)
    {
        $month = $request->month ?? date('m');
        $year  = $request->year ?? date('Y');

        // --- Logic Hitungan (Sama seperti sebelumnya) ---
        // 1. Sync Data Dulu Biar Akurat
        // (Opsional: Bisa dipanggil $this->syncData() disini kalau mau otomatis sync tiap buka laporan)

        $data = DB::table('accounts')
            ->where('tenant_id', $this->tenantId)
            ->whereIn('type', ['revenue', 'expense'])
            ->get()
            ->map(function ($acc) use ($month, $year) {
                $saldo = DB::table('journals')
                    ->where('account_id', $acc->id)
                    ->whereMonth('transaction_date', $month)
                    ->whereYear('transaction_date', $year)
                    ->selectRaw('SUM(credit) - SUM(debit) as balance_revenue, SUM(debit) - SUM(credit) as balance_expense')
                    ->first();

                if ($acc->type == 'revenue') {
                    $acc->balance = $saldo->balance_revenue ?? 0;
                } else {
                    $acc->balance = $saldo->balance_expense ?? 0;
                }
                return $acc;
            });

        $revenues = $data->where('type', 'revenue');
        $expenses = $data->where('type', 'expense');

        $totalRevenue = $revenues->sum('balance');
        $totalExpense = $expenses->sum('balance');
        $netIncome    = $totalRevenue - $totalExpense;

        // --- EXPORT LOGIC ---
        if ($request->export == 'pdf') {
            $pdf = Pdf::loadView('finance.pdf_labarugi', compact('revenues', 'expenses', 'totalRevenue', 'totalExpense', 'netIncome', 'month', 'year'));
            return $pdf->download("Laba_Rugi_{$month}_{$year}.pdf");
        }

        if ($request->export == 'excel') {
            return $this->_exportLabaRugiExcel($revenues, $expenses, $totalRevenue, $totalExpense, $netIncome, $month, $year);
        }

        return view('finance.laba_rugi', compact('revenues', 'expenses', 'totalRevenue', 'totalExpense', 'netIncome', 'month', 'year'));
    }

    // =========================================================
    // 4. NERACA (PLUS EXPORT)
    // =========================================================
    public function neraca(Request $request)
    {
        $asOfDate = $request->date ?? date('Y-m-d');

        // --- Logic Hitungan ---
        $data = DB::table('accounts')
            ->where('tenant_id', $this->tenantId)
            ->whereIn('type', ['asset', 'liability', 'equity'])
            ->get()
            ->map(function ($acc) use ($asOfDate) {
                $saldo = DB::table('journals')
                    ->where('account_id', $acc->id)
                    ->where('transaction_date', '<=', $asOfDate)
                    ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
                    ->first();

                if ($acc->type == 'asset') {
                    $acc->balance = ($saldo->total_debit ?? 0) - ($saldo->total_credit ?? 0);
                } else {
                    $acc->balance = ($saldo->total_credit ?? 0) - ($saldo->total_debit ?? 0);
                }
                return $acc;
            });

        $labaBerjalan = DB::table('journals')
            ->join('accounts', 'journals.account_id', '=', 'accounts.id')
            ->where('journals.tenant_id', $this->tenantId)
            ->where('journals.transaction_date', '<=', $asOfDate)
            ->selectRaw('
                SUM(CASE WHEN accounts.type = "revenue" THEN (journals.credit - journals.debit) ELSE 0 END) -
                SUM(CASE WHEN accounts.type = "expense" THEN (journals.debit - journals.credit) ELSE 0 END)
                as total_laba
            ')->value('total_laba');

        $assets      = $data->where('type', 'asset');
        $liabilities = $data->where('type', 'liability');
        $equities    = $data->where('type', 'equity');

        $totalAsset     = $assets->sum('balance');
        $totalLiability = $liabilities->sum('balance');
        $totalEquity    = $equities->sum('balance') + $labaBerjalan;

        // --- EXPORT LOGIC ---
        if ($request->export == 'pdf') {
            $pdf = Pdf::loadView('finance.pdf_neraca', compact('assets', 'liabilities', 'equities', 'totalAsset', 'totalLiability', 'totalEquity', 'labaBerjalan', 'asOfDate'));
            return $pdf->download("Neraca_{$asOfDate}.pdf");
        }

        if ($request->export == 'excel') {
            return $this->_exportNeracaExcel($assets, $liabilities, $equities, $labaBerjalan, $asOfDate);
        }

        return view('finance.neraca', compact('assets', 'liabilities', 'equities', 'totalAsset', 'totalLiability', 'totalEquity', 'labaBerjalan', 'asOfDate'));
    }

    // =========================================================
    // 5. TAHUNAN (FIXED: UNDEFINED ARRAY KEY HPP)
    // =========================================================
    public function labaRugiTahunan(Request $request)
    {
        try {
            $year = $request->year ?? date('Y');
            $reportData = [];

            for ($m = 1; $m <= 12; $m++) {

                // 1. HITUNG OMZET (Akun Tipe Revenue)
                $omzet = DB::table('journals')
                    ->join('accounts', 'journals.account_id', '=', 'accounts.id')
                    ->where('journals.tenant_id', $this->tenantId)
                    ->where('accounts.type', 'revenue')
                    ->whereMonth('journals.transaction_date', $m)
                    ->whereYear('journals.transaction_date', $year)
                    ->sum('journals.credit');

                // 2. HITUNG HPP (Akun Expense yang namanya mengandung 'HPP', 'Pembelian', atau 'Modal')
                $hpp = DB::table('journals')
                    ->join('accounts', 'journals.account_id', '=', 'accounts.id')
                    ->where('journals.tenant_id', $this->tenantId)
                    ->where('accounts.type', 'expense')
                    ->where(function($q) {
                        $q->where('accounts.name', 'like', '%HPP%')
                          ->orWhere('accounts.name', 'like', '%Pembelian%')
                          ->orWhere('accounts.name', 'like', '%Modal%')
                          ->orWhere('accounts.name', 'like', '%Cost%');
                    })
                    ->whereMonth('journals.transaction_date', $m)
                    ->whereYear('journals.transaction_date', $year)
                    ->sum('journals.debit');

                // 3. HITUNG BEBAN OPERASIONAL (Akun Expense SELAIN HPP)
                $beban = DB::table('journals')
                    ->join('accounts', 'journals.account_id', '=', 'accounts.id')
                    ->where('journals.tenant_id', $this->tenantId)
                    ->where('accounts.type', 'expense')
                    ->whereNot(function($q) {
                        $q->where('accounts.name', 'like', '%HPP%')
                          ->orWhere('accounts.name', 'like', '%Pembelian%')
                          ->orWhere('accounts.name', 'like', '%Modal%')
                          ->orWhere('accounts.name', 'like', '%Cost%');
                    })
                    ->whereMonth('journals.transaction_date', $m)
                    ->whereYear('journals.transaction_date', $year)
                    ->sum('journals.debit');

                // Masukkan ke Array (LENGKAP DENGAN KEY 'hpp')
                $reportData[] = [
                    'bulan'  => \Carbon\Carbon::create()->month($m)->translatedFormat('F'),
                    'omzet'  => $omzet,
                    'hpp'    => $hpp,   // <--- INI KUNCI YANG TADI HILANG
                    'beban'  => $beban,
                    'bersih' => $omzet - $hpp - $beban
                ];
            }

            // --- EXPORT LOGIC ---
            if ($request->export == 'pdf') {
                $pdf = Pdf::loadView('finance.pdf_tahunan', compact('reportData', 'year'));
                return $pdf->download("Laporan_Tahunan_{$year}.pdf");
            }

            if ($request->export == 'excel') {
                return $this->_exportTahunanExcel($reportData, $year);
            }

            return view('finance.tahunan', compact('reportData', 'year'));

        } catch (\Exception $e) {
            Log::error("❌ Error Tahunan: " . $e->getMessage());
            return back()->with('error', 'Gagal memuat laporan tahunan: ' . $e->getMessage());
        }
    }

    // =========================================================
    // HELPER EXCEL (Taruh Paling Bawah Controller)
    // =========================================================

    private function _exportLabaRugiExcel($rev, $exp, $tRev, $tExp, $net, $m, $y) {
        $fileName = "LabaRugi_{$m}_{$y}.csv";
        $callback = function() use($rev, $exp, $tRev, $tExp, $net) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['AKUN', 'NOMINAL']);
            fputcsv($file, ['PENDAPATAN']);
            foreach($rev as $r) fputcsv($file, [$r->name, $r->balance]);
            fputcsv($file, ['TOTAL PENDAPATAN', $tRev]);
            fputcsv($file, []);
            fputcsv($file, ['BEBAN']);
            foreach($exp as $e) fputcsv($file, [$e->name, $e->balance]);
            fputcsv($file, ['TOTAL BEBAN', $tExp]);
            fputcsv($file, []);
            fputcsv($file, ['LABA BERSIH', $net]);
            fclose($file);
        };
        return response()->stream($callback, 200, ["Content-Type"=>"text/csv", "Content-Disposition"=>"attachment; filename=$fileName"]);
    }

    private function _exportNeracaExcel($ast, $lia, $eq, $laba, $date) {
        $fileName = "Neraca_{$date}.csv";
        $callback = function() use($ast, $lia, $eq, $laba) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['KATEGORI', 'AKUN', 'SALDO']);
            foreach($ast as $a) fputcsv($file, ['ASET', $a->name, $a->balance]);
            foreach($lia as $l) fputcsv($file, ['KEWAJIBAN', $l->name, $l->balance]);
            foreach($eq as $e) fputcsv($file, ['MODAL', $e->name, $e->balance]);
            fputcsv($file, ['MODAL', 'Laba Berjalan', $laba]);
            fclose($file);
        };
        return response()->stream($callback, 200, ["Content-Type"=>"text/csv", "Content-Disposition"=>"attachment; filename=$fileName"]);
    }

    private function _exportTahunanExcel($data, $year) {
        $fileName = "Tahunan_{$year}.csv";
        $callback = function() use($data) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['BULAN', 'OMZET', 'BEBAN', 'LABA BERSIH']);
            foreach($data as $d) fputcsv($file, [$d['bulan'], $d['omzet'], $d['beban'], $d['bersih']]);
            fclose($file);
        };
        return response()->stream($callback, 200, ["Content-Type"=>"text/csv", "Content-Disposition"=>"attachment; filename=$fileName"]);
    }

    // Helper PDF
    private function _exportPdf($data, $periodeLabel) {
        $pdf = Pdf::loadView('finance.pdf_journal', compact('data', 'periodeLabel'));
        return $pdf->download("Laporan_Keuangan_{$periodeLabel}.pdf");
    }

    // Helper Excel (CSV)
    private function _exportExcel($data, $periodeLabel) {
        $fileName = "Laporan_Keuangan_{$periodeLabel}.csv";
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];
        $columns = array('Tanggal', 'No Bukti', 'Akun', 'Debit', 'Kredit');

        $callback = function() use($data, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            foreach ($data as $row) {
                fputcsv($file, array(
                    $row->transaction_date,
                    $row->description,
                    $row->account_name,
                    $row->debit,
                    $row->credit
                ));
            }
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }
}
