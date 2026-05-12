<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use App\Models\Api;

class BaseController extends Controller
{
    protected $darmawisataBaseUrl;

    public function __construct()
    {
        // Ambil Base URL dari DB agar bisa dipakai semua Controller yang extends class ini
        $mode = Api::getValue('DHARMAWISATA_MODE', 'global', 'development');
        $this->darmawisataBaseUrl = Api::getValue('DHARMAWISATA_BASE_URL', $mode);
    }

    public function forwardRequest($endpoint, $payload)
{
    $url = rtrim($this->darmawisataBaseUrl, '/') . '/' . ltrim($endpoint, '/');

    // --- TAMBAHKAN KODE DEBUG DI SINI ---
    dd([
        'KETERANGAN' => 'DEBUG PAYLOAD SEBELUM DIKIRIM',
        'URL_TARGET' => $url,
        'HEADERS'    => [
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ],
        'PAYLOAD_JSON' => $payload, // Data yang dikirim
        'PAYLOAD_RAW'  => json_encode($payload) // Bentuk string JSON mentahnya
    ]);
    // ------------------------------------

    // Kode di bawah ini tidak akan jalan selama dd() di atas masih ada
    $response = \Illuminate\Support\Facades\Http::withHeaders([
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
    ])->withoutVerifying()->post($url, $payload);

    return response()->json($response->json(), $response->status());
}
}
