<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class DanaGatewayController extends Controller
{
    /**
     * KONFIGURASI URL CALLBACK
     * URL ini harus SAMA PERSIS dengan yang didaftarkan di Dashboard DANA Developer.
     * Tidak boleh beda satu karakter pun (termasuk http/https).
     */
    private const FIXED_CALLBACK_URL = 'https://apps.tokosancaka.com/dana/callback';

    /**
     * MAIN HANDLER: MENERIMA SEMUA TAMU DARI DANA
     */
    public function handleCallback(Request $request)
    {
        // 1. Ambil Parameter dari DANA
        $authCode = $request->input('auth_code'); // Untuk Binding
        $status   = $request->input('resultStatus'); // Untuk Payment Redirect
        $state    = $request->input('state'); // <--- KTP/IDENTITAS USER

        Log::info("[DANA GATEWAY] Hit Masuk.", [
            'ip' => $request->ip(),
            'state' => $state,
            'code' => $authCode ? 'YES' : 'NO'
        ]);

        // 2. Validasi State
        if (empty($state)) {
            return redirect('/')->with('error', 'Invalid Request: No State Identifier');
        }

        // 3. Bedah State (Parsing)
        // Format yang kita sepakati: ACTION-USERID-SUBDOMAIN-TENANTID
        // Contoh: BIND_TENANT-5-bakso-2
        $parts = explode('-', $state);

        if (count($parts) < 4) {
            Log::error("[DANA GATEWAY] Format State Salah: $state");
            return redirect('/')->with('error', 'Sesi Kadaluarsa atau Format Salah');
        }

        $action    = $parts[0]; // BIND_TENANT, BIND_MEMBER, PAY
        $userId    = $parts[1]; // User ID atau Affiliate ID
        $subdomain = $parts[2]; // Subdomain asal (misal: 'bakso')
        $tenantId  = $parts[3]; // ID Tenant

        // 4. Bangun URL Pulang (Smart Redirect Base)
        $scheme = $request->secure() ? 'https://' : 'http://';
        $appDomain = env('APP_URL_DOMAIN', 'tokosancaka.com');

        // Base URL: https://bakso.tokosancaka.com
        $tenantBaseUrl = $scheme . $subdomain . '.' . $appDomain;

        // 5. Switch Logic Berdasarkan ACTION
        switch ($action) {
            case 'BIND_TENANT':
                return $this->handleBinding($authCode, $userId, 'TENANT', $tenantBaseUrl);

            case 'BIND_MEMBER':
                return $this->handleBinding($authCode, $userId, 'MEMBER', $tenantBaseUrl);

            case 'PAY':
                // Logic jika DANA me-redirect setelah bayar (Acquiring)
                return $this->handlePaymentRedirect($status, $tenantBaseUrl);

            default:
                Log::warning("[DANA GATEWAY] Unknown Action: $action");
                return redirect($tenantBaseUrl)->with('error', 'Aksi tidak dikenali.');
        }
    }

    /**
     * LOGIC: HANDLE BINDING (SAMBUNG AKUN)
     * Tukar Auth Code -> Access Token -> Simpan ke DB -> Redirect
     */
    private function handleBinding($authCode, $userId, $userType, $baseUrl)
    {
        if (!$authCode) {
            // User menolak binding / klik cancel
            return redirect($baseUrl . '/dashboard?dana_status=cancelled')->with('error', 'Koneksi DANA dibatalkan.');
        }

        // A. Tukar Token ke API DANA
        $tokenResult = $this->exchangeDanaToken($authCode);

        if (!$tokenResult['success']) {
            Log::error("[DANA GATEWAY] Gagal Tukar Token User $userId: " . $tokenResult['message']);
            return redirect($baseUrl . '/dashboard?dana_status=failed')->with('error', 'Gagal menghubungkan DANA: ' . $tokenResult['message']);
        }

        $accessToken = $tokenResult['data']['accessToken'];
        $expiry      = $tokenResult['data']['expiresIn'] ?? null; // Detik

        // B. Simpan ke Database yang Sesuai
        try {
            if ($userType === 'TENANT') {
                // Update tabel USERS (Admin Toko)
                DB::table('users')->where('id', $userId)->update([
                    'dana_access_token' => $accessToken,
                    'dana_token_expiry' => $expiry, // Opsional simpan expiry
                    'updated_at'        => now()
                ]);
                Log::info("[DANA GATEWAY] ✅ Token Saved for TENANT User $userId");
                $redirectPath = '/dashboard';

            } else {
                // Update tabel AFFILIATES (Member)
                DB::table('affiliates')->where('id', $userId)->update([
                    'dana_access_token' => $accessToken,
                    // 'dana_token_expiry' => $expiry, // Jika ada kolomnya
                    'updated_at'        => now()
                ]);
                Log::info("[DANA GATEWAY] ✅ Token Saved for MEMBER User $userId");
                $redirectPath = '/member/dashboard';
            }

            // C. Redirect Sukses
            // Kita kirim parameter query string agar frontend di subdomain bisa menangkap notifikasi
            return redirect($baseUrl . $redirectPath . '?dana_status=success&msg=' . urlencode('Akun DANA Berhasil Terhubung!'));

        } catch (\Exception $e) {
            Log::error("[DANA GATEWAY] DB Error: " . $e->getMessage());
            return redirect($baseUrl . '/dashboard?dana_status=error')->with('error', 'Database Error.');
        }
    }

    /**
     * LOGIC: HANDLE REDIRECT SETELAH BAYAR
     */
    private function handlePaymentRedirect($status, $baseUrl)
    {
        // Status dari DANA biasanya: SUCCESS, PENDING, FAILED
        // Kita kembalikan user ke dashboard/history
        if ($status == 'SUCCESS') {
            return redirect($baseUrl . '/dashboard?payment_status=success')->with('success', 'Pembayaran Berhasil!');
        } elseif ($status == 'PENDING') {
            return redirect($baseUrl . '/dashboard?payment_status=pending')->with('warning', 'Pembayaran sedang diproses.');
        } else {
            return redirect($baseUrl . '/dashboard?payment_status=failed')->with('error', 'Pembayaran Gagal.');
        }
    }

    /**
     * HELPER: TUKAR AUTH CODE JADI ACCESS TOKEN
     * API: /v1.0/access-token/b2b2c.htm
     */
    private function exchangeDanaToken($authCode)
    {
        try {
            $timestamp  = now('Asia/Jakarta')->toIso8601String();
            $clientId   = config('services.dana.x_partner_id');
            $externalId = (string) time(); // Unique Request ID

            // Signature String: clientId + "|" + timestamp (Sesuai Dokumen B2B2C)
            $stringToSign = $clientId . "|" . $timestamp;
            $signature    = $this->generateSignature($stringToSign);

            $body = [
                'grantType' => 'authorization_code',
                'authCode'  => $authCode,
            ];

            // Request ke DANA Sandbox / Production
            // Gunakan URL yang sesuai env
            $apiUrl = 'https://api.sandbox.dana.id/v1.0/access-token/b2b2c.htm';

            $response = Http::withHeaders([
                'X-TIMESTAMP'   => $timestamp,
                'X-SIGNATURE'   => $signature,
                'X-PARTNER-ID'  => $clientId,
                'X-CLIENT-KEY'  => $clientId, // Kadang butuh ini
                'X-EXTERNAL-ID' => $externalId,
                'Content-Type'  => 'application/json'
            ])->post($apiUrl, $body);

            $result = $response->json();

            // Kode Sukses DANA
            $successCodes = ['2001100', '2007400', '2000000'];

            if (isset($result['responseCode']) && in_array($result['responseCode'], $successCodes)) {
                return ['success' => true, 'data' => $result];
            }

            Log::error("[DANA API] Token Exchange Failed: " . json_encode($result));
            return [
                'success' => false,
                'message' => $result['responseMessage'] ?? 'Unknown API Error'
            ];

        } catch (\Exception $e) {
            Log::error("[DANA API] Exception: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * HELPER: GENERATE RSA-SHA256 SIGNATURE
     */
    private function generateSignature($stringToSign)
    {
        $privateKeyContent = config('services.dana.private_key');

        // Bersihkan Key dari spasi/enter yang mungkin berantakan
        $cleanKey = preg_replace('/-{5}(BEGIN|END) PRIVATE KEY-{5}|\r|\n|\s/', '', $privateKeyContent);

        // Format ulang ke PEM standard
        $formattedKey = "-----BEGIN PRIVATE KEY-----\n" . chunk_split($cleanKey, 64, "\n") . "-----END PRIVATE KEY-----";

        $binarySignature = "";

        // Sign menggunakan OpenSSL SHA256
        if (!openssl_sign($stringToSign, $binarySignature, $formattedKey, OPENSSL_ALGO_SHA256)) {
            Log::error("[DANA SIG] OpenSSL Error: " . openssl_error_string());
            return null;
        }

        return base64_encode($binarySignature);
    }
}
