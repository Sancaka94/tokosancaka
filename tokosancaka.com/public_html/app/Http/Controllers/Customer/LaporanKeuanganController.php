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
     * Menggabungkan 9 SUMBER DATA:
     * Pemasukan: Top Up, Pendapatan Toko, Komisi Autokirim, & 3 Jenis Refund (Batal).
     * Pengeluaran: Pesanan Manual, Belanja Marketplace, & Pesanan Autokirim.
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
            ->select('created_at', 'description', DB::raw("'topup' as type"), 'amount');

        $revenueTransactions = DB::table('orders')
            ->where('store_id', $storeId)->where('status', 'completed')
            ->select('created_at', DB::raw("CONCAT('Pendapatan Toko #', invoice_number) as description"), DB::raw("'revenue' as type"), 'subtotal as amount');

        // PERBAIKAN: Hapus huruf 's' pada pesanan_autokirim
        $autokirimKomisi = DB::table('pesanan_autokirim')
            ->where('user_id', $userId)->where('komisi_agen', '>', 0)
            ->whereIn('status', ['terkirim', 'selesai', 'sukses', 'delivered', 'success', 'completed'])
            ->select('updated_at as created_at', DB::raw("CONCAT('Pencairan Komisi Agen #', COALESCE(awb_number, order_id)) as description"), DB::raw("'revenue' as type"), 'komisi_agen as amount');

        // ==========================================================
        // B. QUERY PENGELUARAN (TIDAK ADA FILTER BATAL AGAR TERCATAT)
        // ==========================================================
        $orderPayments = DB::table('Pesanan')
            ->where('id_pengguna_pembeli', $userId)->where('payment_method', 'Potong Saldo')
            ->whereNotNull('price')->where('price', '>', 0)
            ->select('tanggal_pesanan as created_at', DB::raw("CONCAT('Potong Saldo (Kirim Manual) #', nomor_invoice) as description"), DB::raw("'payment' as type"), 'price as amount');

        $marketplacePayments = DB::table('order_marketplace')
            ->where('user_id', $userId)->where('payment_method', 'saldo')
            ->whereNotIn('status', ['pending', 'expired']) // Hanya filter yang gagal checkout di awal
            ->select('created_at', DB::raw("CONCAT('Potong Saldo (Belanja) #', invoice_number) as description"), DB::raw("'payment' as type"), 'total_amount as amount');

        // PERBAIKAN: Hapus huruf 's' pada pesanan_autokirim
        $autokirimPayments = DB::table('pesanan_autokirim')
            ->where('user_id', $userId)->where('metode_pembayaran', 'potong_saldo')
            ->where('status', '!=', 'waiting_payment') // Selama saldo dipotong, catat!
            ->select('created_at', DB::raw("CONCAT('Potong Saldo (Autokirim) #', COALESCE(awb_number, order_id)) as description"), DB::raw("'payment' as type"), 'grand_total as amount');

        // ==========================================================
        // C. QUERY REFUND (PEMASUKAN DARI TRANSAKSI BATAL)
        // ==========================================================
        $refundManual = DB::table('Pesanan')
            ->where('id_pengguna_pembeli', $userId)->where('payment_method', 'Potong Saldo')
            ->whereIn('status_pesanan', ['Batal', 'Gagal', 'Dibatalkan', 'batal', 'gagal'])
            ->select(DB::raw("COALESCE(updated_at, tanggal_pesanan) as created_at"), DB::raw("CONCAT('Refund Saldo (Manual Batal) #', nomor_invoice) as description"), DB::raw("'refund' as type"), 'price as amount');

        $refundMarketplace = DB::table('order_marketplace')
            ->where('user_id', $userId)->where('payment_method', 'saldo')
            ->whereIn('status', ['canceled', 'failed', 'returned'])
            ->select(DB::raw("COALESCE(updated_at, created_at) as created_at"), DB::raw("CONCAT('Refund Saldo (Belanja Batal) #', invoice_number) as description"), DB::raw("'refund' as type"), 'total_amount as amount');

        // PERBAIKAN: Hapus huruf 's' pada pesanan_autokirim
        $refundAutokirim = DB::table('pesanan_autokirim')
            ->where('user_id', $userId)->where('metode_pembayaran', 'potong_saldo')
            ->whereIn('status', ['batal', 'gagal'])
            ->select(DB::raw("COALESCE(updated_at, created_at) as created_at"), DB::raw("CONCAT('Refund Saldo (Autokirim Batal) #', COALESCE(awb_number, order_id)) as description"), DB::raw("'refund' as type"), 'grand_total as amount');

        // ==========================================================
        // EKSEKUSI & GABUNGKAN DATA
        // ==========================================================
        if ($startDate && $endDate) {
            $topUpTransactions->whereBetween('created_at', [$startDate, $endDate]);
            $revenueTransactions->whereBetween('created_at', [$startDate, $endDate]);
            $autokirimKomisi->whereBetween('updated_at', [$startDate, $endDate]);
            
            $orderPayments->whereBetween('tanggal_pesanan', [$startDate, $endDate]);
            $marketplacePayments->whereBetween('created_at', [$startDate, $endDate]);
            $autokirimPayments->whereBetween('created_at', [$startDate, $endDate]);
            
            $refundManual->whereBetween(DB::raw("COALESCE(updated_at, tanggal_pesanan)"), [$startDate, $endDate]);
            $refundMarketplace->whereBetween(DB::raw("COALESCE(updated_at, created_at)"), [$startDate, $endDate]);
            $refundAutokirim->whereBetween(DB::raw("COALESCE(updated_at, created_at)"), [$startDate, $endDate]);
        }

        $results = $topUpTransactions
            ->unionAll($revenueTransactions)
            ->unionAll($autokirimKomisi)
            ->unionAll($orderPayments)
            ->unionAll($marketplacePayments)
            ->unionAll($autokirimPayments)
            ->unionAll($refundManual)
            ->unionAll($refundMarketplace)
            ->unionAll($refundAutokirim)
            ->orderBy('created_at', 'desc')
            ->get();

        $results->transform(function($item) {
            $item->created_at = Carbon::parse($item->created_at);
            return $item;
        });
        
        // Kalkulasi Summary
        $totalPemasukan = $results->where('type', 'revenue')->sum('amount');
        $totalTopUp = $results->where('type', 'topup')->sum('amount');
        $totalRefund = $results->where('type', 'refund')->sum('amount');
        $totalPengeluaran = $results->where('type', 'payment')->sum('amount'); 

        $runningBalance = $saldoSaatIni;
        $saldoAwal = 0; 
        
        if ($startDate && $endDate) {
            // Hitung pemasukan masa lalu (Termasuk Refund)
            $awalTopUp = DB::table('transactions')->where('user_id', $userId)->where('status', 'success')->where('type', 'topup')->where('created_at', '<', $startDate)->sum('amount');
            $awalRev   = DB::table('orders')->where('store_id', $storeId)->where('status', 'completed')->where('created_at', '<', $startDate)->sum('subtotal');
            // PERBAIKAN: Hapus huruf 's' pada pesanan_autokirim
            $awalKom   = DB::table('pesanan_autokirim')->where('user_id', $userId)->where('komisi_agen', '>', 0)->whereIn('status', ['terkirim', 'selesai', 'sukses', 'delivered', 'success', 'completed'])->where('updated_at', '<', $startDate)->sum('komisi_agen');
            
            $awalRefMan = DB::table('Pesanan')->where('id_pengguna_pembeli', $userId)->where('payment_method', 'Potong Saldo')->whereIn('status_pesanan', ['Batal', 'Gagal', 'Dibatalkan', 'batal', 'gagal'])->where(DB::raw("COALESCE(updated_at, tanggal_pesanan)"), '<', $startDate)->sum('price');
            $awalRefMar = DB::table('order_marketplace')->where('user_id', $userId)->where('payment_method', 'saldo')->whereIn('status', ['canceled', 'failed', 'returned'])->where(DB::raw("COALESCE(updated_at, created_at)"), '<', $startDate)->sum('total_amount');
            // PERBAIKAN: Hapus huruf 's' pada pesanan_autokirim
            $awalRefAut = DB::table('pesanan_autokirim')->where('user_id', $userId)->where('metode_pembayaran', 'potong_saldo')->whereIn('status', ['batal', 'gagal'])->where(DB::raw("COALESCE(updated_at, created_at)"), '<', $startDate)->sum('grand_total');

            // Hitung pengeluaran masa lalu
            $awalOutMan = DB::table('Pesanan')->where('id_pengguna_pembeli', $userId)->where('payment_method', 'Potong Saldo')->where('tanggal_pesanan', '<', $startDate)->sum('price');
            $awalOutMar = DB::table('order_marketplace')->where('user_id', $userId)->where('payment_method', 'saldo')->whereNotIn('status', ['pending', 'expired'])->where('created_at', '<', $startDate)->sum('total_amount');
            // PERBAIKAN: Hapus huruf 's' pada pesanan_autokirim
            $awalOutAut = DB::table('pesanan_autokirim')->where('user_id', $userId)->where('metode_pembayaran', 'potong_saldo')->where('status', '!=', 'waiting_payment')->where('created_at', '<', $startDate)->sum('grand_total');

            $pemasukanLalu = $awalTopUp + $awalRev + $awalKom + $awalRefMan + $awalRefMar + $awalRefAut;
            $pengeluaranLalu = $awalOutMan + $awalOutMar + $awalOutAut;
            
            $saldoAwal = $pemasukanLalu - $pengeluaranLalu;
            $runningBalance = $saldoAwal + $totalTopUp + $totalPemasukan + $totalRefund - $totalPengeluaran;
        }

        // Hitung Sisa Saldo secara mundur
        $results->transform(function ($item) use (&$runningBalance) {
            $item->running_balance = $runningBalance;
            if (in_array($item->type, ['topup', 'revenue', 'refund'])) {
                $runningBalance -= (float)$item->amount; // Mundur: Kurangi pemasukan
            } else { 
                $runningBalance += (float)$item->amount; // Mundur: Kembalikan pengeluaran
            }
            return $item;
        });
        
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 15;
        $currentPageResults = $results->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $transactions = new LengthAwarePaginator($currentPageResults, count($results), $perPage, $currentPage, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
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