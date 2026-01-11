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
        // 1. QUERY GABUNGAN (MANUAL + PPOB + EKSPEDISI + TOPUP + MARKETPLACE)
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

        // C. Ekspedisi Query (Pesanan)
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

        // 2. Filter Search Global
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

        // 3. Eksekusi Gabungan (Union All) - BAGIAN YANG DIPERBAIKI
        $gabungan = $manualQuery
                    ->unionAll($ppobQuery)
                    ->unionAll($ekspedisiQuery)
                    ->unionAll($topupQuery)
                    ->unionAll($marketplaceQuery);
        
        // Kita hanya perlu mergeBindings dari $gabungan, karena ia sudah menampung semua binding query di atasnya.
        $transaksi = DB::table(DB::raw("({$gabungan->toSql()}) as combined_table"))
            ->mergeBindings($gabungan) 
            ->orderBy('tanggal', 'desc')
            ->paginate(15)
            ->withQueryString();

        // ==================================================================================
        // 2. HITUNG TOTAL STATISTIK (CARD ATAS)
        // ==================================================================================
        
        // A. Manual
        $manualStats = DB::table('keuangans')
            ->selectRaw("SUM(CASE WHEN jenis = 'Pemasukan' THEN jumlah ELSE 0 END) as total_omzet, SUM(CASE WHEN jenis = 'Pengeluaran' THEN jumlah ELSE 0 END) as total_modal, SUM(CASE WHEN jenis = 'Pemasukan' THEN jumlah ELSE -jumlah END) as total_profit")->first();

        // B. PPOB
        $ppobStats = DB::table('ppob_transactions')->where('status', 'Success')->selectRaw("COUNT(id) as total_trx, SUM(price) as total_price_provider")->first();
        $ppobProfit = ($ppobStats->total_trx ?? 0) * 50;
        $ppobModal  = $ppobStats->total_price_provider ?? 0;
        $ppobOmzet  = $ppobModal + $ppobProfit;

        // C. Ekspedisi
        $ekspedisiStats = DB::table('Pesanan')->where('status_pesanan', 'Selesai')->selectRaw("SUM(price) as total_omzet, SUM(shipping_cost + insurance_cost) as total_modal")->first();
        $ekspedisiOmzet = $ekspedisiStats->total_omzet ?? 0;
        $ekspedisiModal = $ekspedisiStats->total_modal ?? 0;
        $ekspedisiProfit = $ekspedisiOmzet - $ekspedisiModal;

        // D. Top Up
        $topupStats = DB::table('transactions')->where('status', 'success')->where('type', 'topup')->sum('amount');
        $topupOmzet = $topupStats; $topupModal = $topupStats; $topupProfit = 0;

        // E. Marketplace
        $marketplaceStats = DB::table('order_marketplace')
            ->whereIn('status', ['completed', 'success', 'delivered', 'shipped'])
            ->selectRaw("SUM(total_amount) as total_omzet, SUM(shipping_cost + insurance_cost) as total_modal")->first();
        $marketplaceOmzet = $marketplaceStats->total_omzet ?? 0;
        $marketplaceModal = $marketplaceStats->total_modal ?? 0;
        $marketplaceProfit = $marketplaceOmzet - $marketplaceModal;

        // F. Gabungkan Semua Total
        $totalOmzet  = ($manualStats->total_omzet ?? 0) + $ppobOmzet + $ekspedisiOmzet + $topupOmzet + $marketplaceOmzet;
        $totalModal  = ($manualStats->total_modal ?? 0) + $ppobModal + $ekspedisiModal + $topupModal + $marketplaceModal;
        $totalProfit = ($manualStats->total_profit ?? 0) + $ppobProfit + $ekspedisiProfit + $topupProfit + $marketplaceProfit;

        return view('admin.keuangan.index', compact('transaksi', 'totalOmzet', 'totalModal', 'totalProfit'));
    }

    // CRUD Manual
    public function store(Request $request) { Keuangan::create($request->all()); return back()->with('success','Disimpan'); }
    public function update(Request $request, $id) { Keuangan::find($id)->update($request->all()); return back()->with('success','Diupdate'); }
    public function destroy($id) { Keuangan::find($id)->delete(); return back()->with('success','Dihapus'); }
}