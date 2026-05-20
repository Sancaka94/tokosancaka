<?php

namespace App\Http\Controllers\Api; // <-- Sudah diperbaiki menjadi Api (huruf kecil)

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Api as ApiModel; // Alias untuk menghindari bentrok nama dengan namespace
use App\Http\Controllers\Customer\TopUpController; 

class MidtransNotificationController extends Controller
{
    /**
     * =========================================================================
     * HANDLER WEBHOOK MIDTRANS
     * Menerima notifikasi otomatis dari server Midtrans (HTTP POST)
     * =========================================================================
     */
    public function handlePaymentNotification(Request $request)
    {
        Log::info('LOG LOG: MIDTRANS WEBHOOK INCOMING', $request->all());

        try {
            $notification = $request->all();
            
            // Tangkap parameter penting dari payload Midtrans
            $orderId           = $notification['order_id'] ?? null;
            $statusCode        = $notification['status_code'] ?? null;
            $grossAmount       = $notification['gross_amount'] ?? null;
            $transactionStatus = $notification['transaction_status'] ?? null;
            $signatureKey      = $notification['signature_key'] ?? null;

            if (!$orderId) {
                Log::error('LOG LOG: MIDTRANS WEBHOOK - Order ID kosong/tidak valid.');
                return response()->json(['message' => 'Invalid Request Data'], 400);
            }

            // 1. Verifikasi Signature Key Midtrans (Keamanan Wajib)
            // Menggunakan alias ApiModel untuk memanggil model konfigurasi
            $mode      = ApiModel::getValue('MIDTRANS_MODE', 'global', 'sandbox');
            $serverKey = ApiModel::getValue('MIDTRANS_SERVER_KEY', $mode);
            
            $mySignatureKey = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
            
            if ($signatureKey !== $mySignatureKey) {
                Log::error('LOG LOG: MIDTRANS WEBHOOK INVALID SIGNATURE', [
                    'expected' => $mySignatureKey,
                    'received' => $signatureKey
                ]);
                return response()->json(['message' => 'Invalid Signature'], 403);
            }

            // 2. Mapping Status Midtrans ke Internal Status Sancaka (PAID / FAILED)
            $internalStatus = 'PENDING';
            if ($transactionStatus == 'capture' || $transactionStatus == 'settlement') {
                $internalStatus = 'PAID';
            } elseif (in_array($transactionStatus, ['deny', 'cancel', 'expire', 'failure'])) {
                $internalStatus = 'FAILED';
            }

            Log::info('LOG LOG: MIDTRANS WEBHOOK TERVERIFIKASI. Meneruskan ke TopUpController...', [
                'order_id' => $orderId,
                'status'   => $internalStatus
            ]);

            // 3. Teruskan ke fungsi prosesor Top Up Sancaka yang sudah ada
            return TopUpController::processTopUpCallback($orderId, $internalStatus, $grossAmount);

        } catch (\Exception $e) {
            Log::error('LOG LOG: MIDTRANS NOTIFY ERROR: ' . $e->getMessage());
            return response()->json(['message' => 'System Error'], 500);
        }
    }

    /**
     * (Opsional) Fungsi cadangan jika Anda menggunakan fitur menghubungkan akun (GoPay Tokenization)
     */
    public function handleAccountLinkingNotification(Request $request)
    {
        Log::info('LOG LOG: MIDTRANS ACCOUNT LINKING WEBHOOK', $request->all());
        return response()->json(['success' => true]);
    }

    /**
     * (Opsional) Fungsi cadangan jika Anda menggunakan fitur pembayaran berulang (Subscription)
     */
    public function handleRecurringNotification(Request $request)
    {
        Log::info('LOG LOG: MIDTRANS RECURRING WEBHOOK', $request->all());
        return response()->json(['success' => true]);
    }
}