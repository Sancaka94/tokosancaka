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

   /**
     * Update data kontak & SINKRONISASI UPDATE/INSERT/DELETE KE SERVER AUTOKIRIM
     */
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

        // Sanitasi
        $validatedData['no_hp'] = $this->_sanitizePhoneNumber($validatedData['no_hp']);

        // ===============================================================
        // LOGIKA CRUD PICKUP POINT API AUTOKIRIM (SMART SYNC)
        // ===============================================================
        if (in_array($validatedData['tipe'], ['Pengirim', 'Keduanya'])) {

            if (!empty($kontak->pickup_point_code)) {
                // 1. Jika sudah punya kode, tembak API UPDATE
                $this->updatePickupPointApi($kontak->pickup_point_code, $validatedData);
            } else {
                // 2. Jika sebelumnya Penerima (tidak punya kode) lalu diubah jadi Pengirim, tembak API INSERT
                $apiResult = $this->insertPickupPointApi($validatedData);

                if (isset($apiResult['rc']) && $apiResult['rc'] === '00') {
                    $validatedData['pickup_point_code'] = $apiResult['data']['pickup_point_code'];
                } elseif (isset($apiResult['rc']) && $apiResult['rc'] === '01' && !empty($apiResult['data']['pickup_point_code'])) {
                    // Bypass Zombie Code
                    $validatedData['pickup_point_code'] = $apiResult['data']['pickup_point_code'];
                    $validatedData['no_hp'] = $validatedData['no_hp'] . rand(100, 999); // Tempel angka acak
                    $retryResult = $this->insertPickupPointApi($validatedData);

                    if (isset($retryResult['rc']) && $retryResult['rc'] === '00') {
                        $validatedData['pickup_point_code'] = $retryResult['data']['pickup_point_code'];
                    }
                    // Kembalikan nomor HP asli untuk disimpan ke DB Lokal
                    $validatedData['no_hp'] = $this->_sanitizePhoneNumber($request->no_hp);
                }
            }

        } elseif ($validatedData['tipe'] === 'Penerima') {
            // 3. Jika sebelumnya Pengirim lalu diubah jadi Penerima murni, HAPUS kode di server Autokirim
            if (!empty($kontak->pickup_point_code)) {
                $this->deletePickupPointApi($kontak->pickup_point_code);
                $validatedData['pickup_point_code'] = null; // Kosongkan di database lokal
            }
        }
        // ===============================================================

        $kontak->update($validatedData);

        return redirect()->route('admin.kontak.index')->with('success', 'Kontak berhasil diperbarui & disinkronkan dengan API.');
    }

    /**
     * Hapus Kontak & DELETE PICKUP POINT DI SERVER AUTOKIRIM
     */
    public function destroy(Kontak $kontak)
    {
        $nama = $kontak->nama;

        // ===============================================================
        // HIT API: DELETE PICKUP POINT SEBELUM MENGHAPUS DB LOKAL
        // ===============================================================
        if (!empty($kontak->pickup_point_code)) {
            $this->deletePickupPointApi($kontak->pickup_point_code);
        }

        $kontak->delete();
        return redirect()->route('admin.kontak.index')->with('success', "Kontak $nama beserta data sinkronisasi server berhasil dihapus.");
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

    private function insertPickupPointApi($data)
    {
        $config = $this->getAutokirimConfig();
        $appMode = app()->environment('production') ? 'PRODUCTION' : 'DEV';

        try {
            $payload = [
                "name"              => (string) $data['nama'],
                "phone"             => (string) $data['no_hp'],
                "address"           => (string) $data['alamat'],
                "email"             => (string) ($data['email'] ?? ''),
                "longitude"         => "",
                "latitude"          => "",
                "district_id"       => (int) $data['district_id'],
                "is_member_deposit" => false
            ];

            Log::info("LOG LOG: [KONTAK - API INSERT] (API: {$config->mode} | APP: {$appMode}) REQUEST:", $payload);

            $response = Http::timeout(15)->withToken($config->token)->post("{$config->base_url}/api/pickup-point/insert", $payload);
            $result = $response->json();

            Log::info("LOG LOG: [KONTAK - API INSERT] RESPONSE:", $result ?? []);
            return $result;
        } catch (\Exception $e) {
            Log::error("LOG LOG: [KONTAK - API INSERT] ERROR: " . $e->getMessage());
            return ['rc' => '500', 'rd' => 'Error: ' . $e->getMessage()];
        }
    }

    private function updatePickupPointApi($pickupCode, $data)
    {
        $config = $this->getAutokirimConfig();
        $appMode = app()->environment('production') ? 'PRODUCTION' : 'DEV';

        try {
            $payload = [
                "name"              => (string) $data['nama'],
                "phone"             => (string) $data['no_hp'],
                "district_id"       => (int) $data['district_id'],
                "address"           => (string) $data['alamat'],
                "longitude"         => "",
                "latitude"          => "",
                "pickup_point_code" => (string) $pickupCode
            ];

            Log::info("LOG LOG: [KONTAK - API UPDATE] (API: {$config->mode} | APP: {$appMode}) REQUEST:", $payload);

            $response = Http::timeout(15)->withToken($config->token)->post("{$config->base_url}/api/pickup-point/update", $payload);
            $result = $response->json();

            Log::info("LOG LOG: [KONTAK - API UPDATE] RESPONSE:", $result ?? []);
            return ($response->successful() && isset($result['rc']) && $result['rc'] === '00');
        } catch (\Exception $e) {
            Log::error("LOG LOG: [KONTAK - API UPDATE] ERROR: " . $e->getMessage());
            return false;
        }
    }

    private function findPickupPointApi($pickupCode)
    {
        $config = $this->getAutokirimConfig();
        $appMode = app()->environment('production') ? 'PRODUCTION' : 'DEV';

        try {
            $payload = ['pickup_point_code' => (string) $pickupCode];
            Log::info("LOG LOG: [KONTAK - API FIND] (API: {$config->mode} | APP: {$appMode}) REQUEST:", $payload);

            $response = Http::timeout(10)->withToken($config->token)->post("{$config->base_url}/api/pickup-point/find", $payload);
            $result = $response->json();

            Log::info("LOG LOG: [KONTAK - API FIND] RESPONSE:", $result ?? []);
            return ($response->successful() && isset($result['rc']) && $result['rc'] === '00');
        } catch (\Exception $e) {
            Log::error("LOG LOG: [KONTAK - API FIND] ERROR: " . $e->getMessage());
            return false;
        }
    }

    private function deletePickupPointApi($pickupCode)
    {
        $config = $this->getAutokirimConfig();
        $appMode = app()->environment('production') ? 'PRODUCTION' : 'DEV';

        try {
            $payload = ['pickup_point_code' => (string) $pickupCode];
            Log::info("LOG LOG: [KONTAK - API DELETE] (API: {$config->mode} | APP: {$appMode}) REQUEST:", $payload);

            $response = Http::timeout(10)->withToken($config->token)->post("{$config->base_url}/api/pickup-point/delete", $payload);
            $result = $response->json();

            Log::info("LOG LOG: [KONTAK - API DELETE] RESPONSE:", $result ?? []);
            return ($response->successful() && isset($result['rc']) && $result['rc'] === '00');
        } catch (\Exception $e) {
            Log::error("LOG LOG: [KONTAK - API DELETE] ERROR: " . $e->getMessage());
            return false;
        }
    }
}
