<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\PpobProduct; // Pastikan Model ini ada
use Illuminate\Support\Facades\DB;

class DigiflazzService
{
    protected $username;
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        // --- KONFIGURASI KREDENSIAL ---
        // Sebaiknya gunakan .env, tapi jika ingin hardcode untuk testing:
        
        $this->username = 'mihetiDVGdeW'; // Username Digiflazz
        $this->apiKey   = 'dev-d54808c0-87ed-11f0-bdb6-8d5622821215'; // API Key Prod/Dev
        
        // Mode Development / Production URL
        $this->baseUrl  = 'https://api.digiflazz.com/v1'; 
    }

    /**
     * 1. Mengambil Daftar Harga (Price List)
     * $cmd bisa 'prepaid' (pulsa, data, token) atau 'postpaid' (tagihan)
     */
    public function getPriceList($cmd = 'prepaid')
    {
        // Signature Price List = md5(username + key + "depo")
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

            Log::error('Digiflazz Price List Error: ' . $response->body());
            return [];
        } catch (\Exception $e) {
            Log::error('Digiflazz Connection Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 2. Sinkronisasi Produk dari API ke Database Lokal
     * Menyimpan data produk ke tabel 'ppob_products'
     */
    public function syncProducts()
    {
        // Ambil data Prepaid
        $products = $this->getPriceList('prepaid');

        // Debug: Cek apakah data kosong atau error
        if (empty($products)) {
            return false;
        }

        DB::beginTransaction();
        try {
            foreach ($products as $item) {
                // Lewati jika item bukan array valid
                if (!is_array($item)) continue; 
                if (!isset($item['buyer_sku_code']) || !isset($item['price'])) continue;

                // --- LOGIKA MARGIN KEUNTUNGAN ---
                // Margin default Rp 2.000 (Bisa diubah nanti di Admin Panel)
                $margin = 2000;
                $modal = (float)$item['price'];
                $hargaJual = $modal + $margin;

                // 1. Cari data lama atau buat objek baru berdasarkan SKU
                $product = PpobProduct::firstOrNew(['buyer_sku_code' => $item['buyer_sku_code']]);

                // 2. Update data dari API
                $product->product_name = $item['product_name'];
                $product->category     = $item['category'];
                $product->brand        = $item['brand'];
                $product->type         = $item['type'];
                $product->seller_name  = $item['seller_name'];
                $product->price        = $modal; // Update Harga Beli terbaru

                // 3. Update Harga Jual
                // Jika produk baru atau harga jual masih 0, set harga jual otomatis.
                // Jika produk lama, biarkan harga jual tetap (agar tidak merusak harga yang sudah diset manual admin),
                // KECUALI jika Anda ingin harga jual selalu mengikuti harga beli + margin, uncomment baris di bawah:
                // $product->sell_price = $hargaJual; 
                
                if (!$product->exists || $product->sell_price <= 0) {
                    $product->sell_price = $hargaJual;
                }

                // 4. Update status & stok
                $product->buyer_product_status  = $item['buyer_product_status']; // Status dari Pusat
                $product->seller_product_status = $item['seller_product_status']; // Status Jual
                $product->unlimited_stock       = $item['unlimited_stock'];
                $product->stock                 = $item['stock'];
                $product->multi                 = $item['multi'];
                $product->start_cut_off         = $item['start_cut_off'];
                $product->end_cut_off           = $item['end_cut_off'];
                $product->desc                  = $item['desc'];

                // 5. Simpan
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
     * 3. Cek Sisa Saldo Deposit Digiflazz
     */
    public function checkDeposit()
    {
        $sign = md5($this->username . $this->apiKey . "depo");

        try {
            $response = Http::post($this->baseUrl . '/cek-saldo', [
                'cmd' => 'deposit',
                'username' => $this->username,
                'sign' => $sign
            ]);

            $result = $response->json();

            if (isset($result['data']['deposit'])) {
                return [
                    'status' => true,
                    'deposit' => $result['data']['deposit'],
                    'message' => 'Berhasil cek saldo'
                ];
            }

            return [
                'status' => false,
                'deposit' => 0,
                'message' => $result['data']['message'] ?? 'Gagal mengambil data saldo'
            ];

        } catch (\Exception $e) {
            Log::error('Digiflazz Check Deposit Error: ' . $e->getMessage());
            return ['status' => false, 'deposit' => 0, 'message' => 'Koneksi error'];
        }
    }

    /**
     * 4. Transaksi Prabayar (Pulsa, Data, Token, E-Money)
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
            'max_price' => $maxPrice, // Proteksi harga naik tiba-tiba
            'testing' => true, // HAPUS ATAU SET FALSE JIKA LIVE
        ];

        try {
            $response = Http::post($this->baseUrl . '/transaction', $payload);
            return $response->json();
        } catch (\Exception $e) {
            Log::error('Digiflazz Transaction Error: ' . $e->getMessage());
            return ['data' => ['status' => 'Gagal', 'message' => 'Koneksi Error']];
        }
    }

    /**
     * 5. Cek Tagihan Pascabayar (Inquiry) - DENGAN LOGGING LENGKAP
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
            'testing' => true,
        ];

        try {
            // 1. Log Data yang Dikirim (Request)
            Log::info("➡️ [Digiflazz Inquiry Request] RefID: $refId", $payload);

            $response = Http::post($this->baseUrl . '/transaction', $payload);
            
            // 2. Log Data yang Diterima (Response)
            Log::info("⬅️ [Digiflazz Inquiry Response] RefID: $refId", $response->json() ?? []);

            return $response->json();

        } catch (\Exception $e) {
            // 3. Log Error Koneksi (Curl/Timeout)
            Log::error("❌ [Digiflazz Connection Error] RefID: $refId - " . $e->getMessage());
            
            return [
                'data' => [
                    'status' => 'Gagal', 
                    'message' => 'Koneksi Server Gagal: ' . $e->getMessage()
                ]
            ];
        }
    }

    /**
     * 6. Bayar Tagihan Pascabayar (Payment)
     * Eksekusi pembayaran setelah user setuju dengan hasil Inquiry
     */
    public function payPasca($sku, $customerNo, $refId)
    {
        $sign = md5($this->username . $this->apiKey . $refId);

        $payload = [
            'commands' => 'pay-pasca', // Command Wajib
            'username' => $this->username,
            'buyer_sku_code' => $sku,
            'customer_no' => $customerNo,
            'ref_id' => $refId,
            'sign' => $sign,
            'testing' => true, // HAPUS JIKA LIVE
        ];

        try {
            $response = Http::post($this->baseUrl . '/transaction', $payload);
            return $response->json();
        } catch (\Exception $e) {
            Log::error('Digiflazz Pay Pasca Error: ' . $e->getMessage());
            return ['data' => ['status' => 'Gagal', 'message' => 'Koneksi Error']];
        }
    }

    /**
     * 7. Cek Validasi ID PLN (Khusus Token Listrik / Prabayar)
     * Memastikan nomor meter valid dan menampilkan nama pelanggan
     */
    public function inquiryPln($customerNo)
    {
        // Endpoint Khusus /inquiry-pln
        // Signature = md5(username + apiKey + customer_no)
        $sign = md5($this->username . $this->apiKey . $customerNo);

        try {
            $response = Http::post($this->baseUrl . '/transaction', [
                'commands' => 'pln-subscribe', // Menggunakan command transaction umum jika endpoint khusus tidak aktif
                'username' => $this->username,
                'customer_no' => $customerNo,
                'sign' => $sign
            ]);
            
            // NOTE: Digiflazz biasanya menyarankan pakai API Transaksi dengan SKU 'PLN' untuk inquiry pasca, 
            // atau endpoint khusus /transaction dengan body tertentu untuk cek ID prepaid.
            // Jika endpoint /inquiry-pln tidak available, gunakan endpoint /transaction dengan payload yang sesuai.
            // Kode di bawah menggunakan endpoint /transaction standard untuk cek nama (biasanya via API pasca 'pln' juga bisa untuk cek nama).
            
            return $response->json();
        } catch (\Exception $e) {
            Log::error('Digiflazz Inquiry PLN Error: ' . $e->getMessage());
            return ['data' => ['status' => 'Gagal', 'message' => 'Koneksi Error']];
        }
    }
}