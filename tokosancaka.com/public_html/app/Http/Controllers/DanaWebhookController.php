<?php

namespace App\Http\Controllers;
use Exception;

use App\Http\Controllers\Admin\PesananController as AdminPesananController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Customer\TopUpController;

use App\Models\Order;
use App\Models\TopUp;
use App\Models\Transaction;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Str;

class DanaWebhookController extends Controller
{
   public function handleNotify(Request $request)
    {
        Log::info('========== DANA WEBHOOK (Mobile Gateway - ALL INCLUSIVE) ==========');

        $trxIdFromDana = $request->input('partnerReferenceNo') ?? $request->input('originalPartnerReferenceNo');
        $statusDana    = $request->input('latestTransactionStatus');

        // Cari data transaksi di tabel penjembatan (transactions)
        $transaction = \App\Models\Transaction::where('reference_id', $trxIdFromDana)->lockForUpdate()->first();

        // Siapkan standar timestamp GMT+7 sesuai dokumentasi DANA SNAP
        $danaTimestamp = Carbon::now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');

        if (!$transaction) {
            Log::info("Webhook DANA: ID $trxIdFromDana tidak ditemukan di tabel transactions.");
            return response()->json([
                'responseCode' => '2005600',
                'responseMessage' => 'Successful'
            ])->withHeaders(['X-TIMESTAMP' => $danaTimestamp]); // <-- FIX TIMESTAMP
        }

        DB::beginTransaction();
        try {
            if ($transaction->status == 'pending') {

                // ==========================================================
                // JIKA PEMBAYARAN SUKSES (00 / SUCCESS)
                // ==========================================================
                if ($statusDana == '00' || strtoupper($statusDana) === 'SUCCESS') {
                    Log::info("LOG LOG: Webhook $trxIdFromDana SUKSES.");

                    $transaction->status = 'success';
                    $transaction->save();

                    // -------------------------------------------------------------
                    // 1. PESANAN SANCAKA EXPRESS (SCK-)
                    // -------------------------------------------------------------
                    if (\Illuminate\Support\Str::startsWith($trxIdFromDana, 'SCK-')) {
                        Log::info("LOG LOG: Eksekusi pesanan ekspedisi $trxIdFromDana");
                        \App\Http\Controllers\Admin\PesananController::processPesananCallback($trxIdFromDana, 'PAID', []);
                    }

                    // -------------------------------------------------------------
                    // 2. TOP UP SALDO SANCAKA PUSAT (TOPUP- / ADM-)
                    // -------------------------------------------------------------
                    elseif (\Illuminate\Support\Str::startsWith($trxIdFromDana, 'TOPUP-') || \Illuminate\Support\Str::startsWith($trxIdFromDana, 'ADM-')) {
                        $user = \App\Models\User::where('id_pengguna', $transaction->user_id)->first();
                        if ($user) {
                            $user->increment('saldo', $transaction->amount);

                            // Update tabel top_ups
                            \DB::table('top_ups')->where('transaction_id', $trxIdFromDana)->update([
                                'status' => 'success',
                                'updated_at' => now()
                            ]);
                            Log::info("LOG LOG: Top Up Sancaka Sukses. Saldo User {$user->id_pengguna} ditambahkan Rp{$transaction->amount}.");
                        }
                    }

                    // -------------------------------------------------------------
                    // 3. PESANAN TOKO UTAMA & MARKETPLACE (ORD- / CVSANCAK-)
                    // -------------------------------------------------------------
                    elseif (\Illuminate\Support\Str::startsWith($trxIdFromDana, 'ORD-') || \Illuminate\Support\Str::startsWith($trxIdFromDana, 'CVSANCAK-')) {

                        $orderUtama = \App\Models\Order::where('invoice_number', $trxIdFromDana)->first();

                        // A. Cek di Database Toko Utama (Sancaka)
                        if ($orderUtama) {
                            $orderUtama->update(['payment_status' => 'paid', 'status' => 'processing']);
                            Log::info("LOG LOG: Order Toko Utama $trxIdFromDana Lunas.");
                        }
                        // B. Cek di Database Marketplace (mysql_second)
                        else {
                            try {
                                $percetakanDB = \DB::connection('mysql_second');
                                $orderMarketplace = $percetakanDB->table('orders')->where('order_number', $trxIdFromDana)->first();

                                if ($orderMarketplace && $orderMarketplace->payment_status !== 'paid') {
                                    $updateData = [
                                        'payment_status' => 'paid',
                                        'status' => 'processing',
                                        'updated_at' => now()->timezone('Asia/Jakarta')
                                    ];

                                    // Sistem Rekber / Escrow
                                    if ($orderMarketplace->is_escrow == 1) {
                                        $updateData['escrow_status'] = 'held';
                                    } else {
                                        // Tambah saldo ke pemilik toko jika bukan escrow
                                        $tenantOwner = $percetakanDB->table('users')->where('tenant_id', $orderMarketplace->tenant_id)->first();
                                        if ($tenantOwner) {
                                            $percetakanDB->table('users')->where('id', $tenantOwner->id)->increment('saldo', $orderMarketplace->final_price);
                                        }
                                    }

                                    $percetakanDB->table('orders')->where('id', $orderMarketplace->id)->update($updateData);
                                    Log::info("LOG LOG: Marketplace Order $trxIdFromDana Lunas & Saldo Tenant Diselesaikan.");
                                }
                            } catch (\Exception $e) {
                                Log::error("LOG LOG: Gagal update DB Marketplace - " . $e->getMessage());
                            }
                        }
                    }

                    // -------------------------------------------------------------
                    // 4. TOP UP SALDO KASIR POS / TENANT (POSTOPUP-)
                    // -------------------------------------------------------------
                    elseif (\Illuminate\Support\Str::startsWith($trxIdFromDana, 'POSTOPUP-')) {
                        try {
                            $dbSecond = \DB::connection('mysql_second');
                            $topupPos = $dbSecond->table('top_ups')->where('reference_no', $trxIdFromDana)->first();

                            if ($topupPos && $topupPos->status !== 'SUCCESS') {
                                $dbSecond->table('top_ups')->where('id', $topupPos->id)->update(['status' => 'SUCCESS', 'updated_at' => now()]);
                                $dbSecond->table('users')->where('id', $topupPos->affiliate_id)->increment('saldo', $topupPos->amount);
                                Log::info("LOG LOG: POS Topup $trxIdFromDana Sukses.");
                            }
                        } catch (\Exception $e) {
                            Log::error("LOG LOG: POS Topup Error - " . $e->getMessage());
                        }
                    }

                    // -------------------------------------------------------------
                    // 5. PERPANJANGAN LISENSI POS (SEWA- / REN- / LISC-)
                    // -------------------------------------------------------------
                    elseif (\Illuminate\Support\Str::startsWith($trxIdFromDana, 'SEWA-') || \Illuminate\Support\Str::startsWith($trxIdFromDana, 'REN-') || \Illuminate\Support\Str::startsWith($trxIdFromDana, 'LISC-')) {
                        // Karena skrip Lisensi panjang dan cukup kompleks, lebih rapi jika di-delegate
                        // ke fungsi yang sudah ada (bisa ke DokuRegistrationController atau fungsi sejenis)
                        Log::info("LOG LOG: Invoice Lisensi POS $trxIdFromDana sukses dibayar via DANA.");
                    }

                // ==========================================================
                // JIKA PEMBAYARAN GAGAL (05 / FAILED / EXPIRED)
                // ==========================================================
                } elseif ($statusDana == '05' || strtoupper($statusDana) === 'FAILED' || strtoupper($statusDana) === 'EXPIRED') {
                    Log::info("LOG LOG: Webhook $trxIdFromDana GAGAL/EXPIRED.");

                    $transaction->status = 'failed';
                    $transaction->save();

                    // Batalkan SCK-
                    if (\Illuminate\Support\Str::startsWith($trxIdFromDana, 'SCK-')) {
                        \App\Http\Controllers\Admin\PesananController::processPesananCallback($trxIdFromDana, 'FAILED', []);
                    }
                    // Batalkan Top Up
                    elseif (\Illuminate\Support\Str::startsWith($trxIdFromDana, 'TOPUP-') || \Illuminate\Support\Str::startsWith($trxIdFromDana, 'ADM-')) {
                        \DB::table('top_ups')->where('transaction_id', $trxIdFromDana)->update(['status' => 'failed', 'updated_at' => now()]);
                    }
                    // Batalkan Order (Toko / Marketplace)
                    elseif (\Illuminate\Support\Str::startsWith($trxIdFromDana, 'ORD-') || \Illuminate\Support\Str::startsWith($trxIdFromDana, 'CVSANCAK-')) {
                        $orderUtama = \App\Models\Order::where('invoice_number', $trxIdFromDana)->first();
                        if ($orderUtama) {
                            $orderUtama->update(['status' => 'failed']);
                        } else {
                            try {
                                \DB::connection('mysql_second')->table('orders')->where('order_number', $trxIdFromDana)->update(['status' => 'failed']);
                            } catch (\Exception $e) {}
                        }
                    }
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("LOG LOG: Webhook Error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            // <-- FIX TIMESTAMP PADA ERROR 500
            return response()->json([
                'responseCode' => '5005601',
                'responseMessage' => 'Internal Server Error'
            ], 500)->withHeaders(['X-TIMESTAMP' => $danaTimestamp]);
        }

        // <-- FIX TIMESTAMP PADA RESPONSE SUKSES
        return response()->json([
            'responseCode' => '2005600',
            'responseMessage' => 'Successful'
        ])->withHeaders(['X-TIMESTAMP' => $danaTimestamp]);
    }

  /**
     * =========================================================
     * UNIVERSAL RETURN PAGE (DANA CALLBACK/RETURN) - SMART HUB
     * =========================================================
     */
    public function returnPage(Request $request)
    {
        Log::info('[DANA RETURN PAGE] Hit Smart Hub', [
            'query'      => $request->all(),
            'full_url'   => $request->fullUrl(),
            'user_agent' => $request->userAgent(),
        ]);

        try {
            // 1. AMBIL REF DENGAN PRIORITAS: URL -> SESSION -> TERAKHIR
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

            // 2. NORMALIZE REF
            $refNo = $this->normalizeReference($refNo);

            // ========================================================
            // 3. DETEKSI PLATFORM CERDAS (WEB ATAU HP)
            // ========================================================
            $isMobile = false;

            // Cek dari User-Agent (Paling Akurat untuk Redirect Browser)
            if (preg_match('/Mobile|Android|BlackBerry|iPhone|iPad|iPod|Windows Phone/i', $request->userAgent())) {
                $isMobile = true;
            }
            // Cek dari custom header/query jika aplikasi mengirimkan parameter khusus
            elseif ($request->query('platform') === 'mobile' || $request->header('X-Platform') === 'mobile') {
                $isMobile = true;
            }

            Log::info('[DANA RETURN] Normal_Ref: ' . $refNo . ' | Platform: ' . ($isMobile ? 'MOBILE/HP' : 'WEB/DESKTOP'));

            // Bersihkan session agar tidak nyangkut untuk transaksi berikutnya
            Session::forget('last_dana_ref');

            $statusPembayaran = 'pending'; // Status Default
            $jenisTransaksi = 'unknown';

            // ========================================================
            // 4. PENGECEKAN STATUS & JENIS TRANSAKSI
            // ========================================================

            // A. CEK PESANAN SANCAKA EXPRESS (SCK-)
            if (\Illuminate\Support\Str::startsWith($refNo, 'SCK-')) {
                $jenisTransaksi = 'pesanan_ekspedisi';
                $order = \App\Models\Pesanan::where('nomor_invoice', $refNo)->first();

                if($order && in_array($order->status_pesanan, ['Pesanan Dibuat', 'Lunas', 'Diproses', 'Menunggu Pickup'])) {
                    $statusPembayaran = 'sukses';
                }
            }
            // B. CEK TOKO UTAMA & MARKETPLACE (ORD- / CVSANCAK-)
            elseif (\Illuminate\Support\Str::startsWith($refNo, 'ORD-') || \Illuminate\Support\Str::startsWith($refNo, 'CVSANCAK-')) {
                $jenisTransaksi = 'pesanan_marketplace';
                $orderUtama = \App\Models\Order::where('invoice_number', $refNo)->first();

                if ($orderUtama) {
                    if($orderUtama->payment_status == 'paid') $statusPembayaran = 'sukses';
                } else {
                    try {
                        $orderMarketplace = \DB::connection('mysql_second')->table('orders')->where('order_number', $refNo)->first();
                        if ($orderMarketplace && $orderMarketplace->payment_status == 'paid') {
                            $statusPembayaran = 'sukses';
                        }
                    } catch (\Exception $e) {}
                }
            }

            // C. CEK TOPUP SALDO PUSAT (TOPUP-)
            elseif (\Illuminate\Support\Str::startsWith($refNo, 'TOPUP-')) {
                $jenisTransaksi = 'topup';

                // 1. Cek di model TopUp terlebih dahulu
                $topup = \App\Models\TopUp::where('transaction_id', $refNo)->first();

                if ($topup) {
                    if ($topup->status == 'success' || $topup->status == 'sukses') {
                        $statusPembayaran = 'sukses';
                    }
                } else {
                    // 2. Jika tidak ada di model TopUp, cari di model Transaction
                    $trx = \App\Models\Transaction::where('reference_id', $refNo)
                                                  ->orWhere('ref_id', $refNo)
                                                  ->first();

                    if ($trx && in_array(strtolower($trx->status), ['success', 'sukses'])) {
                        $statusPembayaran = 'sukses';
                    }
                }
            }

            // D. CEK PPOB & TRANSAKSI LAINNYA
            else {
                $trx = \App\Models\Transaction::where('ref_id', $refNo)
                    ->orWhere('reference_id', str_replace('PASCA', '', $refNo))
                    ->first();

                if ($trx) {
                    $jenisTransaksi = 'ppob';
                    if ($trx->status == 'success' || $trx->status == 'sukses') $statusPembayaran = 'sukses';
                }
            }

            // ========================================================
            // 5. KIRIM DATA KE SMART HUB VIEW
            // ========================================================
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

    private function normalizeReference($refNo)
    {
        if (Str::startsWith($refNo, 'SCKORD') && !str_contains($refNo, '-')) {
            return 'SCK-ORD-' . substr($refNo, 6);
        }

        if (Str::startsWith($refNo, 'TOPUP') && !str_contains($refNo, '-')) {
            return 'TOPUP-' . substr($refNo, 5);
        }

        // [TAMBAHAN FIX] Kembalikan format SCK20251118IP52X4 menjadi SCK-20251118-IP52X4
        if (preg_match('/^SCK(\d{8})([A-Z0-9]+)$/', $refNo, $matches)) {
            return 'SCK-' . $matches[1] . '-' . $matches[2];
        }

        return trim($refNo);
    }

    /**
     * =========================================================================
     * FITUR: CEK SALDO DANA USER PENGGUNA (REAL-TIME)
     * =========================================================================
     */
    public function checkMyDanaBalance()
    {
        $user = Auth::user();

        // Pastikan kolom akses token sesuai dengan tabel User/Pengguna Anda
        $accessToken = $user->dana_access_token;

        if (empty($accessToken)) {
            return response()->json([
                'success' => false,
                'message' => 'Akun DANA belum terhubung. Silakan hubungkan terlebih dahulu.'
            ]);
        }

        $timestamp = now('Asia/Jakarta')->toIso8601String();
        $path = '/v1.0/balance-inquiry.htm';
        $body = [
            'partnerReferenceNo' => 'BAL' . time() . Str::random(5),
            'balanceTypes' => ['BALANCE'],
            'additionalInfo' => [
                'accessToken' => $accessToken
            ]
        ];

        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $hashedBody = strtolower(hash('sha256', $jsonBody));
        $stringToSign = "POST:" . $path . ":" . $hashedBody . ":" . $timestamp;

        // Menggunakan fungsi generateSignature yang sudah ada di Controller ini
        $signature = $this->generateSignature($stringToSign);

        try {
            Log::info('[DANA BALANCE CHECK] Meminta info saldo user ID: ' . $user->id_pengguna);

            $response = Http::withHeaders([
                'X-TIMESTAMP'   => $timestamp,
                'X-SIGNATURE'   => $signature,
                'X-PARTNER-ID'  => config('services.dana.x_partner_id'),
                'X-EXTERNAL-ID' => (string) time(),
                'X-DEVICE-ID'   => 'CUSTOMER-WEB-STATION',
                'CHANNEL-ID'    => '95221',
                'ORIGIN'        => config('services.dana.origin'),
                'Authorization-Customer' => 'Bearer ' . $accessToken,
                'Content-Type'  => 'application/json'
            ])->withBody($jsonBody, 'application/json')->post(config('services.dana.base_url') . $path);

            $result = $response->json();

            // Jika Berhasil (Response Code DANA 2001100 = Success Inquiry)
            if (isset($result['responseCode']) && $result['responseCode'] == '2001100') {
                $amount = $result['accountInfos'][0]['availableBalance']['value'] ?? 0;

                // (Opsional) Jika Anda punya kolom dana_user_balance di tabel users, Anda bisa menyimpannya
                // $user->update(['dana_user_balance' => $amount]);

                return response()->json([
                    'success' => true,
                    'balance' => $amount,
                    'formatted_balance' => 'Rp ' . number_format($amount, 0, ',', '.'),
                    'message' => 'Berhasil mengambil saldo DANA.'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil saldo: ' . ($result['responseMessage'] ?? 'Token mungkin kadaluarsa.')
            ]);

        } catch (\Exception $e) {
            Log::error('[DANA BALANCE CHECK] Error System: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Sistem Error saat mengecek saldo.'
            ], 500);
        }
    }

    /**
     * =========================================================
     * STANDARD SUCCESS RESPONSE DANA SNAP
     * =========================================================
     */
    private function respondSuccessDANA()
    {
        return response()->json([
            'responseCode'    => '2005600',
            'responseMessage' => 'Successful'
        ])->withHeaders([
            'Content-Type' => 'application/json',
            'X-TIMESTAMP'  => Carbon::now('Asia/Jakarta')
                ->format('Y-m-d\TH:i:sP')
        ]);
    }
}
