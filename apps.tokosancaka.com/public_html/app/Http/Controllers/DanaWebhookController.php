<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\PosTopUp;
use App\Models\Order; // Jika diperlukan
use Exception;

class DanaWebhookController extends Controller
{
    /**
     * Entry Point Utama Webhook DANA
     */
    public function handleNotify(Request $request)
    {
        // 1. Log Payload Masuk (Penting untuk Debugging)
        Log::info("[DANA-WEBHOOK] Hit Masuk:", [
            'ip' => $request->ip(),
            'payload' => $request->all()
        ]);

        // 2. [KEAMANAN] Validasi Signature (Hanya jalan jika Secret Key ada di .env)
        // DANA mewajibkan validasi ini di Production
        if (!$this->isValidSignature($request)) {
            Log::warning("[DANA-WEBHOOK] Invalid Signature! IP: " . $request->ip());
            // Return 401 agar DANA tahu kita menolak, atau 200 palsu untuk membingungkan hacker
            return response()->json(['responseCode' => '401', 'responseMessage' => 'Unauthorized'], 401);
        }

        try {
            $data = $request->all();

            // 3. Ambil Reference No (Invoice) & Status
            $refNo = $data['originalPartnerReferenceNo'] ?? $data['partnerReferenceNo'] ?? null;
            $statusRaw = $data['transactionStatusDesc'] ?? $data['orderStatus'] ?? $data['latestTransactionStatus'] ?? 'UNKNOWN';

            // Ambil Nominal Bayar
            $amountVal = $data['amount']['value'] ?? 0;
            $paidAmount = (float) $amountVal;

            if (empty($refNo)) {
                return response()->json(['res' => 'NO_REF'], 400);
            }

            // =============================================================
            // SKENARIO 1: CEK TABEL MEMBER (dana_transactions)
            // =============================================================
            $memberTrx = DB::table('dana_transactions')->where('reference_no', $refNo)->first();

            if ($memberTrx) {
                return $this->processMemberTransaction($memberTrx, $statusRaw, $data, $paidAmount);
            }

            // =============================================================
            // SKENARIO 2: CEK TABEL TENANT/POS (pos_topups)
            // =============================================================
            $tenantTrx = PosTopUp::where('reference_no', $refNo)->first();

            if ($tenantTrx) {
                return $this->processTenantTransaction($tenantTrx, $statusRaw, $data, $paidAmount);
            }

            // Jika tidak ditemukan di kedua tabel
            Log::warning("[DANA-WEBHOOK] Transaksi Hantu (Tidak ditemukan di DB manapun): $refNo");
            // Return success agar DANA berhenti retry (karena emang datanya ga ada di kita)
            return response()->json(['responseCode' => '2000000', 'responseMessage' => 'Success (Ignored)']);

        } catch (Exception $e) {
            Log::error("[DANA-WEBHOOK] Fatal Error: " . $e->getMessage());
            // Return 500 supaya DANA retry nanti
            return response()->json(['responseCode' => '500', 'message' => 'Internal Error'], 500);
        }
    }

