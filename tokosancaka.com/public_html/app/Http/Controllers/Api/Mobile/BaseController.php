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

    try {
        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post($url, $payload);

        // 1. Coba ambil sebagai JSON
        $data = $response->json();

        // 2. Jika JSON kosong, cek apakah isinya XML
        if (empty($data)) {
            $body = $response->body();
            if (str_contains($body, '<?xml') || str_contains($body, '<AuthResponse')) {
                // Konversi XML ke Array
                $xml = simplexml_load_string($body);
                $data = json_decode(json_encode($xml), true);
            }
        }

        // 3. Jika masih kosong juga, tampilkan respon mentah untuk debug
        if (empty($data)) {
            return response()->json([
                'status' => 'FAILED',
                'message' => 'Respon kosong dari server Darmawisata',
                'raw_body' => $response->body()
            ], $response->status());
        }

        return response()->json($data, $response->status());

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'FAILED',
            'message' => 'Exception: ' . $e->getMessage()
        ], 500);
    }
}
}
