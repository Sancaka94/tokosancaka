<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        // Mulai query dasar
        $query = Transaction::with('operator')->orderBy('status', 'asc')->latest();

        // 1. Filter Pencarian berdasarkan Plat Nomor atau ID Transaksi (No. Parkir)
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function($q) use ($keyword) {
                $q->where('plate_number', 'like', "%{$keyword}%")
                  ->orWhere('id', $keyword); // Jika di karcis tertera TRX-00003, cukup ketik angka 3
            });
        }

        // 2. Filter Pencarian berdasarkan Tanggal
        if ($request->filled('tanggal')) {
            $query->whereDate('entry_time', $request->tanggal);
        }

        // Eksekusi query dengan pagination, dan bawa parameter pencarian ke halaman selanjutnya (withQueryString)
        $transactions = $query->paginate(15)->withQueryString();

        return view('transactions.index', compact('transactions'));
    }

   public function store(Request $request)
    {
        $request->validate([
            'vehicle_type' => 'required|in:motor,mobil',
            'plate_number' => 'required|string|max:20',
        ]);

        $transaction = Transaction::create([
            'tenant_id'    => auth()->user()->tenant_id,
            'operator_id'  => auth()->id(),
            'vehicle_type' => $request->vehicle_type,
            'plate_number' => strtoupper($request->plate_number),
            'entry_time'   => \Carbon\Carbon::now(),
            'status'       => 'masuk',
        ]);

        // PERUBAHAN: Tambahkan 'print_id' ke dalam session agar terdeteksi oleh Blade
        return redirect()->route('transactions.index')->with([
            'success' => 'Kendaraan masuk berhasil dicatat.',
            'print_id' => $transaction->id
        ]);
    }

    // Menampilkan form halaman catat manual
    public function createManual()
    {
        return view('transactions.manual');
    }

    // ====================================================================
    // FITUR BARU: CATAT PEMASUKAN MANUAL (LANGSUNG SELESAI/KELUAR)
    // ====================================================================
    public function storeManual(Request $request)
    {
        $request->validate([
            'vehicle_type' => 'required|in:motor,mobil',
            'plate_number' => 'required|string|max:20',
            'fee'          => 'required|numeric|min:0', // Mengharuskan input nominal uang
        ]);

        $now = Carbon::now();

        Transaction::create([
            'tenant_id'    => auth()->user()->tenant_id,
            'operator_id'  => auth()->id(),
            'vehicle_type' => $request->vehicle_type,
            'plate_number' => strtoupper($request->plate_number),
            'entry_time'   => $now, // Anggap waktu masuk dan keluar sama untuk pencatatan manual
            'exit_time'    => $now,
            'fee'          => $request->fee, // Mengambil nominal tarif dari input form
            'status'       => 'keluar',      // Langsung diset ke status keluar
        ]);

        return redirect()->route('transactions.index')->with(
            'success', 'Pemasukan manual sebesar Rp ' . number_format($request->fee, 0, ',', '.') . ' berhasil dicatat.'
        );
    }

    // TAMBAHKAN FUNGSI INI DI BAWAH FUNGSI STORE
    public function print($id)
    {
        $transaction = Transaction::with('operator')->findOrFail($id);

        // Ambil data cabang/perusahaan jika ada, jika tidak pakai nama default
        $tenant = auth()->user()->tenant;

        return view('transactions.print', compact('transaction', 'tenant'));
    }

    // Fungsi untuk proses kendaraan keluar (Checkout)
    public function update(Request $request, Transaction $transaction)
    {
        // Mencegah error jika terjadi klik ganda pada tombol keluar
        if ($transaction->status === 'keluar') {
            return redirect()->back()->with('error', 'Kendaraan ini sudah diselesaikan/keluar sebelumnya.');
        }

        // Tarif dasar (Bisa dikembangkan dengan mengambil dari tabel settings nantinya)
        $tarifMotor = 3000;
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
