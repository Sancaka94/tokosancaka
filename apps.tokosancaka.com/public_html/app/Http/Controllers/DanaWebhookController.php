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
    /**
     * MASTER HANDLER UNTUK SEMUA WEBHOOK DANA
     */
    public function handleNotify(Request $request)
    {
        // 1. Log Request Masuk (Pusat Log)
        Log::info("[DANA-WEBHOOK-MASTER] Hit Masuk", $request->all());

        try {
            $data = $request->all();
            $refNo = $data['partnerReferenceNo'] ?? '';
            $status = $data['orderStatus'] ?? '';

            // 2. DETEKSI TIPE TRANSAKSI BERDASARKAN PREFIX REF NO

            // KASUS A: Deposit (Kode: DEP-xxxx)
            if (Str::startsWith($refNo, 'DEP-')) {
                return $this->handleDeposit($data);
            }

            // KASUS B: Order / Belanja (Kode: ORD-xxxx atau INV-xxxx)
            if (Str::startsWith($refNo, 'ORD-') || Str::startsWith($refNo, 'INV-')) {
                return $this->handleOrder($data);
            }

            // KASUS C: Member Registration (Kode: MEM-xxxx)
            if (Str::startsWith($refNo, 'MEM-')) {
                // return $this->handleMemberAuth($data);
                // Anda bisa buat method handleMemberAuth di bawah
            }

            // Jika tidak dikenali
            Log::warning("[DANA-WEBHOOK-MASTER] Prefix RefNo tidak dikenali: $refNo");
            return response()->json(['responseCode' => '2000000', 'responseMessage' => 'Success (Ignored)']);

        } catch (\Exception $e) {
            Log::error("[DANA-WEBHOOK-MASTER] FATAL ERROR: " . $e->getMessage());
            // Tetap return 500 agar DANA tahu ada error di server kita
            return response()->json(['responseCode' => '500', 'message' => 'Internal Server Error'], 500);
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
