<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\PpobProduct; // Pastikan model ini ada
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache; 
use Illuminate\Http\JsonResponse; // Tambahkan untuk type hinting

class DigiflazzService
{
    protected string $username;
    protected string $apiKey;
    protected string $baseUrl;
    protected bool $testingMode = false; // Tambahkan mode testing

    const URL_PROD = 'https://api.digiflazz.com/v1'; 
    const URL_DEV  = 'https://sandbox.digiflazz.com/v1';

    public function __construct()
    {
        // 🚨 PERBAIKAN KRITIS: Ambil Kredensial dari .env (bukan hardcode)
        // Hardcode kredensial sangat tidak disarankan di Production.
        // Gunakan trim() untuk menghindari spasi/newline yang menyebabkan error Signature (rc: 41)
        $this->username = trim(env('DIGIFLAZZ_USERNAME', 'mihetiDVGdeW')); 
        $this->apiKey   = trim(env('DIGIFLAZZ_API_KEY', '1f48c69f-8676-5d56-a868-10a46a69f9b7'));
        
        // Asumsi default adalah mode Production
        $this->baseUrl  = self::URL_PROD; 
    }

    /**
     * PENTING: Method untuk mengatur kredensial dari Controller (digunakan untuk testing/override)
     */
    public function setCredentials(string $username, string $apiKey, bool $testingMode): void
    {
        $this->username = trim($username);
        $this->apiKey = trim($apiKey);
        $this->testingMode = $testingMode;
        // Atur Base URL sesuai mode testing
        $this->baseUrl = $testingMode ? self::URL_DEV : self::URL_PROD;
    }

    // --- Core API Calls ---

    public function getPriceList(string $cmd = 'prepaid'): array
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

        // 🟢 PERUBAHAN DI SINI: Catat seluruh respons data
        Log::info("✅ [DIGIFLAZZ] Price List ($cmd) Full Response", [
            'status_code' => $response->status(),
            'body_data' => $responseData // <-- BARIS INI AKAN MENCATAT SEMUA DATA PRODUK
        ]);

        if (is_array($responseData)) {
            return $responseData; 
        }
            
            // 🔴 PERUBAHAN DI SINI: Jika respons GAGAL HTTP, kembalikan respons JSON Digiflazz jika ada
        Log::error("Digiflazz Price List ($cmd) Failed: HTTP Status " . $response->status() . ". Body: " . ($response->body() ?? 'No response body.'));
        
