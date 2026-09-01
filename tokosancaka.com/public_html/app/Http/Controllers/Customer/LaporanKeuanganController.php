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
     * Menampilkan halaman laporan keuangan yang disempurnakan.
     * Menggabungkan 6 SUMBER DATA:
     * 1. transactions (Top Up)
     * 2. orders (Pendapatan Marketplace)
     * 3. Pesanan (Pengeluaran Manual)
     * 4. order_marketplace (Pengeluaran Marketplace)
     * 5. pesanan_autokirims (Pengeluaran Bayar Ongkir via Saldo) <-- BARU
     * 6. pesanan_autokirims (Pemasukan Pencairan Komisi Agen) <-- BARU
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
        $revenueTransactions = DB::table('orders')
            ->where('store_id', $storeId)
            ->where('status', 'completed')
            ->select(
                'created_at',
                DB::raw("CONCAT('Pendapatan dari Marketplace #', invoice_number) as description"),
                DB::raw("'revenue' as type"),
                'subtotal as amount'
            );

        // --- 3. Query Pengeluaran (Pesanan Kirim Manual) ---
        $orderPayments = DB::table('Pesanan')
            ->where('id_pengguna_pembeli', $userId)
            ->where('payment_method', 'Potong Saldo')
            ->whereNotNull('price')
            ->where('price', '>', 0)
            ->select(
                'tanggal_pesanan as created_at',
                DB::raw("CONCAT('Pembayaran Kirim Paket Manual #', nomor_invoice) as description"),
                DB::raw("'payment' as type"),
                'price as amount'
            );

        // --- 4. Query Pengeluaran (Checkout Marketplace) ---
        $marketplacePayments = DB::table('order_marketplace')
            ->where('user_id', $userId)
            ->where('payment_method', 'saldo')
            ->whereNotIn('status', ['pending', 'failed', 'expired', 'canceled'])
            ->select(
                'created_at',
                DB::raw("CONCAT('Belanja Marketplace #', invoice_number) as description"),
                DB::raw("'payment' as type"),
                'total_amount as amount'
            );

        // ==========================================================
        // TAMBAHAN DATABASE AUTOKIRIM (PENGELUARAN & PEMASUKAN)
        // ==========================================================
        
        // --- 5. Query Pengeluaran (Pembayaran Autokirim via Saldo) ---
        $autokirimPayments = DB::table('pesanan_autokirims')
            ->where('user_id', $userId)
            ->where('metode_pembayaran', 'potong_saldo')
            ->whereNotIn('status', ['batal', 'gagal', 'waiting_payment'])
            ->select(
                'created_at',
                DB::raw("CONCAT('Pembayaran Resi Autokirim #', COALESCE(awb_number, order_id)) as description"),
                DB::raw("'payment' as type"),
                'grand_total as amount'
            );

        // --- 6. Query Pemasukan (Pencairan Komisi Agen Autokirim) ---
        $autokirimKomisi = DB::table('pesanan_autokirims')
            ->where('user_id', $userId)
            ->where('komisi_agen', '>', 0)
            // Komisi cair pada saat status pesanan sukses
            ->whereIn('status', ['terkirim', 'selesai', 'sukses', 'delivered', 'success', 'completed'])
            ->select(
                'updated_at as created_at', // Menggunakan waktu update karena komisi cair saat status berubah
                DB::raw("CONCAT('Pencairan Komisi Agen Autokirim #', COALESCE(awb_number, order_id)) as description"),
                DB::raw("'revenue' as type"),
                'komisi_agen as amount'
            );

        // Terapkan filter tanggal jika ada
        if ($startDate && $endDate) {
            $topUpTransactions->whereBetween('created_at', [$startDate, $endDate]);
            $revenueTransactions->whereBetween('created_at', [$startDate, $endDate]);
            $orderPayments->whereBetween('tanggal_pesanan', [$startDate, $endDate]);
            $marketplacePayments->whereBetween('created_at', [$startDate, $endDate]);
            $autokirimPayments->whereBetween('created_at', [$startDate, $endDate]);
            $autokirimKomisi->whereBetween('updated_at', [$startDate, $endDate]);
        }

        // Gabungkan SEMUA transaksi (6 sumber)
        $results = $topUpTransactions
            ->unionAll($revenueTransactions)
            ->unionAll($orderPayments)
            ->unionAll($marketplacePayments)
            ->unionAll($autokirimPayments)
            ->unionAll($autokirimKomisi)
            ->orderBy('created_at', 'desc')
            ->get();

        $results->transform(function($item) {
            $item->created_at = Carbon::parse($item->created_at);
            return $item;
        });
        
        // --- Hitung Total Berdasarkan Kategori ---
        $totalPemasukan = $results->where('type', 'revenue')->sum('amount'); // Gabungan Jualan Marketplace + Komisi Autokirim
        $totalTopUp = $results->where('type', 'topup')->sum('amount');
        $totalPengeluaran = $results->where('type', 'payment')->sum('amount'); // Gabungan Belanja, Kirim Manual, & Autokirim

        $runningBalance = $saldoSaatIni;
        $saldoAwal = 0; 
        
        // --- PERBAIKAN FATAL BUG PADA PERHITUNGAN SALDO AWAL ---
        if ($startDate && $endDate) {
            
            // Pemasukan Masa Lalu
            $saldoAwalTopUp = DB::table('transactions')
                ->where('user_id', $userId)->where('status', 'success')->where('type', 'topup')
                ->where('created_at', '<', $startDate)->sum('amount');

            $saldoAwalRevenue = DB::table('orders')
                ->where('store_id', $storeId)->where('status', 'completed')
                ->where('created_at', '<', $startDate)->sum('subtotal');

            $saldoAwalKomisiAutokirim = DB::table('pesanan_autokirims')
                ->where('user_id', $userId)->where('komisi_agen', '>', 0)
                ->whereIn('status', ['terkirim', 'selesai', 'sukses', 'delivered', 'success', 'completed'])
                ->where('updated_at', '<', $startDate)->sum('komisi_agen');

            // Pengeluaran Masa Lalu
            $saldoAwalSpendingManual = DB::table('Pesanan')
                ->where('id_pengguna_pembeli', $userId)->where('payment_method', 'Potong Saldo')
                ->where('tanggal_pesanan', '<', $startDate)->sum('price');
            
            $saldoAwalSpendingMarketplace = DB::table('order_marketplace')
                ->where('user_id', $userId)->where('payment_method', 'saldo')
                ->whereNotIn('status', ['pending', 'failed', 'expired', 'canceled'])
                ->where('created_at', '<', $startDate)->sum('total_amount');

            $saldoAwalSpendingAutokirim = DB::table('pesanan_autokirims')
                ->where('user_id', $userId)->where('metode_pembayaran', 'potong_saldo')
                ->whereNotIn('status', ['batal', 'gagal', 'waiting_payment'])
                ->where('created_at', '<', $startDate)->sum('grand_total');

            // Kalkulasi Matematika Saldo Awal Murni
            $totalPemasukanMasaLalu = $saldoAwalTopUp + $saldoAwalRevenue + $saldoAwalKomisiAutokirim;
            $totalPengeluaranMasaLalu = $saldoAwalSpendingManual + $saldoAwalSpendingMarketplace + $saldoAwalSpendingAutokirim;
            
            $saldoAwal = $totalPemasukanMasaLalu - $totalPengeluaranMasaLalu;
            
            // Saldo berjalan dimulai dari saldo akhir periode yang difilter
            $runningBalance = $saldoAwal + $totalTopUp + $totalPemasukan - $totalPengeluaran;
        }

        // Hitung Sisa Saldo secara mundur ke bawah pada tabel
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
            'totalPemasukan'      => $totalPemasukan,
            'totalTopUp'          => $totalTopUp,
            'totalPengeluaran'    => $totalPengeluaran,
            'transactions'        => $transactions,
            'saldoAwal'           => $saldoAwal,
            'startDate'           => $request->input('start_date'),
            'endDate'             => $request->input('end_date'),
        ]);
    }
}