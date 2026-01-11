<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Keuangan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KeuanganController extends Controller
{
    public function index(Request $request)
    {
        // ==================================================================================
        // 1. SIAPKAN QUERY UNTUK 5 SUMBER DATA
        // ==================================================================================

        // A. Manual Query
        $manualQuery = DB::table('keuangans')
            ->select(
                'id',
                'tanggal',
                'jenis',
                'kategori',
                'nomor_invoice',
                'keterangan',
                DB::raw("CASE WHEN jenis = 'Pemasukan' THEN jumlah ELSE 0 END as omzet"),
                DB::raw("CASE WHEN jenis = 'Pengeluaran' THEN jumlah ELSE 0 END as modal"),
                DB::raw("CASE WHEN jenis = 'Pemasukan' THEN jumlah ELSE -jumlah END as profit")
            );

        // B. PPOB Query (Profit Flat 50)
        $ppobQuery = DB::table('ppob_transactions')
            ->where('status', 'Success')
            ->select(
                'id',
                DB::raw('DATE(created_at) as tanggal'),
                DB::raw("'Pemasukan' as jenis"),
                DB::raw("'PPOB' as kategori"),
                'order_id as nomor_invoice',
                DB::raw("CONCAT(buyer_sku_code, ' - ', customer_no) as keterangan"),
                DB::raw('(price + 50) as omzet'), 
                'price as modal',                 
                DB::raw('50 as profit')           
            );

        // C. Ekspedisi Query
        $ekspedisiQuery = DB::table('Pesanan')
            ->where('status_pesanan', 'Selesai')
            ->select(
                DB::raw("id_pesanan as id"),
                DB::raw('DATE(tanggal_pesanan) as tanggal'),
                DB::raw("'Pemasukan' as jenis"),
                DB::raw("'Ekspedisi' as kategori"),
                'nomor_invoice',
                DB::raw("CONCAT(resi, ' (', service_type, ')') as keterangan"),
                'price as omzet',
                DB::raw('(shipping_cost + insurance_cost) as modal'),
                DB::raw('(price - (shipping_cost + insurance_cost)) as profit')
            );

        // D. Top Up Saldo Query
        $topupQuery = DB::table('transactions')
            ->where('status', 'success')
            ->where('type', 'topup')
            ->select(
                'id',
                DB::raw('DATE(created_at) as tanggal'),
                DB::raw("'Pemasukan' as jenis"),
                DB::raw("'Top Up Saldo' as kategori"),
                'reference_id as nomor_invoice',
                'description as keterangan',
                'amount as omzet', 
                'amount as modal', 
                DB::raw('0 as profit') 
            );

        // E. Marketplace Query
        $marketplaceQuery = DB::table('order_marketplace')
            ->whereIn('status', ['completed', 'success', 'delivered', 'shipped']) 
            ->select(
                'id',
                DB::raw('DATE(created_at) as tanggal'),
                DB::raw("'Pemasukan' as jenis"),
                DB::raw("'Marketplace' as kategori"),
                'invoice_number as nomor_invoice',
                DB::raw("CONCAT(shipping_method, ' - ', COALESCE(shipping_resi, '-')) as keterangan"),
                'total_amount as omzet', 
                DB::raw('(shipping_cost + insurance_cost) as modal'), 
                DB::raw('(total_amount - (shipping_cost + insurance_cost)) as profit') 
            );

        // ==================================================================================
        // 2. TERAPKAN FILTER (SEARCH & TANGGAL) KE SETIAP QUERY
        // ==================================================================================
        
        // --- Filter Search ---
        if ($request->filled('search')) {
            $search = $request->search;
            
            $manualQuery->where(function($q) use ($search) {
                $q->where('nomor_invoice', 'like', "%$search%")->orWhere('keterangan', 'like', "%$search%");
            });
            $ppobQuery->where(function($q) use ($search) {
                $q->where('order_id', 'like', "%$search%")->orWhere('customer_no', 'like', "%$search%");
            });
            $ekspedisiQuery->where(function($q) use ($search) {
                $q->where('nomor_invoice', 'like', "%$search%")->orWhere('resi', 'like', "%$search%");
            });
            $topupQuery->where(function($q) use ($search) {
                $q->where('reference_id', 'like', "%$search%")->orWhere('description', 'like', "%$search%");
            });
            $marketplaceQuery->where(function($q) use ($search) {
                $q->where('invoice_number', 'like', "%$search%")->orWhere('shipping_resi', 'like', "%$search%");
            });
        }

        // --- Filter Tanggal ---
        if ($request->filled('date_range')) {
            // Normalisasi format tanggal dari URL (misal: "2026-01-01 - 2026-01-31" jadi "2026-01-01 to 2026-01-31")
            $rawDate = str_replace([' - ', ' s.d. '], ' to ', $request->date_range);
            $dates = explode(' to ', $rawDate);

            if (count($dates) >= 2) {
                $startDate = trim($dates[0]);
                $endDate = trim($dates[1]);
                
                $manualQuery->whereBetween('tanggal', [$startDate, $endDate]);
                $ppobQuery->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
                $ekspedisiQuery->whereBetween(DB::raw('DATE(tanggal_pesanan)'), [$startDate, $endDate]);
                $topupQuery->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
                $marketplaceQuery->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
            } elseif (count($dates) == 1) {
                $singleDate = trim($dates[0]);
                $manualQuery->whereDate('tanggal', $singleDate);
                $ppobQuery->whereDate('created_at', $singleDate);
                $ekspedisiQuery->whereDate('tanggal_pesanan', $singleDate);
                $topupQuery->whereDate('created_at', $singleDate);
                $marketplaceQuery->whereDate('created_at', $singleDate);
            }
        }

        // ==================================================================================
        // 3. EKSEKUSI GABUNGAN (UNION)
        // ==================================================================================

        $gabungan = $manualQuery
                    ->unionAll($ppobQuery)
                    ->unionAll($ekspedisiQuery)
                    ->unionAll($topupQuery)
                    ->unionAll($marketplaceQuery);
        
        // Bungkus query gabungan agar bisa di-paginate dan di-sum
        $queryFinal = DB::table(DB::raw("({$gabungan->toSql()}) as combined_table"))
                        ->mergeBindings($gabungan); // Binding sekali saja untuk semua union

        // ==================================================================================
        // 4. HITUNG TOTAL (CARD) DARI HASIL FILTER
        // ==================================================================================
        
        // Kita clone $queryFinal agar filter tetap terbawa, lalu lakukan SUM
        $stats = (clone $queryFinal)->selectRaw("
            SUM(omzet) as total_omzet,
            SUM(modal) as total_modal,
            SUM(profit) as total_profit
        ")->first();

        $totalOmzet  = $stats->total_omzet ?? 0;
        $totalModal  = $stats->total_modal ?? 0;
        $totalProfit = $stats->total_profit ?? 0;

        // ==================================================================================
        // 5. AMBIL DATA TABEL (PAGINATION)
        // ==================================================================================

        $transaksi = $queryFinal
            ->orderBy('tanggal', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.keuangan.index', compact('transaksi', 'totalOmzet', 'totalModal', 'totalProfit'));
    }

    // --- CRUD Manual ---
    public function store(Request $request) { Keuangan::create($request->all()); return back()->with('success','Disimpan'); }
    public function update(Request $request, $id) { Keuangan::find($id)->update($request->all()); return back()->with('success','Diupdate'); }
    public function destroy($id) { Keuangan::find($id)->delete(); return back()->with('success','Dihapus'); }
}