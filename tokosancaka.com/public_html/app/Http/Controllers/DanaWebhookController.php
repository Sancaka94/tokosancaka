<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Exception;

use App\Models\Order;
use App\Models\TopUp;
use App\Models\Transaction;
use App\Models\Store;
use App\Models\LicenseApp2;

class DanaWebhookController extends Controller
{
    // =================================================================
    // 1. HANDLER WEBHOOK DANA
    // =================================================================
    public function handleNotify(Request $request)
    {
        Log::info('========== DANA WEBHOOK INCOMING ==========');

        // --- [DD: LOG DETAIL REQUEST] ---
        Log::info('========== [DEBUG WEBHOOK DANA] ==========');
        Log::info('IP PENGIRIM: ' . $request->ip());
        Log::info('FULL URL: ' . $request->fullUrl());
        Log::info('HEADERS: ', $request->headers->all());
        Log::info('PAYLOAD (BODY): ', $request->all());
        Log::info('==========================================');
        // --------------------------------

        $orderId = $request->input('partnerReferenceNo') ?? $request->input('originalPartnerReferenceNo');
        $statusDana = $request->input('latestTransactionStatus');
        $amountValue = $request->input('amount.value') ?? 0;

        // --- MENGEMBALIKAN STRIP (-) PADA INVOICE ---
        if ($orderId) {
            $orderId = $this->normalizeReference($orderId);
        }
        // -----------------------------------------------------------------------

        // Standar Timestamp untuk balasan ke DANA
        $danaTimestamp = \Carbon\Carbon::now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');

        if (!$orderId) {
            Log::warning("Webhook DANA: orderId kosong.");
            return response()->json([
                'responseCode' => '4005600',
                'responseMessage' => 'Bad Request'
            ])->withHeaders(['X-TIMESTAMP' => $danaTimestamp]);
        }

        Log::info("Memproses Invoice: $orderId | Status DANA: $statusDana");

        // =================================================================
        // DATA PAYLOAD: Diseragamkan untuk diteruskan ke controller terkait
        // =================================================================
        $internalStatus = in_array(strtoupper($statusDana), ['00', 'SUCCESS']) ? 'SUCCESS' : 'FAILED';

        $payloadData = [
            'order' => [
                'invoice_number' => $orderId,
                'amount' => $amountValue
            ],
            'transaction' => [
                'status' => $internalStatus
            ]
        ];

        DB::beginTransaction();
        try {
            if ($internalStatus === 'SUCCESS') {

                // Kirim notifikasi pembayaran ke Expo Mobile (Customer & Admin)
                // $this->sendExpoPaymentNotification($orderId);

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
                    Log::info("LOG LOG: Masuk blok RENEW untuk Invoice: $orderId");
                    try {
                        $parts = explode('-', $orderId);
                        $subdomain = strtolower($parts[1] ?? '');

                        if (in_array(strtoupper($parts[1]), ['MONTHLY', 'YEARLY', 'QUARTERLY'])) {
                            $subdomain = strtolower($parts[2] ?? '');
                        }

                        $amountPaid = (int)$amountValue;

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
                        $transactionPos = $dbSecond->table('top_ups')->where('reference_no', $orderId)->first();

                        if ($transactionPos) {
                            if ($transactionPos->status !== 'SUCCESS') {
                                $dbSecond->table('top_ups')->where('id', $transactionPos->id)->update(['status' => 'SUCCESS', 'updated_at' => now()]);
                                $affected = $dbSecond->table('users')->where('id', $transactionPos->affiliate_id)->increment('saldo', $transactionPos->amount);

                                if ($affected) {
                                    Log::info("💰 SALDO POS BERTAMBAH: User ID {$transactionPos->affiliate_id} +{$transactionPos->amount}");
                                
                                    $this->sendExpoPaymentNotification($orderId);
                                
                                    } else {
                                    Log::error("❌ Gagal Update Saldo User ID {$transactionPos->affiliate_id} di DB Second.");
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
                // A.3 LOGIKA UNTUK PESANAN UMUM (SCK-) -> TOKO UTAMA, EKSPEDISI & MARKETPLACE
                // -------------------------------------------------------------
                else if (Str::startsWith($orderId, 'SCK-')) {
                    Log::info("🛍️ LOG TRX (SCK-): Webhook DANA Masuk untuk Order: $orderId");

                    $pesananEkspedisi = \App\Models\Pesanan::where('nomor_invoice', $orderId)->first();
                    $pesananTokoUtama = \App\Models\Order::where('invoice_number', $orderId)->first();

                    if ($pesananEkspedisi) {
                        Log::info("➡️ Order $orderId terdeteksi sebagai pesanan Sancaka Express/Mobile.");
                        App::make(\App\Http\Controllers\Admin\PesananController::class)->handleDanaCallback($payloadData);
                    }
                    else if ($pesananTokoUtama) {
                        Log::info("➡️ Order $orderId terdeteksi sebagai pesanan Toko Utama (Checkout Sancaka).");
                        App::make(\App\Http\Controllers\CheckoutController::class)->handleDanaCallback($payloadData);
                    }
                    else {
                        Log::info("➡️ Order $orderId terdeteksi sebagai pesanan Marketplace (mysql_second).");
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
                                    Log::info("🔒 ESCROW AKTIF: Order $orderId. Menyiapkan Payload API KiriminAja...");

                                    $shipData = json_decode($orderMarketplace->shipping_ref, true);

                                    if (is_array($shipData)) {
                                        $tenantOwner = $percetakanDB->table('users')->where('tenant_id', $orderMarketplace->tenant_id)->first();

                                        if ($tenantOwner) {
                                            $kiriminAja = new \App\Services\KiriminAjaService();
                                            $now = now()->timezone('Asia/Jakarta');

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
                                                    'service'                  => $shipData['code'] ?? 'jne',
                                                    'service_type'             => $shipData['type'] ?? 'REG',
                                                    'shipping_cost'            => (int) $orderMarketplace->shipping_cost
                                                ]]
                                            ];

                                            Log::info("🚀 Menembak API KiriminAja via Service...");
                                            $kaResponse = $kiriminAja->createExpressOrder($payload);

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
                                    $tenantOwner = $percetakanDB->table('users')->where('tenant_id', $orderMarketplace->tenant_id)->first();
                                    if ($tenantOwner) {
                                        $percetakanDB->table('users')->where('id', $tenantOwner->id)->increment('saldo', $orderMarketplace->final_price);
                                    }
                                }

                                $percetakanDB->table('orders')->where('id', $orderMarketplace->id)->update($updateData);
                                Log::info("✅ Selesai memproses Webhook untuk $orderId");

                                $this->sendExpoPaymentNotification($orderId);

                            }
                        } catch (\Exception $e) {
                            Log::error("❌ CRITICAL ERROR WEBHOOK MARKETPLACE:", ['msg' => $e->getMessage()]);
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

                        $percetakanDB = DB::connection('mysql_second');
                        $tenantSec = $percetakanDB->table('tenants')->where('subdomain', $subdomain)->first();

                        $tenantId = null;
                        $userId = null;

                        if ($tenantSec) {
                            $tenantId = $tenantSec->id;
                            $userSec = $percetakanDB->table('users')->where('tenant_id', $tenantId)->first();
                            if ($userSec) {
                                $userId = $userSec->id;
                            }
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

                        Log::info("✅ KODE LISENSI DIBUAT: $licenseCode untuk paket $newPackage");

                        $this->sendExpoPaymentNotification($orderId);

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
                        }
                    } catch (\Exception $e) {
                        Log::error("❌ CRITICAL ERROR LISC-: " . $e->getMessage());
                    }
                }

                // -------------------------------------------------------------
                // A.5 DELEGASI KE CONTROLLER LAIN (TOPUP, ADM, INV, ORD, PPOB)
                // -------------------------------------------------------------
                else {
                    if (Str::startsWith($orderId, 'TOPUP-') || Str::startsWith($orderId, 'ADM-')) {
                        Log::info("➡️ Order $orderId didelegasikan ke TopUpController.");
                        App::make(\App\Http\Controllers\Customer\TopUpController::class)->handleDanaCallback($payloadData);
                    } else if (Str::startsWith($orderId, 'INV-')) {
                        App::make(\App\Http\Controllers\CustomerOrderController::class)->handleDanaCallback($payloadData);
                    } else if (Str::startsWith($orderId, 'CVSANCAK-') || Str::startsWith($orderId, 'ORD-')) {
                        App::make(\App\Http\Controllers\CheckoutController::class)->handleDanaCallback($payloadData);
                    } 
                    // =========================================================
                    // TAMBAHKAN BLOK INI: PENANGANAN SUCCESS DANATOPUP (TOP UP DANA)
                    // =========================================================
                    else if (Str::startsWith($orderId, 'DANATOPUP-')) {
                        $danaTopup = DB::table('dana_transaction_topup')->where('reference_id', $orderId)->first();
                        
                        if ($danaTopup) {
                            if ($danaTopup->status !== 'SUCCESS') {
                                DB::table('dana_transaction_topup')->where('id', $danaTopup->id)->update([
                                    'status' => 'SUCCESS', 
                                    'updated_at' => now()
                                ]);
                                Log::info("✅ LOG LOG: Webhook Top Up DANA Pelanggan ($orderId) SUKSES.");

                                $this->sendExpoPaymentNotification($orderId);

                                $user = DB::table('Pengguna')->where('id_pengguna', $danaTopup->user_id)->first();
                                if ($user) {
                                    $this->_sendEmailNotification($user->email, $user->nama_lengkap, $orderId, 'Top Up DANA', $danaTopup->amount);
                                }
                            } else {
                                Log::info("⚠️ Transaksi Top Up DANA $orderId sudah diproses sebelumnya.");
                            }
                        } else {
                            Log::warning("⚠️ Order $orderId (DANATOPUP) tidak ditemukan di tabel dana_transaction_topup.");
                        }
                    }
                    // =========================================================
                    else if (Str::startsWith($orderId, 'TRF') || Str::startsWith($orderId, 'TUP')) {
                        // PENANGANAN DISBURSEMENT (TRANSFER BANK / TOP UP CORPORATE)
                        $danaTrx = DB::table('dana_transactions')->where('reference_no', $orderId)->first();
                        
                        if ($danaTrx) {
                            // CEK IDEMPOTENCY: Pastikan belum sukses sebelumnya
                            if ($danaTrx->status !== 'SUCCESS') {
                                DB::table('dana_transactions')->where('id', $danaTrx->id)->update([
                                    'status' => 'SUCCESS', 
                                    'updated_at' => now()
                                ]);
                                Log::info("✅ LOG LOG: Webhook Disbursement DANA $orderId SUKSES.");

                                $this->sendExpoPaymentNotification($orderId);

                                $user = DB::table('Pengguna')->where('id_pengguna', $danaTrx->affiliate_id)->first();
                                if ($user) {
                                    $jenis = Str::startsWith($orderId, 'TRF') ? 'Pencairan Dana (Transfer Bank)' : 'Pencairan Dana (Top Up)';
                                    $this->_sendEmailNotification($user->email, $user->nama_lengkap, $orderId, $jenis, $danaTrx->amount);
                                }

                                // Panggil notif HP di sini agar tidak dobel
                                $this->sendExpoPaymentNotification($orderId);
                            } else {
                                Log::info("⚠️ Transaksi Disbursement $orderId sudah diproses sebelumnya. Skip email & notif.");
                            }
                        } else {
                            Log::warning("⚠️ Order $orderId (DANA Disbursement) tidak ditemukan di tabel dana_transactions.");
                        }
                    } else {
                        // PENANGANAN PPOB BERDASARKAN TABEL transactionppobiak
                        $trxPpob = DB::table('transactionppobiak')
                            ->where('ref_id', $orderId)
                            ->orWhere('ref_id', str_replace('PASCA', '', $orderId))
                            ->first();

                        if ($trxPpob) {
                            // CEK IDEMPOTENCY: Pastikan belum sukses sebelumnya
                            if ($trxPpob->status !== 'SUCCESS') {
                                DB::table('transactionppobiak')->where('id', $trxPpob->id)->update(['status' => 'SUCCESS']);
                                Log::info("LOG LOG: Webhook PPOB $orderId SUKSES.");

                                $this->sendExpoPaymentNotification($orderId);

                                $user = DB::table('Pengguna')->where('id_pengguna', $trxPpob->user_id)->first();
                                if ($user) {
                                    $this->_sendEmailNotification($user->email, $user->nama_lengkap, $orderId, 'Pembayaran Produk Digital (PPOB)', $trxPpob->price ?? $amountValue);
                                }

                                // Panggil notif HP di sini agar tidak dobel
                                $this->sendExpoPaymentNotification($orderId);
                            } else {
                                Log::info("⚠️ Transaksi PPOB $orderId sudah diproses sebelumnya. Skip email & notif.");
                            }
                        } else {
                            Log::warning("⚠️ Order $orderId tidak dikenali oleh sistem (Tidak ada di DB Utama, TopUp, maupun PPOB).");
                        }
                    }
                }

            } else {
                // =================================================================
                // JIKA STATUS GAGAL (FAILED / EXPIRED / DENY)
                // =================================================================
                Log::info("DANA Webhook: Status Transaksi bukan SUCCESS ($statusDana). Memproses pembatalan...");

                if (Str::startsWith($orderId, 'SCK-')) {
                    $pesananEkspedisi = \App\Models\Pesanan::where('nomor_invoice', $orderId)->first();
                    if ($pesananEkspedisi) {
                        App::make(\App\Http\Controllers\Admin\PesananController::class)->handleDanaCallback($payloadData);
                    }
                } elseif (Str::startsWith($orderId, 'TOPUP-') || Str::startsWith($orderId, 'ADM-')) {
                    App::make(\App\Http\Controllers\Customer\TopUpController::class)->handleDanaCallback($payloadData);
                } 
                // =========================================================
                // TAMBAHKAN BLOK INI: PENANGANAN FAILED DANATOPUP (TOP UP DANA)
                // =========================================================
                elseif (Str::startsWith($orderId, 'DANATOPUP-')) {
                    $danaTopup = DB::table('dana_transaction_topup')->where('reference_id', $orderId)->first();
                    
                    if ($danaTopup && !in_array($danaTopup->status, ['FAILED', 'FAILED_DANA'])) {
                        DB::table('dana_transaction_topup')->where('id', $danaTopup->id)->update([
                            'status' => 'FAILED_DANA', 
                            'updated_at' => now()
                        ]);
                        
                        // CEK METODE PEMBAYARAN: Hanya refund jika menggunakan Potong Saldo
                        $metodeBayar = strtoupper($danaTopup->payment_method);
                        if (in_array($metodeBayar, ['POTONG SALDO', 'SALDO', 'POTONG_SALDO'])) {
                            DB::table('Pengguna')->where('id_pengguna', $danaTopup->user_id)->increment('saldo', $danaTopup->amount);
                            Log::info("❌ LOG LOG: Webhook DANATOPUP $orderId GAGAL/EXPIRED. Saldo Rp " . number_format($danaTopup->amount, 0, ',', '.') . " di-REFUND ke user ID {$danaTopup->user_id} karena metode bayar adalah: $metodeBayar.");
                        } else {
                            // Jika pakai Payment Gateway (Tripay/DOKU), uang ada di PG. 
                            // Kamu bisa mengatur apakah akan menambahkannya ke saldo akun atau membiarkannya untuk direfund manual.
                            // Contoh: Tetap masuk saldo akun sebagai deposit.
                            DB::table('Pengguna')->where('id_pengguna', $danaTopup->user_id)->increment('saldo', $danaTopup->amount);
                            Log::info("❌ LOG LOG: Webhook DANATOPUP $orderId GAGAL/EXPIRED. User membayar via $metodeBayar. Dana telah dimasukkan ke saldo akun sebagai kompensasi kegagalan DANA.");
                        }
                    }
                }
                // =========================================================
                elseif (Str::startsWith($orderId, 'TRF') || Str::startsWith($orderId, 'TUP')) {
                    // PENANGANAN GAGAL DISBURSEMENT (TRANSFER BANK / TOP UP CORPORATE)
                    $danaTrx = DB::table('dana_transactions')->where('reference_no', $orderId)->first();
                    
                    if ($danaTrx && $danaTrx->status !== 'FAILED') {
                        DB::table('dana_transactions')->where('id', $danaTrx->id)->update([
                            'status' => 'FAILED', 
                            'updated_at' => now()
                        ]);
                        // Refund saldo pelanggan secara otomatis
                        DB::table('Pengguna')->where('id_pengguna', $danaTrx->affiliate_id)->increment('saldo', $danaTrx->amount);
                        Log::info("❌ LOG LOG: Webhook Disbursement DANA $orderId GAGAL/EXPIRED. Saldo Rp " . number_format($danaTrx->amount, 0, ',', '.') . " dikembalikan ke user ID {$danaTrx->affiliate_id}.");
                    }
                } else {
                    $trxPpob = DB::table('transactionppobiak')
                        ->where('ref_id', $orderId)
                        ->orWhere('ref_id', str_replace('PASCA', '', $orderId))
                        ->first();

                    if ($trxPpob) {
                        DB::table('transactionppobiak')->where('id', $trxPpob->id)->update(['status' => 'FAILED']);
                        Log::info("LOG LOG: Webhook PPOB $orderId GAGAL/EXPIRED.");
                    }
                }
            }

            DB::commit();

            // Format respons sukses universal standar SNAP BI
            $responseBody = [
                'responseCode' => '2000000',
                'responseMessage' => 'Success'
            ];

            // Wajib mengembalikan/echo header X-PARTNER-ID dan X-EXTERNAL-ID agar DANA tahu kita merespons dengan benar
            return response()->json($responseBody)->withHeaders([
                'X-TIMESTAMP'   => $danaTimestamp,
                'X-PARTNER-ID'  => $request->header('X-PARTNER-ID'),
                'X-EXTERNAL-ID' => $request->header('X-EXTERNAL-ID'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("LOG LOG: Webhook Error: " . $e->getMessage());
            return response()->json([
                'responseCode' => '5005601',
                'responseMessage' => 'Internal Server Error'
            ], 500)->withHeaders(['X-TIMESTAMP' => $danaTimestamp]);
        }
    }

    // =================================================================
    // 2. HELPER FUNCTIONS
    // =================================================================
    private function _sendFonnteNotification($subdomain, $tipe = 'Aktivasi')
    {
        try {
            $percetakanDB = DB::connection('mysql_second');
            $tenant = $percetakanDB->table('tenants')->where('subdomain', $subdomain)->first();

            if (!$tenant || empty($tenant->whatsapp)) {
                return;
            }

            $phone = preg_replace('/[^0-9]/', '', $tenant->whatsapp);
            if (str_starts_with($phone, '62')) $phone = '0' . substr($phone, 2);
            elseif (str_starts_with($phone, '8')) $phone = '0' . $phone;

            $adminPhone = '085745808809';
            $msg = "💰 *PEMBAYARAN TERKONFIRMASI*\n\n" .
                   "Halo Owner *{$subdomain}*,\n" .
                   "Pembayaran sewa ($tipe) Anda telah kami terima.\n\n" .
                   "Status: *ACTIVE* ✅\n" .
                   "Sistem: *Database SancakaPOS*\n" .
                   "Link Login: https://{$subdomain}.tokosancaka.com/login\n\n" .
                   "_Pesanan Anda sudah bisa diakses. Terima kasih!_";

            $this->_sendFonnteMessage($phone, $msg);
            $this->_sendFonnteMessage($adminPhone, "INFO: Tenant *{$subdomain}* baru saja aktif ($tipe) via Webhook DANA.");

        } catch (\Exception $e) {
            Log::error("LOG LOG: Gagal kirim WA Notif: " . $e->getMessage());
        }
    }

    private function _sendFonnteMessage($target, $message)
    {
        $token = env('FONNTE_API_KEY') ?? env('FONNTE_KEY') ?? 'cC3LrEd8VwDDRuE6urcj';
        if (!$token) return;

        \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => $token
        ])->post('https://api.fonnte.com/send', [
            'target' => $target,
            'message' => $message
        ]);
    }

    private function sendExpoPaymentNotification($orderId)
    {
        try {
            $buyerId = null;
            $pushMessages = [];

            // 1. CARI PEMILIK INVOICE
            $pesanan = DB::table('Pesanan')->where('nomor_invoice', $orderId)->first();
            if ($pesanan) $buyerId = $pesanan->customer_id;

            if (!$buyerId) {
                $order = DB::table('orders')->where('invoice_number', $orderId)->first();
                if ($order) $buyerId = $order->user_id;
            }

            if (!$buyerId && Str::startsWith($orderId, 'TOPUP-')) {
                $topup = DB::table('top_ups')->where('transaction_id', $orderId)->first();
                if ($topup) $buyerId = $topup->customer_id ?? $topup->user_id ?? null;
            }

            // Cari Pemilik untuk Transaksi Disbursement (Transfer Bank & Top Up Corporate)
            if (!$buyerId && (Str::startsWith($orderId, 'TRF') || Str::startsWith($orderId, 'TUP'))) {
                $danaTrx = DB::table('dana_transactions')->where('reference_no', $orderId)->first();
                if ($danaTrx) $buyerId = $danaTrx->affiliate_id;
            }

            // 2. SIAPKAN PESAN CUSTOMER
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

            // 3. SIAPKAN PESAN ADMIN
            $admin = DB::table('Pengguna')->where('id_pengguna', 4)->first();
            if ($admin && !empty($admin->expo_token)) {
                $pushMessages[] = [
                    'to' => $admin->expo_token,
                    'title' => 'Dana Masuk! 💰',
                    'body' => "Invoice $orderId baru saja sukses dibayar.",
                    'sound' => 'default',
                ];
            }

            // 4. TEMBAK PUSH NOTIFIKASI
            if (!empty($pushMessages)) {
                \Illuminate\Support\Facades\Http::withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])->post('https://exp.host/--/api/v2/push/send', $pushMessages);

                Log::info("[WEBHOOK-DANA] 📲 Notifikasi Expo berhasil dikirim untuk $orderId");
            }

        } catch (\Exception $e) {
            Log::error("[WEBHOOK-DANA] ❌ Gagal kirim notifikasi Expo: " . $e->getMessage());
        }
    }

