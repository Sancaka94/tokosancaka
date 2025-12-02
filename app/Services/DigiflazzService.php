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

}