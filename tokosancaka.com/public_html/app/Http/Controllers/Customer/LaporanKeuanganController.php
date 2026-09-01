<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

class LaporanKeuanganController extends Controller
{
    /**
     * Menampilkan halaman laporan keuangan dengan standar Mutasi Rekening (Buku Besar).
     * Menggabungkan 9 SUMBER DATA secara aman menggunakan UNION di level database.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $userId = $user->id_pengguna; 
        $saldoSaatIni = $user->saldo;

        $store = Store::where('user_id', $userId)->first();
        $storeId = $store ? $store->id : null;

        // --- Filter Tanggal ---
        $startDate = $request->filled('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : null;
        $endDate = $request->filled('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : null;

        // ==========================================================
        // A. QUERY PEMASUKAN & KOMISI
        // ==========================================================
        $topUpTransactions = DB::table('transactions')
            ->where('user_id', $userId)->where('status', 'success')->where('type', 'topup')
            ->select('created_at', DB::raw("'Top up saldo' as description"), DB::raw("'topup' as type"), 'amount', 'reference_id as reference', DB::raw("'topup' as source_type"));

        // Pastikan 'subtotal' adalah dana bersih yang diterima penjual (tanpa potongan admin)
        $revenueTransactions = DB::table('orders')
            ->where('store_id', $storeId)->where('status', 'completed')
            ->select('created_at', DB::raw("'Pendapatan Penjualan Marketplace' as description"), DB::raw("'revenue' as type"), 'subtotal as amount', 'invoice_number as reference', DB::raw("'marketplace' as source_type"));

        $autokirimKomisi = DB::table('pesanan_autokirim')
            ->where('user_id', $userId)->where('komisi_agen', '>', 0)
            ->whereIn('status', ['terkirim', 'selesai', 'sukses', 'delivered', 'success', 'completed'])
            ->select('updated_at as created_at', DB::raw("'Pencairan Komisi Autokirim' as description"), DB::raw("'revenue' as type"), 'komisi_agen as amount', 'order_id as reference', DB::raw("'autokirim' as source_type"));

        // ==========================================================
        // B. QUERY PENGELUARAN (Mencatat semua potongan saldo)
        // ==========================================================
        $orderPayments = DB::table('Pesanan')
            ->where('id_pengguna_pembeli', $userId)->where('payment_method', 'Potong Saldo')
            ->whereNotNull('price')->where('price', '>', 0)
            ->select('tanggal_pesanan as created_at', DB::raw("'Bayar Ongkir (Kirim Manual)' as description"), DB::raw("'payment' as type"), 'price as amount', 'nomor_invoice as reference', DB::raw("'manual' as source_type"));

        $marketplacePayments = DB::table('order_marketplace')
            ->where('user_id', $userId)->where('payment_method', 'saldo')
            ->whereNotIn('status', ['pending', 'expired']) 
            ->select('created_at', DB::raw("'Belanja Marketplace' as description"), DB::raw("'payment' as type"), 'total_amount as amount', 'invoice_number as reference', DB::raw("'marketplace' as source_type"));

        $autokirimPayments = DB::table('pesanan_autokirim')
            ->where('user_id', $userId)->where('metode_pembayaran', 'potong_saldo')
            ->where('status', '!=', 'waiting_payment') 
            ->select('created_at', DB::raw("'Bayar Ongkir (Autokirim)' as description"), DB::raw("'payment' as type"), 'grand_total as amount', 'order_id as reference', DB::raw("'autokirim' as source_type"));

        // ==========================================================
        // C. QUERY REFUND (Pengembalian dana dari transaksi gagal/batal)
        // ==========================================================
        $refundManual = DB::table('Pesanan')
            ->where('id_pengguna_pembeli', $userId)->where('payment_method', 'Potong Saldo')
            ->whereIn('status_pesanan', ['Batal', 'Gagal', 'Dibatalkan', 'batal', 'gagal'])
            ->select(DB::raw("COALESCE(updated_at, tanggal_pesanan) as created_at"), DB::raw("'Refund Saldo (Kirim Manual)' as description"), DB::raw("'refund' as type"), 'price as amount', 'nomor_invoice as reference', DB::raw("'manual' as source_type"));

        $refundMarketplace = DB::table('order_marketplace')
            ->where('user_id', $userId)->where('payment_method', 'saldo')
            ->whereIn('status', ['canceled', 'failed', 'returned'])
            ->select(DB::raw("COALESCE(updated_at, created_at) as created_at"), DB::raw("'Refund Saldo (Belanja Marketplace)' as description"), DB::raw("'refund' as type"), 'total_amount as amount', 'invoice_number as reference', DB::raw("'marketplace' as source_type"));

        $refundAutokirim = DB::table('pesanan_autokirim')
            ->where('user_id', $userId)->where('metode_pembayaran', 'potong_saldo')
            ->whereIn('status', ['batal', 'gagal'])
            ->select(DB::raw("COALESCE(updated_at, created_at) as created_at"), DB::raw("'Refund Saldo (Autokirim)' as description"), DB::raw("'refund' as type"), 'grand_total as amount', 'order_id as reference', DB::raw("'autokirim' as source_type"));
            
        // ==========================================================
        // GABUNGKAN DATA KESELURUHAN & FILTER TANGGAL
        // ==========================================================
        $unionQuery = $topUpTransactions
            ->unionAll($revenueTransactions)
            ->unionAll($autokirimKomisi)
            ->unionAll($orderPayments)
            ->unionAll($marketplacePayments)
            ->unionAll($autokirimPayments)
            ->unionAll($refundManual)
            ->unionAll($refundMarketplace)
            ->unionAll($refundAutokirim);

        // Eksekusi menjadi Query Builder baru agar Date Filter & OrderBy optimal di level Database
        $baseQuery = DB::query()->fromSub($unionQuery, 'transactions_union');

        if ($startDate && $endDate) {
            $baseQuery->whereBetween('created_at', [$startDate, $endDate]);
        }

        // Ambil data dari database
        $results = $baseQuery->orderBy('created_at', 'desc')->get();

        $results->transform(function($item) {
            $item->created_at = Carbon::parse($item->created_at);
            return $item;
        });
        
        // ==========================================================
        // KALKULASI SUMMARY & RUNNING BALANCE
        // ==========================================================
        $totalPemasukan   = $results->where('type', 'revenue')->sum('amount');
        $totalTopUp       = $results->where('type', 'topup')->sum('amount');
        $totalRefund      = $results->where('type', 'refund')->sum('amount');
        $totalPengeluaran = $results->where('type', 'payment')->sum('amount'); 

        $runningBalance = $saldoSaatIni;
        $saldoAwal = 0; 
        
        if ($startDate && $endDate) {
            // OPTIMASI: Hitung Saldo Ujung Atas (EndDate) dengan Reverse Engineering
            // Ini mencegah drift/kebocoran jika ada transaksi admin manual di masa lalu yang tidak tertangkap query
            $pemasukanSetelahEndDate = DB::query()->fromSub($unionQuery, 'trx_future')
                ->where('created_at', '>', $endDate)
                ->whereIn('type', ['topup', 'revenue', 'refund'])
                ->sum('amount');

            $pengeluaranSetelahEndDate = DB::query()->fromSub($unionQuery, 'trx_future')
                ->where('created_at', '>', $endDate)
                ->where('type', 'payment')
                ->sum('amount');

            // Saldo saat EndDate = Saldo Hari Ini - Pemasukan Masa Depan + Pengeluaran Masa Depan
            $runningBalance = $saldoSaatIni - $pemasukanSetelahEndDate + $pengeluaranSetelahEndDate;
            
            // Saldo Awal (sebelum StartDate) = Saldo di EndDate - Mutasi di dalam Periode
            $saldoAwal = $runningBalance - ($totalTopUp + $totalPemasukan + $totalRefund) + $totalPengeluaran;
        } else {
            $saldoAwal = $saldoSaatIni - ($totalTopUp + $totalPemasukan + $totalRefund) + $totalPengeluaran;
        }

        // Kalkulasi Sisa Saldo secara mundur untuk setiap baris
        $results->transform(function ($item) use (&$runningBalance) {
            $item->running_balance = $runningBalance;
            if (in_array($item->type, ['topup', 'revenue', 'refund'])) {
                $runningBalance -= (float)$item->amount; // Mundur: Kurangi pemasukan
            } else { 
                $runningBalance += (float)$item->amount; // Mundur: Kembalikan pengeluaran
            }
            return $item;
        });
        
        // Paginasi Manual berbasis Collection
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 15;
        $currentPageResults = $results->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $transactions = new LengthAwarePaginator($currentPageResults, count($results), $perPage, $currentPage, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
            'query' => $request->query() // Pastikan filter tanggal tidak hilang saat pindah page
        ]);

        return view('customer.laporan.index', [
            'saldo'               => $saldoSaatIni,
            'totalPemasukan'      => $totalPemasukan + $totalRefund, // Refund dianggap sebagai uang kembali
            'totalTopUp'          => $totalTopUp,
            'totalPengeluaran'    => $totalPengeluaran,
            'transactions'        => $transactions,
            'saldoAwal'           => $saldoAwal,
            'startDate'           => $request->input('start_date'),
            'endDate'             => $request->input('end_date'),
        ]);
    }
}