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

        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post($url, $payload);

        // TAMBAHKAN INI UNTUK DEBUG
        if ($response->failed() || empty($response->json())) {
            dd([
                'url' => $url,
                'payload' => $payload,
                'status_code' => $response->status(),
                'raw_body' => $response->body(), // Lihat teks asli dari server
            ]);
        }

        return response()->json($response->json(), $response->status());
    }
}
