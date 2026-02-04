<?php

namespace App\Http\Controllers;

// Import bawaan Laravel & Helper
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

// Import DOKU Snap SDK
use Doku\Snap\Snap;
use Doku\Snap\Models\PaymentRequest;
use Doku\Snap\Models\Customer as DokuCustomer;
use Doku\Snap\Models\Order as DokuOrder;
use Doku\Snap\Models\Item as DokuItem;
use Doku\Snap\Models\AdditionalInfo;

// Import Controller Anda untuk Dispatcher Webhook
use App\Http\Controllers\CustomerOrderController; // Untuk 'INV-'
use App\Http\Controllers\Customer\TopUpController; // Untuk 'TOPUP-'
use App\Http\Controllers\Admin\PesananController as AdminPesananController; // Untuk 'CVSANCAK-'
use App\Http\Controllers\TestOrderController;


/**
 * =========================================================================
 * DokuPaymentController (Pintu Gerbang Utama DOKU)
 * =========================================================================
 *
 * Controller ini bertindak sebagai PINTU GERBANG UTAMA untuk DOKU.
 * Tugasnya:
 * 1. Menyediakan helper untuk MEMBUAT link pembayaran (Simple & Marketplace).
 * 2. Menerima SEMUA webhook (callback) dan MENDISTRIBUSIKAN
 * tugas ke controller yang tepat.
 *
 */
class DokuPaymentController extends Controller
{
    /**
     * @var \Doku\Snap\Snap
     */
    protected $snap;

    /**
     * Constructor
     *
     * Siapkan konfigurasi DOKU Snap SDK setiap kali controller ini dipanggil.
     */
    public function __construct()
    {
        try {
            // 1. Ambil semua konfigurasi dari config/doku.php
            $config = config('doku'); 
            
            // 2. Validasi konfigurasi dasar
            if (empty($config['client_id']) || empty($config['secret_key']) || 
                empty($config['merchant_private_key']) || empty($config['merchant_public_key'])) { // <-- Validasi diperbarui
                
                Log::critical('DOKU Config Missing: Pastikan CLIENT_ID, SECRET_KEY, MERCHANT_PRIVATE_KEY, dan MERCHANT_PUBLIC_KEY sudah diisi.');
                // Lemparkan exception agar error-nya jelas saat testing
                throw new Exception('Konfigurasi DOKU tidak lengkap. Cek file .env dan config/doku.php');
            }

            // ===================================================================
            // INI ADALAH BLOK YANG DIPERBAIKI (MENGHILANGKAN ERROR ARGUMENT #2)
            // ===================================================================
            
            // 3. Inisialisasi DOKU Snap SDK
            $this->snap = new Snap(
                $config['merchant_private_key'],          // Argument #1 ($privateKey)
                $config['merchant_public_key'],           // Argument #2 ($publicKey) <-- SUDAH DIPERBAIKI (tidak null lagi)
                $config['doku_public_key'],             // Argument #3 ($dokuPublicKey)
                $config['client_id'],                   // Argument #4 ($clientId)
                '',                                   // Argument #5 ($issuer)
                $config['is_production'],               // Argument #6 ($isProduction)
                $config['secret_key'],                  // Argument #7 ($secretKey)
                '',                                   // Argument #8 ($authCode)
                $config['merchant_private_key_passphrase'] // Argument #9 ($passphrase)
            );

            // ===================================================================
            // AKHIR BLOK PERBAIKAN
            // ===================================================================

            // 4. Set environment (sandbox/production)
            $this->snap->setEnv($config['is_production'] ? 'production' : 'sandbox');

        } catch (\Exception $e) {
            // Tangkap error konstruksi (misal file config tidak ada)
            Log::error('Gagal menginisialisasi DokuPaymentController: ' . $e->getMessage());
            // Kita set $this->snap jadi null agar method lain gagal dengan aman
            $this->snap = null; 
        }
    }

