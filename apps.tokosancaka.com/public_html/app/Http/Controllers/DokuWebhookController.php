<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\App; // <-- PERBAIKAN: Import 'App' facade
use Illuminate\Support\Str;
use App\Models\Store; // <-- PERBAIKAN: Import Model Store
use App\Models\Tenant; // Tambahkan Model Tenant

// Import Controller yang akan memproses pesanan
use App\Http\Controllers\Admin\PesananController as AdminPesananController;
use App\Http\Controllers\CustomerOrderController;
use App\Http\Controllers\Customer\TopUpController;
use App\Http\Controllers\Customer\PesananController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Toko\DokuRegistrationController;
use App\Http\Controllers\RegisterTenantController; // Tambahkan Controller Register

class DokuWebhookController extends Controller
{
    /**
     * Menangani semua notifikasi (webhook) yang masuk dari DOKU Jokul.
     */
    public function handle(Request $request)
    {
        // 1. Catat SEMUA yang masuk
        Log::info('DOKU WEBHOOK JOKUL DITERIMA:', $request->all());

        // 2. Ambil Header Penting dari DOKU
        $clientId = $request->header('Client-Id');
        $requestId = $request->header('Request-Id');
        $requestTimestamp = $request->header('Request-Timestamp');
        $signatureHeader = $request->header('Signature');

        // 3. Ambil Kredensial dari .env
        $myClientId = config('doku.client_id');
        $mySecretKey = config('doku.secret_key');

        $requestTarget = $request->getPathInfo(); // (misal: /api/webhook/doku-jokul)
        $requestBody = $request->getContent(); // Body JSON mentah

        // 4. Validasi Signature (SANGAT PENTING)
        try {
            // Bandingkan Client-Id
            if ($clientId !== $myClientId) {
                Log::warning('DOKU Webhook: Client-Id tidak cocok.', ['received' => $clientId]);
                return response()->json(['message' => 'Invalid Client-Id'], 401);
            }

            $generatedSignature = $this->_generateSignatureForWebhook(
                $clientId,
                $requestId,
                $requestTimestamp,
                $requestTarget,
                $requestBody,
                $mySecretKey
            );

            // Validasi Tanda Tangan
            if ($signatureHeader !== $generatedSignature) {
                Log::error('DOKU Webhook: SIGNATURE TIDAK COCOK!', [
                    'received_sig' => $signatureHeader,
                    'generated_sig' => $generatedSignature,
                    'request_body' => $requestBody,
                ]);
                return response()->json(['message' => 'Invalid Signature'], 401);
            }

        } catch (\Exception $e) {
            Log::error('DOKU Webhook: Exception saat validasi signature.', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Signature validation error'], 500);
        }

        // --- SIGNATURE VALID: PROSES PESANAN ---
        Log::info('DOKU Webhook: Signature Valid. Memproses data...');

        $data = $request->all();

        // =================================================================
        // === PERBAIKAN: LOGIKA TRIAGE (PEMILAH WEBHOOK) ===
        // =================================================================
        // DOKU mengirim berbagai jenis webhook. Kita harus tahu mana yang harus ditangani.

        // 1. Apakah ini Webhook Notifikasi Pembayaran?
        if (isset($data['transaction'])) {

            Log::info('DOKU Webhook: Tipe Notifikasi Pembayaran terdeteksi.');
            $orderId = $data['order']['invoice_number'] ?? null;
            $status = $data['transaction']['status'] ?? null;

            // Hanya proses jika status 'SUCCESS'
            if ($status === 'SUCCESS') {

                // --- LOGIKA DISPATCHER (Penerus Perintah) ---

                // PERBAIKAN: Gunakan App::make() untuk Dependency Injection
                // Jangan pernah gunakan 'new Controller()'!

                if (Str::startsWith($orderId, 'TOPUP-')) {
                    Log::info("DOKU Dispatcher: Mengirim $orderId ke TopUpController...");
                    return App::make(TopUpController::class)->handleDokuCallback($data);

                } else if (Str::startsWith($orderId, 'INV-')) {
                    Log::info("DOKU Dispatcher: Mengirim $orderId ke CustomerOrderController...");
                    return App::make(CustomerOrderController::class)->handleDokuCallback($data);

                } else if (Str::startsWith($orderId, 'SCK-') || Str::startsWith($orderId, 'CVSANCAK-') || Str::startsWith($orderId, 'ORD-')) {
                    Log::info("DOKU Dispatcher: Mengirim $orderId ke CheckoutController (Handler Utama)...");
                    return App::make(CheckoutController::class)->handleDokuCallback($data);

                } else {
                    Log::error("DOKU Webhook: Tidak ada handler untuk prefix $orderId.");
                }
            } else {
                Log::info("DOKU Webhook: Status transaksi bukan SUCCESS (Status: $status). Dilewati.");
            }

        // 2. Apakah ini Webhook Notifikasi Sub Account? (Ini yang Anda perlukan!)
        } else if (isset($data['account'])) {

            Log::info('DOKU Webhook: Tipe Notifikasi Sub Account terdeteksi.');
            $sac_id = $data['account']['id'] ?? null;
            $newStatus = $data['account']['status'] ?? null; // Misal: "ACTIVE"

            if ($sac_id && $newStatus) {
                // Cari toko di database Anda
                $store = Store::where('doku_sac_id', $sac_id)->first();

                if ($store) {
                    // Update statusnya
                    $store->doku_status = $newStatus;
                    $store->save();
                    Log::info("DOKU Webhook: Status Toko ID $store->id (SAC ID: $sac_id) diperbarui ke: $newStatus");
                } else {
                    Log::warning("DOKU Webhook: Menerima update status untuk $sac_id, tapi SAC ID tidak ditemukan di database.");
                }
            }

        // 3. Apakah ini Webhook Notifikasi Payout?
        } else if (isset($data['payout'])) {

            Log::info('DOKU Webhook: Tipe Notifikasi Payout terdeteksi.');
            $sac_id = $data['account']['id'] ?? null;
            $payoutStatus = $data['payout']['status'] ?? null;
            $amount = $data['payout']['amount'] ?? null;

            if ($sac_id && $payoutStatus === 'SUCCESS' && $amount !== null) {
                $store = Store::where('doku_sac_id', $sac_id)->first();
                if ($store) {
                    // Saldo sudah dikurangi di controller saat request.
                    // Webhook ini adalah konfirmasi akhir. Kita bisa paksa refresh saldo.
                    $store->doku_balance_last_updated = null; // Hapus cache agar di-refresh
                    $store->save();
                    Log::info("DOKU Webhook: Payout $sac_id sukses. Menandai cache saldo untuk di-refresh.");
                }
            } else if ($payoutStatus === 'FAILED' || $payoutStatus === 'REVERSED') {
                 $store = Store::where('doku_sac_id', $sac_id)->first();
                 if ($store) {
                    // Payout gagal, kembalikan saldo
                    $store->doku_balance_available += (int) $amount;
                    $store->doku_balance_last_updated = now();
                    $store->save();
                    Log::warning("DOKU Webhook: Payout $sac_id GAGAL. Saldo dikembalikan ke cache.");
                 }
            }

        } else {
            Log::warning('DOKU Webhook: Tipe webhook tidak dikenal (bukan Transaksi, Akun, atau Payout).', $data);
        }


        // Kirim 200 OK ke DOKU agar tidak dikirim ulang
        return response()->json(['message' => 'Webhook received and acknowledged']);
    }

    /**
     * Helper untuk membuat signature yang dikirimkan oleh DOKU untuk validasi Webhook.
     * Menggunakan protokol yang sama dengan Checkout API (tanpa SHA-256= pada string to sign).
     * Protokol webhook DOKU terkadang berbeda dengan API request.
     */
    private function _generateSignatureForWebhook(
        string $clientId,
        string $requestId,
        string $requestTimestamp,
        string $requestTarget,
        string $requestBody,
        string $secretKey
    ): string
    {
        $digest = base64_encode(hash('sha256', $requestBody, true));

        // Protokol Webhook DOKU (seringkali longgar, sama dengan Checkout API)
        // Ini adalah format yang kita temukan berhasil untuk POST /sac-merchant/v1/accounts
        $stringToSign = "Client-Id:" . $clientId . "\n"
                        . "Request-Id:" . $requestId . "\n"
                        . "Request-Timestamp:" . $requestTimestamp . "\n"
                        . "Request-Target:" . $requestTarget . "\n"
                        . "Digest:" . $digest;

        $hmac = hash_hmac('sha256', $stringToSign, $secretKey, true);
        $signature = base64_encode($hmac);

        return "HMACSHA256=" . $signature;
    }
}
