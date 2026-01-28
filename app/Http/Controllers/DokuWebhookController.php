<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App; // <-- PERBAIKAN: Import 'App' facade
use Illuminate\Support\Str;
use App\Models\Store; // <-- PERBAIKAN: Import Model Store

// Import Controller yang akan memproses pesanan
use App\Http\Controllers\Admin\PesananController as AdminPesananController;
use App\Http\Controllers\CustomerOrderController;
use App\Http\Controllers\Customer\TopUpController;
use App\Http\Controllers\Customer\PesananController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Toko\DokuRegistrationController;

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

        // --- 3. PROSES DATA ---
        $data = $request->all();

        if (isset($data['transaction'])) {
            $orderId = $data['order']['invoice_number'] ?? null;
            $status = $data['transaction']['status'] ?? null;

            Log::info("LOG LOG: Memproses Invoice: $orderId | Status: $status");

            if ($status === 'SUCCESS') {

                // =================================================================
                // A. PENANGANAN TENANT (PREFIX SEWA-)
                // =================================================================
                // =================================================================

                // 1. PENANGANAN TENANT (PREFIX SEWA-)
                // =================================================================
                if (Str::startsWith($orderId, 'SEWA-')) {
                    Log::info("LOG LOG: Detected Tenant Payment: $orderId");
                    $subdomain = strtolower(explode('-', $orderId)[1] ?? '');

                    $activated = false;

                    // STEP 1: Cek di Database Utama (Hanya jika kolom 'subdomain' ada)
                    try {
                        $hasColumn = \Illuminate\Support\Facades\Schema::hasColumn('tenants', 'subdomain');

                        if ($hasColumn) {
                            $tenantMain = \Illuminate\Support\Facades\DB::table('tenants')
                                            ->where('subdomain', $subdomain)
                                            ->first();

                            if ($tenantMain) {
                                Log::info("LOG LOG: ✅ Tenant ditemukan di DB UTAMA.");
                                \Illuminate\Support\Facades\DB::table('tenants')
                                    ->where('id', $tenantMain->id)
                                    ->update(['status' => 'active', 'updated_at' => now()]);
                                $activated = true;
                            }
                        }
                    } catch (\Exception $e) {
                        Log::warning("LOG LOG: Database Utama tidak memiliki struktur tenant yang cocok, lanjut ke DB Kedua.");
                    }

                    // STEP 2: Jika belum aktif, lari ke Database Kedua (Percetakan)
                    if (!$activated) {
                        Log::info("LOG LOG: 🔍 Mencari di mysql_second (DB Percetakan)...");
                        try {
                            $percetakanDB = \Illuminate\Support\Facades\DB::connection('mysql_second');
                            $tenantSecond = $percetakanDB->table('tenants')
                                            ->where('subdomain', $subdomain)
                                            ->first();

                            if ($tenantSecond) {
                                $percetakanDB->table('tenants')
                                    ->where('id', $tenantSecond->id)
                                    ->update(['status' => 'active', 'updated_at' => now()]);

                                Log::info("LOG LOG: ✅ Tenant '$subdomain' AKTIF di DB PERCETAKAN.");
                                $this->_sendFonnteNotification($subdomain);
                                $activated = true;
                            }
                        } catch (\Exception $e) {
                            Log::error("LOG LOG: ❌ Gagal akses DB Percetakan: " . $e->getMessage());
                        }
                    }

                    if ($activated) {
                        return response()->json(['message' => 'Activation Success']);
                    } else {
                        Log::warning("LOG LOG: ❌ Tenant '$subdomain' tidak ditemukan di database manapun.");
                        return response()->json(['message' => 'Tenant not found'], 404);
                    }
                }

                // =================================================================
                // B. PENANGANAN ORDER PERCETAKAN (PREFIX SCK-PRT-)
                // =================================================================
                else if (Str::startsWith($orderId, 'SCK-PRT-')) {
                    Log::info("LOG LOG: 🖨️ Mendeteksi Order Percetakan: $orderId");
                    try {
                        $percetakanDB = DB::connection('mysql_second');
                        $orderPercetakan = $percetakanDB->table('orders')->where('order_number', $orderId)->first();
                        if ($orderPercetakan) {
                            $percetakanDB->table('orders')->where('id', $orderPercetakan->id)->update([
                                'payment_status' => 'paid',
                                'status' => 'processing',
                                'updated_at' => now()->timezone('Asia/Jakarta')
                            ]);
                            Log::info("LOG LOG: ✅ Order Percetakan LUNAS di DB Percetakan.");
                            $this->_sendFonnteNotification("Order Lunas: $orderId");
                            return response()->json(['message' => 'Order Updated in DB Second']);
                        }
                    } catch (\Exception $e) {
                        Log::error("LOG LOG: ❌ Gagal Update Order Percetakan: " . $e->getMessage());
                    }
                }

                // =================================================================
                // C. PENANGANAN DB PERTAMA (KODE ASLI ANDA)
                // =================================================================
                else {
                    if (Str::startsWith($orderId, 'TOPUP-')) {
                        return App::make(TopUpController::class)->handleDokuCallback($data);
                    } else if (Str::startsWith($orderId, 'INV-')) {
                        return App::make(CustomerOrderController::class)->handleDokuCallback($data);
                    } else if (Str::startsWith($orderId, 'SCK-') || Str::startsWith($orderId, 'CVSANCAK-') || Str::startsWith($orderId, 'ORD-')) {
                        return App::make(CheckoutController::class)->handleDokuCallback($data);
                    }
                }

            } else {
                Log::info("DOKU Webhook: Status bukan SUCCESS. Dilewati.");
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

    /**
 * Helper Notifikasi WA (Jika database kedua sukses)
 * Otomatis membersihkan nomor pendaftar agar standar 08
 */
private function _sendFonnteNotification($subdomain)
{
    try {
        // 1. Ambil data tenant dari database kedua untuk mendapatkan nomor WA
        $percetakanDB = \Illuminate\Support\Facades\DB::connection('mysql_second');
        $tenant = $percetakanDB->table('tenants')->where('subdomain', $subdomain)->first();

        if (!$tenant || empty($tenant->whatsapp)) {
            Log::warning("LOG LOG: Gagal kirim WA, nomor WA tenant tidak ditemukan.");
            return;
        }

        // 2. PROSES PEMBERSIHAN NOMOR (STANDARISASI 08)
        $phone = $tenant->whatsapp;
        $phone = preg_replace('/[^0-9]/', '', $phone); // Hapus karakter non-angka

        if (str_starts_with($phone, '62')) {
            $phone = '0' . substr($phone, 2); // Ubah 628... jadi 08...
        } elseif (str_starts_with($phone, '8')) {
            $phone = '0' . $phone; // Tambah 0 jika langsung angka 8
        }

        // 3. Susun Pesan
        $adminPhone = '085745808809'; // Nomor Bapak
        $msg = "💰 *PEMBAYARAN TERKONFIRMASI*\n\n" .
               "Halo Owner *{$subdomain}*,\n" .
               "Pembayaran sewa Anda telah kami terima.\n\n" .
               "Status: *ACTIVE* ✅\n" .
               "Sistem: *Database Percetakan*\n" .
               "Link Login: https://{$subdomain}.tokosancaka.com/percetakan/public/login\n\n" .
               "_Pesanan Anda sudah bisa diakses. Terima kasih!_";

        // 4. Kirim ke User & Admin
        $this->_sendFonnteMessage($phone, $msg);
        $this->_sendFonnteMessage($adminPhone, "INFO: Tenant *{$subdomain}* baru saja aktif otomatis via Webhook DOKU.");

        Log::info("LOG LOG: Notifikasi WA Aktivasi Tenant $subdomain dikirim ke $phone.");
    } catch (\Exception $e) {
        Log::error("LOG LOG: Gagal kirim WA Notif: " . $e->getMessage());
    }
}

    /**
     * Fungsi internal kirim pesan via Fonnte
     */
    private function _sendFonnteMessage($target, $message)
    {
        $token = env('FONNTE_API_KEY') ?? env('FONNTE_KEY');
        if (!$token) return;

        \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => $token
        ])->post('https://api.fonnte.com/send', [
            'target' => $target,
            'message' => $message
        ]);
    }
}


