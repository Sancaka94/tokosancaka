<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\PosTopUp;
use Exception;

class DanaWebhookController extends Controller
{
    /**
     * Entry Point Webhook DANA
     */
    public function handleNotify(Request $request)
    {
        // 1. Log Payload Masuk
        Log::info("[DANA-WEBHOOK] Hit Masuk:", [
            'ip' => $request->ip(),
            'payload' => $request->all()
        ]);

        // 2. Validasi Signature (Hanya jalan jika Secret Key ada di .env)
        if (!$this->isValidSignature($request)) {
            Log::warning("[DANA-WEBHOOK] Invalid Signature! IP: " . $request->ip());
            return response()->json(['responseCode' => '401', 'message' => 'Unauthorized'], 401);
        }

        try {
            $data = $request->all();

            // 3. Ambil Invoice / Reference No
            $refNo = $data['originalPartnerReferenceNo'] ?? $data['partnerReferenceNo'] ?? null;
            $statusRaw = $data['transactionStatusDesc'] ?? $data['orderStatus'] ?? $data['latestTransactionStatus'] ?? 'UNKNOWN';

            // Ambil Nominal Bayar
            $amountVal = $data['amount']['value'] ?? 0;
            $paidAmount = (float) $amountVal;

            if (empty($refNo)) {
                return response()->json(['res' => 'NO_REF'], 400);
            }

            // =============================================================
            // STRATEGI PENCARIAN CERDAS (SMART LOOKUP)
            // =============================================================

            // A. CEK DATABASE MEMBER (dana_transactions)
            $memberTrx = DB::table('dana_transactions')->where('reference_no', $refNo)->first();
            if ($memberTrx) {
                return $this->processMemberTransaction($memberTrx, $statusRaw, $data, $paidAmount);
            }

            // B. CEK DATABASE TENANT/ADMIN (pos_topups)
            $tenantTrx = PosTopUp::where('reference_no', $refNo)->first();
            if ($tenantTrx) {
                return $this->processTenantTransaction($tenantTrx, $statusRaw, $data, $paidAmount);
            }

            // Jika Invoice tidak ditemukan di manapun
            Log::warning("[DANA-WEBHOOK] Transaksi Hantu (Unknown Invoice): $refNo");
            return response()->json(['responseCode' => '2000000', 'responseMessage' => 'Success (Ignored)']);

        } catch (Exception $e) {
            Log::error("[DANA-WEBHOOK] Fatal Error: " . $e->getMessage());
            return response()->json(['responseCode' => '500', 'message' => 'Internal Error'], 500);
        }
    }

    /**
     * ---------------------------------------------------------------------
     * LOGIKA 1: MEMBER / AFFILIATE
     * Target: Tabel affiliates & balance_mutations
     * ---------------------------------------------------------------------
     */
    private function processMemberTransaction($trx, $statusRaw, $data, $paidAmount)
    {
        Log::info("[DANA-WEBHOOK] Tipe: MEMBER | Ref: " . $trx->reference_no);

        // Cek Idempotency (Anti Double Topup)
        if ($trx->status === 'SUCCESS') {
            return response()->json(['responseCode' => '2000000', 'responseMessage' => 'Success']);
        }

        $isSuccess = in_array($statusRaw, ['SUCCESS', 'FINISHED', '00']);

        DB::beginTransaction();
        try {
            if ($isSuccess) {
                // 1. Update Status Log
                DB::table('dana_transactions')->where('id', $trx->id)->update([
                    'status' => 'SUCCESS',
                    'response_payload' => json_encode($data),
                    'updated_at' => now()
                ]);

                // 2. Tambah Saldo Member (Hanya tipe TOPUP/DEPOSIT)
                if (in_array($trx->type, ['TOPUP', 'DEPOSIT'])) {
                    DB::table('affiliates')
                        ->where('id', $trx->affiliate_id)
                        ->increment('balance', $paidAmount);

                    // 3. Catat Mutasi Member
                    DB::table('balance_mutations')->insert([
                        'affiliate_id' => $trx->affiliate_id,
                        'type'         => 'CREDIT',
                        'amount'       => $paidAmount,
                        'description'  => 'Topup DANA (' . $trx->reference_no . ')',
                        'reference_no' => $trx->reference_no,
                        'created_at'   => now(),
                        'updated_at'   => now()
                    ]);

                    Log::info("[DANA-WEBHOOK] ✅ Saldo Member ID {$trx->affiliate_id} +$paidAmount");
                }

            } else {
                // Gagal
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
     * LOGIKA 2: TENANT / POS ADMIN
     * Target: Tabel users & user_mutations (atau tenant_mutations)
     * ---------------------------------------------------------------------
     */
    private function processTenantTransaction($trx, $statusRaw, $data, $paidAmount)
    {
        Log::info("[DANA-WEBHOOK] Tipe: TENANT/POS | Ref: " . $trx->reference_no);

        // Cek Idempotency
        if (in_array($trx->status, ['SUCCESS', 'PAID'])) {
            return response()->json(['responseCode' => '2000000', 'responseMessage' => 'Success']);
        }

        $isSuccess = in_array($statusRaw, ['SUCCESS', 'FINISHED', '00']);

        DB::beginTransaction();
        try {
            if ($isSuccess) {
                // 1. Update Status POS Topup
                $trx->update([
                    'status'           => 'SUCCESS',
                    'payment_method'   => 'DANA',
                    'response_payload' => json_encode($data)
                ]);

                // 2. Tambah Saldo ADMIN/USER
                // Note: Di TenantPaymentController, 'affiliate_id' menyimpan User ID
                $userId = $trx->affiliate_id;

                $user = DB::table('users')->where('id', $userId)->first();

                if ($user) {
                    // Update Saldo User
                    DB::table('users')->where('id', $userId)->increment('saldo', $paidAmount);

                    // 3. Catat Mutasi User (Admin Tenant)
                    DB::table('user_mutations')->insert([
                        'user_id'      => $userId,
                        'type'         => 'CREDIT',
                        'amount'       => $paidAmount,
                        'description'  => 'Topup POS DANA (' . $trx->reference_no . ')',
                        'reference_no' => $trx->reference_no,
                        'created_at'   => now(),
                        'updated_at'   => now()
                    ]);

                    Log::info("[DANA-WEBHOOK] ✅ Saldo User ID {$userId} +$paidAmount");

                    // 4. (Opsional) Jika ingin mencatat Mutasi Tenant (Toko) juga
                    /*
                    if ($trx->tenant_id) {
                        DB::table('tenant_mutations')->insert([
                            'tenant_id'    => $trx->tenant_id,
                            'type'         => 'CREDIT',
                            'amount'       => $paidAmount,
                            'description'  => 'Deposit via Admin #' . $userId,
                            'reference_no' => $trx->reference_no,
                            'created_at'   => now(),
                            'updated_at'   => now()
                        ]);
                    }
                    */
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
     * Fungsi Validasi Signature
     */
    private function isValidSignature(Request $request)
    {
        $signatureFromHeader = $request->header('X-Dana-Signature');
        $clientSecret = config('services.dana.client_secret');

        if (empty($clientSecret)) return true; // Bypass jika local/dev
        if (empty($signatureFromHeader)) return true; // Sementara True untuk Sandbox

        $content = $request->getContent();
        $generatedSignature = base64_encode(hash_hmac('sha256', $content, $clientSecret, true));

        return hash_equals($generatedSignature, $signatureFromHeader);
    }
}
