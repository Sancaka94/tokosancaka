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
    // --- MODE DEBUG ON ---
    try {
        // 1. Cek apakah Log aktif
        Log::info("[DANA-DEBUG] Hit Masuk", $request->all());

        // 2. Ambil Data
        $data = $request->all();
        $refNo = $data['partnerReferenceNo'] ?? null;
        $status = $data['orderStatus'] ?? null;

        // Cek struktur amount (sering error disini jika JSON salah)
        if (isset($data['amount']) && is_array($data['amount'])) {
            $paidAmount = (float) ($data['amount']['value'] ?? 0);
        } else {
            $paidAmount = 0;
        }

        if (!$refNo) {
            throw new \Exception("Parameter partnerReferenceNo tidak ditemukan di JSON body.");
        }

        // 3. Cek DB Connection & Transaksi
        $trx = DB::table('dana_transactions')->where('reference_no', $refNo)->first();

        if (!$trx) {
            return response()->json([
                'status' => 'warning',
                'message' => "Transaksi $refNo tidak ditemukan di Database."
            ]);
        }

        // 4. Simulasi Logic (Tanpa Update dulu biar aman, kita cek errornya dmn)
        // Kalau code sampai sini jalan, berarti masalah bukan di logic awal

        // Coba Update DB (Test Write)
        if ($status === 'FINISHED') {
             DB::beginTransaction();

             // Tes Update status
             DB::table('dana_transactions')->where('id', $trx->id)->update(['updated_at' => now()]);

             // Tes Increment Saldo
             DB::table('affiliates')->where('id', $trx->affiliate_id)->increment('balance', $paidAmount);

             DB::commit();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Code Berjalan Lancar!',
            'data_received' => [
                'ref' => $refNo,
                'status' => $status,
                'amount' => $paidAmount
            ]
        ]);

    } catch (\Throwable $e) {
        // --- TANGKAP ERROR DAN TAMPILKAN DI POSTMAN ---
        // Jika DB::rollback perlu
        if (DB::transactionLevel() > 0) DB::rollBack();

        return response()->json([
            'ERROR_TYPE' => 'CRITICAL_ERROR',
            'MESSAGE' => $e->getMessage(),
            'FILE' => $e->getFile(),
            'LINE' => $e->getLine()
        ], 500);
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
