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
    protected $testingMode; // Tambahkan properti untuk mode testing

    public function __construct()
    {
        // Nilai Default saat Service di-instantiate, akan di-override oleh Controller
        $this->username = 'mihetiDVGdeW'; 
        $this->apiKey   = '1f48c69f-8676-5d56-a868-10a46a69f9b7'; // Default ke Production
        //$this->apiKey   = 'dev-d54808c0-87ed-11f0-bdb6-8d5622821215';
        $this->baseUrl  = 'https://api.digiflazz.com/v1'; 
        $this->testingMode = false; // Default ke Production
    }

    /**
     * PENTING: Method untuk mengatur kredensial dari Controller
     */
    public function setCredentials($username, $apiKey, $testingMode)
    {
        $this->username = $username;
        $this->apiKey = $apiKey;
        $this->testingMode = $testingMode;
    }

    /**
     * 1. Mengambil Daftar Harga (Price List)
     */
    public function getPriceList($cmd = 'prepaid')
    {
        // Note: Signature untuk pricelist sebaiknya menggunakan string "pricelist"
        $sign = md5($this->username . $this->apiKey . "pricelist");

        try {
            $response = Http::post($this->baseUrl . '/price-list', [
                'cmd' => $cmd,
                'username' => $this->username,
                'sign' => $sign
            ]);

            if ($response->successful()) {
                return $response->json()['data'];
            }
            return [];
        } catch (\Exception $e) {
            Log::error('Digiflazz Price List Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 2. Sinkronisasi Produk
     */
    public function syncProducts()
    {
        $products = $this->getPriceList('prepaid');
        if (empty($products)) return false;

        DB::beginTransaction();
        try {
            foreach ($products as $item) {
                if (!is_array($item)) continue; 
                if (!isset($item['buyer_sku_code']) || !isset($item['price'])) continue;

                $margin = 2000; // Margin Default
                $modal = (float)$item['price'];
                $hargaJual = $modal + $margin;

                $product = PpobProduct::firstOrNew(['buyer_sku_code' => $item['buyer_sku_code']]);
                
                $product->product_name = $item['product_name'];
                $product->category     = $item['category'];
                $product->brand        = $item['brand'];
                $product->type         = $item['type'];
                $product->seller_name  = $item['seller_name'];
                $product->price        = $modal;

                if (!$product->exists || $product->sell_price <= 0) {
                    $product->sell_price = $hargaJual;
                }

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
            'testing' => $this->testingMode, // Menggunakan properti testing dari Controller
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
            'testing' => $this->testingMode, // Menggunakan properti testing dari Controller
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
        $sign = md5($this->username . $this->apiKey . $refId);

        $payload = [
            'commands'       => 'pay-pasca', 
            'username'       => $this->username,
            'buyer_sku_code' => $sku,
            'customer_no'    => $customerNo,
            'ref_id'         => $refId,      
            'sign'           => $sign,
            'testing'        => $this->testingMode, // Menggunakan properti testing dari Controller
        ];

        try {
            Log::info("➡️ [PAY Request] $refId", $payload);
            
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
        // Note: Signature untuk pricelist sebaiknya menggunakan string "pricelist"
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
}