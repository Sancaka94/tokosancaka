<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Order; // Make sure you have an Order model

class DanaController extends Controller
{
    /**
     * Create a payment request to DANA based on an existing order.
     *
     * @param \App\Models\Order $order
     */
    public function createPayment(Order $order)
    {
        try {
            // Get credentials from the .env file for security
            $clientId = env('DANA_CLIENT_ID');
            $privateKey = env('DANA_PRIVATE_KEY');
            $danaApiUrl = env('DANA_API_URL', 'https://api.sandbox.dana.id');

            if (empty($clientId) || empty($privateKey) || empty($danaApiUrl)) {
                throw new \Exception('DANA credentials are not completely configured. Please check your .env file.');
            }

            // Clean and reformat the private key
            $key = str_replace('-----BEGIN RSA PRIVATE KEY-----', '', $privateKey);
            $key = str_replace('-----END RSA PRIVATE KEY-----', '', $key);
            $key = preg_replace('/\s+/', '', $key);
            $privateKeyContent = "-----BEGIN RSA PRIVATE KEY-----\n" . wordwrap($key, 64, "\n", true) . "\n-----END RSA PRIVATE KEY-----";

            if (!openssl_pkey_get_private($privateKeyContent)) {
                throw new \Exception('The DANA_PRIVATE_KEY format in the .env file is invalid or the key is corrupted.');
            }

            // Get details from the Order model
            $orderTitle = "Payment for Invoice #" . $order->invoice_number;
            $orderAmount = number_format($order->total_amount, 2, '.', '');

            $payload = [
                "order" => [
                    "orderTitle" => $orderTitle,
                    "orderAmount" => [
                        "currency" => "IDR",
                        "value" => $orderAmount
                    ],
                ],
                "merchantInfo" => [
                    "callbackUrl" => route('dana.payment.notify'),
                    "returnUrl" => route('dana.payment.finish', ['order_id' => $order->id]),
                ],
                "externalId" => $order->invoice_number,
            ];

            $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
            $timestamp = Carbon::now('Asia/Jakarta')->toIso8601String();
            
            $hash = hash('sha256', $body);
            $path = "/v1.0/debit/payment.htm"; // Adjust to your DANA API endpoint
            
            $stringToSign = 'POST:' . $path . ':' . $hash . ':' . $timestamp;

            $signature = $this->sign($stringToSign, $privateKeyContent);

            $response = Http::withHeaders([
                'X-CLIENT-KEY' => $clientId,
                'X-TIMESTAMP'  => $timestamp,
                'X-SIGNATURE'  => 'RSA256=' . $signature,
                'Content-Type' => 'application/json'
            ])->post($danaApiUrl . $path, $payload);

            $result = $response->json();

            if ($response->successful() && isset($result['webRedirectUrl'])) {
                $order->update(['payment_url' => $result['webRedirectUrl']]);
                return redirect()->away($result['webRedirectUrl']);
            } else {
                // Tambahkan log untuk melihat response mentah dari DANA
                Log::error('DANA API Response Error: ' . $response->body());
                throw new \Exception('Failed to initiate payment with DANA. Details: ' . ($result['message'] ?? $response->body()));
            }

        } catch (\Exception $e) {
            Log::error('DANA API Exception: ' . $e->getMessage());
            
            // ✅ AKTIFKAN BARIS INI UNTUK MELIHAT ERROR SECARA LANGSUNG
            // dd($e->getMessage());

            $detailedErrorMessage = 'Gagal memproses pembayaran DANA. Detail Error: ' . $e->getMessage();
            return redirect()->route('checkout.index')->with('error', $detailedErrorMessage);
        }
    }

