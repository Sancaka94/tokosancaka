<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\NotifikasiUmum;
use App\Events\AdminNotificationEvent;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\TopUp;
use App\Models\User;
use App\Models\Pesanan;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\Affiliate;
use App\Services\KiriminAjaService;
use App\Services\DokuJokulService;
use App\Services\FonnteService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;

// IMPORT CONTROLLER TERKAIT UNTUK CALLBACK ROUTING
use App\Http\Controllers\Admin\PesananController as AdminPesananController;
use App\Http\Controllers\Customer\TopUpController;

class DanaWebhookController extends Controller
{
    /**
     * MAIN HANDLER - GERBANG UTAMA WEBHOOK DANA
     */
    public function handleNotify(Request $request)
    {
        // Log Payload Masuk murni untuk debugging
        Log::info("[DANA-WEBHOOK] Hit Masuk:", [
            'ip' => $request->ip(),
            'payload' => $request->all()
        ]);

        try {
            $data = $request->all();
            $refNo = $data['originalPartnerReferenceNo'] ?? $data['partnerReferenceNo'] ?? null;
            $statusRaw = $data['transactionStatusDesc'] ?? $data['orderStatus'] ?? 'UNKNOWN';

            // Ambil nominal string asli dari DANA (Mencegah bug desimal floating point)
            $amountVal = $data['amount']['value'] ?? '0.00';

            if (empty($refNo)) {
                return response()->json(['res' => 'NO_REF'], 400);
            }

            // Apakah transaksi ini sukses dari DANA?
            $isDanasuccess = in_array($statusRaw, ['SUCCESS', 'FINISHED', '00', 'PAID']);

            // =============================================================
            // ROUTING LOGIC: Tentukan Tipe Transaksi Berdasarkan Prefix RefNo
            // =============================================================

            // 1. SKENARIO BELANJA BARANG (Prefix: SCK-ORD-, ORD-, SCKORD)
            if (Str::startsWith($refNo, 'SCK-ORD-') || Str::startsWith($refNo, 'ORD-') || Str::startsWith($refNo, 'SCKORD') || str_contains($refNo, 'SCKORD')) {
                Log::info('Routing DANA callback to processOrderCallback (Marketplace)', ['ref' => $refNo]);

                $internalStatus = $isDanasuccess ? 'PAID' : 'FAILED';

                $checkoutCtrl = app(\App\Http\Controllers\Toko\CheckoutController::class);
                $checkoutCtrl->processOrderCallback($refNo, $internalStatus, $data);

                return $this->respondSuccessDANA();
            }

            // 2. SKENARIO TOPUP SALDO CUSTOMER / MEMBER / TENANT (Prefix: TOPUP- atau DEP-)
            elseif (Str::startsWith($refNo, 'TOPUP-') || Str::startsWith($refNo, 'DEP-') || str_contains($refNo, 'TOPUP')) {
                Log::info('Routing DANA callback to TopUpController', ['ref' => $refNo, 'amount' => $amountVal]);

                // PERBAIKAN: Kirim status 'SUCCESS' agar sinkron dengan pembaca status topup di database Anda
                $internalStatus = $isDanasuccess ? 'SUCCESS' : 'FAILED';

                // Panggil TopUpController bawaan proyek Anda untuk mengubah status PENDING -> SUCCESS & menambah saldo
                $topUpCtrl = app(TopUpController::class);
                $topUpCtrl->processTopUpCallback($refNo, $internalStatus, $amountVal, $data);

                return $this->respondSuccessDANA();
            }

            // 3. SKENARIO DATA PESANAN BARANG LAMA / LEGACY (Prefix: SCK-)
            elseif (Str::startsWith($refNo, 'SCK-')) {
                Log::info('Routing DANA callback to AdminPesananController (Legacy)', ['ref' => $refNo]);

                $internalStatus = $isDanasuccess ? 'PAID' : 'FAILED';
                AdminPesananController::processPesananCallback($refNo, $internalStatus, $data);

                return $this->respondSuccessDANA();
            }

            // =============================================================
            // FALLBACK UNTUK UJI COBA MANDIRI DASHBOARD DANA SANDBOX
            // =============================================================
            Log::info("[DANA-WEBHOOK] ID Uji Coba Mandiri DANA Sandbox tidak terdaftar di DB: $refNo. Auto bypass.");
            return $this->respondSuccessDANA();

        } catch (Exception $e) {
            Log::error("[DANA-WEBHOOK] Fatal Error: " . $e->getMessage() . " | Line: " . $e->getLine());
            return response()->json(['responseCode' => '5005601', 'message' => 'Internal Error'], 500);
        }
    }

    /**
     * Helper Respon Standard SNAP DANA
     */
    private function respondSuccessDANA()
    {
        return response()->json([
            'responseCode' => '2005600',
            'responseMessage' => 'Successful'
        ])->withHeaders([
            'X-TIMESTAMP' => Carbon::now('Asia/Jakarta')->format('Y-m-d\TH:i:sP')
        ]);
    }
}
