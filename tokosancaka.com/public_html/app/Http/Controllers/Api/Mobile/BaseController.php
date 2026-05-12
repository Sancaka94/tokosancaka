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
        // 1. Ambil konfigurasi dasar dari Database
        $this->darmawisataMode    = Api::getValue('DHARMAWISATA_MODE', 'global', 'development');
        $this->darmawisataBaseUrl = Api::getValue('DHARMAWISATA_BASE_URL', $this->darmawisataMode);
        $this->darmawisataUserId  = Api::getValue('DHARMAWISATA_USER_ID', $this->darmawisataMode);
        $this->darmawisataToken   = Api::getValue('DHARMAWISATA_ACCESS_TOKEN', $this->darmawisataMode);
    }

    /**
     * Helper untuk meneruskan request ke API Darmawisata dengan Auto-Reconnect
     * * @param string $endpoint Rute API Darmawisata (Contoh: 'Airline/Search')
     * @param array $payload Data payload request
     * @param boolean $isRetry Flag untuk mencegah infinite loop jika login terus gagal
     */
    public function forwardRequest($endpoint, $payload = [], $isRetry = false)
    {
        // 1. INJEKSI KREDENSIAL OTOMATIS
        // Jangan inject jika endpoint-nya adalah Session/Login (karena belum punya token)
        if ($endpoint !== 'Session/Login') {
            $payload['userID']      = $this->darmawisataUserId;
            $payload['accessToken'] = $this->darmawisataToken;
        }

        // 2. Bersihkan URL
        $url = rtrim($this->darmawisataBaseUrl, '/') . '/' . ltrim($endpoint, '/');

        try {
            // 3. Eksekusi Request
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ])
            ->withoutVerifying() // Penting untuk port 7080 server UAT
            ->post($url, $payload);

            $body = $response->body();
            $data = $response->json();

            // 4. Parsing XML jika Darmawisata tidak membalas dengan JSON
            if (empty($data) && (str_contains($body, '<?xml') || str_contains($body, '<AuthResponse'))) {
                $xml = simplexml_load_string($body);
                $data = json_decode(json_encode($xml), true);
            }

            // Jika format respon benar-benar hancur
            if (empty($data)) {
                return response()->json([
                    'status'   => 'FAILED',
                    'message'  => 'Format respon server tidak dikenali (Bukan JSON/XML)',
                    'raw_body' => $body
                ], $response->status());
            }

            // =========================================================================
            // 5. SISTEM AUTO-RECONNECT (DETEKSI TOKEN EXPIRED)
            // =========================================================================
            $isAuthFailed = isset($data['status']) && $data['status'] === 'FAILED'
                            && isset($data['respMessage'])
                            && stripos($data['respMessage'], 'member authentication failed') !== false;

            // Jika token mati, dan ini bukan proses retry, dan bukan sedang mencoba login
            if ($isAuthFailed && !$isRetry && $endpoint !== 'Session/Login') {
                Log::warning("Token Darmawisata Expired. Memulai proses Auto-Reconnect...");

                $newToken = $this->autoReconnect();

                if ($newToken) {
                    // Update property dengan token baru
                    $this->darmawisataToken = $newToken;
                    // Update payload dengan token baru
                    $payload['accessToken'] = $newToken;

                    Log::info("Auto-Reconnect berhasil. Mengulangi request ke: {$endpoint}");

                    // Rekursif: Ulangi request (set $isRetry = true agar tidak looping tanpa batas)
                    return $this->forwardRequest($endpoint, $payload, true);
                }
            }

            // Kembalikan respon normal ke aplikasi mobile
            return response()->json($data, $response->status());

        } catch (\Exception $e) {
            Log::error("Darmawisata API Error pada endpoint {$endpoint}: " . $e->getMessage());

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
        // Tarik data statis dari DB untuk meracik Security Code
        $staticToken = Api::getValue('DHARMAWISATA_STATIC_TOKEN', $this->darmawisataMode);
        $password    = Api::getValue('DHARMAWISATA_PASSWORD', $this->darmawisataMode);

        if (!$staticToken || !$password) {
            Log::error("Auto-Reconnect Gagal: DHARMAWISATA_STATIC_TOKEN atau DHARMAWISATA_PASSWORD tidak ditemukan di Database.");
            return false;
        }

        // Terapkan Rumus Enkripsi Darmawisata
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

            if (isset($data['status']) && $data['status'] === 'SUCCESS' && isset($data['accessToken'])) {
                $newAccessToken = $data['accessToken'];

                // Simpan token baru ke Database secara permanen agar request selanjutnya aman
                Api::updateOrCreate(
                    ['key' => 'DHARMAWISATA_ACCESS_TOKEN', 'group' => $this->darmawisataMode],
                    ['value' => $newAccessToken]
                );

                return $newAccessToken;
            }

            Log::error("Auto-Reconnect Gagal (Respon Login Error): " . json_encode($data));
            return false;

        } catch (\Exception $e) {
            Log::error("Auto-Reconnect Error Koneksi: " . $e->getMessage());
            return false;
        }
    }
}
