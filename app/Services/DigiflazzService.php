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
        $this->username = env('DIGIFLAZZ_USERNAME');
        $this->apiKey = env('DIGIFLAZZ_KEY');
        // Gunakan URL development jika testing, production jika live
        $this->baseUrl = env('DIGIFLAZZ_MODE') === 'production' 
            ? 'https://api.digiflazz.com/v1' 
            : 'https://api.digiflazz.com/v1'; 
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
     */
    public function transaction($sku, $customerNo, $refId)
    {
        // Signature Transaksi = md5(username + key + ref_id)
        $sign = md5($this->username . $this->apiKey . $refId);

        $payload = [
            'username' => $this->username,
            'buyer_sku_code' => $sku,
            'customer_no' => $customerNo,
            'ref_id' => $refId,
            'sign' => $sign,
        ];

        $response = Http::post($this->baseUrl . '/transaction', $payload);

        return $response->json();
    }
    
    /**
     * Cek Saldo Digiflazz
     */
    public function checkDeposit()
    {
        $sign = md5($this->username . $this->apiKey . "depo");
        
        $response = Http::post($this->baseUrl . '/ceksaldo', [
            'cmd' => 'deposit',
            'username' => $this->username,
            'sign' => $sign
        ]);
        
        return $response->json();
    }
}