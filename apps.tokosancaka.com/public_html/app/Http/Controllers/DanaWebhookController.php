<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; // Logging aktif
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Carbon\Carbon; // <--- Pastikan baris ini ada di paling atas file
use App\Http\Controllers\DepositController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\MemberAuthController; // Import controller lain jika perlu

// Models
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderDetail;
use App\Models\OrderAttachment;
use App\Models\Coupon;
use App\Models\Affiliate;
use App\Models\Store;
use App\Models\TopUp;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Category;
use App\Models\Customer;
use App\Models\ProductVariant; // <--- TAMBAHKAN INI

class DanaWebhookController extends Controller
{
    public function handleNotify(Request $request)
{
    // 1. LOG BAHWA DATA MASUK
    Log::info("[DANA-REAL] Webhook Masuk!", $request->all());

    try {
        $data = $request->all();

        // Ambil Data Penting
        $refNo  = $data['partnerReferenceNo'] ?? null;
        $status = $data['orderStatus'] ?? null; // FINISHED / SUCCESS

        // Ambil Nominal (Pastikan aman dari null)
        $amountVal = $data['amount']['value'] ?? 0;
        $paidAmount = (float) $amountVal;

        if (!$refNo) {
            Log::warning("[DANA-REAL] RefNo tidak ada.");
            return response()->json(['res' => 'NO_REF'], 400);
        }

        // 2. CARI TRANSAKSI DI DATABASE
        $trx = DB::table('dana_transactions')->where('reference_no', $refNo)->first();

        // Jika transaksi tidak ada, atau SUDAH SUKSES sebelumnya, langsung return OK (biar DANA gak kirim ulang)
        if (!$trx) {
            Log::info("[DANA-REAL] Transaksi tidak ditemukan: $refNo");
            return response()->json(['responseCode' => '2000000', 'responseMessage' => 'Success']);
        }

        if ($trx->status === 'SUCCESS') {
            Log::info("[DANA-REAL] Transaksi sudah sukses sebelumnya: $refNo");
            return response()->json(['responseCode' => '2000000', 'responseMessage' => 'Success']);
        }

        // 3. PROSES UPDATE (Hanya jika status FINISHED)
        if ($status === 'FINISHED' || $status === 'SUCCESS') {

            DB::beginTransaction();
            try {
                // A. Update Status Transaksi jadi SUCCESS
                DB::table('dana_transactions')->where('id', $trx->id)->update([
                    'status' => 'SUCCESS',
                    'updated_at' => now(),
                    'response_payload' => json_encode($data)
                ]);

                // B. Tambah Saldo Member
                DB::table('affiliates')->where('id', $trx->affiliate_id)->increment('balance', $paidAmount);

                // C. Kurangi Saldo DANA User (Pencatatan)
                DB::table('affiliates')->where('id', $trx->affiliate_id)->decrement('dana_user_balance', $paidAmount);

                DB::commit();

                Log::info("[DANA-REAL] ✅ SUKSES UPDATE SALDO: $refNo sebesar Rp $paidAmount");

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("[DANA-REAL] Gagal Update DB: " . $e->getMessage());
                // Jangan return 500 jika ini terjadi biar DANA nyoba lagi nanti
                return response()->json(['responseCode' => '500', 'message' => 'DB Error'], 500);
            }

        } elseif ($status === 'FAILED') {
            // Jika Gagal
            DB::table('dana_transactions')->where('id', $trx->id)->update([
                'status' => 'FAILED',
                'updated_at' => now(),
                'response_payload' => json_encode($data)
            ]);
            Log::info("[DANA-REAL] Transaksi Gagal: $refNo");
        }

        // 4. BALASAN WAJIB "200 OK" KE DANA
        return response()->json([
            'responseCode' => '2000000',
            'responseMessage' => 'Success'
        ]);

    } catch (\Exception $e) {
        Log::error("[DANA-REAL] Crash: " . $e->getMessage());
        return response()->json(['responseCode' => '500', 'message' => 'Internal Error'], 500);
    }
}

    /**
     * LOGIKA KHUSUS DEPOSIT
     */
    private function handleDeposit($data)
    {
        Log::info("[DANA-WEBHOOK] Mendeteksi Transaksi DEPOSIT");

        $refNo = $data['partnerReferenceNo'];
        $status = $data['orderStatus'];
        $amount = (float) ($data['amount']['value'] ?? 0);

        // Cari Transaksi
        $trx = DB::table('dana_transactions')->where('reference_no', $refNo)->first();

        if (!$trx || $trx->status == 'SUCCESS') {
            return response()->json(['responseCode' => '2000000', 'responseMessage' => 'Success']);
        }

        if ($status === 'FINISHED' || $status === 'SUCCESS') {
            DB::beginTransaction();
            try {
                // Update Status Transaksi
                DB::table('dana_transactions')
                    ->where('id', $trx->id)
                    ->update(['status' => 'SUCCESS', 'updated_at' => now(), 'response_payload' => json_encode($data)]);

                // Tambah Saldo Affiliate
                DB::table('affiliates')->where('id', $trx->affiliate_id)->increment('balance', $amount);
                // Kurangi Saldo DANA User (Opsional/Estimasi)
                DB::table('affiliates')->where('id', $trx->affiliate_id)->decrement('dana_user_balance', $amount);

                DB::commit();
                Log::info("[DANA-WEBHOOK] Deposit Sukses: $refNo");
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Gagal Update Deposit: ".$e->getMessage());
            }
        } elseif ($status === 'FAILED') {
            DB::table('dana_transactions')->where('id', $trx->id)->update(['status' => 'FAILED']);
        }

        return response()->json(['responseCode' => '2000000', 'responseMessage' => 'Success']);
    }

    /**
     * LOGIKA KHUSUS ORDER
     */
    private function handleOrder($data)
    {
        Log::info("[DANA-WEBHOOK] Mendeteksi Transaksi ORDER");

        $refNo = $data['partnerReferenceNo'];
        $status = $data['orderStatus'];

        // Contoh Logika Order
        // $order = Order::where('invoice_number', $refNo)->first();
        // if ($status == 'FINISHED') { $order->update(['status' => 'PAID']); }

        return response()->json(['responseCode' => '2000000', 'responseMessage' => 'Success']);
    }
}
