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
        // 1. DEFINISI QUERY DASAR (SELECT DATA)
        // ==================================================================================

        // A. Manual Query
        // Data manual dianggap selalu sah/valid (Real)
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
        // Filter: Hanya Status Sukses/Lunas/Berhasil
        $ppobQuery = DB::table('ppob_transactions')
            ->whereIn('status', ['Success', 'Lunas', 'Berhasil', 'success'])
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
        // Filter: Hanya Status Selesai/Terkirim/Lunas
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
                DB::raw('(shipping_cost + insurance_cost) as modal'),
                DB::raw('(price - (shipping_cost + insurance_cost)) as profit')
            );

        // D. Top Up Saldo Query
        // Filter: Hanya Status Sukses/Paid
        $topupQuery = DB::table('transactions')
            ->where('type', 'topup')
            ->whereIn('status', ['success', 'paid', 'lunas', 'berhasil'])
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
        // Filter: Hanya Status Selesai/Terkirim/Lunas (Pending/Batal TIDAK MASUK)
        $marketplaceQuery = DB::table('order_marketplace')
            ->whereIn('status', ['completed', 'success', 'delivered', 'selesai', 'terkirim', 'lunas']) 
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
        // 2. LOGIKA PENCARIAN MENYELURUH (SEMUA KOLOM DATABASE)
        // ==================================================================================
        
        if ($request->filled('search')) {
            $keyword = $request->search;
            
            // --- 1. SEARCH MANUAL ---
            $manualQuery->where(function($q) use ($keyword) {
                $q->where('nomor_invoice', 'like', "%$keyword%")
                  ->orWhere('keterangan', 'like', "%$keyword%")
                  ->orWhere('kategori', 'like', "%$keyword%")
                  ->orWhere('jenis', 'like', "%$keyword%")
                  ->orWhere('jumlah', 'like', "%$keyword%");
            });

            // --- 2. SEARCH PPOB ---
            $ppobQuery->where(function($q) use ($keyword) {
                $q->where('order_id', 'like', "%$keyword%")         
                  ->orWhere('customer_no', 'like', "%$keyword%")    
                  ->orWhere('buyer_sku_code', 'like', "%$keyword%") 
                  ->orWhere('sn', 'like', "%$keyword%")             
                  ->orWhere('message', 'like', "%$keyword%")        
                  ->orWhere('desc', 'like', "%$keyword%")
                  ->orWhereRaw("'PPOB' LIKE ?", ["%$keyword%"]);
            });

            // --- 3. SEARCH EKSPEDISI ---
            $ekspedisiQuery->where(function($q) use ($keyword) {
                $q->where('nomor_invoice', 'like', "%$keyword%")    
                  ->orWhere('resi', 'like', "%$keyword%")           
                  ->orWhere('resi_aktual', 'like', "%$keyword%")
                  ->orWhere('expedition', 'like', "%$keyword%")     
                  ->orWhere('service_type', 'like', "%$keyword%")   
                  ->orWhere('sender_name', 'like', "%$keyword%")    
                  ->orWhere('sender_phone', 'like', "%$keyword%")   
                  ->orWhere('sender_address', 'like', "%$keyword%") 
                  ->orWhere('receiver_name', 'like', "%$keyword%")  
                  ->orWhere('receiver_phone', 'like', "%$keyword%") 
                  ->orWhere('receiver_address', 'like', "%$keyword%") 
                  ->orWhere('item_description', 'like', "%$keyword%") 
                  ->orWhere('payment_method', 'like', "%$keyword%")
                  ->orWhereRaw("'Ekspedisi' LIKE ?", ["%$keyword%"])
                  ->orWhereRaw("'JNE' LIKE ?", ["%$keyword%"])
                  ->orWhereRaw("'JNT' LIKE ?", ["%$keyword%"])
                  ->orWhereRaw("'Lion' LIKE ?", ["%$keyword%"]);
            });

            // --- 4. SEARCH TOP UP ---
            $topupQuery->where(function($q) use ($keyword) {
                $q->where('reference_id', 'like', "%$keyword%")     
                  ->orWhere('description', 'like', "%$keyword%")    
                  ->orWhere('amount', 'like', "%$keyword%")         
                  ->orWhere('status', 'like', "%$keyword%")
                  ->orWhereRaw("'Top Up Saldo' LIKE ?", ["%$keyword%"]);
            });

            // --- 5. SEARCH MARKETPLACE ---
            $marketplaceQuery->where(function($q) use ($keyword) {
                $q->where('invoice_number', 'like', "%$keyword%")
                  ->orWhere('shipping_resi', 'like', "%$keyword%")
                  ->orWhere('shipping_method', 'like', "%$keyword%")
                  ->orWhere('shipping_address', 'like', "%$keyword%")
                  ->orWhere('payment_method', 'like', "%$keyword%")
                  ->orWhereRaw("'Marketplace' LIKE ?", ["%$keyword%"]);
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
        // 4. EKSEKUSI GABUNGAN
        // ==================================================================================

        $gabungan = $manualQuery
                    ->unionAll($ppobQuery)
                    ->unionAll($ekspedisiQuery)
                    ->unionAll($topupQuery)
                    ->unionAll($marketplaceQuery);
        
        $queryFinal = DB::table(DB::raw("({$gabungan->toSql()}) as combined_table"))
                        ->mergeBindings($gabungan); 

        // ==================================================================================
        // 5. HITUNG TOTAL & BREAKDOWN PER KATEGORI (CARD DINAMIS)
        // ==================================================================================
        
        // Kita gunakan clone $queryFinal agar filter search/tanggal tetap terbawa
        $stats = (clone $queryFinal)->selectRaw("
            -- GLOBAL TOTALS
            SUM(omzet) as total_omzet,
            SUM(modal) as total_modal,
            SUM(profit) as total_profit,

            -- BREAKDOWN EKSPEDISI
            SUM(CASE WHEN kategori = 'Ekspedisi' THEN omzet ELSE 0 END) as omzet_ekspedisi,
            SUM(CASE WHEN kategori = 'Ekspedisi' THEN 1 ELSE 0 END) as count_ekspedisi,

            -- BREAKDOWN PPOB
            SUM(CASE WHEN kategori = 'PPOB' THEN omzet ELSE 0 END) as omzet_ppob,
            SUM(CASE WHEN kategori = 'PPOB' THEN 1 ELSE 0 END) as count_ppob,

            -- BREAKDOWN MARKETPLACE
            SUM(CASE WHEN kategori = 'Marketplace' THEN omzet ELSE 0 END) as omzet_marketplace,
            SUM(CASE WHEN kategori = 'Marketplace' THEN 1 ELSE 0 END) as count_marketplace,

            -- BREAKDOWN TOP UP
            SUM(CASE WHEN kategori = 'Top Up Saldo' THEN omzet ELSE 0 END) as omzet_topup,
            SUM(CASE WHEN kategori = 'Top Up Saldo' THEN 1 ELSE 0 END) as count_topup

        ")->first();

        // Siapkan variabel untuk dikirim ke View
        $summary = [
            'omzet' => $stats->total_omzet ?? 0,
            'modal' => $stats->total_modal ?? 0,
            'profit' => $stats->total_profit ?? 0,
            
            // Breakdown Detail
            'ekspedisi' => ['omzet' => $stats->omzet_ekspedisi ?? 0, 'count' => $stats->count_ekspedisi ?? 0],
            'ppob'      => ['omzet' => $stats->omzet_ppob ?? 0, 'count' => $stats->count_ppob ?? 0],
            'marketplace' => ['omzet' => $stats->omzet_marketplace ?? 0, 'count' => $stats->count_marketplace ?? 0],
            'topup'     => ['omzet' => $stats->omzet_topup ?? 0, 'count' => $stats->count_topup ?? 0],
        ];

        // ==================================================================================
        // 6. DATA TABEL
        // ==================================================================================

        $transaksi = $queryFinal
            ->orderBy('tanggal', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.keuangan.index', compact('transaksi', 'summary'));
    }
    
    // CRUD Manual
    public function store(Request $request) { Keuangan::create($request->all()); return back()->with('success','Disimpan'); }
    public function update(Request $request, $id) { Keuangan::find($id)->update($request->all()); return back()->with('success','Diupdate'); }
    public function destroy($id) { Keuangan::find($id)->delete(); return back()->with('success','Dihapus'); }
}