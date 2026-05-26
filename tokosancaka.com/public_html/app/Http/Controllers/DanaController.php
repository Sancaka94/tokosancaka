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
        Log::info('LOG LOG: [DANA ORDER] Memulai Auto-Debit / Checkout untuk Order: ' . $order->invoice_number);

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
            $key = str_replace('-----END PRIVATE KEY-----', '', $key); // Tambahan jaga-jaga
            $key = str_replace('-----END RSA PRIVATE KEY-----', '', $key);
            $key = preg_replace('/\s+/', '', $key);
            $privateKeyContent = "-----BEGIN PRIVATE KEY-----\n" . wordwrap($key, 64, "\n", true) . "\n-----END PRIVATE KEY-----";

            if (!openssl_pkey_get_private($privateKeyContent)) {
                throw new \Exception('The DANA_PRIVATE_KEY format in the .env file is invalid or the key is corrupted.');
            }

            // 1. Waktu dan Path SNAP BI
            $timestamp = Carbon::now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
            $validUpTo = Carbon::now('Asia/Jakarta')->addMinutes(30)->format('Y-m-d\TH:i:sP');
            $path = '/rest/redirection/v1.0/debit/payment-host-to-host'; // ENDPOINT BARU
            $orderAmount = number_format($order->total_amount, 2, '.', '');

            // 2. Payload Standar SNAP BI Host-to-Host
            $payload = [
                "partnerReferenceNo" => $order->invoice_number,
                "merchantId"         => env('DANA_MERCHANT_ID'), // Pastikan ini diset di .env
                "validUpTo"          => $validUpTo,
                "amount" => [
                    "value"    => $orderAmount,
                    "currency" => "IDR"
                ],
                "urlParams" => [
                    [
                        "url"        => route('dana.payment.finish', ['order_id' => $order->id]),
                        "type"       => "PAY_RETURN",
                        "isDeeplink" => "Y"
                    ],
                    [
                        "url"        => route('dana.payment.notify'),
                        "type"       => "NOTIFICATION",
                        "isDeeplink" => "N"
                    ]
                ],
                "payOptionDetails" => [
                    [
                        "payMethod"   => "BALANCE",
                        "payOption"   => "BALANCE",
                        "transAmount" => [
                            "value"    => $orderAmount,
                            "currency" => "IDR"
                        ]
                    ]
                ],
                "additionalInfo" => [
                    "supportDeepLinkCheckoutUrl" => "true",
                    "productCode"                => "51051000100000000001",
                    "mcc"                        => "5732",
                    "order" => [
                        "orderTitle"        => substr("Payment for Inv " . $order->invoice_number, 0, 64),
                        "merchantTransType" => "01",
                        "scenario"          => "DIRECT_DEBIT",
                        "buyer" => [
                            // Opsional: Jika Order punya relasi ke User, ganti dengan ID User. Jika tidak, pakai random string
                            "externalUserId"   => "USER-" . time(), 
                            "externalUserType" => "MERCHANT_USER",
                            "nickname"         => "Customer"
                        ]
                    ],
                    "envInfo" => [
                        "sourcePlatform"    => "IPG",
                        "terminalType"      => "SYSTEM",
                        "orderTerminalType" => "WEB"
                    ]
                ]
            ];

            // 3. String to Sign SNAP BI (Method:Path:HashBody:Timestamp)
            $jsonBody = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $hashedBody = strtolower(hash('sha256', $jsonBody));
            $stringToSign = 'POST:' . $path . ':' . $hashedBody . ':' . $timestamp;

            Log::info('LOG LOG: [DANA ORDER] String To Sign: ' . $stringToSign);

            $signature = $this->sign($stringToSign, $privateKeyContent);

            // 4. Request Header SNAP BI
            $headers = [
                'Content-Type'  => 'application/json',
                // Catatan: Jika ini order biasa dan tidak auto-debit (user tetap harus login/masukin PIN), 
                // kita cukup lempar Token B2B. Anda tidak perlu mengirim Authorization-Customer.
                'X-TIMESTAMP'   => $timestamp,
                'X-SIGNATURE'   => $signature,
                'X-PARTNER-ID'  => $clientId,
                'X-EXTERNAL-ID' => (string) time() . \Illuminate\Support\Str::random(6),
                'CHANNEL-ID'    => '95221'
            ];

            Log::info('LOG LOG: [DANA ORDER] Payload Body: ', $payload);

            // 5. Eksekusi API
            $response = Http::withHeaders($headers)
                            ->withBody($jsonBody, 'application/json')
                            ->post($danaApiUrl . $path);

            $result = $response->json();
            
            Log::info('LOG LOG: [DANA ORDER] Response Result: ', $result);

            // Cek sukses respon (2005400)
            if (isset($result['responseCode']) && $result['responseCode'] === '2005400') {
                if (!empty($result['webRedirectUrl'])) {
                    $order->update(['payment_url' => $result['webRedirectUrl']]);
                    return redirect()->away($result['webRedirectUrl']);
                }
            } 
            
            // Jika gagal
            Log::error('LOG LOG: DANA API Response Error: ', $result);
            $errorMessage = $result['responseMessage'] ?? 'Unknown Error';
            throw new \Exception('Failed to initiate payment with DANA. Details: ' . $errorMessage);

        } catch (\Exception $e) {
            Log::error('LOG LOG: DANA API Exception: ' . $e->getMessage());
            
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

