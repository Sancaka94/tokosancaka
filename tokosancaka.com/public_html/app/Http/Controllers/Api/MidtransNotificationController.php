<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str; 
use App\Models\Api as ApiModel; 

// IMPORT KETIGA CONTROLLER INI
use App\Http\Controllers\Customer\TopUpController; 
use App\Http\Controllers\Customer\CheckoutController; 
use App\Http\Controllers\Customer\PesananController; // <-- INI YANG KURANG SEBELUMNYA

class MidtransNotificationController extends Controller
{
    public function handlePaymentNotification(Request $request)
    {
        Log::info('LOG LOG: MIDTRANS WEBHOOK INCOMING', $request->all());

        try {
            $notification = $request->all();
            
            $orderId           = $notification['order_id'] ?? null;
            $statusCode        = $notification['status_code'] ?? null;
            $grossAmount       = $notification['gross_amount'] ?? null;
            $transactionStatus = $notification['transaction_status'] ?? null;
            $signatureKey      = $notification['signature_key'] ?? null;

            if (!$orderId) {
                return response()->json(['message' => 'Invalid Request Data'], 400);
            }

            // 1. Verifikasi Signature Key Midtrans
            $mode      = ApiModel::getValue('MIDTRANS_MODE', 'global', 'sandbox');
            $serverKey = ApiModel::getValue('MIDTRANS_SERVER_KEY', $mode);
            
            $mySignatureKey = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
            
            if ($signatureKey !== $mySignatureKey) {
                return response()->json(['message' => 'Invalid Signature'], 403);
            }

            // 2. Mapping Status Midtrans ke Internal Status
            $internalStatus = 'PENDING';
            if (in_array($transactionStatus, ['capture', 'settlement'])) {
                $internalStatus = 'PAID';
            } elseif (in_array($transactionStatus, ['deny', 'cancel', 'expire', 'failure'])) {
                $internalStatus = 'FAILED';
            }

            // =====================================================================
            // 3. LOGIKA CERDAS ROUTER
            // =====================================================================
            
            if (Str::startsWith($orderId, 'TOPUP-')) {
                return TopUpController::processTopUpCallback($orderId, $internalStatus, $grossAmount);
            } 
            
            elseif (Str::startsWith($orderId, 'SCK-AGEN-')) {
                $checkoutController = new CheckoutController();
                $checkoutController->processOrderCallback($orderId, $internalStatus, $notification);
                return response()->json(['message' => 'Marketplace Order updated']);
            }
            
            // KASUS C: PESANAN PAKET MANUAL
            elseif (Str::startsWith($orderId, 'SCK-')) {
                Log::info('LOG LOG: Order terdeteksi sebagai PESANAN MANUAL EXPRESS.');
                
                // MENGAKSES PESANAN CONTROLLER
                $pesananController = new PesananController();
                $pesananController->processPesananCallback($orderId, $internalStatus);
                
                return response()->json(['message' => 'Pesanan Manual updated']);
            }

            else {
                return response()->json(['message' => 'Unknown Order Prefix'], 400);
            }

        } catch (\Exception $e) {
            Log::error('LOG LOG: MIDTRANS NOTIFY ERROR: ' . $e->getMessage());
            return response()->json(['message' => 'System Error'], 500);
        }
    }
}