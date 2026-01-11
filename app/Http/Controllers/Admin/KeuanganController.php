<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Keuangan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator; // Penting untuk pagination manual

class KeuanganController extends Controller
{
    public function index(Request $request)
    {
        // ==================================================================================
        // 1. QUERY DASAR (Manual, PPOB, Topup, Marketplace)
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

        // E. EKSPEDISI QUERY (Ambil Data Mentah Dulu)
        // Kita tidak hitung profit di SQL, tapi ambil komponennya untuk dihitung di PHP
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
                // Kolom penting untuk perhitungan profit:
                'shipping_cost', 
                'insurance_cost', 
                'expedition',   // String nama kurir & layanan (misal: "regular-jne-yes")
                'service_type',
                DB::raw('0 as modal'), // Placeholder, nanti diisi PHP
                DB::raw('0 as profit') // Placeholder, nanti diisi PHP
            );

        // ==================================================================================
        // 2. SEARCH & FILTER (Terapkan ke semua Query)
        // ==================================================================================
        
        if ($request->filled('search')) {
            $keyword = $request->search;
            
            $filterClosure = function($q) use ($keyword) {
                // Logika umum search
                $q->where('nomor_invoice', 'like', "%$keyword%");
            };
            
            // Terapkan filter spesifik per query (copy dari logika search sebelumnya)
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
            
            // Search Ekspedisi (Lengkap)
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
        // 3. PROSES HITUNG PROFIT EKSPEDISI (PHP SIDE)
        // ==================================================================================

        // Ambil Data Aturan Diskon dari Database
        $diskonRules = DB::table('Ekspedisi')->whereNotNull('keyword')->get();

        // Ambil data Ekspedisi dari DB (Get Collection)
        $ekspedisiData = $ekspedisiQuery->get();

        // Loop dan Hitung Profit per Baris
        $processedEkspedisi = $ekspedisiData->map(function($row) use ($diskonRules) {
            $diskonPersen = 0;
            $expStr = strtolower($row->expedition); // misal: "regular-jne-yes"
            
            // 1. Cari Kurir yang cocok
            foreach ($diskonRules as $rule) {
                if (str_contains($expStr, strtolower($rule->keyword))) {
                    // 2. Parse JSON Rules
                    $rules = json_decode($rule->diskon_rules, true);
                    if (is_array($rules)) {
                        // 3. Cari Layanan yang cocok (reg, yes, cargo, dll)
                        foreach ($rules as $key => $val) {
                            if ($key !== 'default' && str_contains($expStr, $key)) {
                                $diskonPersen = $val;
                                break 2; // Ketemu layanan spesifik
                            }
                        }
                        // Jika tidak ada layanan spesifik, pakai default
                        if (isset($rules['default'])) {
                            $diskonPersen = $rules['default'];
                        }
                    }
                    break; // Ketemu kurir
                }
            }

            // 4. Hitung Modal & Profit
            $ongkirPublish = $row->shipping_cost;
            $ongkirReal    = $ongkirPublish - ($ongkirPublish * $diskonPersen);
            
            $row->modal  = $ongkirReal + $row->insurance_cost;
            $row->profit = $row->omzet - $row->modal;

            return $row;
        });

        // ==================================================================================
        // 4. GABUNGKAN DATA (MERGE) & PAGINATION
        // ==================================================================================

        // Ambil data selain ekspedisi
        $othersData = $manualQuery
            ->unionAll($ppobQuery)
            ->unionAll($topupQuery)
            ->unionAll($marketplaceQuery)
            ->get();

        // Gabung semua data
        $allData = $othersData->merge($processedEkspedisi);

        // Sort descending by tanggal
        $sortedData = $allData->sortByDesc('tanggal');

        // Pagination Manual (Karena data sudah berupa Collection)
        $page = $request->input('page', 1);
        $perPage = 15;
        $offset = ($page * $perPage) - $perPage;

        $transaksi = new LengthAwarePaginator(
            $sortedData->slice($offset, $perPage)->values(), // Items halaman ini
            $sortedData->count(), // Total items
            $perPage, // Items per page
            $page, // Halaman sekarang
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // ==================================================================================
        // 5. HITUNG SUMMARY (CARD) DARI COLLECTION
        // ==================================================================================

        // Hitung manual dari collection karena SQL Union tidak bisa melihat profit ekspedisi yg baru dihitung
        $summary = [
            'omzet'  => $sortedData->sum('omzet'),
            'modal'  => $sortedData->sum('modal'),
            'profit' => $sortedData->sum('profit'),

            // Breakdown Detail
            'ekspedisi' => [
                'omzet' => $processedEkspedisi->sum('omzet'),
                'count' => $processedEkspedisi->count()
            ],
            'ppob' => [
                'omzet' => $othersData->where('kategori', 'PPOB')->sum('omzet'),
                'count' => $othersData->where('kategori', 'PPOB')->count()
            ],
            'marketplace' => [
                'omzet' => $othersData->where('kategori', 'Marketplace')->sum('omzet'),
                'count' => $othersData->where('kategori', 'Marketplace')->count()
            ],
            'topup' => [
                'omzet' => $othersData->where('kategori', 'Top Up Saldo')->sum('omzet'),
                'count' => $othersData->where('kategori', 'Top Up Saldo')->count()
            ],
        ];

        return view('admin.keuangan.index', compact('transaksi', 'summary'));
    }

    // CRUD Manual
    public function store(Request $request) { Keuangan::create($request->all()); return back()->with('success','Disimpan'); }
    public function update(Request $request, $id) { Keuangan::find($id)->update($request->all()); return back()->with('success','Diupdate'); }
    public function destroy($id) { Keuangan::find($id)->delete(); return back()->with('success','Dihapus'); }
}