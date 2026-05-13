<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Api;

class BaseController extends Controller
{
    protected $darmawisataMode;
    protected $darmawisataBaseUrl;
    protected $darmawisataUserId;

    public function __construct()
    {
        // 1. Ambil konfigurasi statis (Boleh pakai Cache)
        $this->darmawisataMode    = Api::getValue('DHARMAWISATA_MODE', 'global', 'development');
        $this->darmawisataBaseUrl = Api::getValue('DHARMAWISATA_BASE_URL', $this->darmawisataMode);
        $this->darmawisataUserId  = Api::getValue('DHARMAWISATA_USER_ID', $this->darmawisataMode);

        // KITA HAPUS LOGIKA PENGAMBILAN TOKEN GLOBAL DARI DATABASE DI SINI
    }

    /**
     * Helper untuk meneruskan request ke API Darmawisata
     */
    public function forwardRequest($endpoint, $payload = [])
    {
        // Tetap pastikan userID disuntikkan dari backend demi keamanan
        if ($endpoint !== 'Session/Login') {
            $payload['userID'] = $this->darmawisataUserId;

            // accessToken TIDAK LAGI DITIMPA.
            // Kita asumsikan $payload['accessToken'] sudah dikirim dari Mobile App
            if (!isset($payload['accessToken']) || empty($payload['accessToken'])) {
                return response()->json([
                    'status'  => 'FAILED',
                    'message' => 'Access Token Darmawisata tidak ditemukan dalam request.'
                ], 400);
            }
        }

        $url = rtrim($this->darmawisataBaseUrl, '/') . '/' . ltrim($endpoint, '/');

        // --- LOG REQUEST ---
        Log::info("\n==================== [DARMAWISATA REQUEST] ====================");
        Log::info("ENDPOINT : POST " . $url);
        Log::info("PAYLOAD  : " . json_encode($payload));
        // -------------------

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ])
            ->withoutVerifying()
            ->post($url, $payload);

            $body = $response->body();
            $data = $response->json();

            // --- LOG RESPONSE ---
            Log::info("STATUS   : " . $response->status());
            Log::info("RESPONSE : " . $body);
            Log::info("===============================================================\n");
            // --------------------

            // Fallback XML
            if (empty($data) && (str_contains($body, '<?xml') || str_contains($body, '<AuthResponse'))) {
                $xml = simplexml_load_string($body);
                $data = json_decode(json_encode($xml), true);
            }

            if (empty($data)) {
                return response()->json([
                    'status'   => 'FAILED',
                    'message'  => 'Format respon server tidak dikenali',
                    'raw_body' => $body
                ], $response->status());
            }

            // CATATAN: Fitur Auto-Reconnect dihapus dari proses ini.
            // Jika token expired di tengah jalan, idealnya user diminta mengulangi pencarian dari awal
            // karena flow "schedule -> price" di Darmawisata sudah hangus jika token mati.

            return response()->json($data, $response->status());

        } catch (\Exception $e) {
            Log::error("DARMAWISATA FATAL ERROR: " . $e->getMessage());
            return response()->json([
                'status'  => 'FAILED',
                'message' => 'Gagal terhubung ke server Darmawisata.'
            ], 500);
        }
    }

    /**
     * Endpoint BARU untuk di-hit oleh Mobile App guna mendapatkan Token baru
     * setiap kali user mulai mencari tiket.
     */
    public function generateNewToken()
    {
        $staticToken = Api::getValue('DHARMAWISATA_STATIC_TOKEN', $this->darmawisataMode);
        $password    = Api::getValue('DHARMAWISATA_PASSWORD', $this->darmawisataMode);

        $md5Password  = md5($password);
        $securityCode = md5($staticToken . $md5Password);

        $payload = [
            'token'        => $staticToken,
            'securityCode' => $securityCode,
            'language'     => "ID",
            'userID'       => $this->darmawisataUserId,
            'accessToken'  => ""
        ];

        $url = rtrim($this->darmawisataBaseUrl, '/') . '/Session/Login';

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ])->withoutVerifying()->post($url, $payload);

            $data = $response->json();

            if (isset($data['status']) && $data['status'] === 'SUCCESS') {
                return response()->json([
                    'status' => 'SUCCESS',
                    'message' => 'Token generated successfully',
                    'accessToken' => $data['accessToken'] // Berikan ini ke Mobile App!
                ]);
            }

            return response()->json(['status' => 'FAILED', 'message' => 'Gagal generate token dari Darmawisata'], 400);

        } catch (\Exception $e) {
            return response()->json(['status' => 'FAILED', 'message' => $e->getMessage()], 500);
        }
    }
}
