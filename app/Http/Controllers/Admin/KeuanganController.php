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
        // 1. SIAPKAN QUERY DASAR
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

        // B. PPOB Query
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
                DB::raw("CONCAT(resi, ' (', expedition, ')') as keterangan"),
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
        // 2. LOGIKA PENCARIAN (SEARCH) - SUDAH DIPERBAIKI
        // ==================================================================================
        
        if ($request->filled('search')) {
            $search = $request->search;
            
            // 1. Manual
            $manualQuery->where(function($q) use ($search) {
                $q->where('nomor_invoice', 'like', "%$search%")
                  ->orWhere('keterangan', 'like', "%$search%")
                  ->orWhere('kategori', 'like', "%$search%")
                  ->orWhere('jenis', 'like', "%$search%")
                  ->orWhere('jumlah', 'like', "%$search%");
            });

            // 2. PPOB
            $ppobQuery->where(function($q) use ($search) {
                $q->where('order_id', 'like', "%$search%")
                  ->orWhere('customer_no', 'like', "%$search%")
                  ->orWhere('buyer_sku_code', 'like', "%$search%")
                  ->orWhere('sn', 'like', "%$search%")
                  ->orWhere('message', 'like', "%$search%");
            });

            // 3. Ekspedisi (Cari Nama Kurir disini: expedition)
            $ekspedisiQuery->where(function($q) use ($search) {
                $q->where('nomor_invoice', 'like', "%$search%")
                  ->orWhere('resi', 'like', "%$search%")
                  ->orWhere('resi_aktual', 'like', "%$search%")
                  ->orWhere('expedition', 'like', "%$search%") 
                  ->orWhere('service_type', 'like', "%$search%")
                  ->orWhere('sender_name', 'like', "%$search%")
                  ->orWhere('sender_phone', 'like', "%$search%")
                  ->orWhere('receiver_name', 'like', "%$search%")
                  ->orWhere('receiver_phone', 'like', "%$search%");
            });

            // 4. Top Up (PERBAIKAN: HAPUS pencarian payment_method karena kolom tidak ada)
            $topupQuery->where(function($q) use ($search) {
                $q->where('reference_id', 'like', "%$search%")
                  ->orWhere('description', 'like', "%$search%") // "via DOKU" ada di sini
                  ->orWhere('amount', 'like', "%$search%");
            });

            // 5. Marketplace
            $marketplaceQuery->where(function($q) use ($search) {
                $q->where('invoice_number', 'like', "%$search%")
                  ->orWhere('shipping_resi', 'like', "%$search%")
                  ->orWhere('shipping_method', 'like', "%$search%")
                  ->orWhere('payment_method', 'like', "%$search%")
                  ->orWhere('shipping_address', 'like', "%$search%");
            });
        }

        // ==================================================================================
        // 3. LOGIKA FILTER TANGGAL
        // ==================================================================================

        if ($request->filled('date_range')) {
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
        // 4. EKSEKUSI GABUNGAN (UNION ALL)
        // ==================================================================================

        $gabungan = $manualQuery
                    ->unionAll($ppobQuery)
                    ->unionAll($ekspedisiQuery)
                    ->unionAll($topupQuery)
                    ->unionAll($marketplaceQuery);
        
        $queryFinal = DB::table(DB::raw("({$gabungan->toSql()}) as combined_table"))
                        ->mergeBindings($gabungan); 

        // ==================================================================================
        // 5. HITUNG TOTAL DINAMIS
        // ==================================================================================
        
        $stats = (clone $queryFinal)->selectRaw("
            SUM(omzet) as total_omzet,
            SUM(modal) as total_modal,
            SUM(profit) as total_profit
        ")->first();

        $totalOmzet  = $stats->total_omzet ?? 0;
        $totalModal  = $stats->total_modal ?? 0;
        $totalProfit = $stats->total_profit ?? 0;

        // ==================================================================================
        // 6. AMBIL DATA TABEL
        // ==================================================================================

        $transaksi = $queryFinal
            ->orderBy('tanggal', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.keuangan.index', compact('transaksi', 'totalOmzet', 'totalModal', 'totalProfit'));
    }
    
    // CRUD Functions
    public function store(Request $request) { Keuangan::create($request->all()); return back()->with('success','Data tersimpan.'); }
    public function update(Request $request, $id) { Keuangan::find($id)->update($request->all()); return back()->with('success','Data diperbarui.'); }
    public function destroy($id) { Keuangan::find($id)->delete(); return back()->with('success','Data dihapus.'); }
}