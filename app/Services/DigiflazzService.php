<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DigiflazzService
{
    protected $username;
    protected $apiKey;
    protected $baseUrl;

 public function __construct()
    {
        // --- UBAH BAGIAN INI (HARDCODE SEMENTARA) ---
        // Kita tulis langsung untuk memastikan tidak ada spasi/error dari .env
        
        $this->username = 'mihetiDVGdeW'; // Sesuai screenshot
        $this->apiKey   = 'dev-d54808c0-87ed-11f0-bdb6-8d5622821215'; // Sesuai screenshot
        $this->baseUrl  = 'https://api.digiflazz.com/v1'; 
        
        // --- KOMENTARI KODE LAMA ---
        // $this->username = env('DIGIFLAZZ_USERNAME');
        // $this->apiKey = env('DIGIFLAZZ_KEY');
        // $this->baseUrl = env('DIGIFLAZZ_MODE') === 'production' 
        //     ? 'https://api.digiflazz.com/v1' 
        //     : 'https://api.digiflazz.com/v1'; 
    }

    /**
     * Mengambil Daftar Harga (Price List)
     * $cmd bisa 'prepaid' (pulsa, data, token) atau 'postpaid' (tagihan)
     */
    public function getPriceList($cmd = 'prepaid')
    {
        // Signature Price List = md5(username + key + "depo")
        $sign = md5($this->username . $this->apiKey . "depo");

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
    }

   /**
     * Melakukan Transaksi (Top Up)
     * Tambahkan parameter $maxPrice untuk proteksi harga
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
            'max_price' => $maxPrice, // <--- FITUR PROTEKSI DIGIFLAZZ
            'testing' => true,
        ];

        try {
            $response = Http::post($this->baseUrl . '/transaction', $payload);
            return $response->json();
        } catch (\Exception $e) {
            Log::error('Digiflazz Transaction Error: ' . $e->getMessage());
            return [
                'data' => [
                    'status' => 'Gagal',
                    'message' => 'Koneksi Error: ' . $e->getMessage()
                ]
            ];
        }
    }
    

    /**
     * Cek Sisa Saldo Deposit Digiflazz
     * Dokumentasi: https://api.digiflazz.com/v1/cek-saldo
     */
    public function checkDeposit()
    {
        // Signature Check Saldo = md5(username + key + "depo")
        $sign = md5($this->username . $this->apiKey . "depo");

        try {
            $response = Http::post($this->baseUrl . '/cek-saldo', [
                'cmd' => 'deposit',
                'username' => $this->username,
                'sign' => $sign
            ]);

            $result = $response->json();

            // Cek apakah response sukses dan ada data deposit
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
            return [
                'status' => false,
                'deposit' => 0,
                'message' => 'Koneksi error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Cek Tagihan Pascabayar (Inquiry)
     * Digunakan untuk PLN Bulanan, PDAM, BPJS, dll
     */
    public function inquiryPasca($sku, $customerNo, $refId)
    {
        $sign = md5($this->username . $this->apiKey . $refId);

        $payload = [
            'commands' => 'inq-pasca', // Command khusus Inquiry
            'username' => $this->username,
            'buyer_sku_code' => $sku,
            'customer_no' => $customerNo,
            'ref_id' => $refId,
            'sign' => $sign,
            'testing' => true, // Ubah ke false jika production
        ];

        try {
            $response = Http::post($this->baseUrl . '/transaction', $payload);
            return $response->json();
        } catch (\Exception $e) {
            Log::error('Digiflazz Inquiry Error: ' . $e->getMessage());
            return ['data' => ['status' => 'Gagal', 'message' => 'Koneksi Error']];
        }
    }

    /**
     * Bayar Tagihan Pascabayar (Payment)
     * Eksekusi pembayaran setelah user setuju dengan hasil Inquiry
     */
    public function payPasca($sku, $customerNo, $refId)
    {
        $sign = md5($this->username . $this->apiKey . $refId);

        $payload = [
            'commands' => 'pay-pasca', // Command khusus Bayar
            'username' => $this->username,
            'buyer_sku_code' => $sku,
            'customer_no' => $customerNo,
            'ref_id' => $refId,
            'sign' => $sign,
            'testing' => true, // Ubah ke false jika production
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
     * Halaman Dinamis untuk Semua Kategori (Pulsa, Data, PLN, Games, E-Money)
     * URL: /digital/kategori/{slug}
     */
    public function category($slug)
    {
        $weblogo = $this->getWebLogo();

        // 1. Mapping Slug URL ke Kategori Database Digiflazz
        $categoriesMap = [
            'pulsa'       => ['Pulsa'],
            'data'        => ['Data'],
            'pln-token'   => ['PLN'],      // Token Listrik
            'e-money'     => ['E-Money'],  // OVO, DANA, dll
            'voucher-game'=> ['Games'],    // FreeFire, MLBB
            'streaming'   => ['TV', 'Streaming'],
        ];

        // Validasi Slug
        if (!array_key_exists($slug, $categoriesMap)) {
            abort(404);
        }

        $dbCategories = $categoriesMap[$slug];

        // 2. Judul & Placeholder Input berdasarkan Kategori
        $pageInfo = [
            'title'       => ucfirst(str_replace('-', ' ', $slug)),
            'slug'        => $slug,
            'input_label' => 'Nomor Handphone',
            'input_place' => 'Contoh: 0812xxxx',
            'icon'        => 'fa-mobile-alt'
        ];

        // Custom Label untuk Non-Pulsa
        if ($slug == 'pln-token') {
            $pageInfo['input_label'] = 'Nomor Meter / ID Pelanggan';
            $pageInfo['input_place'] = 'Contoh: 141234567890';
            $pageInfo['icon']        = 'fa-bolt';
        } elseif ($slug == 'e-money') {
            $pageInfo['icon']        = 'fa-wallet';
        } elseif ($slug == 'voucher-game') {
            $pageInfo['input_label'] = 'ID Pemain (User ID)';
            $pageInfo['input_place'] = 'Masukkan ID Game';
            $pageInfo['icon']        = 'fa-gamepad';
        }

        // 3. Ambil Produk dari Database
        $products = PpobProduct::whereIn('category', $dbCategories)
            ->where('buyer_product_status', true)
            ->where('seller_product_status', true)
            ->orderBy('price', 'asc')
            ->get();

        // 4. Kelompokkan Brand (Agar rapi: Telkomsel, XL / DANA, OVO / MLBB, FF)
        $brands = $products->pluck('brand')->unique()->values();

        return view('ppob.category', compact('products', 'brands', 'weblogo', 'pageInfo'));
    }

}