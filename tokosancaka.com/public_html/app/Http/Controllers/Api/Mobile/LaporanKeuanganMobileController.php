<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Store;
use Carbon\Carbon;

class LaporanKeuanganMobileController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $userId = $user->id_pengguna ?? $user->id;
        $saldoSaatIni = $user->saldo;

        // --- Filter Tanggal ---
        $startDate = $request->filled('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : Carbon::now()->subDays(30)->startOfDay();
        $endDate = $request->filled('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : Carbon::now()->endOfDay();

        // ====================================================================
        // 1. QUERY TABEL TOP_UPS (Ledger Utama: Topup, Revenue, Refund, Tarik)
        // ====================================================================
        $queryTopups = DB::table('top_ups')
            ->where('customer_id', $userId)
            ->where('status', 'success')
            ->select(
                'created_at',
                DB::raw("transaction_id as invoice"),
                DB::raw("
                    CASE
                        WHEN payment_method = 'marketplace_revenue' THEN CONCAT('Pendapatan Toko #', transaction_id)
                        WHEN payment_method = 'refund_marketplace' THEN CONCAT('Pengembalian Dana #', transaction_id)
                        WHEN payment_method = 'saldo_sancaka' THEN CONCAT('Penyesuaian Saldo #', transaction_id)
                        ELSE CONCAT('Top Up Saldo (', payment_method, ')')
                    END as description
                "),
                // Jika amount minus, jadikan expense. Jika plus, jadikan income/revenue
                DB::raw("
                    CASE
                        WHEN amount < 0 THEN 'payment'
                        WHEN payment_method = 'marketplace_revenue' THEN 'revenue'
                        ELSE 'topup'
                    END as type
                "),
                DB::raw("ABS(amount) as amount") // Ubah minus jadi plus untuk ditampilkan
            );

        // ====================================================================
        // 2. QUERY TABEL TRANSACTIONS (Topup via Payment Gateway / Legacy)
        // ====================================================================
        $queryTransactions = DB::table('transactions')
            ->where('user_id', $userId)
            ->where('status', 'success')
            ->select(
                'created_at',
                DB::raw("reference_id as invoice"),
                'description',
                DB::raw("'topup' as type"),
                'amount'
            );

        // ====================================================================
        // 3. QUERY TABEL ORDERS (Pengeluaran Belanja Pake Saldo)
        // ====================================================================
        $queryOrders = DB::table('orders')
            ->where('user_id', $userId)
            ->whereIn(DB::raw('UPPER(payment_method)'), ['SALDO', 'POTONG SALDO'])
            ->whereNotIn('status', ['pending', 'cancelled', 'failed', 'returned', 'returning'])
            ->select(
                'created_at',
                DB::raw("invoice_number as invoice"),
                DB::raw("CONCAT('Belanja Marketplace #', invoice_number) as description"),
                DB::raw("'payment' as type"),
                'total_amount as amount'
            );

        // ====================================================================
        // 4. QUERY TABEL PESANAN (Pengeluaran Ekspedisi Pake Saldo)
        // ====================================================================
        $queryPesanan = DB::table('Pesanan')
            ->where('id_pengguna_pembeli', $userId)
            ->where('payment_method', 'Potong Saldo')
            ->whereNotIn('status_pesanan', ['Dibatalkan', 'Gagal'])
            ->select(
                'tanggal_pesanan as created_at',
                DB::raw("nomor_invoice as invoice"),
                DB::raw("CONCAT('Pengiriman Paket #', nomor_invoice) as description"),
                DB::raw("'payment' as type"),
                'price as amount' // Mengambil tarif ekspedisi
            );

        // Terapkan Filter Tanggal Ke Semua Query
        $queryTopups->whereBetween('created_at', [$startDate, $endDate]);
        $queryTransactions->whereBetween('created_at', [$startDate, $endDate]);
        $queryOrders->whereBetween('created_at', [$startDate, $endDate]);
        $queryPesanan->whereBetween('tanggal_pesanan', [$startDate, $endDate]);

        // Gabungkan semuanya menggunakan UNION ALL
        $allTransactions = $queryTopups
            ->unionAll($queryTransactions)
            ->unionAll($queryOrders)
            ->unionAll($queryPesanan)
            ->orderBy('created_at', 'desc')
            ->get();

        // ====================================================================
        // 5. KALKULASI SALDO BERJALAN (RUNNING BALANCE)
        // ====================================================================
        $runningBalance = $saldoSaatIni;

        $formattedTransactions = $allTransactions->map(function ($item) use (&$runningBalance) {
            // Set saldo SETELAH transaksi ini
            $item->running_balance = $runningBalance;

            // Hitung mundur saldo SEBELUM transaksi ini
            if ($item->type === 'topup' || $item->type === 'revenue') {
                $runningBalance -= (float)$item->amount; // Kurangi karena ini pemasukan
            } else {
                $runningBalance += (float)$item->amount; // Tambah karena ini pengeluaran
            }

            return $item;
        });

        // ====================================================================
        // 6. RESPONSE JSON KE REACT NATIVE
        // ====================================================================
        return response()->json([
            'success' => true,
            'data' => [
                'saldo' => (float)$saldoSaatIni,
                'saldoAwal' => (float)$runningBalance, // Sisa hitung mundur adalah saldo awal
                'totalPemasukan' => $allTransactions->where('type', 'revenue')->sum('amount'),
                'totalTopUp' => $allTransactions->where('type', 'topup')->sum('amount'),
                'totalPengeluaran' => $allTransactions->where('type', 'payment')->sum('amount'),
                'transactions' => $formattedTransactions
            ]
        ]);
    }
}
