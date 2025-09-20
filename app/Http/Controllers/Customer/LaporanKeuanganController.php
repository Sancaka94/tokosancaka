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

        $saldoSaatIni = $user->saldo;

        // --- Filter Tanggal ---
        $startDate = $request->filled('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : null;
        $endDate = $request->filled('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : null;

        // --- Query untuk Riwayat Transaksi ---
        $generalTransactions = DB::table('transactions')
            ->where('user_id', $userId)
            ->select('created_at', 'description', 'type', 'amount');

        $orderPayments = DB::table('Pesanan')
            ->where('id_pengguna_pembeli', $userId)
            ->select(
                'tanggal_pesanan as created_at',
                DB::raw("CONCAT('Pembayaran Pesanan #', nomor_invoice) as description"),
                DB::raw("'payment' as type"),
                'total_harga_barang as amount'
            );

        // --- Query untuk Saldo Awal Periode ---
        $saldoAwalPemasukan = DB::table('transactions')
            ->where('user_id', $userId)->where('type', 'topup')
            ->when($startDate, fn($q) => $q->where('created_at', '<', $startDate))
            ->sum('amount');
        
        $saldoAwalPengeluaran = DB::table('transactions')
            ->where('user_id', $userId)->whereIn('type', ['withdrawal', 'payment'])
            ->when($startDate, fn($q) => $q->where('created_at', '<', $startDate))
            ->sum('amount');

        $saldoAwalPengeluaranPesanan = DB::table('Pesanan')
            ->where('id_pengguna_pembeli', $userId)
            ->when($startDate, fn($q) => $q->where('tanggal_pesanan', '<', $startDate))
            ->sum('total_harga_barang');

        $saldoAwal = $saldoAwalPemasukan - ($saldoAwalPengeluaran + $saldoAwalPengeluaranPesanan);

        if ($startDate && $endDate) {
            $generalTransactions->whereBetween('created_at', [$startDate, $endDate]);
            $orderPayments->whereBetween('tanggal_pesanan', [$startDate, $endDate]);
        }

        $results = $generalTransactions->unionAll($orderPayments)->orderBy('created_at', 'asc')->get();

        // ✅ FIX: Logika diubah agar mengembalikan seluruh item, bukan hanya tanggal.
        // Ini memperbaiki error "Unknown getter 'type'".
        $results->transform(function($item) {
            $item->created_at = Carbon::parse($item->created_at);
            return $item; // Mengembalikan objek item yang sudah dimodifikasi
        });
        
        // Menghitung sisa saldo berjalan MAJU dari saldo awal.
        $runningBalance = $saldoAwal;
        $results->transform(function ($item) use (&$runningBalance) {
            if ($item->type === 'topup') {
                $runningBalance += (float)$item->amount;
            } else {
                $runningBalance -= (float)$item->amount;
            }
            $item->running_balance = $runningBalance;
            return $item;
        });
        
        $results = $results->reverse();

        $totalPemasukan = $results->where('type', 'topup')->sum('amount');
        $totalPengeluaran = $results->whereIn('type', ['withdrawal', 'payment'])->sum('amount');
        
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 15;
        $currentPageResults = $results->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $transactions = new LengthAwarePaginator($currentPageResults, count($results), $perPage, $currentPage, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
        ]);
        
        return view('customer.laporan.index', [
            'saldo'             => $saldoSaatIni,
            'totalPemasukan'    => $totalPemasukan,
            'totalPengeluaran'  => $totalPengeluaran,
            'transactions'      => $transactions,
            'saldoAwal'         => $saldoAwal,
            'startDate'         => $request->input('start_date'),
            'endDate'           => $request->input('end_date'),
        ]);
    }
}

