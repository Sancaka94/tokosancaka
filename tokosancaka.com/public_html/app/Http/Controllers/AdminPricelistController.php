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
     * Mengecek sisa saldo di akun IAK
     */
    public function checkBalance()
    {
        $username = env('IAK_USERNAME');
        $apiKey = env('IAK_API_KEY');
        // LOG LOG: Format Sign Cek Saldo = md5(username + api_key + 'bl')
        $sign = md5($username . $apiKey . 'bl');

        // Membersihkan trailing slash dan /api jika terlanjur ditulis di .env
        $baseUrl = str_replace('/api', '', rtrim(env('IAK_URL'), '/'));
        $url = $baseUrl . '/v1/legacy/index';

        try {
            $response = Http::post($url, [
                'commands' => 'balance',
                'username' => $username,
                'sign'     => $sign
            ]);

            if ($response->successful()) {
                $balance = $response->json('data.balance');
                return back()->with('success', 'Saldo IAK Anda saat ini: Rp ' . number_format($balance, 0, ',', '.'));
            }

            Log::error('LOG LOG - IAK Balance Error: ' . $response->body());
            return back()->with('error', 'Gagal mengecek saldo. Cek koneksi atau kredensial API Anda.');

        } catch (\Exception $e) {
            Log::error('LOG LOG - IAK Balance Exception: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
        }
    }

    /**
     * Menarik data Pricelist dari API IAK dan langsung update ke Database
     */
    public function syncPricelistApi()
    {
        $username = env('IAK_USERNAME');
        $apiKey = env('IAK_API_KEY');
        // LOG LOG: Format Sign Pricelist = md5(username + api_key + 'pl')
        $sign = md5($username . $apiKey . 'pl');

        // Membersihkan trailing slash dan /api dari .env
        $baseUrl = str_replace('/api', '', rtrim(env('IAK_URL'), '/'));

        // FIX 404: Tambahkan /all/all untuk memenuhi parameter path /:type/:operator
        $url = $baseUrl . '/v1/legacy/index/all/all';

        try {
            $response = Http::post($url, [
                'commands' => 'pricelist',
                'username' => $username,
                'sign'     => $sign,
                'status'   => 'all'
            ]);

            // Cek apakah response sukses dan datanya benar-benar ada
            if ($response->successful() && $response->json('data') && !isset($response->json('data')['rc'])) {
                $products = $response->json('data');
                $count = 0;

                foreach ($products as $item) {
                    // LOG LOG: Jika deskripsi kosong/strip, gunakan nama nominalnya
                    $description = ($item['pulsa_details'] == '-' || empty($item['pulsa_details']))
                                    ? $item['pulsa_nominal']
                                    : $item['pulsa_details'];

                    // Insert atau Update ke database
                    IakPricelistPrepaid::updateOrCreate(
                        ['code' => $item['pulsa_code']], // Patokan update
                        [
                            'operator'    => $item['pulsa_op'],
                            'description' => $description,
                            'price'       => $item['pulsa_price'],
                            'type'        => $item['pulsa_type'],
                            // Samakan format status dengan sistem kamu (Active/Offline)
                            'status'      => strtolower($item['status']) == 'active' ? 'Active' : 'Offline'
                        ]
                    );
                    $count++;
                }

                Log::info("LOG LOG - Sinkronisasi API IAK Berhasil. Total: $count produk.");
                return back()->with('success', "Sinkronisasi sukses! $count produk berhasil diupdate langsung dari IAK.");
            }

            // Jika API IAK mengembalikan format error (seperti 404 tadi)
            $errorMsg = $response->json('data.message') ?? $response->body();
            Log::error('LOG LOG - IAK Pricelist Error: ' . $errorMsg);
            return back()->with('error', 'Gagal menarik data pricelist dari IAK: ' . $errorMsg);

        } catch (\Exception $e) {
            Log::error('LOG LOG - IAK Pricelist Exception: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat sinkronisasi: ' . $e->getMessage());
        }
    }
}
