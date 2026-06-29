<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use App\Models\Store;
use App\Models\LicenseApp2;
use App\Services\KiriminAjaService;

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
        if (!$request->hasHeader('Client-Id') && !$request->hasHeader('client-id')) {

            $invoice = $request->query('invoice_number') ?? $request->query('transaction_id') ?? '';
            $subdomain = 'operator'; // Default fallback

            if (str_starts_with($invoice, 'LISC-')) {
                $parts = explode('-', $invoice);
                if (isset($parts[2]) && !is_numeric($parts[2])) {
                    $subdomain = strtolower($parts[2]);
                }
            }

            return redirect()->to("https://apps.tokosancaka.com/redeem-lisensi?subdomain={$subdomain}")
                             ->with('success', 'Pembayaran sedang diproses! Sistem SancakaPOS sedang mengaktifkan lisensi toko Anda.');
        }

        // =================================================================
        // 1. LOGGING & BYPASS VALIDASI
        // =================================================================
        /*Log::info('=== WEBHOOK DOKU MASUK (BYPASS MODE) ===');
        Log::info('Headers:', $request->headers->all());

        $content = $request->getContent();

        $incomingId = $request->header('Client-Id') ?? $request->header('client-id');
        $myId = config('doku.client_id');

        if ($incomingId !== $myId) {
            Log::warning("⚠️ ALERT: Client ID tidak cocok! (Masuk: $incomingId vs Server: $myId)");
            Log::warning("⚠️ Transaksi TETAP DILANJUTKAN karena dalam Mode Debugging.");
        } else {
            Log::info("✅ Client ID Cocok.");
        } */

        // =================================================================
        // 1. LOGGING & VALIDASI ZERO TRUST (KETAT)
        // =================================================================
        Log::info('=== WEBHOOK DOKU MASUK ===');
        $content = $request->getContent();

        $incomingId = $request->header('Client-Id') ?? $request->header('client-id');
        $myId = config('doku.client_id');

        // Tarik Header untuk Validasi Signature
        $signatureHeader = $request->header('Signature') ?? $request->header('signature');
        $requestId = $request->header('Request-Id') ?? $request->header('request-id');
        $requestTimestamp = $request->header('Request-Timestamp') ?? $request->header('request-timestamp');
        $requestTarget = $request->getRequestUri();

        // Buat ekspektasi signature dari sisi server kita
        $expectedSignature = $this->_generateSignatureForWebhook($myId, $requestId, $requestTimestamp, $requestTarget, $content, config('doku.secret_key'));

        // Jika Client ID salah ATAU Signature tidak cocok, langsung TOLAK!
        if ($incomingId !== $myId || $signatureHeader !== $expectedSignature) {
            Log::warning("⚠️ ALERT: Autentikasi Webhook DOKU Gagal! Signature atau Client-ID tidak valid. Potensi serangan!");
            return response()->json(['message' => 'Unauthorized Access'], 401);
        } else {
            Log::info("✅ Client ID dan Signature Cocok. Akses aman.");
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

                // Kirim notifikasi pembayaran ke Expo Mobile (Customer & Admin)
                $this->sendExpoPaymentNotification($orderId);

                // -------------------------------------------------------------
                // A.1 AKTIVASI TENANT BARU (SEWA-)
                // -------------------------------------------------------------
                if (Str::startsWith($orderId, 'SEWA-')) {
                    $subdomain = strtolower(explode('-', $orderId)[1] ?? '');
                    $activated = false;

                    try {
                        if (\Illuminate\Support\Facades\Schema::hasColumn('tenants', 'subdomain')) {
                            $tenantMain = DB::table('tenants')->where('subdomain', $subdomain)->first();
                            if ($tenantMain) {
                                DB::table('tenants')->where('id', $tenantMain->id)->update(['status' => 'active', 'updated_at' => now()]);
                                $activated = true;
                            }
                        }
                    } catch (\Exception $e) {}

                    if (!$activated) {
                        try {
                            $percetakanDB = DB::connection('mysql_second');
                            $tenantSec = $percetakanDB->table('tenants')->where('subdomain', $subdomain)->first();
                            if ($tenantSec) {
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

                // -------------------------------------------------------------
                // A.2 PERPANJANGAN (PREFIX REN- ATAU RENEW-)
                // -------------------------------------------------------------
                else if (Str::startsWith($orderId, 'RENEW-') || Str::startsWith($orderId, 'REN-')) {
                    Log::info("LOG LOG: 1. Masuk blok RENEW untuk Invoice: $orderId");

                    try {
                        $parts = explode('-', $orderId);
                        $subdomain = strtolower($parts[1] ?? '');

                        if (in_array(strtoupper($parts[1]), ['MONTHLY', 'YEARLY', 'QUARTERLY'])) {
                            $subdomain = strtolower($parts[2] ?? '');
                        }

                        $amountPaid = $data['order']['amount'] ?? $data['transaction']['amount'] ?? 0;
                        $amountPaid = (int)$amountPaid;

                        if ($amountPaid >= 1000000) {
                            $days = 365; $newPackage = 'yearly'; $label = "1 Tahun";
                        } elseif ($amountPaid >= 300000) {
                            $days = 90; $newPackage = 'quarterly'; $label = "3 Bulan";
                        } else {
                            $days = 30; $newPackage = 'monthly'; $label = "1 Bulan";
                        }

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

                        $percetakanDB = DB::connection('mysql_second');
                        $tenantSec = $percetakanDB->table('tenants')->where('subdomain', $subdomain)->first();

                        if ($tenantSec) {
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
                // A.2.B TOP UP SALDO POS (POSTOPUP-)
                // -------------------------------------------------------------
                else if (Str::startsWith($orderId, 'POSTOPUP-')) {
                    Log::info("🚀 LOG POS: Webhook Masuk untuk Invoice POS: $orderId");
                    try {
                        $dbSecond = DB::connection('mysql_second');
                        $transaction = $dbSecond->table('top_ups')->where('reference_no', $orderId)->first();

                        if ($transaction) {
                            if ($transaction->status !== 'SUCCESS') {
                                $dbSecond->table('top_ups')->where('id', $transaction->id)->update(['status' => 'SUCCESS', 'updated_at' => now()]);
                                $affected = $dbSecond->table('users')->where('id', $transaction->affiliate_id)->increment('saldo', $transaction->amount);

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

                // =================================================================
                // A.3 LOGIKA UNTUK PESANAN UMUM (SCK-) -> SMART HYBRID SEARCH & DIGITAL EMAIL
                // =================================================================
                else if (Str::startsWith($orderId, 'SCK-')) {
                    Log::info("🛍️ LOG TRX (SCK-): Webhook DOKU Masuk untuk Order: $orderId");

                    // 1. CARI DI DATABASE KEDUA (MARKETPLACE / TENANT - mysql_second) DULU
                    $percetakanDB = DB::connection('mysql_second');

                    // KEMBALI KE ASAL: Di mysql_second kolomnya bernama order_number (Tidak ada parent_invoice)
                    $orderMarketplace = $percetakanDB->table('orders')
                        ->where('order_number', $orderId)
                        ->first();

                    if ($orderMarketplace) {
                        Log::info("➡️ Order $orderId terdeteksi sebagai pesanan Marketplace (mysql_second).");

                        try {
                            if ($orderMarketplace->payment_status !== 'paid') {
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
                                            $kiriminAja = new \App\Services\KiriminAjaService();
                                            $now = now()->timezone('Asia/Jakarta');

                                            $serviceCode = $shipData['code'] ?? 'jne';
                                            $serviceType = $shipData['type'] ?? '';

                                            // Fallback dinamis berdasarkan jenis kurir jika type kosong
                                            if (empty($serviceType)) {
                                                if (in_array(strtolower($serviceCode), ['ninja', 'ninja xpress'])) {
                                                    $serviceType = 'Standard';
                                                } elseif (in_array(strtolower($serviceCode), ['spx', 'shopee express'])) {
                                                    $serviceType = '1'; // Standard SPX
                                                } elseif (strtolower($serviceCode) === 'idx') {
                                                    $serviceType = '00';
                                                } else {
                                                    $serviceType = 'REG';
                                                }
                                            }

                                            if ($now->hour >= 15 || $now->isSunday()) {
                                                $pickupSchedule = $now->addDay()->setTime(9, 0, 0)->format('Y-m-d H:i:s');
                                            } else {
                                                $pickupSchedule = $now->addHours(1)->format('Y-m-d H:i:s');
                                            }

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
                                                    'order_id'                 => $orderId,
                                                    'item_name'                => 'Produk Marketplace',
                                                    'package_type_id'          => 1,
                                                    'destination_name'         => $orderMarketplace->customer_name,
                                                    'destination_phone'        => $orderMarketplace->customer_phone,
                                                    'destination_address'      => $orderMarketplace->destination_address,
                                                    'destination_kecamatan_id' => (int) ($shipData['dist'] ?? 0),
                                                    'destination_kelurahan_id' => (int) ($shipData['sub'] ?? 0),
                                                    'destination_zipcode'      => '00000',
                                                    'weight'                   => (int) ceil($shipData['weight'] ?? 1000),
                                                    'width'                    => 10,
                                                    'height'                   => 10,
                                                    'length'                   => 10,
                                                    'item_value'               => (int) $orderMarketplace->total_price,
                                                    'insurance_amount'         => 0,
                                                    'cod'                      => 0,
                                                    'service'                  => $serviceCode,
                                                    'service_type'             => trim($serviceType),
                                                    'shipping_cost'            => (int) $orderMarketplace->shipping_cost
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
                                    } else {
                                        Log::error("❌ GAGAL NEMBAK KA: Kolom shipping_ref kosong atau bukan JSON.", ['shipping_ref' => $orderMarketplace->shipping_ref]);
                                    }
                                } else {
                                    $tenantOwner = $percetakanDB->table('users')->where('tenant_id', $orderMarketplace->tenant_id)->first();
                                    if ($tenantOwner) {
                                        $percetakanDB->table('users')->where('id', $tenantOwner->id)->increment('saldo', $orderMarketplace->final_price);
                                    }
                                }

                                // KEMBALI KE ASAL: Update cukup berdasarkan ID pesanan yang ketemu
                                $percetakanDB->table('orders')->where('id', $orderMarketplace->id)->update($updateData);

                                Log::info("✅ Status order $orderId di mysql_second diupdate jadi paid & processing.");

                                // ==========================================================
                                // 🔥 TAMBAHAN EMAIL DIGITAL PRODUK UNTUK MARKETPLACE (mysql_second)
                                // ==========================================================
                                try {
                                    $customerEmail = $orderMarketplace->customer_email ?? null;
                                    $customerName = $orderMarketplace->customer_name ?? 'Pelanggan';
                                    $totalAmount = $orderMarketplace->total_price ?? 0;

                                    if (!empty($customerEmail)) {
                                        // Cari data item via Query Builder dengan Join ke tabel products
                                        $orderItems = $percetakanDB->table('order_items')
                                            ->leftJoin('products', 'order_items.product_id', '=', 'products.id')
                                            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
                                            ->select('order_items.*', 'products.name as product_name', 'products.is_digital', 'products.digital_url', 'products.digital_file_path', 'products.image', 'categories.category_group')
                                            ->where('order_items.order_id', $orderMarketplace->id)
                                            ->get();

                                        $rincianDigitalHtml = "";
                                        $adaProdukDigital = false;

                                        foreach ($orderItems as $item) {
                                            $isItemDigital = ($item->is_digital == 1) ||
                                                             in_array(strtolower($item->category_group ?? ''), ['produk_digital', 'jasa']) ||
                                                             str_contains(strtolower($item->type ?? ''), 'digital');

                                            if ($isItemDigital) {
                                                $adaProdukDigital = true;
                                                $aksesLink = "";

                                                if (!empty($item->digital_url)) {
                                                    $aksesLink = $item->digital_url;
                                                } elseif (!empty($item->digital_file_path)) {
                                                    $aksesLink = asset('public/storage/' . $item->digital_file_path);
                                                } elseif (!empty($item->image)) {
                                                    $aksesLink = asset('public/storage/' . $item->image);
                                                }

                                                if (!empty($aksesLink)) {
                                                    $rincianDigitalHtml .= "<li style='margin-bottom: 15px;'>
                                                        <strong style='font-size: 16px;'>{$item->product_name}</strong> (x{$item->quantity})<br>
                                                        <a href='{$aksesLink}' target='_blank' style='display: inline-block; margin-top: 5px; padding: 8px 15px; background-color: #4f46e5; color: #ffffff; text-decoration: none; border-radius: 4px; font-size: 14px;'>📥 Unduh / Akses Produk</a>
                                                    </li>";
                                                } else {
                                                    $rincianDigitalHtml .= "<li style='margin-bottom: 15px;'>
                                                        <strong style='font-size: 16px;'>{$item->product_name}</strong> (x{$item->quantity})<br>
                                                        <i style='color: #6b7280; font-size: 14px;'>Akses file/link sedang disiapkan oleh penjual. Anda akan dihubungi lebih lanjut.</i>
                                                    </li>";
                                                }
                                            }
                                        }

                                        // Eksekusi Pengiriman Email
                                        if ($adaProdukDigital && !empty($rincianDigitalHtml)) {
                                            $emailData = [
                                                'to' => $customerEmail,
                                                'subject' => 'Akses Produk Digital Anda (LUNAS): ' . $orderId,
                                                'body' => "
                                                    <div style='font-family: Helvetica, Arial, sans-serif; color: #333; line-height: 1.6; max-width: 600px; margin: 0 auto;'>
                                                        <h2 style='color: #4f46e5; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px;'>Pembayaran Berhasil! 🎉</h2>
                                                        <p>Halo <b>{$customerName}</b>,</p>
                                                        <p>Terima kasih! Pembayaran untuk pesanan <b>{$orderId}</b> senilai <b>Rp " . number_format($totalAmount, 0, ',', '.') . "</b> telah berhasil dikonfirmasi.</p>
                                                        <p>Berikut adalah akses eksklusif ke produk digital yang Anda beli:</p>
                                                        <ul style='background-color: #f9fafb; padding: 20px 20px 20px 40px; border-radius: 6px; border-left: 5px solid #4f46e5; list-style-type: none;'>
                                                            {$rincianDigitalHtml}
                                                        </ul>
                                                        <p style='margin-top: 20px; font-size: 13px; color: #6b7280;'>Simpan email ini sebagai bukti transaksi. Jika Anda mengalami kendala saat mengakses file atau URL di atas, silakan hubungi penjual terkait.</p>
                                                        <p>Salam hangat,<br><b>Tim Sancaka Marketplace</b></p>
                                                    </div>
                                                "
                                            ];

                                            $emailController = app(\App\Http\Controllers\Admin\EmailController::class);
                                            $emailRequest = new \Illuminate\Http\Request();
                                            $emailRequest->replace($emailData);
                                            $emailController->send($emailRequest);

                                            Log::info("✅ Email link/file produk digital terkirim ke: " . $customerEmail);
                                        } else {
                                            // Fallback Email Reguler jika barangnya fisik
                                            $checkoutController = app(\App\Http\Controllers\CheckoutController::class);
                                            if (method_exists($checkoutController, 'sendTransactionSuccessEmail')) {
                                                $checkoutController->sendTransactionSuccessEmail($customerEmail, $customerName, $orderId, 'Pesanan Marketplace Sancaka', $totalAmount);
                                                Log::info("✅ Email resi fisik terkirim ke: " . $customerEmail);
                                            }
                                        }
                                    }
                                } catch (\Exception $e) {
                                    Log::error("❌ Gagal kirim email lunas produk digital: " . $e->getMessage());
                                }
                                // ==========================================================
                            } else {
                                Log::info("⚠️ Order $orderId sudah berstatus paid sebelumnya.");
                            }
                        } catch (\Exception $e) {
                            Log::error("❌ CRITICAL ERROR WEBHOOK MARKETPLACE:", ['msg' => $e->getMessage()]);
                        }
                    }
                    // 2. JIKA TIDAK ADA DI MARKETPLACE, BARU CARI DI MAIN DB (TOKO UTAMA / EKSPEDISI)
                    else {
                        // UNTUK MYSQL UTAMA, KITA TETAP PERTAHANKAN PENCARIAN PARENT_INVOICE!
                        $pesananTokoUtama = \App\Models\Order::where('parent_invoice', $orderId)
                                                            ->orWhere('invoice_number', $orderId)
                                                            ->first();

                        $pesananEkspedisi = \App\Models\Pesanan::where('nomor_invoice', $orderId)->first();

                        if ($pesananTokoUtama) {
                            Log::info("➡️ Order $orderId terdeteksi di Toko Utama (mysql). Lempar ke CheckoutController.");
                            return App::make(\App\Http\Controllers\CheckoutController::class)->handleDokuCallback($data);
                        }
                        else if ($pesananEkspedisi) {
                            Log::info("➡️ Order $orderId terdeteksi di Ekspedisi/Mobile (mysql). Lempar ke PesananController.");
                            return App::make(\App\Http\Controllers\Admin\PesananController::class)->handleDokuCallback($data);
                        }
                        else {
                            Log::error("❌ WEBHOOK GAGAL: Order $orderId tidak ditemukan di mysql_second maupun mysql!");
                        }
                    }
                }


                // -------------------------------------------------------------
                // A.4 ORDER LISENSI (LISC)
                // -------------------------------------------------------------
                else if (Str::startsWith($orderId, 'LISC-')) {
                    Log::info("🚀 LOG LISENSI: Webhook Masuk untuk Pembelian Lisensi: $orderId");

                    try {
                        $parts = explode('-', $orderId);
                        $packageType = strtoupper($parts[1] ?? 'MONTHLY');
                        $subdomain = strtolower($parts[2] ?? '');
                        $newPackage = strtolower($packageType);

                        Log::info("🔍 Mencari data tenant untuk subdomain '$subdomain' di DB Percetakan...");
                        $percetakanDB = DB::connection('mysql_second');
                        $tenantSec = $percetakanDB->table('tenants')->where('subdomain', $subdomain)->first();

                        $tenantId = null;
                        $userId = null;

                        if ($tenantSec) {
                            $tenantId = $tenantSec->id;
                            $userSec = $percetakanDB->table('users')->where('tenant_id', $tenantId)->first();
                            if ($userSec) {
                                $userId = $userSec->id;
                            } else {
                                Log::warning("⚠️ User untuk tenant '$subdomain' tidak ditemukan, user_id mungkin null.");
                            }
                        } else {
                            Log::error("❌ CRITICAL ERROR: Tenant dengan subdomain '$subdomain' tidak ditemukan. Lisensi tidak bisa dikaitkan secara spesifik.");
                        }

                        $licenseCode = 'SNCK-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4));

                        $durationDays = 30;
                        if ($newPackage === 'half_year') $durationDays = 180;
                        if ($newPackage === 'yearly') $durationDays = 365;

                        LicenseApp2::create([
                            'license_code'  => $licenseCode,
                            'tenant_id'     => $tenantId,
                            'user_id'       => $userId,
                            'package_type'  => $newPackage,
                            'duration_days' => $durationDays,
                            'status'        => 'available'
                        ]);

                        Log::info("✅ KODE LISENSI DIBUAT: $licenseCode untuk paket $newPackage ($durationDays hari) terikat pada Tenant ID: $tenantId, User ID: $userId");

                        if ($tenantSec && !empty($tenantSec->whatsapp)) {
                            $phone = preg_replace('/[^0-9]/', '', $tenantSec->whatsapp);
                            if (str_starts_with($phone, '62')) $phone = '0' . substr($phone, 2);
                            elseif (str_starts_with($phone, '8')) $phone = '0' . $phone;

                            $msg = "🎉 *PEMBAYARAN BERHASIL*\n\n" .
                                   "Terima kasih telah membeli lisensi SancakaPOS!\n" .
                                   "Paket: *" . strtoupper($newPackage) . "*\n\n" .
                                   "🔑 *KODE LISENSI ANDA:*\n" .
                                   "*$licenseCode*\n\n" .
                                   "Silakan *Copy* kode di atas dan masukkan di halaman aktivasi:\n" .
                                   "https://apps.tokosancaka.com/redeem-lisensi?subdomain={$subdomain}";

                            $this->_sendFonnteMessage($phone, $msg);
                            Log::info("✅ Kode Lisensi $licenseCode berhasil dikirim ke WA $phone");
                        } else {
                            Log::error("⚠️ Gagal kirim WA: Nomor WA tidak ditemukan untuk subdomain $subdomain.");
                        }

                    } catch (\Exception $e) {
                        Log::error("❌ CRITICAL ERROR LISC-: " . $e->getMessage());
                    }
                }

                // =============================================================
                // A.5 TIKET PESAWAT (FLT-) --> TAMBAHKAN BLOK INI DI SINI
                // =============================================================
                else if (Str::startsWith($orderId, 'FLT-')) {
                    Log::info("✈️ LOG FLIGHT: Webhook DOKU Masuk untuk Tiket Pesawat: $orderId");
                    try {
                        $parts = explode('-', $orderId);
                        $flightDbId = $parts[1] ?? null;

                        if ($flightDbId) {
                            $orderFlight = DB::table('flight_orders')->where('id', $flightDbId)->first();

                            // Eksekusi jika order ditemukan dan belum berstatus ISSUED
                            if ($orderFlight && $orderFlight->status !== 'ISSUED') {
                                Log::info("Memulai Eksekusi Auto-Issued Pesawat via DOKU untuk Order ID: {$flightDbId}");

                                $ticketingController = app(\App\Http\Controllers\Api\Mobile\TicketingController::class);

                                // Buat Mock Request
                                $reqIssued = new \Illuminate\Http\Request();
                                $reqIssued->replace(['order_id' => $flightDbId]);

                                // Resolusi User
                                $user = \App\Models\User::where('id_pengguna', $orderFlight->user_id)->first();

                                if ($user) {
                                    $reqIssued->setUserResolver(function () use ($user) {
                                        return $user;
                                    });

                                    // TEMBAK! Eksekusi pencetakan tiket ke maskapai
                                    $ticketingController->airlineIssued($reqIssued);
                                    Log::info("Tembakan Auto-Issued untuk PNR {$orderFlight->booking_code} dieksekusi.");
                                } else {
                                    Log::error("User tidak ditemukan untuk order penerbangan ID {$flightDbId}");
                                }
                            } else {
                                Log::info("Order tiket $flightDbId tidak ditemukan atau sudah ISSUED.");
                            }
                        }
                    } catch (\Exception $e) {
                        Log::error("❌ CRITICAL ERROR FLIGHT DOKU WEBHOOK: " . $e->getMessage());
                    }
                }

                // -------------------------------------------------------------
                // A.5 DELEGASI KE CONTROLLER LAIN (TOPUP, ADM, INV, ORD)
                // -------------------------------------------------------------
                else {
                    if (Str::startsWith($orderId, 'TOPUP-') || Str::startsWith($orderId, 'ADM-')) {
                        Log::info("➡️ Order $orderId didelegasikan ke TopUpController.");
                        return App::make(\App\Http\Controllers\Customer\TopUpController::class)->handleDokuCallback($data);
                    } else if (Str::startsWith($orderId, 'INV-')) {
                        return App::make(\App\Http\Controllers\CustomerOrderController::class)->handleDokuCallback($data);
                    } else if (Str::startsWith($orderId, 'CVSANCAK-') || Str::startsWith($orderId, 'ORD-')) {
                        return App::make(\App\Http\Controllers\CheckoutController::class)->handleDokuCallback($data);
                    }
                    else if (Str::startsWith($orderId, 'DANATOPUP-')) {
                        Log::info("➡️ Order $orderId (Top Up DANA) didelegasikan ke TopupDanaController.");
                        // Melempar array $data dari DOKU ke TopupDanaController
                        return App::make(\App\Http\Controllers\Customer\TopupDanaController::class)->handleDokuCallback($data);
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
                        $store->doku_balance_last_updated = null;
                        $store->save();
                        Log::info("💰 Payout Sukses ($sac_id). Cache saldo di-reset.");
                    }
                    else if ($payoutStatus === 'FAILED' || $payoutStatus === 'REVERSED') {
                        $store->doku_balance_available += (int) $amount;
                        $store->doku_balance_last_updated = now();
                        $store->save();
                        Log::warning("⚠️ Payout Gagal ($sac_id). Saldo Rp $amount dikembalikan.");
                    }
                }
            }
        }

        return response()->json(['message' => 'Notification received (Processed)'], 200);
    }

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

        $stringToSign = "Client-Id:" . $clientId . "\n"
                        . "Request-Id:" . $requestId . "\n"
                        . "Request-Timestamp:" . $requestTimestamp . "\n"
                        . "Request-Target:" . $requestTarget . "\n"
                        . "Digest:" . $digest;

        $hmac = hash_hmac('sha256', $stringToSign, $secretKey, true);
        $signature = base64_encode($hmac);

        return "HMACSHA256=" . $signature;
    }

    /* private function _sendFonnteNotification($subdomain)
    {
        try {
            $percetakanDB = \Illuminate\Support\Facades\DB::connection('mysql_second');
            $tenant = $percetakanDB->table('tenants')->where('subdomain', $subdomain)->first();

            if (!$tenant || empty($tenant->whatsapp)) {
                Log::warning("LOG LOG: Gagal kirim WA, nomor WA tenant tidak ditemukan.");
                return;
            }

            $phone = $tenant->whatsapp;
            $phone = preg_replace('/[^0-9]/', '', $phone);

            if (str_starts_with($phone, '62')) {
                $phone = '0' . substr($phone, 2);
            } elseif (str_starts_with($phone, '8')) {
                $phone = '0' . $phone;
            }

            $adminPhone = '085745808809';
            $msg = "💰 *PEMBAYARAN TERKONFIRMASI*\n\n" .
                   "Halo Owner *{$subdomain}*,\n" .
                   "Pembayaran sewa Anda telah kami terima.\n\n" .
                   "Status: *ACTIVE* ✅\n" .
                   "Sistem: *Database SancakaPOS*\n" .
                   "Link Login: https://{$subdomain}.tokosancaka.com/login\n\n" .
                   "_Pesanan Anda sudah bisa diakses. Terima kasih!_";

            $this->_sendFonnteMessage($phone, $msg);
            $this->_sendFonnteMessage($adminPhone, "INFO: Tenant *{$subdomain}* baru saja aktif otomatis via Webhook DOKU.");

            Log::info("LOG LOG: Notifikasi WA Aktivasi Tenant $subdomain dikirim ke $phone.");
        } catch (\Exception $e) {
            Log::error("LOG LOG: Gagal kirim WA Notif: " . $e->getMessage());
        }
    }

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
    } */

    private function _sendFonnteNotification($subdomain, $tipe = 'Aktivasi')
    {
        try {
            $percetakanDB = \Illuminate\Support\Facades\DB::connection('mysql_second');
            $tenant = $percetakanDB->table('tenants')->where('subdomain', $subdomain)->first();

            if (!$tenant || empty($tenant->whatsapp)) {
                Log::warning("LOG LOG: Gagal kirim WA, nomor WA tenant tidak ditemukan.");
                return;
            }

            $phone = $tenant->whatsapp;
            $phone = preg_replace('/[^0-9]/', '', $phone);

            if (str_starts_with($phone, '62')) {
                $phone = '0' . substr($phone, 2);
            } elseif (str_starts_with($phone, '8')) {
                $phone = '0' . $phone;
            }

            // Ambil nomor admin dari ENV, jangan di-hardcode
            $adminPhone = env('ADMIN_PHONE_NUMBER', '081234567890');
            $msg = "💰 *PEMBAYARAN TERKONFIRMASI*\n\n" .
                   "Halo Owner *{$subdomain}*,\n" .
                   "Pembayaran sewa Anda telah kami terima.\n\n" .
                   "Status: *ACTIVE* ✅\n" .
                   "Sistem: *Database SancakaPOS*\n" .
                   "Link Login: https://{$subdomain}.tokosancaka.com/login\n\n" .
                   "_Pesanan Anda sudah bisa diakses. Terima kasih!_";

            $this->_sendFonnteMessage($phone, $msg);
            $this->_sendFonnteMessage($adminPhone, "INFO: Tenant *{$subdomain}* baru saja aktif otomatis via Webhook.");

            Log::info("LOG LOG: Notifikasi WA Aktivasi Tenant $subdomain dikirim ke $phone.");
        } catch (\Exception $e) {
            Log::error("LOG LOG: Gagal kirim WA Notif: " . $e->getMessage());
        }
    }

    private function _sendFonnteMessage($target, $message)
    {
        // Ambil token dari ENV, jangan ditaruh di dalam string kode
        $token = env('FONNTE_API_KEY') ?? env('FONNTE_KEY');
        if (!$token) return;

        \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => $token
        ])->post('https://api.fonnte.com/send', [
            'target' => $target,
            'message' => $message
        ]);
    }

    // =========================================================================
    // FUNGSI KHUSUS UNTUK KIRIM NOTIFIKASI PEMBAYARAN KE EXPO MOBILE
    // =========================================================================
    private function sendExpoPaymentNotification($orderId)
    {
        try {
            $buyerId = null;
            $pushMessages = [];

            // 1. CARI PEMILIK INVOICE DI DATABASE UTAMA
            // A. Cek di tabel Pesanan (Sancaka Express / Mobile)
            $pesanan = DB::table('Pesanan')->where('nomor_invoice', $orderId)->first();
            if ($pesanan) {
                $buyerId = $pesanan->customer_id;
            }

            // B. Cek di tabel Orders (Toko Utama / Checkout)
            if (!$buyerId) {
                $order = DB::table('orders')->where('invoice_number', $orderId)->first();
                if ($order) {
                    $buyerId = $order->user_id;
                }
            }

           // C. Cek di tabel TopUp (Isi Saldo Aplikasi)
            if (!$buyerId && Str::startsWith($orderId, 'TOPUP-')) {
                $topup = DB::table('top_ups')
                            ->where('transaction_id', $orderId) // <--- Cukup cari berdasarkan transaction_id
                            ->first();
                if ($topup) {
                    $buyerId = $topup->customer_id ?? $topup->user_id ?? null;
                }
            }

            // 2. SIAPKAN PESAN UNTUK CUSTOMER (JIKA DITEMUKAN)
            if ($buyerId) {
                $customer = DB::table('Pengguna')->where('id_pengguna', $buyerId)->first();
                if ($customer && !empty($customer->expo_token)) {
                    $pushMessages[] = [
                        'to' => $customer->expo_token,
                        'title' => 'Pembayaran Berhasil! 🎉',
                        'body' => "Yey! Pembayaran untuk pesanan $orderId telah berhasil diverifikasi.",
                        'sound' => 'default',
                    ];
                }
            }

            // 3. SIAPKAN PESAN UNTUK ADMIN (ID 4) SELALU DIKIRIM
            $admin = DB::table('Pengguna')->where('id_pengguna', 4)->first();
            if ($admin && !empty($admin->expo_token)) {
                $pushMessages[] = [
                    'to' => $admin->expo_token,
                    'title' => 'Dana Masuk! 💰',
                    'body' => "Invoice $orderId baru saja sukses dibayar oleh customer.",
                    'sound' => 'default',
                ];
            }

            // 4. TEMBAK SEMUA NOTIFIKASI KE EXPO SEKALIGUS
            if (!empty($pushMessages)) {
                \Illuminate\Support\Facades\Http::withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])->post('https://exp.host/--/api/v2/push/send', $pushMessages);

                Log::info("[WEBHOOK-DOKU] 📲 Notifikasi pembayaran Expo berhasil dikirim untuk $orderId");
            }

        } catch (\Exception $e) {
            Log::error("[WEBHOOK-DOKU] ❌ Gagal kirim notifikasi Expo: " . $e->getMessage());
        }
    }
}
