<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DokuJokulService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http; // Tambahkan HTTP Client
use Illuminate\Support\Str; // Tambahkan Str Helper
use App\Models\PosTopUp;
use App\Models\TopUp;
use Throwable;

class TenantPaymentController extends Controller
{
    /**
     * GENERATE URL PEMBAYARAN (DOKU & DANA)
     */
    public function generateUrl(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:10000',
            'payment_method' => 'nullable|in:DOKU,DANA' // Tambahkan validasi metode
        ]);

        $user = Auth::user();
        if (!$user) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }
            return redirect()->route('login')->with('error', 'Sesi habis.');
        }

        // 1. Ambil Tenant ID
        $tenantId = $user->tenant_id ?? null;
        if (!$tenantId) {
            $host = $request->getHost();
            $subdomain = explode('.', $host)[0];
            $tenantData = DB::table('tenants')->where('subdomain', $subdomain)->first();
            $tenantId = $tenantData ? $tenantData->id : 1;
        }

        // 2. CEK METODE PEMBAYARAN
        $method = $request->payment_method ?? 'DOKU'; // Default ke DOKU

        if ($method === 'DANA') {
            return $this->processDanaPayment($request, $user, $tenantId);
        } else {
            return $this->processDokuPayment($request, $user, $tenantId);
        }
    }

    /**
     * PROSES PEMBAYARAN VIA DOKU (Logic Lama Dipisah kesini)
     */
    private function processDokuPayment($request, $user, $tenantId)
    {
        try {
            $dokuService = new DokuJokulService();
            $referenceNo = 'POSTOPUP-' . $user->id . '-' . time() . '-' . rand(100, 999);

            $customerData = [
                'name'  => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? '08123456789'
            ];

            $paymentUrl = $dokuService->createPayment($referenceNo, $request->amount, $customerData);

            PosTopUp::create([
                'tenant_id'      => $tenantId,
                'affiliate_id'   => $user->id,
                'reference_no'   => $referenceNo,
                'amount'         => $request->amount,
                'unique_code'    => 0,
                'total_amount'   => $request->amount,
                'status'         => 'PENDING',
                'payment_method' => 'DOKU',
                'response_payload' => [
                    'payment_url' => $paymentUrl,
                    'generated_by' => 'TenantPaymentController',
                    'user_role'    => $user->role ?? 'unknown'
                ]
            ]);

            session(['doku_url' => $paymentUrl]);

            if (!$request->wantsJson()) {
                return redirect()->away($paymentUrl);
            }

            return response()->json([
                'success' => true,
                'url'     => $paymentUrl
            ]);

        } catch (\Exception $e) {
            Log::error("LOG POS: Gagal generate TopUp DOKU: " . $e->getMessage());
            if (!$request->wantsJson()) return redirect()->back()->with('error', 'Gagal: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * PROSES PEMBAYARAN VIA DANA (Logic Baru)
     */
    private function processDanaPayment($request, $user, $tenantId)
    {
        Log::info("[DANA TOPUP] Memulai proses untuk User: {$user->id}, Tenant: {$tenantId}");

        // 1. Cek Apakah User Sudah Binding DANA?
        // Asumsi ada kolom 'dana_access_token' di tabel users atau affiliates
        $accessToken = $user->dana_access_token;

        if (!$accessToken) {
            // Jika belum binding, arahkan ke route binding dulu
            // Anda harus punya route 'dana.binding.start'
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akun DANA belum terhubung.',
                    'action'  => 'BIND_REQUIRED',
                    'url'     => route('member.dana.start') // Arahkan ke proses binding
                ], 400);
            }
            return redirect()->route('member.dana.start')->with('error', 'Silakan hubungkan akun DANA Anda terlebih dahulu.');
        }

        // 2. Setup Request DANA Acquiring (Create Order)
        $refNo = 'DEP-DANA-' . time() . mt_rand(100, 999);
        $timestamp = now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');

        $payload = [
            "request" => [
                "head" => [
                    "version"      => "2.0",
                    "function"     => "dana.acquiring.order.create",
                    "clientId"     => config('services.dana.x_partner_id'),
                    "clientSecret" => config('services.dana.client_secret'),
                    "reqTime"      => $timestamp,
                    "reqMsgId"     => (string) Str::uuid(),
                    "reserve"      => "{}"
                ],
                "body" => [
                    "merchantId" => config('services.dana.merchant_id'),
                    "merchantTransId" => $refNo,
                    "order" => [
                        "orderTitle" => "Topup Saldo POS",
                        "orderAmount" => [
                            "currency" => "IDR",
                            "value" => number_format($request->amount, 2, '.', '') // Format 2 desimal string
                        ],
                        "merchantTransType" => "01", // 01 = Transaction
                        "orderMemo" => "Topup Saldo Tenant ID: " . $tenantId
                    ],
                    "envInfo" => [
                        "sourcePlatform" => "IPG",
                        "terminalType"   => "SYSTEM" // Atau WEB
                    ],
                    // Sertakan Token User agar tidak perlu login lagi (langsung PIN)
                    "userCredential" => [
                        "accessToken" => $accessToken
                    ]
                ]
            ]
        ];

        // 3. Generate Signature
        $jsonToSign = json_encode($payload['request'], JSON_UNESCAPED_SLASHES);
        $signature  = $this->generateSignature($jsonToSign); // Pastikan function ini ada di Controller atau Trait

        try {
            // 4. Catat Transaksi PENDING ke DB
            PosTopUp::create([
                'tenant_id'      => $tenantId,
                'affiliate_id'   => $user->id,
                'reference_no'   => $refNo,
                'amount'         => $request->amount,
                'unique_code'    => 0,
                'total_amount'   => $request->amount,
                'status'         => 'PENDING',
                'payment_method' => 'DANA',
                'response_payload' => json_encode(['init_payload' => $payload])
            ]);

            // 5. Kirim Request ke DANA
            Log::info('[DANA TOPUP] Sending Request...', ['ref' => $refNo]);

            $response = Http::post('https://api.sandbox.dana.id/dana/acquiring/order/create.htm', [
                "request"   => $payload['request'],
                "signature" => $signature
            ]);

            $result = $response->json();
            Log::info('[DANA TOPUP] Response:', ['res' => $result]);

            // 6. Cek Hasil
            $resBody = $result['response']['body'] ?? [];
            $resStatus = $resBody['resultInfo']['resultStatus'] ?? 'F';

            if ($resStatus == 'S') {
                // Ambil URL Redirect (Checkout URL)
                // DANA akan mengembalikan URL untuk user memasukkan PIN
                $checkoutUrl = $resBody['checkoutUrl'] ?? null;

                if ($checkoutUrl) {
                    // Update Log
                    PosTopUp::where('reference_no', $refNo)->update([
                        'response_payload' => json_encode($result)
                    ]);

                    if (!$request->wantsJson()) {
                        return redirect()->away($checkoutUrl);
                    }

                    return response()->json([
                        'success' => true,
                        'url'     => $checkoutUrl
                    ]);
                }
            }

            // Jika Gagal
            $errMsg = $resBody['resultInfo']['resultMsg'] ?? 'Gagal membuat order DANA.';
            PosTopUp::where('reference_no', $refNo)->update(['status' => 'FAILED']);

            if (!$request->wantsJson()) return redirect()->back()->with('error', $errMsg);
            return response()->json(['success' => false, 'message' => $errMsg], 400);

        } catch (\Exception $e) {
            Log::error("[DANA TOPUP] Exception: " . $e->getMessage());
            if (!$request->wantsJson()) return redirect()->back()->with('error', 'System Error');
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * HELPER SIGNATURE (Copy dari MemberAuthController jika belum ada trait)
     */
    private function generateSignature($stringToSign) {
        $privateKeyContent = config('services.dana.private_key');

        // Format Key ke PEM jika perlu
        $cleanKey = preg_replace('/-{5}(BEGIN|END) PRIVATE KEY-{5}|\r|\n|\s/', '', $privateKeyContent);
        $formattedKey = "-----BEGIN PRIVATE KEY-----\n" . chunk_split($cleanKey, 64, "\n") . "-----END PRIVATE KEY-----";

        $binarySignature = "";
        openssl_sign($stringToSign, $binarySignature, $formattedKey, OPENSSL_ALGO_SHA256);
        return base64_encode($binarySignature);
    }

    public function checkStatus(Request $request)
    {
        try {
            $invoice = $request->input('invoice');

            if ($invoice) {
                // Cek status di DB Lokal dulu
                $topup = PosTopUp::where('reference_no', $invoice)->first();

                if ($topup && in_array($topup->status, ['SUCCESS', 'PAID'])) {
                    return response()->json([
                        'active' => true,
                        'status' => 'success',
                        'message' => 'Top Up Berhasil!',
                        'current_balance' => Auth::user()->fresh()->saldo,
                        'redirect_url' => url()->previous()
                    ]);
                }

                // [OPSIONAL] Jika masih PENDING dan metode DANA, bisa cek status ke API DANA (Acquiring Query)
                // Implementasikan logic cek status ke DANA disini jika perlu real-time check

                return response()->json(['active' => false, 'status' => 'pending']);
            }

            // Fallback Logic (Tetap Sama)
            $host = $request->getHost();
            $subdomain = explode('.', $host)[0];
            $tenant = DB::table('tenants')->where('subdomain', $subdomain)->first();

            if ($tenant && $tenant->status === 'active') {
                return response()->json([
                    'active' => true,
                    'message' => 'Akun Aktif',
                    'redirect_url' => "https://{$tenant->subdomain}.tokosancaka.com/dashboard"
                ]);
            }

            return response()->json(['active' => false]);

        } catch (Throwable $e) {
            return response()->json(['active' => false, 'error' => $e->getMessage()], 200);
        }
    }
}
