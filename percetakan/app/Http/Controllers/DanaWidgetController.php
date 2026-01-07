<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Services\DanaSignatureService;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class DanaWidgetController extends Controller
{
    protected $danaSignature;

    public function __construct(DanaSignatureService $danaSignature)
    {
        $this->danaSignature = $danaSignature;
    }

    /**
     * Step 1: Merchant membuat Request Pembayaran
     */
    public function createPayment(Request $request)
    {
        // 1. Persiapan Data (Hardcode dulu untuk testing, nanti bisa ambil dari database)
        $orderId = 'INV-' . time(); // ID Unik Order
        $amount  = '1000.00';       // Nominal (String, 2 desimal)
        
        // Setup URL Return (Tempat user kembali setelah bayar)
        // DANA akan menambahkan ?status=SUCCESS&originalReferenceNo=... di belakang url ini
        $returnUrl = route('dana.return'); 

        // 2. Setup Payload (Body Request)
        // Sesuaikan struktur ini dengan dokumen PDF "Direct Debit Payment" Anda
        // Biasanya untuk Widget Non-Binding strukturnya seperti ini:
        $body = [
            "partnerReferenceNo" => $orderId,
            "amount" => [
                "value" => $amount,
                "currency" => "IDR"
            ],
            "payOptionDetails" => [
                "payMethod" => "DANA_WALLET", // Atau kosongkan jika ingin user pilih di DANA
                "transType" => "PAGE",
            ],
            "additionalInfo" => [
                "origin" => "IS_WIDGET" // Penanda transaksi Widget
            ],
            "urlParams" => [
                "url" => $returnUrl,     // URL Redirect balik ke toko
                "type" => "NOTIFICATION" // Agar DANA juga mengirim notifikasi background
            ]
        ];

        // 3. Setup Header & Signature
        $method = 'POST';
        
        // PENTING: Cek PDF Anda untuk "Relative URL" yang pasti. 
        // Untuk Direct Debit Payment SNAP biasanya: /v1.0/debit/payment.host 
        // Atau kadang: /v1.0/order/create (tergantung versi)
        $relativePath = '/v1.0/debit/payment.host'; 
        
        $timestamp = Carbon::now()->toIso8601String();

        try {
            // Generate Signature menggunakan Service yang sudah FIX tadi
            $signature = $this->danaSignature->generateSignature(
                $method, 
                $relativePath, 
                $body, 
                $timestamp
            );
        } catch (\Exception $e) {
            return response()->json(['error' => 'Signature Error: ' . $e->getMessage()], 500);
        }

        // 4. Kirim Request ke DANA
        $fullUrl = config('services.dana.base_url') . $relativePath;
        
        // Generate Unique External ID
        $externalId = Str::random(32);

        $response = Http::withHeaders([
            'X-PARTNER-ID' => config('services.dana.client_id'),
            'X-EXTERNAL-ID' => $externalId,
            'X-TIMESTAMP'  => $timestamp,
            'X-SIGNATURE'  => $signature,
            'Content-Type' => 'application/json',
            'CHANNEL-ID'   => 'MOBILE_WEB', // Sesuaikan jika Web Desktop
        ])->post($fullUrl, $body);

        $result = $response->json();

        // 5. Cek Response & Redirect User
        // Jika sukses, DANA memberikan "webRedirectUrl"
        if (isset($result['responseCode']) && $result['responseCode'] == '2005300') { // 2005300 adalah kode Sukses SNAP
             
             $redirectUrl = $result['webRedirectUrl'];
             
             // Simpan data order ke database di sini (Status: Pending)
             // ...
             
             // Arahkan user ke halaman pembayaran DANA
             return redirect($redirectUrl);
        }

        // Jika gagal, tampilkan error
        return response()->json($result);
    }

    /**
     * Step 2: Halaman Return (User kembali setelah bayar)
     */
    public function returnPage(Request $request)
    {
        // Tangkap parameter dari URL
        // Format: ?originalReferenceNo=xxx&merchantId=xxx&status=SUCCESS
        $status = $request->query('status');
        $orderId = $request->query('originalPartnerReferenceNo'); // Order ID kita
        $danaRef = $request->query('originalReferenceNo'); // Order ID DANA

        if ($status == 'SUCCESS') {
            return "<h1>Pembayaran Berhasil!</h1><p>Order ID: $orderId</p><p>DANA Ref: $danaRef</p>";
        } else {
            return "<h1>Pembayaran Gagal / Dibatalkan</h1><p>Status: $status</p>";
        }
    }
}