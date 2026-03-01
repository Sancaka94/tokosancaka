<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Carbon\Carbon;

// --- TAMBAHAN LIBRARY MIKE42 ---
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector; // Gunakan ini jika server/komputer kasir pakai Windows
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector; // Gunakan ini jika printer pakai kabel LAN/IP Address
use Mike42\Escpos\PrintConnectors\DummyPrintConnector;

use Illuminate\Support\Facades\Log;

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

   public function storeManual(Request $request)
    {
        $request->validate([
            'vehicle_type' => 'required|in:motor,mobil',
            'plate_number' => 'required|string|max:20',
            'fee'          => 'required|numeric|min:0',
            'toilet_fee'   => 'nullable|numeric|min:0',
        ]);

        $now = \Carbon\Carbon::now();
        $toiletFee = $request->toilet_fee ?? 0;
        $totalPendapatan = $request->fee + $toiletFee;

        Transaction::create([
            'tenant_id'    => auth()->user()->tenant_id,
            'operator_id'  => auth()->id(),
            'vehicle_type' => $request->vehicle_type,
            'plate_number' => strtoupper($request->plate_number),
            'entry_time'   => $now,
            'exit_time'    => $now,
            'fee'          => $request->fee,
            'toilet_fee'   => $toiletFee, // Data masuk ke kolom yang baru ditambahkan via SQL
            'status'       => 'keluar',
        ]);

        return redirect()->route('transactions.index')->with(
            'success', 'Pemasukan manual sebesar Rp ' . number_format($totalPendapatan, 0, ',', '.') . ' berhasil dicatat.'
        );
    }

    public function print($id)
    {
        \Log::info("=== MULAI PROSES CETAK BASE64 TRX ID: {$id} ===");

        try {
            $transaction = \App\Models\Transaction::findOrFail($id);

            $connector = new \Mike42\Escpos\PrintConnectors\DummyPrintConnector();
            $printer = new \Mike42\Escpos\Printer($connector);

            // --- CETAK HEADER ---
            $printer->setJustification(\Mike42\Escpos\Printer::JUSTIFY_CENTER);
            $printer->text("SANCAKA PARKIR\n");
            $printer->text("Jl. Dr. Wahidin No. 18A, Ngawi\n");
            $printer->text("WA: 085 745 808 809\n");
            $printer->text("--------------------------------\n");

            // --- CETAK DETAIL ---
            $printer->setJustification(\Mike42\Escpos\Printer::JUSTIFY_LEFT);
            $printer->text("No. Plat : " . $transaction->plate_number . "\n");
            $printer->text("Jenis    : " . ucfirst($transaction->vehicle_type) . "\n");
            $printer->text("Masuk    : " . \Carbon\Carbon::parse($transaction->entry_time)->format('d/m/Y H:i') . "\n");
            $printer->text("--------------------------------\n");

            // --- CETAK QR CODE ---
            $printer->setJustification(\Mike42\Escpos\Printer::JUSTIFY_CENTER);
            $printer->qrCode((string)$transaction->id, \Mike42\Escpos\Printer::QR_ECLEVEL_L, 6);

            $printer->text("TRX-" . str_pad($transaction->id, 5, '0', STR_PAD_LEFT) . "\n");
            $printer->text("--------------------------------\n");

            // --- CETAK FOOTER ---
            $printer->text("Simpan karcis ini sebagai\n");
            $printer->text("bukti parkir yang sah.\n");
            $printer->text("Terima Kasih.\n");

            $printer->feed(3);
            $printer->cut();

            $data = $connector->getData();
            $printer->close();

            $base64Data = base64_encode($data);

            \Log::info("Berhasil generate ESC/POS. Panjang Base64: " . strlen($base64Data));

            return response($base64Data)->header('Content-Type', 'text/plain');

        } catch (\Exception $e) {
            // CATAT ERROR KE FILE LOG LARAVEL
            \Log::error("ERROR CETAK BASE64: " . $e->getMessage());
            return response('Error Cetak: ' . $e->getMessage(), 500);
        }
    }

    // ==========================================
    // FUNGSI BARU: Cetak Langsung via PHP Backend (Mike42)
    // ==========================================
    public function printDirect($id)
    {
        $transaction = Transaction::with('operator')->findOrFail($id);
        $tenant = auth()->user()->tenant;

        try {
            // PENTING: Sesuaikan nama printer dengan yang ada di Devices and Printers Windows Anda
            // Pastikan printer sudah di-share di Windows.
            $connector = new WindowsPrintConnector("NamaPrinterThermalAnda");
            $printer = new Printer($connector);

            // --- HEADER ---
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $namaToko = $tenant->name ?? "AZKEN PARKIR";
            $printer->text("$namaToko\n");
            $printer->text("Jl. Dr. Wahidin No. 18A, Ngawi\n");
            $printer->text("Nomor WA 085 745 808 809\n");
            $printer->text("--------------------------------\n");

            // --- DETAIL KENDARAAN ---
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->text("No. Plat : " . $transaction->plate_number . "\n");
            $printer->text("Jenis    : " . ucfirst($transaction->vehicle_type) . "\n");
            $printer->text("Masuk    : " . Carbon::parse($transaction->entry_time)->format('d/m/Y H:i') . "\n");
            $printer->text("--------------------------------\n");

            // --- QR CODE ---
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            // Mencetak QR Code berdasarkan ID Transaksi
            $printer->qrCode((string)$transaction->id, Printer::QR_ECLEVEL_L, 6);

            // Teks ID di bawah QR
            $formatId = "TRX-" . str_pad($transaction->id, 5, '0', STR_PAD_LEFT);
            $printer->text("\n" . $formatId . "\n\n");

            // --- FOOTER ---
            $printer->text("Simpan karcis ini sebagai\n");
            $printer->text("bukti parkir yang sah.\n");
            $printer->text("Terima Kasih.\n");

            // Perintah potong kertas (jika printer support auto-cutter) dan tutup koneksi
            $printer->cut();
            $printer->close();

            // Kembali ke halaman sebelumnya dengan pesan sukses
            return redirect()->back()->with('success', 'Struk berhasil dicetak.');

        } catch (\Exception $e) {
            // Tangkap error jika printer mati / tidak tersambung
            return redirect()->back()->with('error', 'Gagal mencetak: ' . $e->getMessage());
        }
    }

    // Fungsi untuk proses kendaraan keluar (Checkout)
    public function update(Request $request, Transaction $transaction)
    {
        if ($transaction->status === 'keluar') {
            return redirect()->back()->with('error', 'Kendaraan ini sudah diselesaikan/keluar sebelumnya.');
        }

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
        if (auth()->user()->isOperator()) {
            abort(403, 'Operator tidak memiliki izin untuk menghapus histori parkir.');
        }

        $transaction->delete();
        return redirect()->route('transactions.index')->with('success', 'Histori transaksi berhasil dihapus.');
    }

    public function edit(Transaction $transaction)
    {
        return view('transactions.manual', compact('transaction'));
    }
}