    /**
     * Method #1: GENERATE URL (Simple / Form Publik)
     *
     * Untuk form publik (invoice 'CVSANCAK-').
     * Uang akan masuk ke REKENING UTAMA merchant.
     *
     * @param object $orderData (Contoh: {invoice_number: 'CVSANCAK-123', amount: 50000})
     * @param object $customerData (Contoh: {name: 'Budi', email: 'budi@mail.com', phone: '0812...'})
     * @return string|null (URL Pembayaran DOKU atau null jika gagal)
     */
    public function generateSimplePaymentUrl($orderData, $customerData)
    {
        if (!$this->snap) {
            Log::error('DOKU generateSimplePaymentUrl Gagal: Snap SDK tidak terinisialisasi.');
            return null; // Gagal jika constructor error
        }

        try {
            // 1. Siapkan Model Customer DOKU
            $customer = new DokuCustomer();
            $customer->setName($customerData->name);
            $customer->setEmail($customerData->email);
            $customer->setPhone($customerData->phone);

            // 2. Siapkan Model Order DOKU
            $order = new DokuOrder();
            $order->setInvoiceNumber($orderData->invoice_number);
            $order->setAmount($orderData->amount);

            // 3. Siapkan Request Pembayaran
            $paymentRequest = new PaymentRequest();
            $paymentRequest->setCustomer($customer);
            $paymentRequest->setOrder($order);
            
            // TIDAK ADA $paymentRequest->setAdditionalInfo()
            // Ini berarti uang akan masuk ke rekening utama Anda.

            // 4. Panggil API DOKU Snap
            $response = $this->snap->createPayment($paymentRequest);
            return $response->getPaymentUrl(); // Kembalikan URL-nya

        } catch (\Exception $e) {
            Log::error('DOKU Snap Generate Simple URL Error: ' . $e->getMessage(), [
                'invoice' => $orderData->invoice_number ?? 'N/A'
            ]);
            return null; // Kembalikan null jika gagal
        }
    }
    
    /**
     * Method #2: GENERATE URL (Marketplace / Sub-Account)
     *
     * Untuk backend marketplace Anda (invoice 'INV-').
     * Uang akan masuk ke e-wallet (SUB-ACCOUNT) vendor.
     *
     * @param object $orderData (Contoh: {invoice_number: 'INV-456', amount: 150000})
     * @param object $customerData (Contoh: {name: 'Ana', email: 'ana@mail.com'})
     * @param string $vendorSacId (ID Sub-Account vendor, misal: 'SAC-11111111')
     * @return string|null (URL Pembayaran DOKU atau null jika gagal)
     */
    public function generatePaymentUrl($orderData, $customerData, $vendorSacId)
    {
        if (!$this->snap) {
            Log::error('DOKU generatePaymentUrl Gagal: Snap SDK tidak terinisialisasi.');
            return null; // Gagal jika constructor error
        }
        
        try {
            // 1. Siapkan Model Customer DOKU
            $customer = new DokuCustomer();
            $customer->setName($customerData->name);
            $customer->setEmail($customerData->email);
            
            // 2. Siapkan Model Order DOKU
            $order = new DokuOrder();
            $order->setInvoiceNumber($orderData->invoice_number);
            $order->setAmount($orderData->amount);

            // 3. Siapkan Additional Info (INI KUNCINYA!)
            $additionalInfo = new AdditionalInfo();
            $additionalInfo->setAccount([
                'id' => $vendorSacId
            ]);

            // 4. Siapkan Request Pembayaran Utama
            $paymentRequest = new PaymentRequest();
            $paymentRequest->setCustomer($customer);
            $paymentRequest->setOrder($order);
            $paymentRequest->setAdditionalInfo($additionalInfo); // <-- Menggunakan Sub-Account

            // 5. Panggil API DOKU Snap
            $response = $this->snap->createPayment($paymentRequest);
            return $response->getPaymentUrl(); // Kembalikan URL-nya

        } catch (\Exception $e) {
            Log::error('DOKU Snap Generate URL (Marketplace) Error: ' . $e->getMessage(), [
                'invoice' => $orderData->invoice_number ?? 'N/A',
                'sac_id' => $vendorSacId
            ]);
            return null;
        }
    }


