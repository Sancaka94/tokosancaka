<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\PpobProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache; // <--- INI PENTING

class DigiflazzService
{
    protected $username;
    protected $apiKey;
    protected $baseUrl;

    const URL_PROD = 'https://api.digiflazz.com/v1'; 
    const URL_DEV  = 'https://sandbox.digiflazz.com/v1';

    public function __construct()
    {
        // Kredensial (Sesuaikan dengan .env jika production)
        $this->username = 'mihetiDVGdeW'; 
        //$this->apiKey   = 'dev-d54808c0-87ed-11f0-bdb6-8d5622821215';
        $this->apiKey   = '1f48c69f-8676-5d56-a868-10a46a69f9b7';
        $this->baseUrl  = 'https://api.digiflazz.com/v1'; 
    }

    public function getPriceList($cmd = 'prepaid')
    {
        // Formula signature: md5(username + apiKey + "pricelist")
        $sign = md5($this->username . $this->apiKey . "pricelist");
        
        $payload = [
            'cmd' => $cmd,
            'username' => $this->username,
            'sign' => $sign
        ];

        try {
            Log::info("➡️ [DIGIFLAZZ] Requesting Price List ($cmd)", ['payload' => $payload]);

            $url = $this->baseUrl . '/price-list';
            $response = Http::timeout(30)->post($url, $payload);
            
            $responseData = $response->json();

            // Log respons lengkap untuk debugging
            Log::info("✅ [DIGIFLAZZ] Price List ($cmd) Response", [
                'status_code' => $response->status(),
                'body' => $responseData
            ]);

            if ($response->successful()) {
                // ⭐ PERBAIKAN KRITIS: Cek apakah key 'data' ada dan merupakan array
                if (isset($responseData['data']) && is_array($responseData['data'])) {
                    // Mengembalikan array produk langsung (yang akan digabungkan di Controller)
                    return $responseData['data']; 
                }

                // Jika respons sukses tapi struktur data salah/kosong
                Log::warning("Digiflazz Price List ($cmd) Succeeded but data structure is invalid or empty.");
                return [];
            }
            
            // Jika respons tidak sukses (misalnya status 400, 500)
            Log::error("Digiflazz Price List ($cmd) Failed: HTTP Status " . $response->status() . ". Body: " . ($response->body() ?? 'No response body.'));
            return [];
            
        } catch (\Exception $e) {
            // Tangani Exception koneksi (timeout, SSL, dll.)
            Log::error("Digiflazz Price List ($cmd) EXCEPTION: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 2. Sinkronisasi Produk
     * Menggunakan Cache::remember untuk membatasi eksekusi sync menjadi 1x setiap 5 menit.
     */
    /**
 * 2. Sinkronisasi Produk (Manual/Sekali Jalan dengan Jeda Cache 6 Menit)
 */
public function syncProducts()
{
    $prepaidCacheKey = 'digiflazz_sync_prepaid_last_run';
    $postpaidCacheKey = 'digiflazz_sync_postpaid_last_run';
    $jedaMenit = 6; // Jeda yang dibutuhkan (6 menit)

    Log::info('🔄 [SYNC START MANUAL] Melakukan Sinkronisasi Pricelist Digiflazz (Semua Jenis).');

    // 1. --- Ambil Produk Prabayar ---
    $productsPrepaid = $this->getPriceList('prepaid');
    $prepaidSuccess = $this->updateDatabase($productsPrepaid, 'prepaid');

    // Set cache jika Prepaid berhasil, mencatat waktu terakhir sinkronisasi.
    if ($prepaidSuccess) {
        Cache::put($prepaidCacheKey, now(), now()->addMinutes($jedaMenit));
    }
    
    // 2. --- Ambil Produk Pascabayar (Bersyarat) ---
    $productsPostpaid = [];
    $waktuPrepaidTerakhir = Cache::get($prepaidCacheKey);

    if (now()->greaterThan($waktuPrepaidTerakhir)) {
        // Jika waktu saat ini lebih dari waktu di cache (artinya sudah lewat 6 menit),
        // atau jika cache tidak ada, kita bisa melanjutkan (kecuali jika API menolak).
        
        Log::info("➡️ [DIGIFLAZZ] Jeda $jedaMenit menit terpenuhi. Requesting Price List (postpaid)");
        
        // Kita tetap menggunakan sleep singkat sebagai pencegahan tambahan
        sleep(4); 
        
        $postpaidResponse = $this->getPriceList('postpaid');

        // Cek jika respons adalah error limit (rc: 83)
        if (isset($postpaidResponse['rc']) && $postpaidResponse['rc'] == '83') {
            Log::warning('Sinkronisasi Pascabayar Gagal karena limitasi Digiflazz (rc: 83). Produk Pascabayar dilewati.');
        } else {
            // Jika berhasil/bukan error limit, coba update database
            $productsPostpaid = $postpaidResponse;
            $this->updateDatabase($productsPostpaid, 'postpaid');
            Cache::put($postpaidCacheKey, now(), now()->addMinutes($jedaMenit));
        }
        
    } else {
        // Belum mencapai jeda 6 menit, lewati pascabayar.
        Log::info("⌛ [SYNC SKIP] Sinkronisasi Pascabayar dilewati. Belum mencapai jeda $jedaMenit menit sejak Prepaid.");
    }
    
    // Logika penggabungan dan DB update kini ada di updateDatabase() dan logika di atas.
    
    // Mengembalikan status sukses jika prepaid sukses (dan postpaid jika dieksekusi)
    return $prepaidSuccess; 
}
    /**
     * 3. Transaksi Prabayar (Pulsa/Data/Token)
     */
    public function transaction($sku, $customerNo, $refId, $maxPrice = 0)
    {
        $sign = md5($this->username . $this->apiKey . $refId);

        $payload = [
            'username' => $this->username,
            'buyer_sku_code' => $sku,
            'customer_no' => $customerNo,
            'ref_id' => $refId,
            'sign' => $sign,
            'max_price' => $maxPrice,
            'testing' => true, // Pastikan TRUE untuk test case
        ];

        try {
            Log::info("➡️ [TRX Request] $refId - $sku - $customerNo", $payload);
            $response = Http::post($this->baseUrl . '/transaction', $payload);
            Log::info("⬅️ [TRX Response] $refId", $response->json() ?? []);
            
            return $response->json();
        } catch (\Exception $e) {
            Log::error("❌ [TRX Error] $refId: " . $e->getMessage());
            return ['data' => ['status' => 'Gagal', 'message' => 'Koneksi Error']];
        }
    }

    /**
     * 4. Cek Tagihan Pascabayar (Inquiry)
     */
    public function inquiryPasca($sku, $customerNo, $refId)
    {
        $sign = md5($this->username . $this->apiKey . $refId);

        $payload = [
            'commands' => 'inq-pasca',
            'username' => $this->username,
            'buyer_sku_code' => $sku,
            'customer_no' => $customerNo,
            'ref_id' => $refId,
            'sign' => $sign,
            'testing' => false, // Wajib TRUE untuk Test Case
        ];

        try {
            Log::info("➡️ [INQ Request] $refId - $sku - $customerNo", $payload);
            $response = Http::post($this->baseUrl . '/transaction', $payload);
            Log::info("⬅️ [INQ Response] $refId", $response->json() ?? []);

            return $response->json();
        } catch (\Exception $e) {
            Log::error("❌ [INQ Error] $refId: " . $e->getMessage());
            return ['data' => ['status' => 'Gagal', 'message' => 'Koneksi Error']];
        }
    }

    /**
     * 5. Cek ID PLN (Inquiry PLN Prepaid)
     */
    public function inquiryPln($customerNo)
    {
        $sign = md5($this->username . $this->apiKey . $customerNo);

        $payload = [
            'username' => $this->username,
            'customer_no' => $customerNo,
            'sign' => $sign
        ];

        try {
            $response = Http::post($this->baseUrl . '/inquiry-pln', $payload);
            return $response->json();
        } catch (\Exception $e) {
            Log::error('Digiflazz Inquiry PLN Error: ' . $e->getMessage());
            return ['data' => ['status' => 'Gagal', 'message' => 'Koneksi Error']];
        }
    }

    /**
     * 6. Bayar Tagihan Pascabayar (PAY)
     * Mengubah status Inquiry menjadi Payment (Lunas)
     */
    public function payPasca($sku, $customerNo, $refId)
    {
        // NOTE: Ref ID harus SAMA PERSIS dengan saat inquiry (Cek Tagihan)
        $sign = md5($this->username . $this->apiKey . $refId);

        $payload = [
            'commands'       => 'pay-pasca', // Perintah khusus bayar
            'username'       => $this->username,
            'buyer_sku_code' => $sku,
            'customer_no'    => $customerNo,
            'ref_id'         => $refId,      // Wajib sama dengan INQ
            'sign'           => $sign,
            'testing'        => true,        // Wajib TRUE untuk Test Case
        ];

        try {
            Log::info("➡️ [PAY Request] $refId", $payload);
            
            // Timeout diperpanjang karena proses bayar pasca kadang butuh waktu
            $response = Http::timeout(45)->post($this->baseUrl . '/transaction', $payload);
            $respData = $response->json();
            
            Log::info("⬅️ [PAY Response] $refId", $respData ?? []);

            return $respData;

        } catch (\Exception $e) {
            Log::error("❌ [PAY Error] $refId: " . $e->getMessage());
            return ['data' => ['status' => 'Gagal', 'message' => 'Koneksi Error: ' . $e->getMessage()]];
        }
    }

      /**
     * Mengambil daftar semua produk PBB (untuk list kota).
     * Filter manual karena Digiflazz tidak menyediakan API khusus kota.
     */
    public function getPbbProducts()
    {
        // Signature untuk pricelist adalah "pricelist"
        $sign = md5($this->username . $this->apiKey . "pricelist");

        try {
            $response = Http::post($this->baseUrl . '/price-list', [
                'cmd' => 'prepaid', // Mengambil semua produk prepaid/postpaid
                'username' => $this->username,
                'sign' => $sign
            ]);

            if ($response->successful()) {
                $allProducts = $response->json()['data'] ?? [];
                $pbbProducts = [];
                
                // Filter produk PBB berdasarkan nama atau SKU
                foreach ($allProducts as $product) {
                    $sku = strtolower($product['buyer_sku_code'] ?? '');
                    if (str_contains(strtolower($product['product_name'] ?? ''), 'pbb') || $sku === 'cimahi' || $sku === 'pdl') {
                        $pbbProducts[] = [
                            'sku' => $sku,
                            'name' => $product['product_name'] ?? $sku,
                            'brand' => $product['brand'] ?? 'PBB',
                        ];
                    }
                }
                
                return $pbbProducts;
            }
            return [];

        } catch (\Exception $e) {
            Log::error('Digiflazz PBB Price List Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * PENTING: Method untuk mengatur kredensial dari Controller
     */
    public function setCredentials($username, $apiKey, $testingMode)
    {
        $this->username = $username;
        $this->apiKey = $apiKey;
        $this->testingMode = $testingMode;
        // Atur Base URL sesuai mode testing
        $this->baseUrl = $testingMode ? self::URL_DEV : self::URL_PROD;
    }

    // DigiflazzService.php

// Pastikan model PpobProduct sudah diimpor: use App\Models\PpobProduct;

/**
 * Metode Pembantu untuk Logika Update Database
 * Melakukan bulk update/insert data produk ke database.
 * * @param array $products Daftar produk dari API (prepaid atau postpaid)
 * @param string $type Jenis produk ('prepaid' atau 'postpaid')
 * @return bool Status keberhasilan update database
 */
protected function updateDatabase(array $products, string $type)
{
    if (empty($products)) {
        Log::warning("Sync Product Failed ($type): Product list is empty.");
        return false;
    }

    DB::beginTransaction();
    try {
        $processedCount = 0;
        
        // Margin keuntungan yang diterapkan (diasumsikan Rp 2000)
        $margin = 2000;

        foreach ($products as $item) {
            // Pastikan item adalah array dan memiliki key dasar yang diperlukan
            if (!is_array($item)) continue;
            if (!isset($item['buyer_sku_code']) || !isset($item['price'])) continue;

            $modal = (float)$item['price'];
            $hargaJualAwal = $modal + $margin;

            // 1. Cari atau buat record berdasarkan buyer_sku_code (Upsert logic)
            $product = PpobProduct::firstOrNew(['buyer_sku_code' => $item['buyer_sku_code']]);
            
            // 2. Isi/Update data produk dari API
            $product->fill([
                'product_name' => $item['product_name'] ?? null,
                'category' => $item['category'] ?? null,
                'brand' => $item['brand'] ?? null,
                'type' => $item['type'] ?? null,
                'seller_name' => $item['seller_name'] ?? null,
                'price' => $modal, // Harga Modal dari Digiflazz
                'buyer_product_status' => $item['buyer_product_status'] ?? false,
                'seller_product_status' => $item['seller_product_status'] ?? false,
                'unlimited_stock' => $item['unlimited_stock'] ?? false,
                'stock' => $item['stock'] ?? 0,
                'multi' => $item['multi'] ?? false,
                'start_cut_off' => $item['start_cut_off'] ?? null,
                'end_cut_off' => $item['end_cut_off'] ?? null,
                'desc' => $item['desc'] ?? null,
            ]);

            // 3. Atur Harga Jual (Hanya di-set jika produk baru atau harga jual belum pernah diatur)
            // Ini mencegah menimpa manual sell_price yang mungkin sudah diatur admin.
            if (!$product->exists || $product->sell_price <= 0) {
                $product->sell_price = $hargaJualAwal;
            }
            
            // Tambahkan kolom penanda jenis produk (jika ada di model)
            // $product->is_postpaid = ($type === 'postpaid');
            
            // 4. Simpan ke database
            $product->save();
            $processedCount++;
        }
        
        DB::commit(); // Komit semua perubahan
        
        Log::info("✅ [SYNC END] Sinkronisasi Produk ($type) Berhasil. Total $processedCount items.");
        return true; 

    } catch (\Exception $e) {
        DB::rollBack(); // Rollback jika terjadi error
        Log::error("Sync Product Failed ($type): Gagal saat memproses data. " . $e->getMessage());
        return false; 
    }
}

    /**
     * 2.1. Sinkronisasi Produk PRABAYAR
     */
    public function syncPrepaidProducts()
    {
        $cacheDuration = 300; // 5 menit
        $cacheKey = 'digiflazz_prepaid_pricelist_sync'; 

        $syncResult = Cache::remember($cacheKey, $cacheDuration, function () {
            Log::info('🔄 [SYNC START] Melakukan Sinkronisasi Pricelist PRABAYAR ke Digiflazz.');
            
            $productsPrepaid = $this->getPriceList('prepaid');
            
            return $this->updateDatabase($productsPrepaid, 'prepaid');
        });
        
        return $syncResult;
    }

    /**
     * 2.2. Sinkronisasi Produk PASCABAYAR
     */
    public function syncPostpaidProducts()
    {
        $cacheDuration = 300; // 5 menit
        $cacheKey = 'digiflazz_postpaid_pricelist_sync'; 

        $syncResult = Cache::remember($cacheKey, $cacheDuration, function () {
            Log::info('🔄 [SYNC START] Melakukan Sinkronisasi Pricelist PASCABAYAR ke Digiflazz.');

            $productsPostpaid = $this->getPriceList('postpaid');
            
            return $this->updateDatabase($productsPostpaid, 'postpaid');
        });
        
        return $syncResult;
    }
}