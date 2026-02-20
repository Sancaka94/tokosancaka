<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TransactionController extends Controller
{
    public function index()
    {
        // Tampilkan semua transaksi yang sedang parkir (masuk) di atas, sisanya di bawah
        $transactions = Transaction::with('operator')->orderBy('status', 'asc')->latest()->paginate(15);
        return view('transactions.index', compact('transactions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'vehicle_type' => 'required|in:motor,mobil',
            'plate_number' => 'required|string|max:20',
        ]);

        Transaction::create([
            'operator_id' => auth()->id(),
            'vehicle_type' => $request->vehicle_type,
            'plate_number' => strtoupper($request->plate_number),
            'entry_time' => Carbon::now(),
            'status' => 'masuk',
        ]);

        return redirect()->route('transactions.index')->with('success', 'Kendaraan masuk berhasil dicatat.');
    }

    // Fungsi untuk proses kendaraan keluar (Checkout)
    public function update(Request $request, Transaction $transaction)
    {
        // Tarif dasar (Bisa dipindah ke tabel settings nantinya)
        $tarifMotor = 2000;
        $tarifMobil = 5000;

        $waktuKeluar = Carbon::now();
        $fee = ($transaction->vehicle_type == 'motor') ? $tarifMotor : $tarifMobil;

        $transaction->update([
            'exit_time' => $waktuKeluar,
            'fee' => $fee,
            'status' => 'keluar'
        ]);

        return redirect()->route('transactions.index')->with('success', 'Kendaraan keluar. Tarif: Rp ' . number_format($fee, 0, ',', '.'));
    }

    public function destroy(Transaction $transaction)
    {
        // Batasi siapa yang bisa hapus (misal hanya Admin/Superadmin)
        if (auth()->user()->role == 'operator') {
            abort(403, 'Operator tidak bisa menghapus histori parkir.');
        }

        $transaction->delete();
        return redirect()->route('transactions.index')->with('success', 'Histori dihapus.');
    }
}
