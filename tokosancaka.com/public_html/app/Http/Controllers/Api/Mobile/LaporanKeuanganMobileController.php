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

        $store = Store::where('user_id', $userId)->first();
        $storeId = $store ? $store->id : null;

        // --- Filter Tanggal ---
        $startDate = $request->filled('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : Carbon::now()->subDays(30)->startOfDay();
        $endDate = $request->filled('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : Carbon::now()->endOfDay();

        // --- 1. Query Pemasukan (Top Up Saldo) ---
        $topUpTransactions = DB::table('transactions')
            ->where('user_id', $userId)
            ->where('status', 'success')
            ->where('type', 'topup')
            ->select('created_at', 'description', DB::raw("'topup' as type"), 'amount');

        // --- 2. Query Pemasukan (Pendapatan Marketplace dari tabel orders) ---
        $revenueTransactions = DB::table('orders')
            ->where('store_id', $storeId)
            ->where('status', 'completed')
            ->select('created_at', DB::raw("CONCAT('Pendapatan Marketplace #', invoice_number) as description"), DB::raw("'revenue' as type"), 'subtotal as amount');

        // --- 3. Query Pengeluaran (Pesanan Manual dari tabel Pesanan) ---
        $orderPayments = DB::table('Pesanan')
            ->where('id_pengguna_pembeli', $userId)
            ->where('payment_method', 'Potong Saldo')
            ->where('price', '>', 0)
            ->select('tanggal_pesanan as created_at', DB::raw("CONCAT('Pembayaran Pesanan Manual #', nomor_invoice) as description"), DB::raw("'payment' as type"), 'price as amount');

        // --- 4. Query Pengeluaran (Checkout Marketplace dari tabel orders) ---
        $marketplacePayments = DB::table('orders')
            ->where('user_id', $userId)
            ->whereIn('payment_method', ['saldo', 'POTONG SALDO'])
            ->whereNotIn('status', ['pending', 'failed', 'expired', 'cancelled'])
            ->select('created_at', DB::raw("CONCAT('Pembelian Marketplace #', invoice_number) as description"), DB::raw("'payment' as type"), 'total_amount as amount');

        // Terapkan filter tanggal ke semua query
        $topUpTransactions->whereBetween('created_at', [$startDate, $endDate]);
        $revenueTransactions->whereBetween('created_at', [$startDate, $endDate]);
        $orderPayments->whereBetween('tanggal_pesanan', [$startDate, $endDate]);
        $marketplacePayments->whereBetween('created_at', [$startDate, $endDate]);

        // Gabungkan SEMUA
        $results = $topUpTransactions
            ->unionAll($revenueTransactions)
            ->unionAll($orderPayments)
            ->unionAll($marketplacePayments)
            ->orderBy('created_at', 'desc')
            ->get();

        // --- HITUNG SALDO AWAL PERIODE ---
        // (Semua transaksi sukses sebelum $startDate)
        $saldoAwalTopUp = DB::table('transactions')->where('user_id', $userId)->where('status', 'success')->where('created_at', '<', $startDate)->sum('amount');
        $saldoAwalRev   = DB::table('orders')->where('store_id', $storeId)->where('status', 'completed')->where('created_at', '<', $startDate)->sum('subtotal');
        $saldoAwalOut1  = DB::table('Pesanan')->where('id_pengguna_pembeli', $userId)->where('payment_method', 'Potong Saldo')->where('tanggal_pesanan', '<', $startDate)->sum('price');
        $saldoAwalOut2  = DB::table('orders')->where('user_id', $userId)->whereIn('payment_method', ['saldo', 'POTONG SALDO'])->whereNotIn('status', ['pending', 'failed', 'expired', 'cancelled'])->where('created_at', '<', $startDate)->sum('total_amount');

        $saldoAwal = ($saldoAwalTopUp + $saldoAwalRev) - ($saldoAwalOut1 + $saldoAwalOut2);

        // --- HITUNG RUNNING BALANCE (Mundur dari Saldo Saat Ini) ---
        $runningBalance = $saldoSaatIni;
        $formattedTransactions = $results->map(function ($item) use (&$runningBalance) {
            $item->running_balance = $runningBalance;
            // Jika baris ini adalah pemasukan, maka sisa saldo sebelumnya lebih kecil
            if ($item->type === 'topup' || $item->type === 'revenue') {
                $runningBalance -= (float)$item->amount;
            } else {
                $runningBalance += (float)$item->amount;
            }
            return $item;
        });

        return response()->json([
            'success' => true,
            'data' => [
                'saldo' => (float)$saldoSaatIni,
                'saldoAwal' => (float)$saldoAwal,
                'totalPemasukan' => $results->where('type', 'revenue')->sum('amount'),
                'totalTopUp' => $results->where('type', 'topup')->sum('amount'),
                'totalPengeluaran' => $results->where('type', 'payment')->sum('amount'),
                'transactions' => $formattedTransactions
            ]
        ]);
    }
}