    private function normalizeReference($refNo)
    {
        if (Str::startsWith($refNo, 'SCKORD') && !str_contains($refNo, '-')) {
            return 'SCK-ORD-' . substr($refNo, 6);
        }
        if (Str::startsWith($refNo, 'TOPUP') && !str_contains($refNo, '-')) {
            return 'TOPUP-' . substr($refNo, 5);
        }
        // TAMBAHKAN BARIS INI UNTUK DANATOPUP
        if (Str::startsWith($refNo, 'DANATOPUP') && !str_contains($refNo, '-')) {
            return 'DANATOPUP-' . substr($refNo, 9);
        }
        if (preg_match('/^SCK(\d{8})([A-Z0-9]+)$/', $refNo, $matches)) {
            return 'SCK-' . $matches[1] . '-' . $matches[2];
        }
        return trim($refNo);
    }

    // =========================================================
    // UNIVERSAL RETURN PAGE (DANA CALLBACK/RETURN) - SMART HUB
    // =========================================================
    public function returnPage(Request $request)
    {
        Log::info('[DANA RETURN PAGE] Hit Smart Hub', [
            'query'      => $request->all(),
            'full_url'   => $request->fullUrl(),
            'user_agent' => $request->userAgent(),
        ]);

        try {
            $refNo = $request->query('trx_id')
                  ?? $request->partnerReferenceNo
                  ?? $request->bizNo
                  ?? $request->id
                  ?? session('last_dana_ref')
                  ?? '';

            if (!$refNo) {
                Log::warning('[DANA RETURN] REF EMPTY');
                return redirect('/')->with('error', 'Transaksi tidak ditemukan.');
            }

            $refNo = $this->normalizeReference($refNo);

            $isMobile = false;
            if (preg_match('/Mobile|Android|BlackBerry|iPhone|iPad|iPod|Windows Phone/i', $request->userAgent())) {
                $isMobile = true;
            } elseif ($request->query('platform') === 'mobile' || $request->header('X-Platform') === 'mobile') {
                $isMobile = true;
            }

            Log::info('[DANA RETURN] Normal_Ref: ' . $refNo . ' | Platform: ' . ($isMobile ? 'MOBILE/HP' : 'WEB/DESKTOP'));

            Session::forget('last_dana_ref');

            $statusPembayaran = 'pending';
            $jenisTransaksi = 'unknown';

            if (Str::startsWith($refNo, 'SCK-')) {
                $jenisTransaksi = 'pesanan_ekspedisi';
                $order = \App\Models\Pesanan::where('nomor_invoice', $refNo)->first();

                if($order && in_array($order->status_pesanan, ['Pesanan Dibuat', 'Lunas', 'Diproses', 'Menunggu Pickup'])) {
                    $statusPembayaran = 'sukses';
                }
            }
            elseif (Str::startsWith($refNo, 'ORD-') || Str::startsWith($refNo, 'CVSANCAK-')) {
                $jenisTransaksi = 'pesanan_marketplace';
                $orderUtama = \App\Models\Order::where('invoice_number', $refNo)->first();

                if ($orderUtama) {
                    if($orderUtama->payment_status == 'paid') $statusPembayaran = 'sukses';
                } else {
                    try {
                        $orderMarketplace = DB::connection('mysql_second')->table('orders')->where('order_number', $refNo)->first();
                        if ($orderMarketplace && $orderMarketplace->payment_status == 'paid') {
                            $statusPembayaran = 'sukses';
                        }
                    } catch (\Exception $e) {}
                }
            }
            elseif (Str::startsWith($refNo, 'TOPUP-')) {
                $jenisTransaksi = 'topup';
                $topup = \App\Models\TopUp::where('transaction_id', $refNo)->first();
                if ($topup && in_array(strtolower($topup->status), ['success', 'sukses'])) {
                    $statusPembayaran = 'sukses';
                } else {
                    $trx = \App\Models\Transaction::where('reference_id', $refNo)->orWhere('ref_id', $refNo)->first();
                    if ($trx && in_array(strtolower($trx->status), ['success', 'sukses'])) {
                        $statusPembayaran = 'sukses';
                    }
                }
            }
            else {
                $trx = DB::table('transactionppobiak')
                    ->where('ref_id', $refNo)
                    ->orWhere('ref_id', str_replace('PASCA', '', $refNo))
                    ->first();

                if ($trx) {
                    $jenisTransaksi = 'ppob';
                    if (in_array(strtolower($trx->status), ['success', 'sukses', 'paid'])) {
                        $statusPembayaran = 'sukses';
                    }
                }
            }

            return view('pembayaran_suksesdana', [
                'refNo' => $refNo,
                'isMobile' => $isMobile,
                'statusPembayaran' => $statusPembayaran,
                'jenisTransaksi' => $jenisTransaksi
            ]);

        } catch (Exception $e) {
            Log::error('[DANA RETURN PAGE ERROR]', ['msg' => $e->getMessage()]);
            return redirect('/')->with('error', 'Terjadi kesalahan sistem.');
        }
    }

    /**
     * Helper untuk mengirim Email Sukses (Background)
     */
    private function _sendEmailNotification($email, $name, $invoice, $type, $amount)
    {
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) return;

        try {
            $subject = "✅ Pembayaran Berhasil - Invoice $invoice";
            
            $data = [
                'name' => $name,
                'invoice' => $invoice,
                'type' => $type,
                'amount' => $amount,
                'date' => now()->timezone('Asia/Jakarta')->format('d M Y, H:i:s')
            ];

            // Render view blade menjadi format HTML string
            $htmlBody = view('emails.transaction_success', $data)->render();

            \Illuminate\Support\Facades\Mail::html($htmlBody, function ($message) use ($email, $subject) {
                $message->to($email)
                        ->subject($subject)
                        ->from(config('mail.from.address', 'admin@tokosancaka.com'), config('mail.from.name', 'Sancaka Server'));
            });

            Log::info("📧 [EMAIL SENT] Notifikasi sukses dikirim ke: $email untuk invoice $invoice");
        } catch (\Exception $e) {
            Log::error("❌ [EMAIL FAILED] Gagal kirim email ke $email: " . $e->getMessage());
        }
    }
}
