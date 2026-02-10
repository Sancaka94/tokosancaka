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
     * MAIN HANDLER
     */
    public function handleNotify(Request $request)
    {
        // 1. Log Payload Masuk
        Log::info("[DANA-WEBHOOK] Hit Masuk:", [
            'ip' => $request->ip(),
            'payload' => $request->all()
        ]);

        try {
            $data = $request->all();
            $refNo = $data['originalPartnerReferenceNo'] ?? $data['partnerReferenceNo'] ?? null;
            $statusRaw = $data['transactionStatusDesc'] ?? $data['orderStatus'] ?? 'UNKNOWN';

            // Nominal
            $amountVal = $data['amount']['value'] ?? 0;
            $paidAmount = (float) $amountVal;

            if (empty($refNo)) {
                return response()->json(['res' => 'NO_REF'], 400);
            }

            // =============================================================
            // DETEKSI PREFIX: DEP-T- (TENANT) vs DEP- (MEMBER)
            // =============================================================

            // Cek apakah string mengandung 'DEP-T-'
            if (str_contains($refNo, 'DEP-T-')) {

                // --- JALUR TENANT (USER ADMIN) ---
                $trx = DB::table('dana_transactions')->where('reference_no', $refNo)->first();

                // Fallback cek ke PosTopUp jika di dana_transactions belum ada
                if (!$trx) {
                    $trx = PosTopUp::where('reference_no', $refNo)->first();
                }

                if ($trx) {
                    return $this->processTenantTransaction($trx, $statusRaw, $data, $paidAmount);
                }

            } else {

                // --- JALUR MEMBER (AFFILIATE) ---
                $trx = DB::table('dana_transactions')->where('reference_no', $refNo)->first();
                if ($trx) {
                    return $this->processMemberTransaction($trx, $statusRaw, $data, $paidAmount);
                }
            }

            Log::warning("[DANA-WEBHOOK] Transaksi Tidak Dikenali: $refNo");
            return response()->json(['responseCode' => '2000000', 'responseMessage' => 'Success (Ignored)']);

        } catch (Exception $e) {
            Log::error("[DANA-WEBHOOK] Fatal Error: " . $e->getMessage());
            return response()->json(['responseCode' => '500', 'message' => 'Internal Error'], 500);
        }
    }

    /**
     * PROSES TENANT (SALDO KE TABEL USERS)
     */
    private function processTenantTransaction($trx, $statusRaw, $data, $paidAmount)
    {
        Log::info("[DANA-WEBHOOK] Tipe: TENANT/ADMIN | Ref: " . $trx->reference_no);

        $isSuccess = in_array($statusRaw, ['SUCCESS', 'FINISHED', '00']);

        DB::beginTransaction();
        try {
            if ($isSuccess) {
                // 1. Update Log dana_transactions (Jika ada)
                DB::table('dana_transactions')->where('reference_no', $trx->reference_no)->update([
                    'status' => 'SUCCESS',
                    'response_payload' => json_encode($data),
                    'updated_at' => now()
                ]);

                // 2. Update PosTopUp
                PosTopUp::where('reference_no', $trx->reference_no)->update([
                    'status' => 'SUCCESS',
                    'response_payload' => json_encode($data)
                ]);

                // 3. TAMBAH SALDO KE TABEL USERS (BUKAN AFFILIATE)
                // affiliate_id di sini menyimpan ID User Admin
                $userId = $trx->affiliate_id;

                DB::table('users')->where('id', $userId)->increment('saldo', $paidAmount);

                // 4. Catat Mutasi User (Admin Tenant)
                // Pastikan tabel user_mutations sudah dibuat
                try {
                    DB::table('user_mutations')->insert([
                        'user_id'      => $userId,
                        'type'         => 'CREDIT',
                        'amount'       => $paidAmount,
                        'description'  => 'Topup DANA (' . $trx->reference_no . ')',
                        'reference_no' => $trx->reference_no,
                        'created_at'   => now(),
                        'updated_at'   => now()
                    ]);
                } catch (\Exception $e) {
                    Log::warning("[DANA-WEBHOOK] Gagal catat user_mutation: " . $e->getMessage());
                }

                Log::info("[DANA-WEBHOOK] ✅ Saldo ADMIN USER ID {$userId} bertambah: +$paidAmount");

            } else {
                // Gagal
                DB::table('dana_transactions')->where('reference_no', $trx->reference_no)->update(['status' => 'FAILED']);
                PosTopUp::where('reference_no', $trx->reference_no)->update(['status' => 'FAILED']);
            }
            DB::commit();
            return response()->json(['responseCode' => '2000000', 'responseMessage' => 'Success']);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("[DANA-WEBHOOK] Error Tenant: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * PROSES MEMBER (SALDO KE TABEL AFFILIATES)
     */
    private function processMemberTransaction($trx, $statusRaw, $data, $paidAmount)
    {
        Log::info("[DANA-WEBHOOK] Tipe: MEMBER | Ref: " . $trx->reference_no);

        if ($trx->status === 'SUCCESS') {
            return response()->json(['responseCode' => '2000000', 'responseMessage' => 'Success']);
        }

        $isSuccess = in_array($statusRaw, ['SUCCESS', 'FINISHED', '00']);

        DB::beginTransaction();
        try {
            if ($isSuccess) {
                // 1. Update Log
                DB::table('dana_transactions')->where('id', $trx->id)->update([
                    'status' => 'SUCCESS',
                    'response_payload' => json_encode($data),
                    'updated_at' => now()
                ]);

                // 2. Tambah Saldo Member (Hanya tipe DEPOSIT/TOPUP)
                if (in_array($trx->type, ['TOPUP', 'DEPOSIT'])) {
                    DB::table('affiliates')
                        ->where('id', $trx->affiliate_id)
                        ->increment('balance', $paidAmount);

                    // 3. Catat Mutasi Member
                    try {
                        DB::table('balance_mutations')->insert([
                            'affiliate_id' => $trx->affiliate_id,
                            'type'         => 'CREDIT',
                            'amount'       => $paidAmount,
                            'description'  => 'Topup DANA (' . $trx->reference_no . ')',
                            'reference_no' => $trx->reference_no,
                            'created_at'   => now(),
                            'updated_at'   => now()
                        ]);
                    } catch (\Exception $e) {
                        Log::warning("[DANA-WEBHOOK] Gagal catat balance_mutation: " . $e->getMessage());
                    }

                    Log::info("[DANA-WEBHOOK] ✅ Saldo Member ID {$trx->affiliate_id} bertambah: +$paidAmount");
                }

            } else {
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
}
