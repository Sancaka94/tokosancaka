<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log; // <-- Pastikan Log di-import

class LaporanKeuanganController extends Controller
{
    /**
     * Menampilkan halaman laporan keuangan yang disempurnakan.
     * Menggabungkan 4 SUMBER DATA:
     * 1. transactions (Top Up)
     * 2. orders (Pendapatan Marketplace)
     * 3. Pesanan (Pengeluaran Manual)
     * 4. order_marketplace (Pengeluaran Marketplace) <-- INI YANG BARU
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

        // --- 1. Query Pemasukan (Top Up Saldo) ---
        $topUpTransactions = DB::table('transactions')
            ->where('user_id', $userId)
            ->where('status', 'success')
            ->where('type', 'topup')
            ->select(
                'created_at',
                'description',
                DB::raw("'topup' as type"),
                'amount'
            );

        // --- 2. Query Pemasukan (Pendapatan Marketplace) ---
        // (Anda menggunakan tabel 'orders' untuk ini, bukan 'order_marketplace')
        $revenueTransactions = DB::table('orders')
            ->where('store_id', $storeId)
            ->where('status', 'completed')
            ->select(
                'created_at',
                DB::raw("CONCAT('Pendapatan dari Marketplace (Etalase) Dengan Order Id: ', invoice_number) as description"), // Diganti ke nomor_invoice
                DB::raw("'revenue' as type"),
                'subtotal as amount' // Ganti nama kolom jadi 'amount'
            );

        // --- 3. Query Pengeluaran (Pesanan Manual) ---
        $orderPayments = DB::table('Pesanan')
            ->where('id_pengguna_pembeli', $userId)
            ->where('payment_method', 'Potong Saldo')
            ->whereNotNull('price')
            ->where('price', '>', 0)
            ->select(
                'tanggal_pesanan as created_at',
                DB::raw("CONCAT('Pembayaran Pesanan Manual #', nomor_invoice) as description"),
                DB::raw("'payment' as type"),
                'price as amount' // Ganti nama kolom jadi 'amount'
            );

        // ==========================================================
        // PERBAIKAN: INI YANG HILANG
        // --- 4. Query Pengeluaran (Checkout Marketplace) ---
        // ==========================================================
        $marketplacePayments = DB::table('order_marketplace') // <-- Membaca tabel baru
            ->where('user_id', $userId) // Pembelinya adalah user ini
            ->where('payment_method', 'saldo') // Yang bayar pakai saldo
            ->where('status', '!=', 'pending') // Yang tidak pending (processing, paid, dll)
            ->where('status', '!=', 'failed')
            ->where('status', '!=', 'expired')
            ->where('status', '!=', 'canceled')
            ->select(
                'created_at',
                DB::raw("CONCAT('Pembelian Marketplace (Agen) Dengan Order Id: ', invoice_number) as description"),
                DB::raw("'payment' as type"), // Tipe sama dengan pengeluaran lain
                'total_amount as amount' // Ambil total akhir sebagai pengeluaran
            );
        // ==========================================================
        // AKHIR PERBAIKAN
        // ==========================================================


        // Terapkan filter tanggal jika ada
        if ($startDate && $endDate) {
            $topUpTransactions->whereBetween('created_at', [$startDate, $endDate]);
            $revenueTransactions->whereBetween('created_at', [$startDate, $endDate]);
            $orderPayments->whereBetween('tanggal_pesanan', [$startDate, $endDate]);
            $marketplacePayments->whereBetween('created_at', [$startDate, $endDate]); // <-- Tambahkan filter
        }

        // Gabungkan SEMUA transaksi (4 sumber)
        $results = $topUpTransactions
            ->unionAll($revenueTransactions)
            ->unionAll($orderPayments)
            ->unionAll($marketplacePayments) // <-- Tambahkan query baru
            ->orderBy('created_at', 'desc')
            ->get();

        $results->transform(function($item) {
            $item->created_at = Carbon::parse($item->created_at);
            return $item;
        });
        
        // --- PERBAIKAN LOGIKA SISA SALDO (Dihitung Mundur) ---
        $runningBalance = $saldoSaatIni;
        
        // Hitung total untuk kartu (berdasarkan hasil query)
        $totalPemasukan = $results->where('type', 'revenue')->sum('amount');
        $totalTopUp = $results->where('type', 'topup')->sum('amount');
        $totalPengeluaran = $results->where('type', 'payment')->sum('amount'); // Otomatis menggabungkan manual + marketplace

        // --- Hitung Saldo Awal Periode (Jika ada filter) ---
        $saldoAwal = 0; 
        if ($startDate && $endDate) {
            
            $saldoAwalTopUp = DB::table('transactions')
                ->where('user_id', $userId)->where('status', 'success')->where('type', 'topup')
                ->where('created_at', '<', $startDate)
                ->sum('amount');

            $saldoAwalRevenue = DB::table('orders')
                ->where('store_id', $storeId)->where('status', 'completed')
                ->where('created_at', '<', $startDate)
                ->sum('subtotal');

            $saldoAwalSpendingManual = DB::table('Pesanan')
                ->where('id_pengguna_pembeli', $userId)->where('payment_method', 'Potong Saldo')
                ->where('tanggal_pesanan', '<', $startDate)
                ->sum('price');
            
            // Tambahkan perhitungan saldo awal pengeluaran marketplace
            $revenueTransactions = DB::table('order_marketplace') // <-- DIGANTI ke tabel baru
            ->where('store_id', $storeId) // Ini adalah toko milik SI PENJUAL
            ->where('status', 'completed') // Asumsi status selesai
            ->select(
                'created_at',
                DB::raw("CONCAT('Pendapatan dari Marketplace (Etalase) Dengan Order Id: ', invoice_number) as description"), // <-- DIPERBAIKI
                DB::raw("'revenue' as type"),
                'subtotal as amount' // Anda bisa ganti ke 'total_amount' jika itu pendapatan bersih Anda
            );

            $saldoAwal = ($saldoAwalTopUp + $saldoAwalRevenue) - ($saldoAwalSpendingManual + $saldoAwalSpendingMarketplace);
            
            // Saldo berjalan dimulai dari saldo akhir periode yang difilter
            $runningBalance = $saldoAwal + $totalTopUp + $totalPemasukan - $totalPengeluaran;
        }

        // Hitung Sisa Saldo secara mundur
        $results->transform(function ($item) use (&$runningBalance) {
            $item->running_balance = $runningBalance;
            // Hitung saldo SEBELUM transaksi ini terjadi
            if ($item->type === 'topup' || $item->type === 'revenue') {
                $runningBalance -= (float)$item->amount; // Kurangi pemasukan
            } else { // 'payment'
                $runningBalance += (float)$item->amount; // Tambahkan kembali pengeluaran
            }
            return $item;
        });
        
        // --- Paginasi Manual ---
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 15;
        $currentPageResults = $results->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $transactions = new LengthAwarePaginator($currentPageResults, count($results), $perPage, $currentPage, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
        ]);

        return view('customer.laporan.index', [
            'saldo'               => $saldoSaatIni,
            'totalPemasukan'      => $totalPemasukan, // (Pendapatan Marketplace)
            'totalTopUp'          => $totalTopUp, // (Top Up Saldo)
            'totalPengeluaran'    => $totalPengeluaran, // (Gabungan Manual + Marketplace)
            'transactions'        => $transactions,
            'saldoAwal'           => $saldoAwal,
            'startDate'           => $request->input('start_date'),
            'endDate'             => $request->input('end_date'),
        ]);
    }
}