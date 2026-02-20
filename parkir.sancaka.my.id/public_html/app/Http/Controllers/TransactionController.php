<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TransactionController extends Controller
{
    public function index()
    {
        // Tampilkan semua transaksi, prioritaskan yang sedang parkir (masuk) di atas, sisanya di bawah
        $transactions = Transaction::with('operator')
            ->orderBy('status', 'asc')
            ->latest()
            ->paginate(15);

        return view('transactions.index', compact('transactions'));
    }

    public function store(Request $request)
    {
        // 1. Validasi Keamanan Hak Akses
        // Cegah akun Super Admin utama (yang tidak memiliki tenant_id) melakukan input operasional
        if (auth()->user()->tenant_id === null) {
            return redirect()->back()->with('error', 'Akses Ditolak: Akun Super Admin tidak dapat melakukan input transaksi parkir. Transaksi hanya bisa dilakukan dari subdomain cabang (Tenant).');
        }

        // 2. Validasi Form
        $request->validate([
            'vehicle_type' => 'required|in:motor,mobil',
            'plate_number' => 'required|string|max:20',
        ]);

        // 3. Simpan Data dengan tenant_id yang dipanggil secara eksplisit
        Transaction::create([
            'tenant_id'    => auth()->user()->tenant_id, // Solusi untuk Error 1364
            'operator_id'  => auth()->id(),
            'vehicle_type' => $request->vehicle_type,
            'plate_number' => strtoupper($request->plate_number),
            'entry_time'   => Carbon::now(),
            'status'       => 'masuk',
        ]);

        return redirect()->route('transactions.index')->with('success', 'Kendaraan masuk berhasil dicatat.');
    }

    // Fungsi untuk proses kendaraan keluar (Checkout)
    public function update(Request $request, Transaction $transaction)
    {
        // Mencegah error jika terjadi klik ganda pada tombol keluar
        if ($transaction->status === 'keluar') {
            return redirect()->back()->with('error', 'Kendaraan ini sudah diselesaikan/keluar sebelumnya.');
        }

        // Tarif dasar (Bisa dikembangkan dengan mengambil dari tabel settings nantinya)
        $tarifMotor = 2000;
        $tarifMobil = 5000;

        $waktuKeluar = Carbon::now();
        $fee = ($transaction->vehicle_type === 'motor') ? $tarifMotor : $tarifMobil;

        $transaction->update([
            'exit_time' => $waktuKeluar,
            'fee'       => $fee,
            'status'    => 'keluar'
        ]);

        return redirect()->route('transactions.index')->with('success', 'Kendaraan keluar. Tarif: Rp ' . number_format($fee, 0, ',', '.'));
    }

    public function destroy(Transaction $transaction)
    {
        // Batasi siapa yang bisa hapus (Menggunakan helper function isOperator() dari Model User)
        if (auth()->user()->isOperator()) {
            abort(403, 'Operator tidak memiliki izin untuk menghapus histori parkir.');
        }

        $transaction->delete();
        return redirect()->route('transactions.index')->with('success', 'Histori transaksi berhasil dihapus.');
    }
}
