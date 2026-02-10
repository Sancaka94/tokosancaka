<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Order;
use App\Models\Affiliate;
use Exception;

class DanaWebhookController extends Controller
{
    public function handleNotify(Request $request)
    {
        // 1. Log Payload Asli
        Log::info("[DANA-WEBHOOK] Hit Masuk:", [
            'ip' => $request->ip(),
            'payload' => $request->all()
        ]);

        try {
            $data = $request->all();

            // --- PERBAIKAN DI SINI (Mapping Sesuai Webhook.site) ---


            // Cek RefNo (Bisa 'originalPartnerReferenceNo' atau 'partnerReferenceNo')
            $refNo = $data['originalPartnerReferenceNo'] ?? $data['partnerReferenceNo'] ?? null;

            // Cek Status (Bisa 'transactionStatusDesc', 'orderStatus', atau 'latestTransactionStatus')
            // '00' atau 'SUCCESS' atau 'FINISHED' dianggap sukses
            $statusRaw = $data['transactionStatusDesc'] ?? $data['orderStatus'] ?? $data['latestTransactionStatus'] ?? 'UNKNOWN';

            // Cek Amount
            $amountVal = $data['amount']['value'] ?? 0;
            $paidAmount = (float) $amountVal;

            if (empty($refNo)) {
                Log::warning("[DANA-WEBHOOK] RefNo Kosong/Tidak Dikenali.");
                return response()->json(['res' => 'NO_REF'], 400);
            }

            // 2. Database Transaction & Locking
            DB::beginTransaction();

            try {
                $trx = DB::table('dana_transactions')
                    ->where('reference_no', $refNo)
                    ->lockForUpdate()
                    ->first();

                if (!$trx) {
                    Log::warning("[DANA-WEBHOOK] Transaksi tidak ada di DB: $refNo");
                    DB::rollBack();
                    return response()->json(['responseCode' => '2000000', 'responseMessage' => 'Success']);
                }

                if ($trx->status === 'SUCCESS' || $trx->status === 'PAID') {
                    Log::info("[DANA-WEBHOOK] Idempotency: $refNo sudah sukses sebelumnya.");
                    DB::rollBack();
                    return response()->json(['responseCode' => '2000000', 'responseMessage' => 'Success']);
                }

                // --- LOGIKA CEK STATUS BARU ---
                $isSuccess = in_array($statusRaw, ['SUCCESS', 'FINISHED', '00']);

                if ($isSuccess) {
                    // UPDATE SUKSES
                    DB::table('dana_transactions')->where('id', $trx->id)->update([
                        'status' => 'SUCCESS',
                        'response_payload' => json_encode($data),
                        'updated_at' => now()
                    ]);

                    // TAMBAH SALDO
                    if ($trx->type === 'DEPOSIT') {
                        DB::table('affiliates')
                            ->where('id', $trx->affiliate_id)
                            ->increment('balance', $paidAmount);

                        Log::info("[DANA-WEBHOOK] ✅ Saldo Masuk: $paidAmount ke User: $trx->affiliate_id");
                    }
                } else {
                    // UPDATE GAGAL
                    DB::table('dana_transactions')->where('id', $trx->id)->update([
                        'status' => 'FAILED',
                        'response_payload' => json_encode($data),
                        'updated_at' => now()
                    ]);
                    Log::info("[DANA-WEBHOOK] ❌ Transaksi Gagal/Pending: $statusRaw");
                }

                DB::commit();

            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }

            return response()->json(['responseCode' => '2000000', 'responseMessage' => 'Success']);

        } catch (Exception $e) {
            Log::error("[DANA-WEBHOOK] Error: " . $e->getMessage());
            return response()->json(['responseCode' => '500', 'message' => 'Error'], 500);
        }
    }

    /**
     * Logika Khusus Deposit: Tambah Saldo Member
     */
    private function processDeposit($trx, $amount)
    {
        // Tambah Saldo Utama Member
        $affected = DB::table('affiliates')
            ->where('id', $trx->affiliate_id)
            ->increment('balance', $amount);

        if ($affected) {
            Log::info("[DANA-WEBHOOK] Balance Updated for User ID: " . $trx->affiliate_id);

            // Catat Mutasi Saldo (History)
            DB::table('balance_mutations')->insert([
                'affiliate_id' => $trx->affiliate_id,
                'type' => 'CREDIT', // Masuk
                'amount' => $amount,
                'description' => 'Deposit via DANA (' . $trx->reference_no . ')',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }

    /**
     * Logika Khusus Order: Update Status Pesanan Toko
     */
    private function processOrder($trx, $amount)
    {
        // Cari Order berdasarkan Nomor Referensi (Invoice)
        // Asumsi: reference_no di dana_transactions sama dengan invoice_number di table orders
        $order = Order::where('invoice_number', $trx->reference_no)->first();

        if ($order) {
            // Update Status Order Jadi PAID / PROCESSING
            $order->update([
                'payment_status' => 'PAID',
                'status' => 'PROCESSING', // Atau status selanjutnya sesuai flow toko
                'payment_method' => 'DANA',
                'paid_at' => now()
            ]);

            Log::info("[DANA-WEBHOOK] Order Invoice " . $trx->reference_no . " status updated to PAID.");
        } else {
            Log::error("[DANA-WEBHOOK] Order Not Found for Ref: " . $trx->reference_no);
        }
    }

    /**
     * Validasi Signature untuk Keamanan (HMAC-SHA256)
     */
    private function isValidSignature(Request $request)
    {
        // Ambil Signature dari Header DANA
        $signatureFromHeader = $request->header('X-Dana-Signature');

        // Ambil Secret Key dari Config
        $clientSecret = config('services.dana.client_secret');

        if (empty($signatureFromHeader) || empty($clientSecret)) {
            // Jika setting belum ada, anggap valid dulu (Mode Development)
            // Ubah jadi return false jika sudah Production
            return true;
        }

        // Generate Signature Lokal dari Body Content
        $content = $request->getContent();
        $generatedSignature = base64_encode(hash_hmac('sha256', $content, $clientSecret, true));

        // Bandingkan Signature
        return hash_equals($generatedSignature, $signatureFromHeader);
    }
}
