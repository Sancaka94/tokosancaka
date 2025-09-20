<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request; // ✅ 1. Tambahkan Request untuk menerima input filter
use Illuminate\Support\Facades\Auth;
use App\Models\Transaction;
use Carbon\Carbon; // ✅ 2. Tambahkan Carbon untuk memanipulasi tanggal

class LaporanKeuanganController extends Controller
{
    /**
     * Menampilkan halaman laporan keuangan yang disempurnakan dengan filter tanggal.
     */
    public function index(Request $request) // ✅ 3. Terima objek Request
    {
        // Dapatkan pengguna yang sedang terautentikasi
        $user = Auth::user();

        // Ambil saldo saat ini langsung dari tabel pengguna (ini adalah total saldo, tidak terpengaruh filter)
        $saldoSaatIni = $user->saldo;

        // ✅ 4. Siapkan query dasar untuk transaksi pengguna
        $transactionQuery = Transaction::where('user_id', $user->id_pengguna);

        // ✅ 5. Terapkan filter tanggal jika ada input dari pengguna
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = Carbon::parse($request->input('end_date'))->endOfDay();
            $transactionQuery->whereBetween('created_at', [$startDate, $endDate]);
        }

        // ✅ 6. Lakukan perhitungan berdasarkan query yang sudah difilter
        // Mengkloning query agar filter tidak tumpang tindih untuk perhitungan yang berbeda.
        $totalPemasukan = (clone $transactionQuery)->where('type', 'topup')->sum('amount');
        $totalPengeluaran = (clone $transactionQuery)->whereIn('type', ['withdrawal', 'payment'])->sum('amount');
        
        // ✅ 7. Ambil riwayat transaksi yang sudah difilter dengan paginasi
        //    withQueryString() penting agar filter tanggal tetap aktif saat berpindah halaman.
        $transactions = (clone $transactionQuery)->latest()->paginate(15)->withQueryString();

        // ✅ 8. Kirim semua data, termasuk tanggal filter, ke view
        return view('customer.laporan.index', [
            'saldo'             => $saldoSaatIni,
            'totalPemasukan'    => $totalPemasukan,
            'totalPengeluaran'  => $totalPengeluaran,
            'transactions'      => $transactions,
            'startDate'         => $request->input('start_date'), // Untuk mengisi kembali nilai di form filter
            'endDate'           => $request->input('end_date'),
        ]);
    }
}

