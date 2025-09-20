<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

class LaporanKeuanganController extends Controller
{
    /**
     * Menampilkan halaman laporan keuangan yang disempurnakan dengan filter tanggal.
     * Menggabungkan data dari tabel 'transactions' dan 'Pesanan' untuk riwayat yang lengkap.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $userId = $user->id_pengguna;

        // Saldo saat ini tetap diambil langsung dari user, ini adalah sumber paling akurat.
        $saldoSaatIni = $user->saldo;

        // --- Filter Tanggal ---
        $startDate = $request->filled('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : null;
        $endDate = $request->filled('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : null;

        // --- Query untuk Riwayat Transaksi ---

        // 1. Data dari tabel 'transactions' (Top up / Pengurangan oleh Admin)
        $generalTransactions = DB::table('transactions')
            ->where('user_id', $userId)
            ->select('created_at', 'description', 'type', 'amount');

        // 2. Data dari tabel 'Pesanan' (Pembayaran untuk pesanan)
        $orderPayments = DB::table('Pesanan')
            ->where('id_pengguna_pembeli', $userId)
            ->select(
                'tanggal_pesanan as created_at',
                DB::raw("CONCAT('Pembayaran Pesanan #', nomor_invoice) as description"),
                DB::raw("'payment' as type"),
                'total_harga_barang as amount'
            );

        // Terapkan filter tanggal ke setiap sub-query jika ada
        if ($startDate && $endDate) {
            $generalTransactions->whereBetween('created_at', [$startDate, $endDate]);
            $orderPayments->whereBetween('tanggal_pesanan', [$startDate, $endDate]);
        }

        // Gabungkan kedua sumber data dan urutkan berdasarkan tanggal terbaru
        $transactionsQuery = $generalTransactions->unionAll($orderPayments)->orderBy('created_at', 'desc');
        
        $results = $transactionsQuery->get();

        // Konversi string tanggal menjadi objek Carbon
        $results->transform(function ($item) {
            $item->created_at = Carbon::parse($item->created_at);
            return $item;
        });

        // ✅ PENYEMPURNAAN: Menghitung sisa saldo berjalan untuk setiap transaksi
        $runningBalance = $saldoSaatIni;
        $results->transform(function ($item) use (&$runningBalance) {
            // Saldo yang ditampilkan adalah saldo SETELAH transaksi ini terjadi.
            $item->running_balance = $runningBalance;

            // Hitung mundur saldo untuk transaksi SEBELUMNYA (yang lebih lama).
            if ($item->type === 'topup') {
                $runningBalance -= (float)$item->amount; // Kurangi karena ini pemasukan
            } else { // 'withdrawal' or 'payment'
                $runningBalance += (float)$item->amount; // Tambah karena ini pengeluaran
            }
            return $item;
        });

        // --- Perhitungan Total Pemasukan & Pengeluaran Berdasarkan Hasil Gabungan ---
        $totalPemasukan = $results->where('type', 'topup')->sum('amount');
        $totalPengeluaran = $results->whereIn('type', ['withdrawal', 'payment'])->sum('amount');
        
        // Paginasi Manual dari hasil query gabungan
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 15;
        $currentPageResults = $results->slice(($currentPage - 1) * $perPage, $perPage)->all();
        $transactions = new LengthAwarePaginator($currentPageResults, count($results), $perPage, $currentPage, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
        ]);
        
        // Kirim semua data, termasuk tanggal filter, ke view
        return view('customer.laporan.index', [
            'saldo'             => $saldoSaatIni,
            'totalPemasukan'    => $totalPemasukan,
            'totalPengeluaran'  => $totalPengeluaran,
            'transactions'      => $transactions,
            'startDate'         => $request->input('start_date'),
            'endDate'           => $request->input('end_date'),
        ]);
    }
}

