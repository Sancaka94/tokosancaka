<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon; // ✅ Import Carbon untuk memanipulasi tanggal

class LaporanKeuanganController extends Controller
{
    /**
     * Menampilkan halaman laporan keuangan untuk customer yang login.
     */
    public function index()
    {
        $userId = Auth::id();

        // Menghitung total pemasukan (dari top up yang berhasil)
        $totalPemasukan = DB::table('top_ups')
                            ->where('customer_id', $userId)
                            ->where('status', 'success')
                            ->sum('amount');

        // Menghitung total pengeluaran (berdasarkan total harga barang di pesanan)
        $totalPengeluaran = DB::table('Pesanan')
                                ->where('id_pengguna_pembeli', $userId)
                                ->sum('total_harga_barang');

        // Menghitung saldo saat ini
        $saldo = $totalPemasukan - $totalPengeluaran;
        

        // Mengambil riwayat top up
        $topUps = DB::table('top_ups')
                    ->where('customer_id', $userId)
                    ->where('status', 'success')
                    ->select(
                        'created_at',
                        DB::raw("'Top Up Saldo' as description"),
                        DB::raw("'masuk' as type"),
                        'amount'
                    );

        // Mengambil riwayat pembayaran pesanan
        $orderPayments = DB::table('Pesanan')
                            ->where('id_pengguna_pembeli', $userId)
                            ->select(
                                'tanggal_pesanan as created_at',
                                DB::raw("CONCAT('Pembayaran Pesanan #', nomor_invoice) as description"),
                                DB::raw("'keluar' as type"),
                                'total_harga_barang as amount'
                            );

        // Menggabungkan kedua query dan mengurutkan berdasarkan tanggal terbaru
        $transactionsQuery = $topUps->unionAll($orderPayments)->orderBy('created_at', 'desc');
        
        $results = $transactionsQuery->get();

        // ✅ PERBAIKAN: Mengonversi string tanggal menjadi objek Carbon setelah diambil dari DB
        $results->transform(function ($item) {
            $item->created_at = Carbon::parse($item->created_at);
            return $item;
        });

        // Membuat paginasi manual dari hasil query gabungan
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 15;
        $currentPageResults = $results->slice(($currentPage - 1) * $perPage, $perPage)->all();
        $transactions = new LengthAwarePaginator($currentPageResults, count($results), $perPage);
        $transactions->setPath(request()->url());

        // Mengirim semua data yang dibutuhkan ke view
        return view('customer.laporan.index', compact(
            'saldo', 
            'totalPemasukan', 
            'totalPengeluaran',
            'transactions'
        ));
    }
}
