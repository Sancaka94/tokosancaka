<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use Exception;

// Gunakan class dari DANA SDK
use Dana\Dana;
use Dana\Models\Request\RequestOrder;
use Dana\Models\Response\ResponseOrder;

class PaymentController extends Controller
{
    /**
     * Konstruktor untuk mengatur kredensial DANA.
     */
    public function __construct()
    {
        // Mengatur environment dan kredensial DANA dari file config/dana.php
        // Pastikan Anda sudah mem-publish file config-nya.
        try {
            Dana::setEnvironment(config('dana.environment'));
            Dana::setCredentials(config('dana.credentials'));
        } catch (Exception $e) {
            // Catat error jika konfigurasi tidak ditemukan
            Log::critical('Konfigurasi DANA tidak ditemukan. Jalankan "php artisan vendor:publish --provider=Dana\\DanaServiceProvider". Error: ' . $e->getMessage());
        }
    }

    /**
     * Membuat permintaan pembayaran ke DANA menggunakan SDK.
     */
    public function createPayment(Order $order)
    {
        try {
            // 1. Buat objek RequestOrder
            $requestOrder = new RequestOrder();
            $requestOrder->orderTitle = "Pembayaran Order #" . $order->invoice_number;
            $requestOrder->orderAmount = [
                "currency" => "IDR",
                "value" => number_format($order->total_amount, 2, '.', '')
            ];
            $requestOrder->merchantId = config('dana.credentials.merchantId');
            $requestOrder->paymentNotifyUrl = route('payment.callback');
            $requestOrder->paymentRedirectUrl = route('payment.finish');
            $requestOrder->partnerReferenceNo = (string) $order->id; // Gunakan ID order sebagai referensi

            Log::info('Membuat request order DANA:', $requestOrder->toArray());

            // 2. Kirim request ke DANA melalui SDK
            $response = Dana::createOrder($requestOrder);

            // 3. Proses response
            if ($response->isSuccess() && !empty($response->webRedirectUrl)) {
                // Jika berhasil, redirect pengguna ke halaman pembayaran DANA
                return redirect()->away($response->webRedirectUrl);
            } else {
                // Jika gagal, catat error dan kembalikan pengguna
                Log::error('Gagal membuat pembayaran DANA:', [
                    'responseCode' => $response->responseCode,
                    'responseMessage' => $response->responseMessage
                ]);
                return back()->with('error', 'Gagal memulai pembayaran dengan DANA. Pesan: ' . $response->responseMessage);
            }

        } catch (Exception $e) {
            Log::error('Error saat membuat pembayaran DANA (SDK): ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan sistem saat memproses pembayaran.');
        }
    }

    /**
     * Menangani notifikasi/callback pembayaran dari DANA.
     */
    public function handleCallback(Request $request)
    {
        Log::info('Payment Callback DANA Diterima: ', $request->all());

        try {
            // SDK akan otomatis memverifikasi signature
            $notification = Dana::notification();

            if (!$notification->isSignatureValid()) {
                Log::error('Signature callback DANA tidak valid.');
                return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 403);
            }
            
            $orderId = $notification->partnerReferenceNo;
            $transactionStatus = $notification->status;

            DB::transaction(function () use ($orderId, $transactionStatus) {
                $order = Order::findOrFail($orderId);

                if ($transactionStatus == 'SUCCESS') {
                    $order->status = 'paid';
                } elseif ($transactionStatus == 'PENDING') {
                    $order->status = 'pending';
                } else { // FAILED, EXPIRED, CANCELED
                    $order->status = 'failed';
                }
                
                $order->save();
            });

            return response()->json(Dana::accept());

        } catch (Exception $e) {
            Log::error('Error memproses callback DANA: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Menampilkan halaman "finish" kepada pengguna.
     */
    public function finishPage(Request $request)
    {
        // Logika halaman finish tetap sama
        $status = $request->query('status'); 
        $orderId = $request->query('partnerReferenceNo');

        if ($status === 'SUCCESS') {
            $message = "Pembayaran untuk pesanan #{$orderId} telah berhasil!";
            $isSuccess = true;
        } else if ($status === 'PENDING') {
            $message = "Kami sedang menunggu konfirmasi pembayaran untuk pesanan #{$orderId}.";
            $isSuccess = null;
        } else {
            $message = "Pembayaran untuk pesanan #{$orderId} gagal atau dibatalkan.";
            $isSuccess = false;
        }

        return view('payment.finish', ['isSuccess' => $isSuccess, 'message' => $message]);
    }
}
