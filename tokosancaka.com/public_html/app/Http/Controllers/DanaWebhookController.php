<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\PosTopUp;
use App\Models\Order;     // <--- WAJIB: Tambahkan Import Model Order
use App\Models\Affiliate; // <--- WAJIB: Tambahkan Import Affiliate (untuk komisi)
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
            // ROUTING LOGIC: Tentukan Tipe Transaksi Berdasarkan Prefix RefNo
            // =============================================================

            // 1. CEK TRANSAKSI ORDER BARANG (Prefix: SCK-)
            if (str_contains($refNo, 'SCK-')) {
                return $this->processOrderTransaction($refNo, $statusRaw, $data, $paidAmount);
            }

            // 2. CEK TOPUP TENANT (Prefix: DEP-T-)
            elseif (str_contains($refNo, 'DEP-T-')) {
                $trx = DB::table('dana_transactions')->where('reference_no', $refNo)->first();
                if (!$trx) $trx = PosTopUp::where('reference_no', $refNo)->first(); // Fallback

                if ($trx) {
                    return $this->processTenantTransaction($trx, $statusRaw, $data, $paidAmount);
                }
            }

            // 3. CEK TOPUP MEMBER (Default / DEP-)
            else {
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
     * [BARU] PROSES ORDER BARANG (SCK-...)
     */
    private function processOrderTransaction($orderNumber, $statusRaw, $data, $paidAmount)
    {
        Log::info("[DANA-WEBHOOK] Tipe: ORDER BARANG | Ref: " . $orderNumber);

        // Cari Order
        $order = Order::where('order_number', $orderNumber)->first();

        if (!$order) {
            Log::error("[DANA-WEBHOOK] Order #$orderNumber tidak ditemukan di database.");
            return response()->json(['responseCode' => '404', 'message' => 'Order Not Found'], 404);
        }

        // Cek Idempotency (Jika sudah lunas, jangan diproses lagi)
        if ($order->payment_status === 'paid') {
            Log::info("[DANA-WEBHOOK] Order #$orderNumber sudah lunas sebelumnya. Skip.");
            return response()->json(['responseCode' => '2000000', 'responseMessage' => 'Success']);
        }

        $isSuccess = in_array($statusRaw, ['SUCCESS', 'FINISHED', '00', 'PAID']);

        if ($isSuccess) {
            DB::beginTransaction();
            try {
                // 1. Update Status Order
                $order->update([
                    'status'         => 'processing',
                    'payment_status' => 'paid',
                    'note'           => $order->note . "\n[DANA PAID] Otomatis via Webhook " . now(),
                    // Simpan JSON respons jika perlu debug nanti
                    'payment_data' => json_encode($data)
                ]);

                // 2. Kirim Notifikasi WA (Langsung Dispatch Job)
                $this->dispatchWaNotification($order, $paidAmount);

                // 3. Proses Komisi Afiliasi (Jika pakai kupon)
                if ($order->coupon_id && class_exists('App\Models\Coupon')) {
                    $coupon = \App\Models\Coupon::find($order->coupon_id);
                    if ($coupon) {
                        $this->processAffiliateCommission($coupon->code, $paidAmount);
                    }
                }

                DB::commit();
                Log::info("[DANA-WEBHOOK] ✅ Order #$orderNumber BERHASIL LUNAS.");

                return response()->json(['responseCode' => '2000000', 'responseMessage' => 'Success']);

            } catch (Exception $e) {
                DB::rollBack();
                Log::error("[DANA-WEBHOOK] Gagal Update Order: " . $e->getMessage());
                return response()->json(['responseCode' => '500', 'message' => 'DB Error'], 500);
            }
        } else {
            Log::warning("[DANA-WEBHOOK] Status Order Gagal/Pending: $statusRaw");
            return response()->json(['responseCode' => '2000000', 'responseMessage' => 'Success (Not Paid Yet)']);
        }
    }

    /**
     * PROSES TENANT (SALDO KE TABEL USERS)
     */
    private function processTenantTransaction($trx, $statusRaw, $data, $paidAmount)
    {
        Log::info("[DANA-WEBHOOK] Tipe: TENANT/ADMIN | Ref: " . $trx->reference_no);

        if ($trx->status === 'SUCCESS') {
            return response()->json(['responseCode' => '2000000', 'responseMessage' => 'Success']);
        }

        $isSuccess = in_array($statusRaw, ['SUCCESS', 'FINISHED', '00']);

        DB::beginTransaction();
        try {
            if ($isSuccess) {
                // 1. Update Log dana_transactions
                DB::table('dana_transactions')->where('reference_no', $trx->reference_no)->update([
                    'status' => 'SUCCESS',
                    'response_payload' => json_encode($data),
                    'updated_at' => now()
                ]);

                // 2. Update PosTopUp
                PosTopUp::where('reference_no', $trx->reference_no)->update(['status' => 'SUCCESS', 'response_payload' => json_encode($data)]);

                // 3. Tambah Saldo ADMIN
                $userId = $trx->affiliate_id;
                DB::table('users')->where('id', $userId)->increment('saldo', $paidAmount);

                // 4. Catat Mutasi
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
                } catch (\Exception $e) { Log::warning("Skip mutasi: " . $e->getMessage()); }

                Log::info("[DANA-WEBHOOK] ✅ Saldo ADMIN USER ID {$userId} bertambah: +$paidAmount");

            } else {
                DB::table('dana_transactions')->where('reference_no', $trx->reference_no)->update(['status' => 'FAILED']);
                PosTopUp::where('reference_no', $trx->reference_no)->update(['status' => 'FAILED']);
            }
            DB::commit();
            return response()->json(['responseCode' => '2000000', 'responseMessage' => 'Success']);

        } catch (Exception $e) {
            DB::rollBack();
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

                // 2. Tambah Saldo Member
                if (in_array($trx->type, ['TOPUP', 'DEPOSIT'])) {
                    DB::table('affiliates')->where('id', $trx->affiliate_id)->increment('balance', $paidAmount);

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
                    } catch (\Exception $e) { Log::warning("Skip mutasi member: " . $e->getMessage()); }

                    Log::info("[DANA-WEBHOOK] ✅ Saldo Member ID {$trx->affiliate_id} bertambah: +$paidAmount");
                }
            } else {
                DB::table('dana_transactions')->where('id', $trx->id)->update(['status' => 'FAILED']);
            }
            DB::commit();
            return response()->json(['responseCode' => '2000000', 'responseMessage' => 'Success']);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // --- HELPER KHUSUS ORDER ---

    private function dispatchWaNotification($order, $paidAmount)
    {
        try {
            $msgCustomer = "Halo Kak *{$order->customer_name}* 👋\n\n" .
                           "Terima kasih! Pembayaran sebesar *Rp " . number_format($paidAmount, 0, ',', '.') . "* via DANA berhasil kami terima.\n\n" .
                           "🧾 *Invoice:* {$order->order_number}\n" .
                           "📦 *Status:* Diproses\n\n" .
                           "Lihat Struk: " . url('/invoice/' . $order->order_number);

            if ($order->customer_phone) {
                \App\Jobs\SendWhatsappJob::dispatch($order->customer_phone, $msgCustomer);
            }

            // WA Admin
            $msgAdmin = "💰 *ORDER DANA MASUK*\n\n" .
                        "Invoice: {$order->order_number}\n" .
                        "Nama: {$order->customer_name}\n" .
                        "Total: Rp " . number_format($paidAmount, 0, ',', '.') . "\n" .
                        "Status: LUNAS (DANA)";

            \App\Jobs\SendWhatsappJob::dispatch('085745808809', $msgAdmin);

        } catch (\Exception $e) {
            Log::error("WA Job Error: " . $e->getMessage());
        }
    }

    private function processAffiliateCommission($couponCode, $finalPrice)
    {
        try {
            $affiliateOwner = Affiliate::where('coupon_code', $couponCode)->first();
            if ($affiliateOwner) {
                $komisiRate = 0.10;
                $komisiDiterima = $finalPrice * $komisiRate;
                $affiliateOwner->increment('balance', $komisiDiterima);

                \App\Jobs\SendWhatsappJob::dispatch(
                    $affiliateOwner->whatsapp,
                    "💰 *KOMISI MASUK*\n\nSelamat! Kupon *{$couponCode}* digunakan.\nKomisi: Rp " . number_format($komisiDiterima, 0, ',', '.')
                );
            }
        } catch (\Exception $e) {
            Log::error("Affiliate Commission Error: " . $e->getMessage());
        }
    }
}
