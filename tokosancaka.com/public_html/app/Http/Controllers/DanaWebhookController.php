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
use App\Models\Affiliate; // Untuk kelengkapan komisi
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
        // 1. Log Payload Masuk (Persis format log Anda)
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

            // Mapping status dari DANA ke internal proyek Anda
            $internalStatus = in_array($statusRaw, ['SUCCESS', 'FINISHED', '00', 'PAID']) ? 'PAID' : 'FAILED';

            // =============================================================
            // ROUTING LOGIC: Tentukan Tipe Transaksi Berdasarkan Prefix RefNo
            // =============================================================

            // 1. CEK TRANSAKSI ORDER BARANG BARU (Prefix: SCK-ORD- atau SCKORD)
            if (Str::startsWith($refNo, 'SCK-ORD-') || Str::startsWith($refNo, 'ORD-') || Str::startsWith($refNo, 'SCKORD')) {
                Log::info('Routing DANA callback to processOrderCallback (Marketplace)', ['ref' => $refNo]);

                // Gunakan class CheckoutController untuk memproses order agar resi KiriminAja otomatis keluar
                $checkoutCtrl = app(\App\Http\Controllers\Toko\CheckoutController::class);
                $checkoutCtrl->processOrderCallback($refNo, $internalStatus, $data);

                return response()->json(['responseCode' => '2005600', 'responseMessage' => 'Successful'])
                        ->withHeaders(['X-TIMESTAMP' => Carbon::now()->toIso8601String()]);
            }

            // 2. CEK TRANSAKSI ORDER LAMA / LEGACY (Prefix: SCK-)
            elseif (Str::startsWith($refNo, 'SCK-')) {
                Log::info('Routing DANA callback to AdminPesananController (Legacy)', ['ref' => $refNo]);
                AdminPesananController::processPesananCallback($refNo, $internalStatus, $data);

                return response()->json(['responseCode' => '2005600', 'responseMessage' => 'Successful'])
                        ->withHeaders(['X-TIMESTAMP' => Carbon::now()->toIso8601String()]);
            }

            // 3. CEK TOPUP (Prefix: TOPUP- atau DEP-)
            elseif (Str::startsWith($refNo, 'TOPUP-') || Str::startsWith($refNo, 'DEP-')) {
                Log::info('Routing DANA callback to TopUpController', ['ref' => $refNo]);
                TopUpController::processTopUpCallback($refNo, $internalStatus, $paidAmount, $data);

                return response()->json(['responseCode' => '2005600', 'responseMessage' => 'Successful'])
                        ->withHeaders(['X-TIMESTAMP' => Carbon::now()->toIso8601String()]);
            }

            // =============================================================
            // FALLBACK UNTUK PENGUJIAN DASHBOARD MANDIRI DANA SANDBOX
            // =============================================================
            Log::info("[DANA-WEBHOOK] ID Uji Coba Mandiri DANA Sandbox: $refNo. Merespon sukses bypass.");
            return response()->json(['responseCode' => '2005600', 'responseMessage' => 'Successful'])
                    ->withHeaders(['X-TIMESTAMP' => Carbon::now()->toIso8601String()]);

        } catch (Exception $e) {
            Log::error("[DANA-WEBHOOK] Fatal Error: " . $e->getMessage());
            return response()->json(['responseCode' => '5005601', 'message' => 'Internal Error'], 500);
        }
    }
}