    /**
     * Handle the redirect from DANA after the user completes the payment.
     */
    public function handleFinishRedirect(Request $request)
    {
        $orderId = $request->query('order_id');
        $order = Order::find($orderId);

        if (!$order) {
            return redirect()->route('etalase.index')->with('error', 'Order not found.');
        }

        // Redirect the user to their order details page with a pending message.
        // The actual confirmation will come via the webhook.
        return redirect()->route('customer.orders.show', $order->id)
            ->with('success', 'Payment is being processed. Please wait for the next notification.');
    }

    /**
     * Handle notifications (webhooks) from the DANA server.
     * This is the most critical part for confirming payment.
     */
    public function handleNotification(Request $request)
    {
        Log::info('DANA Webhook Received:', $request->all());

        try {
            // 1. Get headers and body from the DANA request
            $danaSignature = $request->header('X-SIGNATURE');
            $danaTimestamp = $request->header('X-TIMESTAMP');
            $requestBody = $request->getContent(); // Get raw body

            if (empty($danaSignature)) {
                Log::warning('DANA Webhook: Signature is missing.');
                return response()->json(['status' => 'error', 'message' => 'Signature is missing'], 400);
            }

            // 2. Validate the signature
            $danaPublicKey = env('DANA_PUBLIC_KEY'); // You need to store DANA's public key in .env
            if (empty($danaPublicKey)) {
                throw new \Exception('DANA Public Key is not configured.');
            }
            
            // Reformat the public key if necessary (same as the private key)
            $key = str_replace('-----BEGIN PUBLIC KEY-----', '', $danaPublicKey);
            $key = str_replace('-----END PUBLIC KEY-----', '', $key);
            $key = preg_replace('/\s+/', '', $key);
            $publicKeyContent = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($key, 64, "\n", true) . "\n-----END PUBLIC KEY-----";

            // The stringToSign format for notifications (adjust according to DANA's documentation)
            $stringToSign = $requestBody . ':' . $danaTimestamp;

            // Extract the signature from the header (remove 'RSA256=')
            $actualSignature = substr($danaSignature, 7);

            $isSignatureValid = $this->verify($stringToSign, $actualSignature, $publicKeyContent);

            if (!$isSignatureValid) {
                Log::error('DANA Webhook: Invalid Signature.');
                return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 401);
            }

            // 3. If the signature is valid, process the notification
            $notificationData = json_decode($requestBody, true);
            $externalId = $notificationData['externalId'] ?? null;
            $transactionState = $notificationData['transactionState'] ?? null;

            if (!$externalId) {
                Log::warning('DANA Webhook: externalId not found in notification.');
                return response()->json(['status' => 'error', 'message' => 'externalId not found'], 400);
            }

            $order = Order::where('invoice_number', $externalId)->first();

            if ($order) {
                // Check the transaction status and update your database
                if ($transactionState === 'SUCCESS') {
                    $order->payment_status = 'paid';
                    // Add other logic: send email, start shipping process, etc.
                } else if (in_array($transactionState, ['FAILED', 'EXPIRED'])) {
                    $order->payment_status = 'failed';
                }
                $order->save();
            } else {
                Log::warning('DANA Webhook: Order not found for externalId: ' . $externalId);
            }

            // Send a success response to DANA
            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error('DANA Webhook Exception: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Helper function to create an RSA SHA256 signature.
     */
    private function sign($data, $privateKey)
    {
        $signature = '';
        openssl_sign($data, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        return base64_encode($signature);
    }

    /**
     * Helper function to verify an RSA SHA256 signature.
     */
    private function verify($data, $signature, $publicKey)
    {
        $decodedSignature = base64_decode($signature);
        $publicKeyResource = openssl_pkey_get_public($publicKey);
        
        if ($publicKeyResource === false) {
            Log::error('Could not get public key from a string.');
            return false;
        }

        $result = openssl_verify($data, $decodedSignature, $publicKeyResource, OPENSSL_ALGO_SHA256);
        
        openssl_free_key($publicKeyResource);

        return $result === 1; // 1 = valid, 0 = invalid, // -1 = error
    }
}