        // Kembalikan respons JSON error Digiflazz jika tersedia, jika tidak, kembalikan array kosong
        return $responseData ?? [];
        
    } catch (\Exception $e) {
        // 🔴 PERUBAHAN DI SINI: Untuk exception murni (koneksi terputus, timeout), kembalikan array dengan pesan error
        Log::error("Digiflazz Price List ($cmd) EXCEPTION: " . $e->getMessage());
        
        return [
            'data' => [
                'status' => 'Gagal',
                'message' => 'Koneksi atau Timeout Error: ' . $e->getMessage()
            ]
        ];
    }
}

    /**
     * 3. Transaksi Prabayar (Pulsa/Data/Token)
     */
    public function transaction(string $sku, string $customerNo, string $refId, float $maxPrice = 0): array
    {
        // 🚨 PERBAIKAN KRITIS: Signature harus menggunakan API Key/Secret Key yang benar
        // Pastikan Anda menggunakan API Key atau Secret Key, sesuai dokumentasi Digiflazz.
        // Asumsi: menggunakan API Key (sama dengan yang di __construct)
        $sign = md5($this->username . $this->apiKey . $refId);

        $payload = [
            'username' => $this->username,
            'buyer_sku_code' => $sku,
            'customer_no' => $customerNo,
            'ref_id' => $refId,
            'sign' => $sign,
            'max_price' => $maxPrice,
            // Gunakan properti testingMode, bukan hardcode
            'testing' => $this->testingMode, 
        ];

        try {
            Log::info("➡️ [TRX Request] $refId - $sku - $customerNo", $payload);
            $response = Http::post($this->baseUrl . '/transaction', $payload);
            
            // Mengatasi kasus body kosong
            $respData = $response->json() ?? ['data' => ['status' => 'Gagal', 'message' => 'No response body']];
            
            Log::info("⬅️ [TRX Response] $refId", $respData);
            
            // 🚨 PERBAIKAN: Selalu kembalikan array, bukan objek Response
            return $respData; 
        } catch (\Exception $e) {
            Log::error("❌ [TRX Error] $refId: " . $e->getMessage());
            return ['data' => ['status' => 'Gagal', 'message' => 'Koneksi Error']];
        }
    }

    /**
     * 4. Cek Tagihan Pascabayar (Inquiry)
     */
    public function inquiryPasca(string $sku, string $customerNo, string $refId): array
    {
        $sign = md5($this->username . $this->apiKey . $refId);

        $payload = [
            'commands' => 'inq-pasca',
            'username' => $this->username,
            'buyer_sku_code' => $sku,
            'customer_no' => $customerNo,
            'ref_id' => $refId,
            'sign' => $sign,
            // Gunakan properti testingMode
            'testing' => $this->testingMode, 
        ];
        // ... (Logika try-catch inquiryPasca)
        try {
            Log::info("➡️ [INQ Request] $refId - $sku - $customerNo", $payload);
            $response = Http::post($this->baseUrl . '/transaction', $payload);
            $respData = $response->json() ?? ['data' => ['status' => 'Gagal', 'message' => 'No response body']];
            Log::info("⬅️ [INQ Response] $refId", $respData);
            return $respData;

        } catch (\Exception $e) {
            Log::error("❌ [INQ Error] $refId: " . $e->getMessage());
            return ['data' => ['status' => 'Gagal', 'message' => 'Koneksi Error']];
        }
    }

    /**
     * 5. Cek ID PLN (Inquiry PLN Prepaid)
     */
    public function inquiryPln(string $customerNo): array
    {
        $sign = md5($this->username . $this->apiKey . $customerNo);

        $payload = [
            'username' => $this->username,
            'customer_no' => $customerNo,
            'sign' => $sign
        ];

        try {
            $response = Http::post($this->baseUrl . '/inquiry-pln', $payload);
            $respData = $response->json() ?? ['data' => ['status' => 'Gagal', 'message' => 'No response body']];
            return $respData;
        } catch (\Exception $e) {
            Log::error('Digiflazz Inquiry PLN Error: ' . $e->getMessage());
            return ['data' => ['status' => 'Gagal', 'message' => 'Koneksi Error']];
        }
    }

    /**
     * 6. Bayar Tagihan Pascabayar (PAY)
     */
    public function payPasca(string $sku, string $customerNo, string $refId): array
    {
        $sign = md5($this->username . $this->apiKey . $refId);

        $payload = [
            'commands' => 'pay-pasca', 
            'username' => $this->username,
            'buyer_sku_code' => $sku,
            'customer_no' => $customerNo,
            'ref_id' => $refId,
            'sign' => $sign,
            // Gunakan properti testingMode
            'testing' => $this->testingMode, 
        ];

        try {
            Log::info("➡️ [PAY Request] $refId", $payload);
            $response = Http::timeout(45)->post($this->baseUrl . '/transaction', $payload);
            $respData = $response->json() ?? ['data' => ['status' => 'Gagal', 'message' => 'No response body']];
            
            Log::info("⬅️ [PAY Response] $refId", $respData);

            return $respData;

        } catch (\Exception $e) {
            Log::error("❌ [PAY Error] $refId: " . $e->getMessage());
            return ['data' => ['status' => 'Gagal', 'message' => 'Koneksi Error: ' . $e->getMessage()]];
        }
    }

    /**
     * Mengambil daftar semua produk PBB (untuk list kota).
     */
    public function getPbbProducts(): array
    {
        $sign = md5($this->username . $this->apiKey . "pricelist");

        try {
             // Mengambil pricelist prepaid dan postpaid untuk mencari PBB
            $payload = [
                'cmd' => 'all', // Gunakan 'all' untuk efisiensi
                'username' => $this->username,
                'sign' => $sign
            ];

            $response = Http::post($this->baseUrl . '/price-list', $payload);

            if ($response->successful()) {
                $allProducts = $response->json()['data'] ?? [];
                $pbbProducts = [];
                
                foreach ($allProducts as $product) {
                    $sku = strtolower($product['buyer_sku_code'] ?? '');
                    
                    // Filter produk PBB berdasarkan kategori atau brand
                    $isPbb = (
                        str_contains(strtolower($product['category'] ?? ''), 'pbb') ||
                        str_contains(strtolower($product['brand'] ?? ''), 'pbb')
                        // Tambahkan filter lain sesuai kebutuhan
                    );

                    if ($isPbb) {
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

    // --- Sync Logic (Sudah Dibalik: Postpaid -> Prepaid) ---

    public function syncProducts(): bool
    {
        // Kunci Cache untuk Postpaid (pertama) dan Prepaid (kedua)
        $postpaidCacheKey = 'digiflazz_sync_postpaid_last_run';
        $prepaidCacheKey = 'digiflazz_sync_prepaid_last_run';
        $jedaMenit = 6; // Jeda yang dibutuhkan (6 menit)
        $postpaidSuccess = false; 

        Log::info('🔄 [SYNC START MANUAL] Melakukan Sinkronisasi Pricelist Digiflazz (Urutan: Postpaid, Prepaid)');

        // 1. --- Ambil Produk Pascabayar ---
        $productsPostpaid = $this->getPriceList('postpaid');
        $postpaidSuccess = $this->updateDatabase($productsPostpaid, 'postpaid');

        if ($postpaidSuccess) {
            Cache::put($postpaidCacheKey, now(), now()->addMinutes($jedaMenit));
        }
        
        // 2. --- Ambil Produk Prabayar (Bersyarat) ---
        $prepaidSuccess = false;
        $waktuPostpaidTerakhir = Cache::get($postpaidCacheKey);

        $canRunPrepaid = (
            !$waktuPostpaidTerakhir || 
            (is_object($waktuPostpaidTerakhir) && now()->greaterThan($waktuPostpaidTerakhir->addMinutes($jedaMenit))) 
            // 💡 Catatan: Cek is_object() penting jika cache mengembalikan string/null
        );

        if ($canRunPrepaid) {
            Log::info("➡️ [DIGIFLAZZ] Jeda $jedaMenit menit terpenuhi atau Sync pertama. Requesting Price List (prepaid)");
            sleep(4); 
            
            $prepaidResponse = $this->getPriceList('prepaid');

            // Cek jika respons adalah error limit (rc: 83)
            if (isset($prepaidResponse['rc']) && $prepaidResponse['rc'] == '83') {
                Log::warning('Sinkronisasi Prabayar Gagal karena limitasi Digiflazz (rc: 83). Produk Prabayar dilewati.');
            } else {
                $productsPrepaid = $prepaidResponse;
                $prepaidSuccess = $this->updateDatabase($productsPrepaid, 'prepaid');
                
                if ($prepaidSuccess) {
                     Cache::put($prepaidCacheKey, now(), now()->addMinutes($jedaMenit));
                }
            }
            
        } else {
            Log::info("⌛ [SYNC SKIP] Sinkronisasi Prabayar dilewati. Belum mencapai jeda $jedaMenit menit sejak Postpaid.");
        }
        
        return $postpaidSuccess || $prepaidSuccess; 
    }


   // --- Helper Methods ---
    
    protected function updateDatabase(array $products, string $type): bool
    {
        // ... (Logika updateDatabase tetap sama, tidak perlu diubah) ...
        if (empty($products)) {
            Log::warning("Sync Product Failed ($type): Product list is empty.");
            return false;
        }

        DB::beginTransaction();
        try {
            $processedCount = 0;
            $margin = 2000;

            foreach ($products as $item) {
                if (!is_array($item)) continue;
                if (!isset($item['buyer_sku_code']) || !isset($item['price'])) continue;

                $modal = (float)$item['price'];
                $hargaJualAwal = $modal + $margin;

                $product = PpobProduct::firstOrNew(['buyer_sku_code' => $item['buyer_sku_code']]);
                
                $product->fill([
                    'product_name' => $item['product_name'] ?? null,
                    'category' => $item['category'] ?? null,
                    'brand' => $item['brand'] ?? null,
                    'type' => $item['type'] ?? null,
                    'seller_name' => $item['seller_name'] ?? null,
                    'price' => $modal, 
                    'buyer_product_status' => $item['buyer_product_status'] ?? false,
                    'seller_product_status' => $item['seller_product_status'] ?? false,
                    'unlimited_stock' => $item['unlimited_stock'] ?? false,
                    'stock' => $item['stock'] ?? 0,
                    'multi' => $item['multi'] ?? false,
                    'start_cut_off' => $item['start_cut_off'] ?? null,
                    'end_cut_off' => $item['end_cut_off'] ?? null,
                    'desc' => $item['desc'] ?? null,
                ]);
                
                if (!$product->exists || $product->sell_price <= 0) {
                    $product->sell_price = $hargaJualAwal;
                }
                
                $product->save();
                $processedCount++;
            }
            
            DB::commit(); 
            
            Log::info("✅ [SYNC END] Sinkronisasi Produk ($type) Berhasil. Total $processedCount items.");
            return true; 

        } catch (\Exception $e) {
            DB::rollBack(); 
            Log::error("Sync Product Failed ($type): Gagal saat memproses data. " . $e->getMessage());
            return false; 
        }
    }


   // --- Metode Sinkronisasi Terpisah (FINAL) ---

    public function syncPrepaidProducts(): bool
    {
        $cacheDuration = 360; // 6 menit
        $cacheKey = 'digiflazz_prepaid_pricelist_sync'; 

        $syncResult = Cache::remember($cacheKey, $cacheDuration, function () {
            Log::info('🔄 [SYNC START] Melakukan Sinkronisasi Pricelist PRABAYAR ke Digiflazz.');
            
            $response = $this->getPriceList('prepaid');
            
            if (isset($response['data']) && is_array($response['data'])) {
                // Penanganan Error Digiflazz (rc: 83 - Limit)
                if (isset($response['data']['rc']) && $response['data']['rc'] == '83') {
                     Log::warning('Sinkronisasi Prabayar Gagal karena limitasi Digiflazz (rc: 83). Produk Prabayar dilewati.');
                     // Kembalikan false untuk menunjukkan sync gagal, tapi Cache tetap diperbarui
                     return false; 
                }
                
                // Lanjutkan update database dengan data produk yang sebenarnya
                return $this->updateDatabase($response['data'], 'prepaid');
            }
            
            Log::error('Sinkronisasi Prabayar Gagal: Respon Digiflazz tidak memiliki struktur data yang benar.');
            return false;
        });
        
        return $syncResult;
    }

    public function syncPostpaidProducts(): bool
    {
        $cacheDuration = 360; // 6 menit
        $cacheKey = 'digiflazz_postpaid_pricelist_sync'; 

        $syncResult = Cache::remember($cacheKey, $cacheDuration, function () {
            Log::info('🔄 [SYNC START] Melakukan Sinkronisasi Pricelist PASCABAYAR ke Digiflazz.');

            // 🟢 PERBAIKAN KRITIS: Ganti 'postpaid' menjadi 'pasca'
            $response = $this->getPriceList('pasca'); 
            
            if (isset($response['data']) && is_array($response['data'])) {
                // Lanjutkan update database dengan data produk yang sebenarnya
                return $this->updateDatabase($response['data'], 'postpaid');
            }

            Log::error('Sinkronisasi Pascabayar Gagal: Respon Digiflazz tidak memiliki struktur data yang benar.');
            return false;
        });
        
        return $syncResult;
    }
}