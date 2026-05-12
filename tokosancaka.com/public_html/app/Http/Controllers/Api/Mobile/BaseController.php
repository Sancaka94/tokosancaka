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
    // Cek apakah BaseUrl sudah terisi atau belum
    if (empty($this->darmawisataBaseUrl)) {
        return response()->json([
            'status' => 'FAILED',
            'message' => 'Konfigurasi Base URL Darmawisata tidak ditemukan di database.'
        ], 500);
    }

    // Gabungkan URL: Pastikan tidak ada double slash atau missing slash
    $url = rtrim($this->darmawisataBaseUrl, '/') . '/' . ltrim($endpoint, '/');

    try {
        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post($url, $payload);

        return response()->json($response->json(), $response->status());
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'FAILED',
            'message' => 'Koneksi ke server gagal: ' . $e->getMessage()
        ], 500);
    }
}
}
