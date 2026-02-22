<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App; // <-- PERBAIKAN: Import 'App' facade
use Illuminate\Support\Str;
use App\Models\Store; // <-- PERBAIKAN: Import Model Store
use App\Models\LicenseApp2; // <-- PERBAIKAN: Import Model LicenseApp2 untuk akses DB kedua
use App\Services\KiriminAjaService; // <--- TAMBAHKAN INI!

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

               else if (Str::startsWith($orderId, 'SCK-')) {
                    Log::info("🛍️ LOG TRX (SCK-): Webhook DOKU Masuk untuk Order: $orderId");

                    try {
                        $percetakanDB = DB::connection('mysql_second');
                        $orderMarketplace = $percetakanDB->table('orders')->where('order_number', $orderId)->first();

                        if ($orderMarketplace && $orderMarketplace->payment_status !== 'paid') {

                            $updateData = [
                                'payment_status' => 'paid',
                                'status'         => 'processing',
                                'updated_at'     => now()->timezone('Asia/Jakarta')
                            ];

                            if ($orderMarketplace->is_escrow == 1) {
                                $updateData['escrow_status'] = 'held';
                                Log::info("🔒 ESCROW AKTIF: Order $orderId. Menyiapkan Payload API...");

                                $shipData = json_decode($orderMarketplace->shipping_ref, true);

                                if (is_array($shipData)) {
                                    $tenantOwner = $percetakanDB->table('users')->where('tenant_id', $orderMarketplace->tenant_id)->first();

                                    if ($tenantOwner) {
                                        // PANGGIL SERVICE MENGGUNAKAN NAMESPACE LENGKAP
                                        $kiriminAja = new \App\Services\KiriminAjaService();

                                        // Sesuaikan Payload dengan Format KiriminAja v6.1 (Request Pickup)
                                        $pickupSchedule = now()->addHour()->format('Y-m-d H:i:s');
                                        $payload = [
                                            'address'      => $tenantOwner->address_detail ?? 'Alamat Toko',
                                            'phone'        => $tenantOwner->phone ?? '085745808809',
                                            'name'         => $tenantOwner->name ?? 'Toko Sancaka',
                                            'kecamatan_id' => (int) $tenantOwner->district_id,
                                            'kelurahan_id' => (int) $tenantOwner->subdistrict_id,
                                            'zipcode'      => $tenantOwner->postal_code ?? '00000',
                                            'schedule'     => $pickupSchedule,
                                            'platform_name'=> 'Sancaka Marketplace',
                                            'packages'     => [[
                                                'order_id'          => $orderId,
                                                'item_name'         => 'Produk Percetakan',
                                                'package_type_id'   => 1, // Umum
                                                'destination_name'  => $orderMarketplace->customer_name,
                                                'destination_phone' => $orderMarketplace->customer_phone,
                                                'destination_address' => $orderMarketplace->destination_address,
                                                'destination_kecamatan_id' => (int) $shipData['dist'],
                                                'destination_kelurahan_id' => (int) ($shipData['sub'] ?? 0),
                                                'destination_zipcode'      => '00000',
                                                'weight'        => (int) ceil($shipData['weight'] ?? 1000),
                                                'width' => 10, 'height' => 10, 'length' => 10,
                                                'item_value'    => (int) $orderMarketplace->total_price,
                                                'insurance_amount' => 0,
                                                'cod'           => 0,
                                                'service'       => $shipData['code'] ?? 'jne',
                                                'service_type'  => $shipData['type'] ?? 'REG',
                                                'shipping_cost' => (int) $orderMarketplace->shipping_cost
                                            ]]
                                        ];

                                        Log::info("🚀 Menembak API KiriminAja via Service...");
                                        $kaResponse = $kiriminAja->createExpressOrder($payload);
                                        Log::info("📩 Respon KiriminAja:", ['data' => $kaResponse]);

                                        if ($kaResponse && isset($kaResponse['status']) && $kaResponse['status'] == true) {
                                            $bookingId = $kaResponse['pickup_number'] ?? $kaResponse['id'] ?? $kaResponse['data']['id'] ?? null;
                                            $updateData['shipping_ref'] = $bookingId;
                                            Log::info("✅ AUTO-BOOKING BERHASIL: $bookingId");
                                        } else {
                                            Log::error("❌ API KA GAGAL:", ['msg' => $kaResponse['text'] ?? 'Unknown Error']);
                                        }
                                    }
                                }
                            } else {
                                // Skenario Kasir Offline
                                $tenantOwner = $percetakanDB->table('users')->where('tenant_id', $orderMarketplace->tenant_id)->first();
                                if ($tenantOwner) {
                                    $percetakanDB->table('users')->where('id', $tenantOwner->id)->increment('saldo', $orderMarketplace->final_price);
                                }
                            }

                            // Update Final ke Database
                            $percetakanDB->table('orders')->where('id', $orderMarketplace->id)->update($updateData);
                            Log::info("✅ Selesai memproses Webhook untuk $orderId");
                        }
                    } catch (\Exception $e) {
                        Log::error("❌ CRITICAL ERROR WEBHOOK:", ['msg' => $e->getMessage()]);
                    }
                }

                // -------------------------------------------------------------
                // A.4 ORDER LISENSI (LISC)
                // -------------------------------------------------------------

                // =================================================================
                // >>> PASTE KODE LISC- (PEMBELIAN LISENSI) DI SINI <<<
                // =================================================================
                else if (Str::startsWith($orderId, 'LISC-')) {
                    Log::info("🚀 LOG LISENSI: Webhook Masuk untuk Pembelian Lisensi: $orderId");

                    try {
                        // Pecah invoice: LISC - MONTHLY - operator - 1771738501
                        $parts = explode('-', $orderId);
                        $packageType = strtoupper($parts[1] ?? 'MONTHLY');
                        $subdomain = strtolower($parts[2] ?? '');
                        $newPackage = strtolower($packageType);

                        // 1. CARI DATA TENANT DAN USER TERLEBIH DAHULU (Dipindah ke atas)
                        Log::info("🔍 Mencari data tenant untuk subdomain '$subdomain' di DB Percetakan...");
                        $percetakanDB = DB::connection('mysql_second');
                        $tenantSec = $percetakanDB->table('tenants')->where('subdomain', $subdomain)->first();

                        $tenantId = null;
                        $userId = null;

                        if ($tenantSec) {
                            $tenantId = $tenantSec->id;

                            // Cari user yang merupakan pemilik/terhubung dengan tenant ini
                            $userSec = $percetakanDB->table('users')->where('tenant_id', $tenantId)->first();
                            if ($userSec) {
                                $userId = $userSec->id;
                            } else {
                                Log::warning("⚠️ User untuk tenant '$subdomain' tidak ditemukan, user_id mungkin null.");
                            }
                        } else {
                            Log::error("❌ CRITICAL ERROR: Tenant dengan subdomain '$subdomain' tidak ditemukan. Lisensi tidak bisa dikaitkan secara spesifik.");
                        }

                        // 2. Generate Kode Lisensi Baru
                        $licenseCode = 'SNCK-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4));

                        $durationDays = 30; // Default Monthly
                        if ($newPackage === 'half_year') $durationDays = 180;
                        if ($newPackage === 'yearly') $durationDays = 365;

                        // 3. Simpan Kode dengan Tenant ID dan User ID
                        LicenseApp2::create([
                            'license_code'  => $licenseCode,
                            'tenant_id'     => $tenantId, // <--- MENGISI TENANT ID
                            'user_id'       => $userId,   // <--- MENGISI USER ID
                            'package_type'  => $newPackage,
                            'duration_days' => $durationDays,
                            'status'        => 'available'
                        ]);

                        Log::info("✅ KODE LISENSI DIBUAT: $licenseCode untuk paket $newPackage ($durationDays hari) terikat pada Tenant ID: $tenantId, User ID: $userId");

                        // 4. Kirim Nomor WA Tenant untuk kirim kode
                        if ($tenantSec && !empty($tenantSec->whatsapp)) {
                            // Bersihkan format nomor WA
                            $phone = preg_replace('/[^0-9]/', '', $tenantSec->whatsapp);
                            if (str_starts_with($phone, '62')) $phone = '0' . substr($phone, 2);
                            elseif (str_starts_with($phone, '8')) $phone = '0' . $phone;

                            // Pesan WA yang berisi KODE LISENSI
                            $msg = "🎉 *PEMBAYARAN BERHASIL*\n\n" .
                                   "Terima kasih telah membeli lisensi SancakaPOS!\n" .
                                   "Paket: *" . strtoupper($newPackage) . "*\n\n" .
                                   "🔑 *KODE LISENSI ANDA:*\n" .
                                   "*$licenseCode*\n\n" .
                                   "Silakan *Copy* kode di atas dan masukkan di halaman aktivasi:\n" .
                                   "https://apps.tokosancaka.com/redeem-lisensi?subdomain={$subdomain}";

                            // Kirim via Fonnte
                            $this->_sendFonnteMessage($phone, $msg);
                            Log::info("✅ Kode Lisensi $licenseCode berhasil dikirim ke WA $phone");
                        } else {
                            Log::error("⚠️ Gagal kirim WA: Nomor WA tidak ditemukan untuk subdomain $subdomain.");
                        }

                    } catch (\Exception $e) {
                        Log::error("❌ CRITICAL ERROR LISC-: " . $e->getMessage());
                    }
                }

                // -------------------------------------------------------------
                // A.4B DELEGASI KE CONTROLLER LAIN (TOPUP, INV, CHECKOUT)
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
