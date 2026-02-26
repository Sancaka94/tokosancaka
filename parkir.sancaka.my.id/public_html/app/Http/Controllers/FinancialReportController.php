<?php

namespace App\Http\Controllers;

use App\Models\FinancialReport;
use App\Models\User;
use Illuminate\Http\Request;

class FinancialReportController extends Controller
{
    public function index(Request $request)
    {
        $query = FinancialReport::orderBy('tanggal', 'desc')->latest();

        // Filter berdasarkan bulan/tahun tetap dipertahankan
        if ($request->filled('bulan')) {
            $query->whereMonth('tanggal', $request->bulan);
        }
        if ($request->filled('tahun')) {
            $query->whereYear('tanggal', $request->tahun);
        }

        $reports = $query->paginate(20)->withQueryString();

        // Perhitungan summary
        $totalPemasukan = FinancialReport::where('jenis', 'pemasukan')->sum('nominal');
        $totalPengeluaran = FinancialReport::where('jenis', 'pengeluaran')->sum('nominal');
        $saldo = $totalPemasukan - $totalPengeluaran;

        // AMBIL DATA PEGAWAI UNTUK MODAL GAJI
        $employees = User::whereIn('role', ['operator', 'admin'])->get();

        // Kirim $employees ke view
        return view('financial_reports.index', compact(
            'reports',
            'totalPemasukan',
            'totalPengeluaran',
            'saldo',
            'employees'
        ));
    }

    public function store(Request $request)
    {
        // Validasi untuk input berupa Array (karena kita pakai multiple form)
        $request->validate([
            'transactions' => 'required|array',
            'transactions.*.tanggal' => 'required|date',
            'transactions.*.jenis' => 'required|in:pemasukan,pengeluaran',
            'transactions.*.kategori' => 'required|string|max:255',
            'transactions.*.nominal' => 'required|numeric|min:1',
            'transactions.*.salaries' => 'nullable|array',
        ]);

        // Looping setiap transaksi yang dikirim dari form
        foreach ($request->transactions as $trx) {

            // 1. Simpan Transaksi Utama (Misal: Setoran Parkir / Biaya Operasional)
            FinancialReport::create([
                'tanggal' => $trx['tanggal'],
                'jenis' => $trx['jenis'],
                'kategori' => $trx['kategori'],
                'nominal' => $trx['nominal'],
                'keterangan' => $trx['keterangan'] ?? null,
            ]);

            // 2. Simpan Gaji Pegawai (Jika diisi)
            if (isset($trx['salaries']) && is_array($trx['salaries'])) {
                foreach ($trx['salaries'] as $employeeId => $amount) {
                    if ($amount > 0) {
                        $employee = User::find($employeeId);
                        if ($employee) {
                            FinancialReport::create([
                                'tanggal' => $trx['tanggal'],
                                'jenis' => 'pengeluaran',
                                'kategori' => 'Gaji Pegawai',
                                'nominal' => $amount,
                                'keterangan' => 'Pembayaran gaji harian: ' . $employee->name,
                            ]);
                        }
                    }
                }
            }
        }

        return redirect()->route('financial.index')->with('success', 'Semua data transaksi dan gaji berhasil dicatat.');
    }

    // ==========================================
    // FUNGSI EDIT & UPDATE
    // ==========================================

    public function edit(FinancialReport $financial)
    {
        // Mengambil semua operator agar muncul di form edit gaji
        $employees = User::whereIn('role', ['operator', 'admin'])->get();

        return view('financial_reports.edit', [
            'kas' => $financial,
            'employees' => $employees
        ]);
    }

    public function update(Request $request, FinancialReport $financial)
    {
        // Validasi untuk update (hanya 1 transaksi karena form edit biasanya single)
        $request->validate([
            'tanggal' => 'required|date',
            'jenis' => 'required|in:pemasukan,pengeluaran',
            'kategori' => 'required|string|max:255',
            'nominal' => 'required|numeric|min:1',
        ]);

        // 1. Update data transaksi utama
        $financial->update([
            'tanggal' => $request->tanggal,
            'jenis' => $request->jenis,
            'kategori' => $request->kategori,
            'nominal' => $request->nominal,
            'keterangan' => $request->keterangan ?? $financial->keterangan,
        ]);

        // 2. Proses Tambahan/Koreksi Gaji Pegawai (jika form edit punya input gaji)
        if ($request->has('salaries')) {
            foreach ($request->salaries as $employeeId => $amount) {
                // Hanya simpan jika nominal lebih dari 0
                if ($amount > 0) {
                    $employee = User::find($employeeId);

                    // Mencatat gaji sebagai pengeluaran baru (koreksi)
                    FinancialReport::create([
                        'tanggal' => $request->tanggal,
                        'jenis' => 'pengeluaran',
                        'kategori' => 'Gaji Pegawai',
                        'nominal' => $amount,
                        'keterangan' => 'Koreksi gaji: ' . ($employee->name ?? 'Pegawai'),
                    ]);
                }
            }
        }

        return redirect()->route('financial.index')->with('success', 'Catatan keuangan berhasil diperbarui.');
    }

    // ==========================================

    public function destroy(FinancialReport $financial)
    {
        $financial->delete();
        return redirect()->route('financial.index')->with('success', 'Catatan keuangan berhasil dihapus.');
    }
}
