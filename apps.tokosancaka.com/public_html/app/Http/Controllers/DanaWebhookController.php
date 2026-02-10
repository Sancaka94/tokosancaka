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
    /**
     * Main Handler untuk Notifikasi DANA
     */
    public function handleNotify(Request $request)
    {
        // 1. Log Request Masuk untuk Audit Trail
        Log::info("[DANA-WEBHOOK] Incoming Hit", [
            'ip' => $request->ip(),
            'payload' => $request->all()
        ]);

        try {
            // Validasi Signature (Opsional - Aktifkan jika sudah ada Secret Key)
            if (!$this->isValidSignature($request)) {
                Log::warning("[DANA-WEBHOOK] Invalid Signature from IP: " . $request->ip());
                // Tetap return OK untuk security by obscurity, atau return 401 jika strict
                return response()->json(['responseCode' => '401', 'responseMessage' => 'Unauthorized'], 401);
            }

            $data = $request->all();
            $refNo = $data['partnerReferenceNo'] ?? null;
            $status = $data['orderStatus'] ?? null; // FINISHED / SUCCESS / FAILED
            $amountVal = $data['amount']['value'] ?? 0;
            $paidAmount = (float) $amountVal;

            if (empty($refNo)) {
                return response()->json(['responseCode' => '400', 'responseMessage' => 'Missing Reference Number'], 400);
            }

            // 2. Mulai Database Transaction (ACID)
            DB::beginTransaction();

            try {
                // 3. ATOMIC LOCK: Ambil data dan kunci baris ini agar tidak bisa diedit proses lain
                $trx = DB::table('dana_transactions')
                    ->where('reference_no', $refNo)
                    ->lockForUpdate()
                    ->first();

                // Jika transaksi tidak ditemukan di database kita
                if (!$trx) {
                    Log::error("[DANA-WEBHOOK] Transaction Not Found: $refNo");
                    DB::rollBack();
                    // Return 200 agar DANA berhenti mengirim ulang (Assume data mismatch)
                    return response()->json(['responseCode' => '2000000', 'responseMessage' => 'Success']);
                }

                // 4. IDEMPOTENCY CHECK: Cek apakah sudah sukses sebelumnya?
                if ($trx->status === 'SUCCESS' || $trx->status === 'PAID') {
                    Log::info("[DANA-WEBHOOK] Idempotency: Transaction $refNo already completed.");
                    DB::rollBack();
                    return response()->json(['responseCode' => '2000000', 'responseMessage' => 'Success']);
                }

                // 5. Routing Logic Berdasarkan Status DANA
                if ($status === 'FINISHED' || $status === 'SUCCESS') {

                    // A. Update Status Table Transaksi DANA
                    DB::table('dana_transactions')->where('id', $trx->id)->update([
                        'status' => 'SUCCESS',
                        'response_payload' => json_encode($data),
                        'updated_at' => now()
                    ]);

                    // B. Routing Berdasarkan Tipe (DEPOSIT vs ORDER)
                    if ($trx->type === 'DEPOSIT') {
                        $this->processDeposit($trx, $paidAmount);
                    } elseif ($trx->type === 'ORDER') {
                        $this->processOrder($trx, $paidAmount);
                    } else {
                        Log::warning("[DANA-WEBHOOK] Unknown Transaction Type: " . $trx->type);
                    }

                    Log::info("[DANA-WEBHOOK] ✅ Transaction $refNo processed successfully.");

                } elseif ($status === 'FAILED' || $status === 'CLOSED') {

                    // Handle Status Gagal
                    DB::table('dana_transactions')->where('id', $trx->id)->update([
                        'status' => 'FAILED',
                        'response_payload' => json_encode($data),
                        'updated_at' => now()
                    ]);

                    Log::info("[DANA-WEBHOOK] ❌ Transaction $refNo marked as FAILED.");
                }

                // 6. Commit Semua Perubahan Database
                DB::commit();

            } catch (Exception $e) {
                DB::rollBack();
                throw $e; // Lempar ke catch utama
            }

            // 7. Response Sukses Standar DANA
            return response()->json([
                'responseCode' => '2000000',
                'responseMessage' => 'Success'
            ]);

        } catch (Exception $e) {
            Log::error("[DANA-WEBHOOK] System Error: " . $e->getMessage());
            return response()->json(['responseCode' => '500', 'responseMessage' => 'Internal Server Error'], 500);
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