    /**
     * Method #3: MENERIMA WEBHOOK (Callback / Notification)
     *
     * Ini adalah dispatcher/penerus perintah.
     * DOKU akan mengirim notifikasi ke URL yang mengarah ke method ini.
     */
    public function callbackHandler(Request $request)
    {
        // Selalu catat log untuk setiap notifikasi yang masuk
        Log::info('DOKU Snap Webhook Received: ', $request->all());
        
        if (!$this->snap) {
             Log::critical('DOKU Webhook Gagal: DokuPaymentController tidak terinisialisasi.');
             return response()->json(['status' => 'error', 'message' => 'Controller not initialized'], 500);
        }

        try {
            // 1. Validasi Signature (Keamanan WAJIB!)
            $signature = $request->header('Signature');
            $requestBody = $request->getContent(); // Body JSON mentah
            $clientId = $request->header('Client-Id');
            $requestTimestamp = $request->header('Request-Timestamp');
            $requestId = $request->header('Request-Id');

            $isValid = $this->snap->validateSignature(
                $signature,
                $requestBody,
                $clientId,
                $requestId,
                $requestTimestamp
            );

            if (!$isValid) {
                Log::warning('DOKU Snap Webhook: Invalid Signature', [
                    'client_id' => $clientId, 'request_id' => $requestId
                ]);
                return response()->json(['status' => 'error', 'message' => 'Invalid Signature'], 403);
            }
            
            // --- SIGNATURE VALID ---
            
            $data = $request->all();
            
            if (!isset($data['order']['invoice_number']) || !isset($data['transaction']['status'])) {
                 Log::warning('DOKU Webhook: Malformed Data', $data);
                 return response()->json(['status' => 'error', 'message' => 'Malformed data'], 400);
            }

            $orderId = $data['order']['invoice_number'];
            $status = $data['transaction']['status'];

            if ($status !== 'SUCCESS') {
                Log::info("DOKU Webhook: Status $status untuk $orderId, diabaikan.", ['status' => $status]);
                // TODO: Tambahkan logika untuk handle 'FAILED' atau 'EXPIRED' di sini jika perlu
                return response()->json(['status' => 'success', 'message' => 'Non-success status, ignored.']);
            }

            // 2. LOGIKA DISPATCHER (Penerus Perintah)
            // Hanya untuk status 'SUCCESS'
            
            if (Str::startsWith($orderId, 'TOPUP-')) {
                // Handle pembayaran TopUp
                Log::info("DOKU Dispatcher: Mengirim $orderId ke TopUpController...");
                return (new TopUpController())->handleDokuCallback($data);
            
            } else if (Str::startsWith($orderId, 'INV-')) {
                // Handle pembayaran Marketplace
                Log::info("DOKU Dispatcher: Mengirim $orderId ke CustomerOrderController...");
                return (new CustomerOrderController())->handleDokuCallback($data);
            
            } else if (Str::startsWith($orderId, 'CVSANCAK-')) {
                // Handle pembayaran Form Publik
                Log::info("DOKU Dispatcher: Mengirim $orderId ke AdminPesananController...");
                return (new AdminPesananController())->handleDokuCallback($data); 

            } else {
                // Tidak ada handler
                Log::error("DOKU Webhook: Tidak ada handler untuk prefix $orderId.");
                return response()->json(['status' => 'error', 'message' => 'No handler for this order prefix'], 200);
            }
            
        } catch (\Exception $e) {
            // Tangkap semua error lainnya
            Log::error('DOKU Snap Webhook Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['status' => 'error', 'message' => 'Internal Server Error'], 500);
        }
    }
}