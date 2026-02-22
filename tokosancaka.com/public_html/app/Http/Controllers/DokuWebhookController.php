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
    public function handle(Request $request)
    {
        // =================================================================
        // 0. DETEKSI BROWSER USER vs SERVER DOKU
        // =================================================================
        // Webhook resmi dari server DOKU selalu membawa header 'Client-Id' atau 'Signature'.
        // Jika header ini tidak ada, berarti ini adalah MANUSIA (pelanggan) yang di-redirect dari halaman pembayaran DOKU.

        if (!$request->hasHeader('Client-Id') && !$request->hasHeader('client-id')) {

            // Coba ambil invoice dari URL parameter (jika DOKU menyertakannya saat redirect)
            $invoice = $request->query('invoice_number') ?? $request->query('transaction_id') ?? '';
            $subdomain = 'operator'; // Default fallback

            // Jika invoice memiliki format LISC-MONTHLY-operator-1234, kita ambil subdomainnya
            if (str_starts_with($invoice, 'LISC-')) {
                $parts = explode('-', $invoice);
                if (isset($parts[2]) && !is_numeric($parts[2])) {
                    $subdomain = strtolower($parts[2]);
                }
            }

            // Redirect pengguna manusia ke halaman Redeem Lisensi yang cantik
            return redirect()->to("https://apps.tokosancaka.com/redeem-lisensi?subdomain={$subdomain}")
                             ->with('success', 'Pembayaran sedang diproses! Sistem SancakaPOS sedang mengaktifkan lisensi toko Anda.');
        }

        // =================================================================
        // 1. LOGGING & BYPASS VALIDASI (Supaya tidak Error 400)
        // =================================================================

        // Catat Header & Body untuk Debugging
        Log::info('=== WEBHOOK DOKU MASUK (BYPASS MODE) ===');
        Log::info('Headers:', $request->headers->all());

        $content = $request->getContent();
        // Log::info('Body Raw:', [$content]); // Uncomment jika ingin log raw body

        // Cek Client ID (Hanya untuk catatan Log, TIDAK memblokir transaksi)
        $incomingId = $request->header('Client-Id') ?? $request->header('client-id');
        $myId = config('doku.client_id');

        if ($incomingId !== $myId) {
            Log::warning("⚠️ ALERT: Client ID tidak cocok! (Masuk: $incomingId vs Server: $myId)");
            Log::warning("⚠️ Transaksi TETAP DILANJUTKAN karena dalam Mode Debugging.");
        } else {
            Log::info("✅ Client ID Cocok.");
        }

        // =================================================================
        // 2. PARSING & PROSES DATA
        // =================================================================
        $data = json_decode($content, true);

        // --- A. JIKA DATA TRANSAKSI / ORDER ---
        if (isset($data['transaction']) || isset($data['order'])) {

            $orderId = $data['order']['invoice_number'] ?? null;
            $status = $data['transaction']['status'] ?? null;

            Log::info("Memproses Invoice: $orderId | Status: $status");

            if (strtoupper($status) === 'SUCCESS') {

                // -------------------------------------------------------------
                // A.1 AKTIVASI TENANT BARU (SEWA-)
                // -------------------------------------------------------------
                if (Str::startsWith($orderId, 'SEWA-')) {
                    $subdomain = strtolower(explode('-', $orderId)[1] ?? '');
                    $activated = false;

                    // STEP 1: DB Utama (tenants)
                    try {
                        if (\Illuminate\Support\Facades\Schema::hasColumn('tenants', 'subdomain')) {
                            $tenantMain = DB::table('tenants')->where('subdomain', $subdomain)->first();
                            if ($tenantMain) {
                                DB::table('tenants')->where('id', $tenantMain->id)->update(['status' => 'active', 'updated_at' => now()]);
                                $activated = true;
                            }
                        }
                    } catch (\Exception $e) {}

                    // STEP 2: DB Percetakan (mysql_second)
                    if (!$activated) {
                        try {
                            $percetakanDB = DB::connection('mysql_second');
                            $tenantSec = $percetakanDB->table('tenants')->where('subdomain', $subdomain)->first();
                            if ($tenantSec) {
                                // Default Trial 7 hari atau Paket 30/365 hari
                                $days = ($tenantSec->package == 'yearly') ? 365 : (($tenantSec->package == 'trial') ? 7 : 30);
                                $expiredDate = now()->addDays($days)->timezone('Asia/Jakarta');

                                $percetakanDB->table('tenants')->where('id', $tenantSec->id)->update([
                                    'status' => 'active',
                                    'expired_at' => $expiredDate,
                                    'updated_at' => now()
                                ]);

                                Log::info("✅ Tenant '$subdomain' AKTIF di DB Percetakan. Expired: $expiredDate");
                                $this->_sendFonnteNotification($subdomain);
                            }
                        } catch (\Exception $e) {
                            Log::error("❌ Gagal Update DB Percetakan: " . $e->getMessage());
                        }
                    }
                }

                // =================================================================
            // A.2 PERPANJANGAN (PREFIX REN- ATAU RENEW-) - VERSI UPDATE DUAL DB
            // =================================================================
            else if (Str::startsWith($orderId, 'RENEW-') || Str::startsWith($orderId, 'REN-')) {
                Log::info("LOG LOG: 1. Masuk blok RENEW untuk Invoice: $orderId");

                try {
                    $parts = explode('-', $orderId);
                    $subdomain = strtolower($parts[1] ?? '');

                    // Support format baru RENEW-PAKET-SUBDOMAIN
                    if (in_array(strtoupper($parts[1]), ['MONTHLY', 'YEARLY', 'QUARTERLY'])) {
                        $subdomain = strtolower($parts[2] ?? '');
                    }

                    // 1. Ambil Nominal & Tentukan Paket
                    $amountPaid = $data['order']['amount'] ?? $data['transaction']['amount'] ?? 0;
                    $amountPaid = (int)$amountPaid;

                    if ($amountPaid >= 1000000) {
                        $days = 365; $newPackage = 'yearly'; $label = "1 Tahun";
                    } elseif ($amountPaid >= 300000) {
                        $days = 90; $newPackage = 'quarterly'; $label = "3 Bulan";
                    } else {
                        $days = 30; $newPackage = 'monthly'; $label = "1 Bulan";
                    }

                    // --- [UPDATE 1] DATABASE UTAMA (tokq3391_percetakan) ---
                    // Ini yang Anda lihat di phpMyAdmin tadi
                    $tenantMain = DB::table('tenants')->where('subdomain', $subdomain)->first();
                    if ($tenantMain) {
                        $currentExpiry = ($tenantMain->expired_at && \Carbon\Carbon::parse($tenantMain->expired_at)->isFuture())
                                        ? \Carbon\Carbon::parse($tenantMain->expired_at) : now();
                        $newExpiry = $currentExpiry->copy()->addDays($days)->timezone('Asia/Jakarta');

                        DB::table('tenants')->where('id', $tenantMain->id)->update([
                            'status' => 'active',
                            'package' => $newPackage,
                            'expired_at' => $newExpiry,
                            'updated_at' => now()
                        ]);
                        Log::info("✅ DB UTAMA: Tenant '$subdomain' diperpanjang sampai $newExpiry");
                    }

                    // --- [UPDATE 2] DATABASE KEDUA (tokq3391_db) ---
                    // Ini yang 'mysql_second'
                    $percetakanDB = DB::connection('mysql_second');
                    $tenantSec = $percetakanDB->table('tenants')->where('subdomain', $subdomain)->first();

                    if ($tenantSec) {
                        // Logic expired sama
                        $currentExpiry = ($tenantSec->expired_at && \Carbon\Carbon::parse($tenantSec->expired_at)->isFuture())
                                        ? \Carbon\Carbon::parse($tenantSec->expired_at) : now();
                        $newExpiry = $currentExpiry->copy()->addDays($days)->timezone('Asia/Jakarta');

                        $percetakanDB->table('tenants')->where('id', $tenantSec->id)->update([
                            'status' => 'active',
                            'package' => $newPackage,
                            'expired_at' => $newExpiry,
                            'updated_at' => now()
                        ]);
                        Log::info("✅ DB SECOND: Tenant '$subdomain' diperpanjang sampai $newExpiry");
                    }

                    // Notifikasi WA (Cukup sekali saja)
                    if ($tenantMain || $tenantSec) {
                        $this->_sendFonnteNotification($subdomain, "Perpanjangan $label");
                    } else {
                        Log::error("❌ GAGAL: Tenant '$subdomain' tidak ditemukan di DB Utama maupun Second.");
                    }

                } catch (\Exception $e) {
                    Log::error("❌ CRITICAL ERROR RENEW: " . $e->getMessage());
                }
            }

            // -------------------------------------------------------------
                // A.2.B TOP UP SALDO POS (POSTOPUP-) -> DATABASE KEDUA
                // -------------------------------------------------------------
                else if (Str::startsWith($orderId, 'POSTOPUP-')) {

                    Log::info("🚀 LOG POS: Webhook Masuk untuk Invoice POS: $orderId");

                    try {
                        // Konek langsung ke Database Kedua (Tanpa Model agar lebih aman di App Pertama)
                        $dbSecond = DB::connection('mysql_second');

                        // 1. Cari Transaksi di tabel 'top_ups' Database Kedua
                        $transaction = $dbSecond->table('top_ups')
                                            ->where('reference_no', $orderId)
                                            ->first();

                        if ($transaction) {
                            if ($transaction->status !== 'SUCCESS') {

                                // 2. Update Status Transaksi di DB SECOND
                                $dbSecond->table('top_ups')
                                         ->where('id', $transaction->id)
                                         ->update([
                                             'status' => 'SUCCESS',
                                             'updated_at' => now()
                                         ]);

                                // 3. Update Saldo User di DB SECOND
                                $affected = $dbSecond->table('users')
                                            ->where('id', $transaction->affiliate_id)
                                            ->increment('saldo', $transaction->amount);

                                if ($affected) {
                                    Log::info("💰 SALDO POS BERTAMBAH: User ID {$transaction->affiliate_id} +{$transaction->amount}");
                                } else {
                                    Log::error("❌ Gagal Update Saldo User ID {$transaction->affiliate_id} di DB Second.");
                                }

                            } else {
                                Log::info("⚠️ TopUp POS $orderId sudah sukses sebelumnya.");
                            }
                        } else {
                            Log::error("❌ Data TopUp POS tidak ditemukan di DB Second: $orderId");
                        }
                    } catch (\Exception $e) {
                        Log::error("❌ Gagal Proses TopUp POS: " . $e->getMessage());
                    }
                }

                // -------------------------------------------------------------
                // A.3 ORDER PERCETAKAN (SCK-PRT-)
                // -------------------------------------------------------------
                else if (Str::startsWith($orderId, 'SCK-PRT-')) {
                    try {
                        $percetakanDB = DB::connection('mysql_second');
                        $orderPercetakan = $percetakanDB->table('orders')->where('order_number', $orderId)->first();

                        if ($orderPercetakan) {
                            $percetakanDB->table('orders')->where('id', $orderPercetakan->id)->update([
                                'payment_status' => 'paid',
                                'status' => 'processing', // Langsung proses
                                'updated_at' => now()->timezone('Asia/Jakarta')
                            ]);
                            Log::info("✅ Order SancakaPOS $orderId LUNAS.");
                            // Notif manual string karena struktur tabel beda dengan tenant
                             $this->_sendFonnteMessage('085745808809', "🖨️ Order SancakaPOS Masuk & Lunas: *$orderId*");
                        }
                    } catch (\Exception $e) {
                        Log::error("❌ Gagal Update Order SancakaPOS: " . $e->getMessage());
                    }
                }

                // -------------------------------------------------------------
                // A.4 ORDER LISENSI (LISC)
                // -------------------------------------------------------------

                // =================================================================
                // >>> PASTE KODE LISC- (AKTIVASI LISENSI) DI SINI <<<
                // =================================================================
                else if (Str::startsWith($orderId, 'LISC-')) {
                    Log::info("🚀 LOG LISENSI: Webhook Masuk untuk Aktivasi Lisensi: $orderId");

                    try {
                        // Pecah invoice: LISC - MONTHLY - operator - 1771738501
                        $parts = explode('-', $orderId);
                        $packageType = strtoupper($parts[1] ?? 'MONTHLY');
                        $subdomain = strtolower($parts[2] ?? '');

                        if (empty($subdomain) || is_numeric($subdomain)) {
                            Log::error("❌ GAGAL: Subdomain tidak valid pada invoice $orderId. Pastikan generate invoice menyertakan subdomain.");
                        } else {
                            $monthsToAdd = 1;
                            if ($packageType === 'HALF_YEAR') $monthsToAdd = 6;
                            if ($packageType === 'YEARLY') $monthsToAdd = 12;
                            $newPackage = strtolower($packageType);

                            // --- 1. UPDATE DB UTAMA ---
                            $tenantMain = DB::table('tenants')->where('subdomain', $subdomain)->first();
                            if ($tenantMain) {
                                $currentExpired = $tenantMain->expired_at ? \Carbon\Carbon::parse($tenantMain->expired_at) : now();
                                if ($currentExpired->isPast()) $currentExpired = now();
                                $newExpiredDate = $currentExpired->copy()->addMonths($monthsToAdd)->timezone('Asia/Jakarta');

                                DB::table('tenants')->where('id', $tenantMain->id)->update([
                                    'status' => 'active',
                                    'package' => $newPackage,
                                    'expired_at' => $newExpiredDate,
                                    'updated_at' => now()
                                ]);
                                Log::info("✅ DB UTAMA: Tenant '$subdomain' diperpanjang $monthsToAdd bulan hingga {$newExpiredDate}.");
                            }

                            // --- 2. UPDATE DB KEDUA (mysql_second) ---
                            $percetakanDB = DB::connection('mysql_second');
                            $tenantSec = $percetakanDB->table('tenants')->where('subdomain', $subdomain)->first();

                            if ($tenantSec) {
                                $currentExpiredSec = $tenantSec->expired_at ? \Carbon\Carbon::parse($tenantSec->expired_at) : now();
                                if ($currentExpiredSec->isPast()) $currentExpiredSec = now();
                                $newExpiredDateSec = $currentExpiredSec->copy()->addMonths($monthsToAdd)->timezone('Asia/Jakarta');

                                $percetakanDB->table('tenants')->where('id', $tenantSec->id)->update([
                                    'status' => 'active',
                                    'package' => $newPackage,
                                    'expired_at' => $newExpiredDateSec,
                                    'updated_at' => now()
                                ]);
                                Log::info("✅ DB SECOND: Tenant '$subdomain' diperpanjang hingga {$newExpiredDateSec}.");
                            }

                            // --- 3. KIRIM NOTIFIKASI WA ---
                            if ($tenantMain || $tenantSec) {
                                $this->_sendFonnteNotification($subdomain);
                            } else {
                                Log::error("❌ GAGAL: Tenant '$subdomain' tidak ditemukan di kedua database.");
                            }
                        }
                    } catch (\Exception $e) {
                        Log::error("❌ CRITICAL ERROR LISC-: " . $e->getMessage());
                    }
                }
                // =================================================================
                // >>> AKHIR KODE LISC- <<<
                // =================================================================

                // -------------------------------------------------------------
                // A.4 DELEGASI KE CONTROLLER LAIN (TOPUP, INV, CHECKOUT)
                // -------------------------------------------------------------
                else {
                    if (Str::startsWith($orderId, 'TOPUP-')) {
                        return App::make(\App\Http\Controllers\Customer\TopUpController::class)->handleDokuCallback($data);
                    } else if (Str::startsWith($orderId, 'INV-')) {
                        return App::make(\App\Http\Controllers\CustomerOrderController::class)->handleDokuCallback($data);
                    } else if (Str::startsWith($orderId, 'SCK-') || Str::startsWith($orderId, 'CVSANCAK-') || Str::startsWith($orderId, 'ORD-')) {
                        return App::make(\App\Http\Controllers\CheckoutController::class)->handleDokuCallback($data);
                    }
                }
            } else {
                Log::info("DOKU Webhook: Status Transaksi bukan SUCCESS ($status). Dilewati.");
            }

        // --- B. NOTIFIKASI SUB ACCOUNT (Update Status Toko) ---
        } else if (isset($data['account'])) {

            $sac_id = $data['account']['id'] ?? null;
            $newStatus = $data['account']['status'] ?? null;

            if ($sac_id && $newStatus) {
                $store = Store::where('doku_sac_id', $sac_id)->first();
                if ($store) {
                    $store->doku_status = $newStatus;
                    $store->save();
                    Log::info("ℹ️ Status Toko (SAC: $sac_id) update ke: $newStatus");
                }
            }

        // --- C. NOTIFIKASI PAYOUT (Pencairan Dana) ---
        } else if (isset($data['payout'])) {

            $sac_id = $data['account']['id'] ?? null;
            $payoutStatus = $data['payout']['status'] ?? null;
            $amount = $data['payout']['amount'] ?? 0;

            if ($sac_id) {
                $store = Store::where('doku_sac_id', $sac_id)->first();
                if ($store) {
                    if ($payoutStatus === 'SUCCESS') {
                        // Sukses: Clear cache saldo agar refresh real-time
                        $store->doku_balance_last_updated = null;
                        $store->save();
                        Log::info("💰 Payout Sukses ($sac_id). Cache saldo di-reset.");
                    }
                    else if ($payoutStatus === 'FAILED' || $payoutStatus === 'REVERSED') {
                        // Gagal: Kembalikan saldo ke 'available'
                        $store->doku_balance_available += (int) $amount;
                        $store->doku_balance_last_updated = now(); // Update timestamp
                        $store->save();
                        Log::warning("⚠️ Payout Gagal ($sac_id). Saldo Rp $amount dikembalikan.");
                    }
                }
            }
        }

        // =================================================================
        // 3. FINAL RESPONSE (WAJIB 200 OK)
        // =================================================================
        return response()->json(['message' => 'Notification received (Processed)'], 200);
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
               "Sistem: *Database SancakaPOS*\n" .
               "Link Login: https://{$subdomain}.tokosancaka.com/login\n\n" .
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
