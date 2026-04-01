<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log; // LOG LOG
use App\Models\IakPricelistPrepaid;
use Illuminate\Support\Facades\Http;

class AdminPricelistController extends Controller
{
    public function index()
    {
        return view('admin.pricelist_upload');
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
            'type' => 'required|string'
        ]);

        try {
            $data = Excel::toArray([], $request->file('file'));
            $sheet = $data[0];

            $count = 0;

            foreach ($sheet as $key => $row) {
                // Lewati baris pertama (header)
                if ($key == 0) {
                    continue;
                }

                // ========================================================
                // LOGIKA UNTUK PASCABAYAR (Berdasarkan format gambar)
                // ========================================================
                if ($request->type === 'pasca') {
                    // Cek jika Product Code (Kolom B / Index 1) kosong, maka lewati
                    if (empty($row[1]) || $row[1] == 'Product Code') {
                        continue;
                    }

                    $code = $row[1];
                    $operator = 'Pascabayar'; // Set default operator untuk Pasca
                    $description = $row[2]; // Product Name (Kolom C)

                    // Bersihkan Fee (Kolom D / Index 3) dari "Rp" dan titik
                    $rawPrice = $row[3] ?? '0';
                    $cleanPrice = preg_replace('/[^0-9]/', '', $rawPrice);
                    if (empty($cleanPrice)) $cleanPrice = 0; // Fallback jika kosong

                    $status = $row[5] ?? 'Active'; // Kolom F

                }
                // ========================================================
                // LOGIKA UNTUK PRABAYAR (Sesuai format lama)
                // ========================================================
                else {
                    // Cek jika Kode (Kolom C / Index 2) kosong, maka lewati
                    if (empty($row[2]) || $row[1] == 'Operator') {
                        continue;
                    }

                    $code = $row[2];
                    $operator = $row[1]; // Operator (Kolom B)

                    // Bersihkan Harga (Kolom F / Index 5)
                    $rawPrice = $row[5] ?? '0';
                    $cleanPrice = preg_replace('/[^0-9]/', '', $rawPrice);
                    if (empty($cleanPrice)) $cleanPrice = 0; // Fallback jika kosong

                    $nominal = $row[3] ?? '';
                    $detail = $row[4] ?? '';
                    $description = ($detail != '' && $detail != '-') ? $detail : $nominal;

                    $status = $row[6] ?? 'Active'; // Kolom G
                }

                // ========================================================
                // INSERT ATAU UPDATE KE DATABASE
                // ========================================================
                IakPricelistPrepaid::updateOrCreate(
                    ['code' => $code], // Patokan update adalah kode produk
                    [
                        'operator'    => $operator,
                        'description' => $description,
                        'price'       => $cleanPrice,
                        'status'      => $status,
                        'type'        => $request->type
                    ]
                );

                $count++;
            }

            Log::info('LOG LOG - Upload Excel Sukses', ['type' => $request->type, 'total_baris' => $count]); // LOG LOG
            return back()->with('success', "$count data pricelist berhasil diupload dan disimpan!");

        } catch (\Exception $e) {
            Log::error('LOG LOG - Error Upload Excel', ['error' => $e->getMessage()]); // LOG LOG
            return back()->with('error', 'Gagal mengolah file Excel: ' . $e->getMessage());
        }
    }

   /**
     * Mengecek sisa saldo di akun IAK (Manual via Tombol)
     */
    public function checkBalance()
    {
        $username = env('IAK_USERNAME');
        $apiKey = env('IAK_API_KEY');
        // LOG LOG: Format Sign Cek Saldo = md5(username + api_key + 'bl')
        $sign = md5($username . $apiKey . 'bl');

        // Membersihkan URL dan memastikan /api selalu ada di belakangnya
        $baseUrl = rtrim(env('IAK_URL'), '/');
        if (!str_ends_with(strtolower($baseUrl), '/api')) {
            $baseUrl .= '/api';
        }
        $url = $baseUrl . '/v1/legacy/index';

        try {
            // Gunakan asForm() untuk memastikan API Legacy IAK membaca input dengan benar
            $response = Http::asForm()->post($url, [
                'commands' => 'balance',
                'username' => $username,
                'sign'     => $sign
            ]);

            if ($response->successful() && $response->json('data.balance') !== null) {
                $balance = $response->json('data.balance');

                // --- TAMBAHAN BARU: Simpan saldo ke database user auth ---
                if (auth()->check()) {
                    $user = auth()->user();
                    $user->balance_iak = $balance; // <-- UBAH DI SINI
                    $user->save();
                }

                return back()->with('success', 'Saldo IAK Anda saat ini: Rp ' . number_format($balance, 0, ',', '.'));
            }

            $realError = $response->json('data.message') ?? $response->body();
            Log::error('LOG LOG - IAK Balance Error: ' . $realError);
            // Menampilkan respon mentah IAK ke layar agar kita tahu penyebab pastinya
            return back()->with('error', 'Error dari IAK: ' . $realError);

        } catch (\Exception $e) {
            Log::error('LOG LOG - IAK Balance Exception: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
        }
    }

    /**
     * Menarik data Pricelist dari API IAK dan langsung update ke Database (API Versi 2 Final)
     */
    public function syncPricelistApi()
    {
        $username = env('IAK_USERNAME');
        $apiKey = env('IAK_API_KEY');
        // LOG LOG: Format Sign Pricelist = md5(username + api_key + 'pl')
        $sign = md5($username . $apiKey . 'pl');

        // Pastikan endpoint bersih menuju /api/pricelist
        $baseUrl = str_replace('/api', '', rtrim(env('IAK_URL'), '/'));
        $url = $baseUrl . '/api/pricelist';

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ])->post($url, [
                'username' => $username,
                'sign'     => $sign,
                'status'   => 'all'
                // Command tidak perlu lagi dikirim di Versi 2
            ]);

            $responseData = $response->json();

            // Cek Response Code (RC) dari IAK
            if (isset($responseData['data']['rc']) && $responseData['data']['rc'] !== '00') {
                $errorMsg = $responseData['data']['message'] ?? 'Pesan error tidak diketahui';
                Log::error('LOG LOG - IAK API Return Error: ' . $errorMsg);
                return back()->with('error', 'API IAK Menolak: ' . $errorMsg);
            }

            // PERUBAHAN UTAMA V2: Array produk ada di dalam index 'pricelist'
            if ($response->successful() && isset($responseData['data']['pricelist']) && is_array($responseData['data']['pricelist'])) {
                $products = $responseData['data']['pricelist'];
                $count = 0;

                foreach ($products as $item) {
                    $code = $item['product_code'] ?? null;
                    if (!$code) continue;

                    // Mengacu pada Doc V2: product_description = nama operator
                    $operator = $item['product_description'] ?? 'Unknown';

                    // Logika Deskripsi: Ambil product_details, kalau kosong/strip pakai product_nominal
                    $details = $item['product_details'] ?? '-';
                    $nominal = $item['product_nominal'] ?? 'Tanpa Deskripsi';
                    $description = ($details != '-' && !empty($details)) ? $details : $nominal;

                    $price = $item['product_price'] ?? 0;
                    $type = $item['product_type'] ?? 'Unknown';

                    $status = (strtolower($item['status'] ?? '') === 'active') ? 'Active' : 'Offline';

                    // Insert atau Update ke database
                    IakPricelistPrepaid::updateOrCreate(
                        ['code' => $code],
                        [
                            'operator'    => $operator,
                            'description' => $description,
                            'price'       => $price,
                            'type'        => $type,
                            'status'      => $status
                        ]
                    );
                    $count++;
                }

                Log::info("LOG LOG - Sinkron API IAK Versi 2 Berhasil: $count produk.");
                return back()->with('success', "Sinkronisasi sukses! $count produk berhasil ditarik dan diperbarui dari IAK API v2.");
            }

            Log::error('LOG LOG - IAK Pricelist Unreadable: ' . $response->body());
            return back()->with('error', 'Gagal memproses data. Struktur JSON dari IAK tidak sesuai format Versi 2.');

        } catch (\Exception $e) {
            Log::error('LOG LOG - IAK Pricelist Exception: ' . $e->getMessage());
            return back()->with('error', 'Koneksi ke API IAK terputus: ' . $e->getMessage());
        }
    }

    /**
     * API Internal: Tarik Saldo IAK secara Real-Time via AJAX (Widget Biru)
     * Otomatis menyimpan ke database ketika sinkronisasi berhasil.
     */
    public function liveBalanceApi()
    {
        $username = env('IAK_USERNAME');
        $apiKey = env('IAK_API_KEY');
        $sign = md5($username . $apiKey . 'bl');

        $baseUrl = str_replace('/api', '', rtrim(env('IAK_URL'), '/'));
        $url = $baseUrl . '/api/check-balance';

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ])->post($url, [
                'username' => $username,
                'sign'     => $sign
            ]);

            $data = $response->json();

            // Jika sukses dan key balance tersedia
            if ($response->successful() && isset($data['data']['balance'])) {
                $balance = $data['data']['balance'];

                // --- TAMBAHAN BARU: Otomatis update saldo user auth di background ---
                if (auth()->check()) {
                    $user = auth()->user();
                    $user->balance_iak = $balance; // <-- UBAH DI SINI
                    $user->save();
                }

                return response()->json([
                    'success' => true,
                    'balance' => $balance
                ]);
            }

            // Jika gagal, tangkap pesan aslinya agar bisa dilihat di layar
            $errorMsg = $data['data']['message'] ?? 'Format response IAK berubah';
            return response()->json(['success' => false, 'message' => 'API Error: ' . $errorMsg]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Koneksi Server Gagal']);
        }
    }

    /**
     * Endpoint API Internal untuk mengambil produk berdasarkan Operator, Tipe, & Nominal
     */
    public function getProductsByOperator(Request $request)
    {
        $operator = strtoupper($request->operator);
        $type = strtolower($request->type);
        $nominal = $request->nominal; // Tangkap input nominal

        $searchOperator = strtolower($operator);
        if ($operator === 'SMARTFREN') $searchOperator = 'smart';
        elseif ($operator === 'THREE') $searchOperator = 'tri';
        elseif ($operator === 'BY.U') $searchOperator = 'by.u';

        // Fungsi Closure untuk narik data dasar
        $fetchData = function($opName) use ($type) {
            return \App\Models\IakPricelistPrepaid::whereRaw('LOWER(operator) LIKE ?', ["%{$opName}%"])
                    ->whereRaw('LOWER(type) LIKE ?', ["%{$type}%"])
                    ->whereIn('status', ['Active', 'active', 'ACTIVE', 'Aktif'])
                    ->orderBy('price', 'asc')
                    ->get(['code', 'description', 'price']);
        };

        // 1. Tarik semua produk yang cocok
        $products = $fetchData($searchOperator);

        // Fallback Khusus
        if ($products->isEmpty() && $operator === 'BY.U') $products = $fetchData('telkomsel');
        if ($products->isEmpty() && $operator === 'THREE') $products = $fetchData('three');

        $message = '';
        $isExactMatch = false;

        // 2. Jika user mengetikkan Nominal, lakukan penyaringan (Filter)
        if (!empty($nominal) && $products->isNotEmpty()) {
            $filtered = $products->filter(function($item) use ($nominal) {
                // Bersihkan titik & koma dari deskripsi untuk pencarian yang akurat
                // Misal: "Pulsa 10.000" jadi "pulsa 10000"
                $cleanDesc = str_replace(['.', ','], '', strtolower($item->description));
                $cleanNominal = str_replace(['.', ','], '', strtolower($nominal));

                return str_contains($cleanDesc, $cleanNominal);
            })->values(); // Re-index array

            // 3. Cek apakah ada yang cocok
            if ($filtered->isNotEmpty()) {
                $products = $filtered; // Timpa dengan hasil filter
                $isExactMatch = true;
            } else {
                // Jika tidak ketemu (misal ketik 12345), kembalikan semua produk sebagai saran
                $message = "Nominal <b>{$nominal}</b> tidak ditemukan. Berikut saran produk yang tersedia:";
            }
        }

        return response()->json([
            'success' => true,
            'is_exact' => $isExactMatch,
            'message' => $message,
            'data' => $products
        ]);
    }

}
