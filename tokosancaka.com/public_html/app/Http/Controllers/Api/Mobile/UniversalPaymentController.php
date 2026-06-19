<?php

// App\Http\Controllers\Api\Mobile\UniversalPaymentController.php


namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use App\Services\DanaSignatureService;

class UniversalPaymentController extends Controller
{
    protected $danaSignature;

    public function __construct(DanaSignatureService $danaSignature)
    {
        $this->danaSignature = $danaSignature;
    }

    public function universalCancel(Request $request, $orderId)
    {
        $orderId = strtoupper($orderId);
        
        // 1. Delegate to TopUp Logic
        if (Str::startsWith($orderId, 'TOPUP-') || Str::startsWith($orderId, 'DANATOPUP-')) {
             return app(\App\Http\Controllers\Api\Mobile\TopUpController::class)->cancelDanaPayment($orderId);
        }

        // 2. Delegate to PPOB Logic (You will need to implement cancel logic in PpobController)
        if (Str::startsWith($orderId, 'PPOBD-')) {
             return response()->json(['success' => false, 'message' => 'Pembatalan PPOB belum didukung.']);
        }

        // 3. Delegate to Marketplace Orders
        if (Str::startsWith($orderId, 'ORD-') || Str::startsWith($orderId, 'SCK-ORD-')) {
             // You need to call your Marketplace Order Cancel logic here
             // Example: return app(CheckoutController::class)->cancelOrder($orderId);
             return response()->json(['success' => false, 'message' => 'Pembatalan Order Marketplace belum didukung.']);
        }

        return response()->json(['success' => false, 'message' => 'Format Invoice tidak dikenali sistem.']);
    }

    public function universalRefund(Request $request, $orderId)
    {
        $orderId = strtoupper($orderId);
        
        // Follow the same delegation pattern for refunds
        if (Str::startsWith($orderId, 'TOPUP-') || Str::startsWith($orderId, 'DANATOPUP-')) {
             return app(\App\Http\Controllers\Api\Mobile\TopUpController::class)->refundDanaPayment($orderId);
        }

        return response()->json(['success' => false, 'message' => 'Refund untuk tipe invoice ini belum didukung.']);
    }
}