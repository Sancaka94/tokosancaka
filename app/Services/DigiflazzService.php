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

    public function __construct()
    {
        // Kredensial (Sesuaikan dengan .env jika production)
        $this->username = 'mihetiDVGdeW'; 
        $this->apiKey   = 'dev-d54808c0-87ed-11f0-bdb6-8d5622821215'; 
        $this->baseUrl  = 'https://api.digiflazz.com/v1'; 
    }

    /**
     * 1. Mengambil Daftar Harga (Price List)
     */
    public function getPriceList($cmd = 'prepaid')
    {
        $sign = md5($this->username . $this->apiKey . "depo");

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
            'testing' => true, // Pastikan TRUE untuk test case (misal: xld10087800001230)
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
            'testing' => true, // Wajib TRUE untuk Test Case (misal: pln530000000001)
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
}