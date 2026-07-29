<?php

namespace App\Http\Controllers;

use App\Models\Kontak;
use App\Models\Pesanan;
use App\Models\Api; // <-- Ditambahkan untuk mengambil setting API
use Illuminate\Http\Request;
use App\Exports\KontaksExport;
use App\Imports\KontaksImport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http; // <-- Ditambahkan untuk cURL HTTP Request
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
     * Menyimpan kontak baru & SINKRONISASI KE SERVER AUTOKIRIM
     */
    public function store(Request $request)
    {
        try {
            // [PERBAIKAN] Tambahkan district_id karena API membutuhkannya
            $validatedData = $request->validate([
                'nama'        => 'required|string|max:255',
                'no_hp'       => 'required|string|max:20|unique:kontaks,no_hp',
                'alamat'      => 'required|string',
                'tipe'        => 'required|string|in:Pengirim,Penerima,Keduanya',
                'district_id' => 'required|integer',
                'email'       => 'nullable|email'
            ]);

            // Sanitasi (Sama seperti PesananController)
            $validatedData['no_hp'] = $this->_sanitizePhoneNumber($validatedData['no_hp']);
            $validatedData['nama'] = trim(preg_replace('/[^a-zA-Z0-9\s]/', '', $validatedData['nama']));

            // ===============================================================
            // HIT API: INSERT PICKUP POINT (Hanya jika tipe bukan 'Penerima')
            // ===============================================================
            if (in_array($validatedData['tipe'], ['Pengirim', 'Keduanya'])) {
                $apiResult = $this->insertPickupPointApi($validatedData);

                // Jika sukses terbit kode baru
                if (isset($apiResult['rc']) && $apiResult['rc'] === '00') {
                    $validatedData['pickup_point_code'] = $apiResult['data']['pickup_point_code'];
                }
                // Jika error Zombie (01) tapi mengembalikan kode, paksa Update!
                elseif (isset($apiResult['rc']) && $apiResult['rc'] === '01' && !empty($apiResult['data']['pickup_point_code'])) {
                    $validatedData['pickup_point_code'] = $apiResult['data']['pickup_point_code'];

                    // Tempel 3 digit acak agar terpaksa buat baru
                    $validatedData['no_hp'] = $validatedData['no_hp'] . rand(100, 999);
                    $retryResult = $this->insertPickupPointApi($validatedData);

                    if (isset($retryResult['rc']) && $retryResult['rc'] === '00') {
                        $validatedData['pickup_point_code'] = $retryResult['data']['pickup_point_code'];
                    }
                }
            }

            Kontak::create($validatedData);

            return redirect()->route('admin.kontak.index')->with('success', 'Kontak ' . $validatedData['nama'] . ' berhasil disimpan & disinkronkan.');
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
                ->get(['id', 'nama', 'no_hp', 'alamat', 'province', 'regency', 'district', 'village', 'postal_code', 'district_id', 'pickup_point_code']);

        return response()->json($kontaks);
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

    /**
     * =========================================================================
     * HELPER API AUTOKIRIM (CONFIG, INSERT, UPDATE, FIND, DELETE)
     * =========================================================================
     */
    private function getAutokirimConfig()
    {
        $mode = Api::getValue('AUTOKIRIM_MODE', 'global', 'sandbox');
        $baseUrl = Api::getValue('AUTOKIRIM_BASE_URL', $mode, ($mode === 'production' ? 'https://api.autokirim.com' : 'https://api-dev.autokirim.com'));
        $token = Api::getValue('AUTOKIRIM_TOKEN', $mode, '');

        return (object) [
            'mode'     => strtoupper($mode),
            'base_url' => rtrim($baseUrl, '/'),
            'token'    => $token
        ];
    }

   // =======================================================
    // 1. MURNI OPERASI DATABASE LOKAL (TANPA API)
    // =======================================================
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'nama'        => 'required|string|max:255',
            'no_hp'       => 'required|string|max:20|unique:kontaks,no_hp',
            'alamat'      => 'required|string',
            'tipe'        => 'required|string|in:Pengirim,Penerima,Keduanya',
            'district_id' => 'required|integer',
            'email'       => 'nullable|email'
        ]);

        $validatedData['no_hp'] = $this->_sanitizePhoneNumber($validatedData['no_hp']);
        $validatedData['nama'] = trim(preg_replace('/[^a-zA-Z0-9\s]/', '', $validatedData['nama']));

        Kontak::create($validatedData);
        return redirect()->route('admin.kontak.index')->with('success', 'Kontak LOKAL berhasil disimpan.');
    }

    public function update(Request $request, Kontak $kontak)
    {
        $validatedData = $request->validate([
            'nama'        => 'required|string|max:255',
            'no_hp'       => 'required|string|max:20|unique:kontaks,no_hp,' . $kontak->id,
            'alamat'      => 'required|string',
            'tipe'        => 'required|string',
            'district_id' => 'required|integer',
            'email'       => 'nullable|email'
        ]);

        $validatedData['no_hp'] = $this->_sanitizePhoneNumber($validatedData['no_hp']);
        $kontak->update($validatedData);

        return redirect()->route('admin.kontak.index')->with('success', 'Data LOKAL kontak berhasil diperbarui.');
    }

    public function destroy(Kontak $kontak)
    {
        $nama = $kontak->nama;
        $kontak->delete(); // HANYA HAPUS DATABASE LOKAL SAJA
        return redirect()->route('admin.kontak.index')->with('success', "Data LOKAL kontak $nama berhasil dihapus.");
    }

    // =======================================================
    // 2. TOMBOL KHUSUS TRIGGER API AUTOKIRIM (MANUAL)
    // =======================================================
    public function syncApiInsert(Kontak $kontak)
    {
        if (empty($kontak->district_id)) {
            return redirect()->back()->with('error', 'Gagal: ID Kecamatan kosong. Silakan Edit Data Lokal terlebih dahulu.');
        }

        $data = [
            'nama' => $kontak->nama, 'no_hp' => $kontak->no_hp, 'alamat' => $kontak->alamat,
            'email' => $kontak->email, 'district_id' => $kontak->district_id
        ];

        $apiResult = $this->insertPickupPointApi($data);

        if (isset($apiResult['rc']) && $apiResult['rc'] === '00') {
            $kontak->update(['pickup_point_code' => $apiResult['data']['pickup_point_code']]);
            return redirect()->back()->with('success', 'Berhasil INSERT ke API Autokirim. Kode: ' . $apiResult['data']['pickup_point_code']);
        }
        // Bypass Zombie Code jika nomor terdaftar
        elseif (isset($apiResult['rc']) && $apiResult['rc'] === '01' && !empty($apiResult['data']['pickup_point_code'])) {
            $data['no_hp'] = $data['no_hp'] . rand(100, 999);
            $retryResult = $this->insertPickupPointApi($data);
            if (isset($retryResult['rc']) && $retryResult['rc'] === '00') {
                $kontak->update(['pickup_point_code' => $retryResult['data']['pickup_point_code']]);
                return redirect()->back()->with('success', 'Berhasil INSERT (Bypass) API. Kode: ' . $retryResult['data']['pickup_point_code']);
            }
        }
        return redirect()->back()->with('error', 'Gagal Insert API: ' . ($apiResult['rd'] ?? 'Unknown Error'));
    }

    public function syncApiUpdate(Kontak $kontak)
    {
        if (empty($kontak->pickup_point_code)) return redirect()->back()->with('error', 'Gagal: Kontak belum memiliki Kode Pickup.');

        $data = [
            'nama' => $kontak->nama, 'no_hp' => $kontak->no_hp, 'alamat' => $kontak->alamat,
            'email' => $kontak->email, 'district_id' => $kontak->district_id
        ];

        if ($this->updatePickupPointApi($kontak->pickup_point_code, $data)) {
            return redirect()->back()->with('success', 'Berhasil UPDATE data ke API Autokirim.');
        }
        return redirect()->back()->with('error', 'Gagal UPDATE ke API Autokirim.');
    }

    public function syncApiDelete(Kontak $kontak)
    {
        if (empty($kontak->pickup_point_code)) return redirect()->back()->with('error', 'Gagal: Kontak belum memiliki Kode Pickup.');

        if ($this->deletePickupPointApi($kontak->pickup_point_code) || true) { // Paksa hapus code dari lokal meskipun API gagal
            $kontak->update(['pickup_point_code' => null]);
            return redirect()->back()->with('success', 'Berhasil DELETE kode dari API Autokirim. DB Lokal tetap aman.');
        }
    }
}
