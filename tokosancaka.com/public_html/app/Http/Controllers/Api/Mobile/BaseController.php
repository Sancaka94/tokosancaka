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
    protected $darmawisataToken;

    public function __construct()
    {
        // 1. Ambil konfigurasi statis (Boleh pakai Cache)
        $this->darmawisataMode    = Api::getValue('DHARMAWISATA_MODE', 'global', 'development');
        $this->darmawisataBaseUrl = Api::getValue('DHARMAWISATA_BASE_URL', $this->darmawisataMode);
        $this->darmawisataUserId  = Api::getValue('DHARMAWISATA_USER_ID', $this->darmawisataMode);

        // 2. KHUSUS TOKEN: Bypass Cache! Ambil langsung secara real-time dari Database
        $tokenData = Api::where('key', 'DHARMAWISATA_ACCESS_TOKEN')
                        ->where('group', $this->darmawisataMode)
                        ->first();
        $this->darmawisataToken = $tokenData ? $tokenData->value : '';
    }

    /**
     * Helper untuk meneruskan request ke API Darmawisata dengan Auto-Reconnect
     */
    public function forwardRequest($endpoint, $payload = [], $isRetry = false)
    {
        if ($endpoint !== 'Session/Login') {
            $payload['userID']      = $this->darmawisataUserId;
            $payload['accessToken'] = $this->darmawisataToken;
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

            // Parsing XML fallback
            if (empty($data) && (str_contains($body, '<?xml') || str_contains($body, '<AuthResponse'))) {
                Log::info("LOG: Deteksi respon XML, melakukan konversi ke JSON...");
                $xml = simplexml_load_string($body);
                $data = json_decode(json_encode($xml), true);
            }

            if (empty($data)) {
                Log::error("LOG ERROR: Format respon server tidak dikenali (Bukan JSON/XML)");
                return response()->json([
                    'status'   => 'FAILED',
                    'message'  => 'Format respon server tidak dikenali',
                    'raw_body' => $body
                ], $response->status());
            }

            // =========================================================================
            // SISTEM AUTO-RECONNECT (DETEKSI TOKEN EXPIRED)
            // =========================================================================
            $isAuthFailed = isset($data['status']) && $data['status'] === 'FAILED'
                            && isset($data['respMessage'])
                            && stripos($data['respMessage'], 'member authentication failed') !== false;

            if ($isAuthFailed && !$isRetry && $endpoint !== 'Session/Login') {
                Log::warning("LOG WARNING: Token Darmawisata Expired / Ditolak. Memulai proses Auto-Reconnect...");

                $newToken = $this->autoReconnect();

                if ($newToken) {
                    $this->darmawisataToken = $newToken;
                    $payload['accessToken'] = $newToken;

                    Log::info("LOG SUCCESS: Auto-Reconnect berhasil. Mengulangi request ke: {$endpoint}");
                    return $this->forwardRequest($endpoint, $payload, true);
                } else {
                    Log::error("LOG ERROR: Auto-Reconnect gagal. Menghentikan request.");
                }
            }

            return response()->json($data, $response->status());

        } catch (\Exception $e) {
            Log::error("\n==================== [DARMAWISATA FATAL ERROR] ====================");
            Log::error("ENDPOINT : {$endpoint}");
            Log::error("MESSAGE  : " . $e->getMessage());
            Log::error("===================================================================\n");

            return response()->json([
                'status'  => 'FAILED',
                'message' => 'Gagal terhubung ke server Darmawisata: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fungsi rahasia untuk melakukan login ulang di belakang layar
     */
    protected function autoReconnect()
    {
        Log::info("LOG: Mengambil kredensial dari database untuk Auto-Reconnect...");

        $staticToken = Api::getValue('DHARMAWISATA_STATIC_TOKEN', $this->darmawisataMode);
        $password    = Api::getValue('DHARMAWISATA_PASSWORD', $this->darmawisataMode);

        if (!$staticToken || !$password) {
            Log::error("LOG ERROR: Auto-Reconnect Gagal. Kredensial (Static Token / Password) tidak ditemukan di Database.");
            return false;
        }

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

        Log::info("LOG: Menembak API Session/Login...");

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ])->withoutVerifying()->post($url, $payload);

            $data = $response->json();

            if (isset($data['status']) && $data['status'] === 'SUCCESS' && isset($data['accessToken'])) {
                $newAccessToken = $data['accessToken'];

                Log::info("LOG SUCCESS: Mendapatkan Token Baru: " . substr($newAccessToken, 0, 10) . "...");

                // Simpan token baru ke Database (Cara ini lebih aman untuk mem-bypass error cache)
                $apiRecord = Api::firstOrNew([
                    'key'   => 'DHARMAWISATA_ACCESS_TOKEN',
                    'group' => $this->darmawisataMode
                ]);
                $apiRecord->value = $newAccessToken;
                $apiRecord->save();

                Log::info("LOG SUCCESS: Token baru berhasil disimpan ke Database permanen.");

                return $newAccessToken;
            }

            Log::error("LOG ERROR: Respon Login Gagal. Pesan dari Darmawisata: " . json_encode($data));
            return false;

        } catch (\Exception $e) {
            Log::error("LOG ERROR: Koneksi ke Session/Login terputus. Pesan: " . $e->getMessage());
            return false;
        }
    }
}
