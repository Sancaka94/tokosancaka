<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DanaRetryInquiry extends Command
{
    /**
     * Nama dan tanda tangan dari console command.
     */
    protected $signature = 'dana:retry-inquiry';

    /**
     * Deskripsi perintah.
     */
    protected $description = 'Otomatis melakukan inquiry status ke DANA untuk transaksi PENDING sesuai aturan SNAP (Max 5x)';

    /**
     * Eksekusi console command.
     */
    public function handle()
    {
        // 1. Ambil transaksi yang statusnya PENDING atau FAILED (karena timeout) 
        // dan jumlah retry masih di bawah 5 kali
        $pendingTransactions = DB::table('dana_transactions')
            ->whereIn('status', ['PENDING', 'FAILED'])
            ->where('retry_count', '<', 5)
            ->where('type', 'TOPUP')
            ->get();

        if ($pendingTransactions->isEmpty()) {
            return;
        }

        foreach ($pendingTransactions as $trx) {
            $this->executeInquiry($trx);
        }
    }

    /**
     * Logika utama inquiry status ke API DANA
     */
    private function executeInquiry($trx)
    {
        // --- [LOG 1] START RETRY ATTEMPT ---
        $attempt = $trx->retry_count + 1;
        Log::info("[DANA AUTO-RETRY] Mencoba Inquiry Trx: {$trx->reference_no} (Attempt: {$attempt})");

        $aff = DB::table('affiliates')->where('id', $trx->affiliate_id)->first();
        if (!$aff || !$aff->dana_access_token) {
            Log::warning("[DANA AUTO-RETRY] Skip: Token Affiliate ID {$trx->affiliate_id} tidak ditemukan.");
            return;
        }

        $timestamp = now('Asia/Jakarta')->toIso8601String();
        $path = '/v1.0/emoney/topup-status.htm';

        // --- [BODY] SESUAI DOKUMEN SNAP ---
        $body = [
            "originalPartnerReferenceNo" => $trx->reference_no, // Required
            "originalReferenceNo"        => "", // Opsional
            "originalExternalId"         => "", // Opsional
            "serviceCode"                => "38", // Wajib "38" untuk Topup
            "additionalInfo"             => (object)[]
        ];

        // --- [SECURITY] GENERATE SIGNATURE ---
        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES);
        $hashedBody = strtolower(hash('sha256', $jsonBody));
        $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;
        
        // Asumsi fungsi generateSignature ada di service atau kita panggil manual
        $signature = $this->generateSignature($stringToSign);

        $headers = [
            'Content-Type'   => 'application/json',
            'Authorization'  => 'Bearer ' . $aff->dana_access_token,
            'X-TIMESTAMP'    => $timestamp,
            'X-SIGNATURE'    => $signature,
            'X-PARTNER-ID'   => config('services.dana.x_partner_id'),
            'X-EXTERNAL-ID'  => (string) time() . Str::random(6),
            'CHANNEL-ID'     => '95221'
        ];

        try {
            $baseUrl = config('services.dana.dana_env') === 'PRODUCTION' 
                       ? 'https://api.dana.id' 
                       : 'https://api.sandbox.dana.id';

            $response = Http::withHeaders($headers)->withBody($jsonBody, 'application/json')->post($baseUrl . $path);
            $result = $response->json();

            // --- [LOG 2] RESPONSE DANA ---
            Log::info("[DANA AUTO-RETRY] Response API:", ['res' => $result]);

            if (isset($result['responseCode']) && $result['responseCode'] == '2003900') {
                $status = $result['latestTransactionStatus']; // 00-07

                if ($status == '00') {
                    // SUCCESS
                    DB::table('dana_transactions')->where('id', $trx->id)->update([
                        'status' => 'SUCCESS',
                        'response_payload' => json_encode($result)
                    ]);
                    
                    // Potong saldo jika sebelumnya gagal memotong (karena pending/timeout)
                    // Logika ini menjaga agar saldo tidak terpotong 2x
                    Log::info("[DANA AUTO-RETRY] Transaksi BERHASIL disinkronkan.");
                    
                } elseif (in_array($status, ['04', '05', '06', '07'])) {
                    // FAILED/CANCELLED
                    DB::table('dana_transactions')->where('id', $trx->id)->update([
                        'status' => 'FAILED',
                        'response_payload' => json_encode($result)
                    ]);
                    Log::error("[DANA AUTO-RETRY] Transaksi GAGAL dikonfirmasi.");
                } else {
                    // Masih Pending (01, 02, 03), naikkan count retry
                    DB::table('dana_transactions')->where('id', $trx->id)->increment('retry_count');
                }
            } else {
                // Error API (4XX / 5XX), naikkan count retry sesuai aturan
                DB::table('dana_transactions')->where('id', $trx->id)->increment('retry_count');
            }

        } catch (\Exception $e) {
            Log::error("[DANA AUTO-RETRY] Error Sistem: " . $e->getMessage());
            DB::table('dana_transactions')->where('id', $trx->id)->increment('retry_count');
        }
    }

    /**
     * Helper Signature (Pastikan private key tersedia di config)
     */
    private function generateSignature($stringToSign) {
        $privateKey = config('services.dana.private_key');
        $binarySignature = "";
        openssl_sign($stringToSign, $binarySignature, $privateKey, OPENSSL_ALGO_SHA256);
        return base64_encode($binarySignature);
    }
}
