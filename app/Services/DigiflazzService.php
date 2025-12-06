<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\PpobProduct;
use Illuminate\Support\Facades\DB;

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
     */
    public function syncProducts()
{
    // 1. Ambil Produk Prabayar
    $productsPrepaid = $this->getPriceList('prepaid');
    
    // 2. Ambil Produk Pascabayar
    $productsPostpaid = $this->getPriceList('postpaid');

    // Pastikan hasil dari getPriceList adalah array sebelum digabungkan
    $productsPrepaid = is_array($productsPrepaid) ? $productsPrepaid : [];
    $productsPostpaid = is_array($productsPostpaid) ? $productsPostpaid : [];
    
    // 3. Gabungkan hasilnya
    $products = array_merge($productsPrepaid, $productsPostpaid);

    // Cek apakah hasil gabungan kosong
    if (empty($products)) {
        Log::error('Sync Product Failed: Both prepaid and postpaid lists are empty.');
        return false;
    }

    DB::beginTransaction();
    try {
        foreach ($products as $item) {
            
            // FIX: Tambahkan pemeriksaan untuk memastikan $item adalah array yang valid 
            // sebelum memproses offset (untuk mencegah TypeError)
            if (!is_array($item)) continue;
            if (!isset($item['buyer_sku_code']) || !isset($item['price'])) continue;

            $margin = 2000; // Margin Default
            $modal = (float)$item['price'];
            $hargaJual = $modal + $margin;

            $product = PpobProduct::firstOrNew(['buyer_sku_code' => $item['buyer_sku_code']]);
            
            // Perbarui data produk
            $product->product_name = $item['product_name'];
            $product->category     = $item['category'];
            $product->brand        = $item['brand'];
            $product->type         = $item['type'];
            $product->seller_name  = $item['seller_name'];
            $product->price        = $modal;

            // Hanya update sell_price jika produk baru atau sell_price 0/kosong
            if (!$product->exists || $product->sell_price <= 0) {
                $product->sell_price = $hargaJual;
            }
            
            // Update status & detail dari API
            $product->buyer_product_status  = $item['buyer_product_status'];
            $product->seller_product_status = $item['seller_product_status'];
            $product->unlimited_stock       = $item['unlimited_stock'];
            $product->stock                 = $item['stock'];
            $product->multi                 = $item['multi'];
            $product->start_cut_off         = $item['start_cut_off'];
            $product->end_cut_off           = $item['end_cut_off'];
            $product->desc                  = $item['desc'];

            $product->save();
        }
        DB::commit();
        return true;
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Sync Product Failed: ' . $e->getMessage());
        return false;
    }
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
}