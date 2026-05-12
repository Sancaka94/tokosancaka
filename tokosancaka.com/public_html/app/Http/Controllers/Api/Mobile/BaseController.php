<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Api;

class BaseController extends Controller
{
    protected $darmawisataBaseUrl;

    public function __construct()
    {
        // 1. Ambil mode (development/production)
        $mode = Api::getValue('DHARMAWISATA_MODE', 'global', 'development');

        // 2. Ambil Base URL dari Database
        $this->darmawisataBaseUrl = Api::getValue('DHARMAWISATA_BASE_URL', $mode);
    }

    /**
     * Helper untuk meneruskan request ke API Darmawisata
     */
    public function forwardRequest($endpoint, $payload)
    {
        // Bersihkan URL agar tidak ada double slash
        $url = rtrim($this->darmawisataBaseUrl, '/') . '/' . ltrim($endpoint, '/');

        try {
            // Eksekusi Request
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ])
            ->withoutVerifying() // Penting untuk port 7080 server UAT agar tidak error SSL
            ->post($url, $payload);

            // Ambil body respon mentah
            $body = $response->body();

            // 1. Coba parsing sebagai JSON
            $data = $response->json();

            // 2. Jika JSON gagal (Darmawisata sering kirim XML), parsing sebagai XML
            if (empty($data)) {
                if (str_contains($body, '<?xml') || str_contains($body, '<AuthResponse')) {
                    $xml = simplexml_load_string($body);
                    $data = json_decode(json_encode($xml), true);
                }
            }

            // 3. Jika tetap kosong, kembalikan body mentah untuk debug di frontend
            if (empty($data)) {
                return response()->json([
                    'status'   => 'FAILED',
                    'message'  => 'Format respon server tidak dikenali (Bukan JSON/XML)',
                    'raw_body' => $body
                ], $response->status());
            }

            return response()->json($data, $response->status());

        } catch (\Exception $e) {
            // Log error jika terjadi kegagalan koneksi
            Log::error("Darmawisata API Error: " . $e->getMessage());

            return response()->json([
                'status'  => 'FAILED',
                'message' => 'Gagal terhubung ke server Darmawisata: ' . $e->getMessage()
            ], 500);
        }
    }
}