    /**
     * ---------------------------------------------------------------------
     * LOGIKA 1: MEMBER / AFFILIATE (Topup Saldo Pribadi)
     * ---------------------------------------------------------------------
     */
    private function processMemberTransaction($trx, $statusRaw, $data, $paidAmount)
    {
        Log::info("[DANA-WEBHOOK] Terdeteksi sebagai Transaksi MEMBER: " . $trx->reference_no);

        // A. Cek Idempotency (Apakah sudah sukses sebelumnya?)
        if ($trx->status === 'SUCCESS') {
            Log::info("[DANA-WEBHOOK] Member TRX $trx->reference_no sudah sukses sebelumnya. Skip.");
            return response()->json(['responseCode' => '2000000', 'responseMessage' => 'Success']);
        }

        // List Status Sukses DANA
        $isSuccess = in_array($statusRaw, ['SUCCESS', 'FINISHED', '00']);

        DB::beginTransaction();
        try {
            if ($isSuccess) {
                // 1. Update Status Log Transaksi
                DB::table('dana_transactions')->where('id', $trx->id)->update([
                    'status' => 'SUCCESS',
                    'response_payload' => json_encode($data),
                    'updated_at' => now()
                ]);

                // 2. Tambah Saldo Member (Hanya untuk tipe DEPOSIT/TOPUP)
                if (in_array($trx->type, ['TOPUP', 'DEPOSIT'])) {
                    DB::table('affiliates')
                        ->where('id', $trx->affiliate_id)
                        ->increment('balance', $paidAmount);

                    // 3. Catat Mutasi (History Saldo) - PENTING
                    DB::table('balance_mutations')->insert([
                        'affiliate_id' => $trx->affiliate_id,
                        'type'         => 'CREDIT', // Uang Masuk
                        'amount'       => $paidAmount,
                        'description'  => 'Topup DANA Sukses (' . $trx->reference_no . ')',
                        'created_at'   => now(),
                        'updated_at'   => now()
                    ]);

                    Log::info("[DANA-WEBHOOK] ✅ Saldo Member ID {$trx->affiliate_id} bertambah: Rp $paidAmount");
                }

            } else {
                // Jika Gagal (Expired/Cancelled)
                DB::table('dana_transactions')->where('id', $trx->id)->update([
                    'status' => 'FAILED',
                    'response_payload' => json_encode($data),
                    'updated_at' => now()
                ]);
            }
            DB::commit();
            return response()->json(['responseCode' => '2000000', 'responseMessage' => 'Success']);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * ---------------------------------------------------------------------
     * LOGIKA 2: TENANT / POS ADMIN (Topup Saldo Toko)
     * ---------------------------------------------------------------------
     */
    private function processTenantTransaction($trx, $statusRaw, $data, $paidAmount)
    {
        Log::info("[DANA-WEBHOOK] Terdeteksi sebagai Transaksi TENANT/POS: " . $trx->reference_no);

        // A. Cek Idempotency
        if (in_array($trx->status, ['SUCCESS', 'PAID'])) {
            Log::info("[DANA-WEBHOOK] Tenant TRX $trx->reference_no sudah sukses sebelumnya. Skip.");
            return response()->json(['responseCode' => '2000000', 'responseMessage' => 'Success']);
        }

        $isSuccess = in_array($statusRaw, ['SUCCESS', 'FINISHED', '00']);

        DB::beginTransaction();
        try {
            if ($isSuccess) {
                // 1. Update Status Transaksi POS
                $trx->update([
                    'status'           => 'SUCCESS', // Atau 'PAID'
                    'payment_method'   => 'DANA',
                    'response_payload' => json_encode($data)
                ]);

                // 2. Tambah Saldo ADMIN / USER (Bukan Member)
                // Kita update tabel users karena admin/tenant login pakai tabel users
                $user = DB::table('users')->where('id', $trx->affiliate_id)->first();

                if ($user) {
                    DB::table('users')->where('id', $trx->affiliate_id)->increment('saldo', $paidAmount);
                    Log::info("[DANA-WEBHOOK] ✅ Saldo Admin User ID {$trx->affiliate_id} bertambah: Rp $paidAmount");

                    // 3. Catat Mutasi (Opsional: Jika ada tabel user_mutations)

                    DB::table('user_mutations')->insert([
                        'user_id'     => $trx->affiliate_id,
                        'type'        => 'CREDIT',
                        'amount'      => $paidAmount,
                        'description' => 'Topup POS DANA (' . $trx->reference_no . ')',
                        'created_at'  => now()
                    ]);

                }

            } else {
                // Gagal
                $trx->update([
                    'status'           => 'FAILED',
                    'response_payload' => json_encode($data)
                ]);
            }
            DB::commit();
            return response()->json(['responseCode' => '2000000', 'responseMessage' => 'Success']);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * ---------------------------------------------------------------------
     * FUNGSI KEAMANAN (VALIDASI SIGNATURE)
     * ---------------------------------------------------------------------
     */
    private function isValidSignature(Request $request)
    {
        // 1. Ambil Signature dari Header
        $signatureFromHeader = $request->header('X-Dana-Signature'); // Kadang 'signature' lowercase

        // 2. Ambil Client Secret dari .env
        $clientSecret = config('services.dana.client_secret');

        // Jika setting kosong (Development), bypass validasi
        if (empty($clientSecret)) {
            return true;
        }

        // Jika header kosong, return false
        if (empty($signatureFromHeader)) {
            // Log::warning("Signature Header Missing");
            // return false; // Uncomment ini saat Production
            return true; // Sementara True untuk Sandbox
        }

        // 3. Generate Signature Lokal: HMAC-SHA256(Body, Secret)
        $content = $request->getContent(); // Raw Body
        $stringToSign = $content; // DANA v2 biasanya langsung body

        // Generate Signature
        $generatedSignature = base64_encode(hash_hmac('sha256', $stringToSign, $clientSecret, true));

        // 4. Bandingkan
        // Gunakan hash_equals untuk mencegah timing attack
        return hash_equals($generatedSignature, $signatureFromHeader);
    }
}
