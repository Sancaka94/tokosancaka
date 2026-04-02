<?php

namespace App\Http\Controllers;

use App\Models\Kontak;
use App\Models\Pesanan; // <-- Pastikan ini ditambahkan
use Illuminate\Http\Request;
use App\Exports\KontaksExport;
use App\Imports\KontaksImport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class KontakController extends Controller
{
    /**
     * Menampilkan daftar kontak dengan logika pencarian dan statistik card
     * Mengadopsi logic "Step-by-Step" dari PesananController
     */
    public function index(Request $request)
    {
        // =================================================================
        // STEP 1: QUERY GLOBAL (Berlaku untuk Tabel & Monitoring Bar)
        // =================================================================
        $query = Kontak::withCount('pengiriman as total_pengiriman');

        // A. LOGIC SEARCH (Nama, NoHP, Alamat)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('no_hp', 'like', "%{$search}%")
                  ->orWhere('alamat', 'like', "%{$search}%");
            });
        }

        // B. LOGIC FILTER TIPE (Pengirim, Penerima, Keduanya)
        if ($request->filled('filter') && $request->input('filter') !== 'Semua') {
            $query->where('tipe', $request->input('filter'));
        }

        // =================================================================
        // STEP 2: CLONE QUERY UNTUK STATISTIK (Monitoring Bar)
        // =================================================================
        $statsQuery = clone $query;

        // Hitung Data Monitoring (Repeat Order)
        $totalAll = (clone $statsQuery)->count();
        $countBaru = (clone $statsQuery)->has('pengiriman', '=', 1)->count();
        $countRepeat = (clone $statsQuery)->has('pengiriman', '=', 2)->count();
        $countLoyal = (clone $statsQuery)->has('pengiriman', '>', 2)->count();

        $stats = [
            'count_baru'   => $countBaru,
            'count_repeat' => $countRepeat,
            'count_loyal'  => $countLoyal,
            'persen_baru'  => $totalAll > 0 ? round(($countBaru / $totalAll) * 100, 1) : 0,
            'persen_repeat'=> $totalAll > 0 ? round(($countRepeat / $totalAll) * 100, 1) : 0,
            'persen_loyal' => $totalAll > 0 ? round(($countLoyal / $totalAll) * 100, 1) : 0,
        ];

        // =================================================================
        // STEP 3: EXECUTE TABLE QUERY
        // =================================================================
        // Filter status khusus tabel (Baru/Repeat/Loyal)
        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status == 'baru') $query->has('pengiriman', '=', 1);
            elseif ($status == 'repeat') $query->has('pengiriman', '=', 2);
            elseif ($status == 'loyal') $query->has('pengiriman', '>', 2);
        }

        $kontaks = $query->latest()->paginate(15);
        $kontaks->appends($request->all());

        return view('admin.kontak.index', compact('kontaks', 'stats'));
    }

    /**
     * Menyimpan kontak baru dengan Sanitasi HP & Nama
     */
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'nama' => 'required|string|max:255',
                'no_hp' => 'required|string|max:20|unique:kontaks,no_hp',
                'alamat' => 'required|string',
                'tipe' => 'required|string|in:Pengirim,Penerima,Keduanya',
            ]);

            // Sanitasi (Sama seperti PesananController)
            $validatedData['no_hp'] = $this->_sanitizePhoneNumber($validatedData['no_hp']);
            $validatedData['nama'] = trim(preg_replace('/[^a-zA-Z0-9\s]/', '', $validatedData['nama']));

            Kontak::create($validatedData);

            return redirect()->route('admin.kontak.index')->with('success', 'Kontak ' . $validatedData['nama'] . ' berhasil disimpan.');
        } catch (\Exception $e) {
            Log::error('Gagal simpan kontak: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal menyimpan: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Menampilkan data kontak via AJAX (JSON)
     */
    public function show(Kontak $kontak)
    {
        // Muat info tambahan jika perlu (seperti riwayat kirim terakhir)
        return response()->json($kontak);
    }

    /**
     * AJAX Live Search untuk Form Pesanan (Integrasi dengan PesananController)
     */
    public function search(Request $request)
    {
        $queryText = $request->input('query') ?? $request->input('search');

        if(empty($queryText)) return response()->json([]);

        $kontaks = Kontak::where(function($q) use ($queryText) {
                    $q->where('nama', 'LIKE', "%{$queryText}%")
                      ->orWhere('no_hp', 'LIKE', "%{$queryText}%");
                })
                ->limit(10)
                ->get(['id', 'nama', 'no_hp', 'alamat', 'province', 'regency', 'district', 'village', 'postal_code']);

        return response()->json($kontaks);
    }

    /**
     * Update data kontak dengan logic penggabungan Tipe
     */
    public function update(Request $request, Kontak $kontak)
    {
        $validatedData = $request->validate([
            'nama' => 'required|string|max:255',
            'no_hp' => 'required|string|max:20|unique:kontaks,no_hp,' . $kontak->id,
            'alamat' => 'required|string',
            'tipe' => 'required|string',
        ]);

        // Sanitasi
        $validatedData['no_hp'] = $this->_sanitizePhoneNumber($validatedData['no_hp']);

        $kontak->update($validatedData);

        return redirect()->route('admin.kontak.index')->with('success', 'Kontak berhasil diperbarui.');
    }

    /**
     * Hapus Kontak
     */
    public function destroy(Kontak $kontak)
    {
        $nama = $kontak->nama;
        $kontak->delete();
        return redirect()->route('admin.kontak.index')->with('success', "Kontak $nama berhasil dihapus.");
    }

    // --- LOGIC EXPORT (Sesuai PesananController) ---

    public function exportExcel()
    {
        return Excel::download(new KontaksExport, 'data-kontak-' . date('Ymd') . '.xlsx');
    }

    public function exportPdf()
    {
        $kontaks = Kontak::all();
        $pdf = Pdf::loadView('admin.kontak.pdf', compact('kontaks'))->setPaper('a4', 'portrait');
        return $pdf->download('data-kontak-' . date('Ymd') . '.pdf');
    }

    public function importExcel(Request $request)
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls']);

        try {
            Excel::import(new KontaksImport, $request->file('file'));
            return redirect()->back()->with('success', 'Import data berhasil.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal import: ' . $e->getMessage());
        }
    }

    /**
     * PRIVATE HELPER: Sanitasi Nomor HP (Identik dengan PesananController)
     */
    private function _sanitizePhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (Str::startsWith($phone, '62')) {
            if (Str::startsWith(substr($phone, 2), '0')) {
                return '0' . substr($phone, 3);
            }
            return '0' . substr($phone, 2);
        }

        if (!Str::startsWith($phone, '0') && Str::startsWith($phone, '8')) {
            return '0' . $phone;
        }

        return $phone;
    }

    /**
     * API: Mengambil Riwayat Pesanan/Pengiriman Pelanggan (Untuk Modal Detail)
     */
    public function history(Request $request, $id)
    {
        $kontak = Kontak::findOrFail($id);

        // Cari pesanan di mana nomor HP ini menjadi Pengirim atau Penerima
        $query = Pesanan::where('sender_phone', $kontak->no_hp)
                        ->orWhere('receiver_phone', $kontak->no_hp);

        // Hitung total keseluruhan paket
        $totalPaket = (clone $query)->count();

        // Hitung total omzet (Abaikan yang statusnya Batal/Gagal)
        $totalOmzet = (clone $query)->whereNotIn('status_pesanan', ['Batal', 'Kadaluarsa', 'Gagal Bayar', 'Dibatalkan'])
                                    ->sum('price');

        // Ambil data untuk paginasi (5 data per halaman agar modal rapi)
        $history = $query->orderBy('created_at', 'desc')->paginate(5);

        return response()->json([
            'kontak' => $kontak,
            'total_paket' => $totalPaket,
            'total_omzet' => $totalOmzet,
            'history' => $history
        ]);
    }
}
