<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DokuJokulService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\PosTopUp;
use Throwable;

class TenantPaymentController extends Controller
{
    /**
     * 1. GENERATE URL PEMBAYARAN (ENTRY POINT)
     * Memilah metode pembayaran (DANA vs DOKU)
     */
    public function generateUrl(Request $request)
    {
        // Validasi Input
        $request->validate([
            'amount' => 'required|numeric|min:10000',
            'payment_method' => 'nullable|in:DOKU,DANA'
        ]);

        $user = Auth::user();
        if (!$user) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }
            return redirect()->route('login')->with('error', 'Sesi habis, silakan login kembali.');
        }

        // A. DETEKSI TENANT ID (Otomatis & Cerdas)
        // Prioritas 1: Dari User (jika user terikat tenant)
        $tenantId = $user->tenant_id ?? null;

        // Prioritas 2: Dari Subdomain URL saat ini
        if (!$tenantId) {
            $host = $request->getHost();
            $subdomain = explode('.', $host)[0];
            $tenantData = DB::table('tenants')->where('subdomain', $subdomain)->first();
            $tenantId = $tenantData ? $tenantData->id : 1; // Default ke Admin/Pusat (ID 1)
        }

        // B. PILIH PROSESOR PEMBAYARAN
        $method = $request->payment_method ?? 'DOKU';

        if ($method === 'DANA') {
            return $this->processDanaPayment($request, $user, $tenantId);
        } else {
            return $this->processDokuPayment($request, $user, $tenantId);
        }
    }

    /**
     * 2. LOGIC PEMBAYARAN DANA (ACQUIRING / DIRECT DEBIT)
     */
    private function processDanaPayment($request, $user, $tenantId)
    {
        Log::info("[DANA TOPUP] Start. User: {$user->id}, Tenant: {$tenantId}");

        // Cek Binding DANA (Wajib punya Token)
        $accessToken = $user->dana_access_token;

        if (!$accessToken) {
            // Logic Redirect ke Binding jika belum terhubung
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akun DANA belum terhubung.',
                    'action'  => 'BIND_REQUIRED',
                    'url'     => route('member.dana.start')
                ], 400);
            }
            return redirect()->route('member.dana.start')->with('error', 'Hubungkan akun DANA Anda terlebih dahulu.');
        }

        // Setup Payload DANA
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
                            "value" => number_format($request->amount, 2, '.', '')
                        ],
                        "merchantTransType" => "01",
                        "orderMemo" => "Topup Tenant ID: " . $tenantId
                    ],
                    "envInfo" => [
                        "sourcePlatform" => "IPG",
                        "terminalType"   => "SYSTEM"
                    ],
                    "userCredential" => [
                        "accessToken" => $accessToken
                    ]
                ]
            ]
        ];

        // Generate Signature
        $jsonToSign = json_encode($payload['request'], JSON_UNESCAPED_SLASHES);
        $signature  = $this->generateSignature($jsonToSign);

        try {
            // Simpan Transaksi PENDING
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

            // Kirim ke DANA
            Log::info('[DANA TOPUP] Sending Request...', ['ref' => $refNo]);
            $response = Http::post('https://api.sandbox.dana.id/dana/acquiring/order/create.htm', [
                "request"   => $payload['request'],
                "signature" => $signature
            ]);

            $result = $response->json();
            Log::info('[DANA TOPUP] Response:', ['res' => $result]);

            $resBody = $result['response']['body'] ?? [];
            $resStatus = $resBody['resultInfo']['resultStatus'] ?? 'F';

            if ($resStatus == 'S') {
                $checkoutUrl = $resBody['checkoutUrl'] ?? null;

                if ($checkoutUrl) {
                    PosTopUp::where('reference_no', $refNo)->update(['response_payload' => json_encode($result)]);

                    if (!$request->wantsJson()) return redirect()->away($checkoutUrl);

                    return response()->json(['success' => true, 'url' => $checkoutUrl]);
                }
            }

            // Handle Error
            $errMsg = $resBody['resultInfo']['resultMsg'] ?? 'Gagal membuat order DANA.';
            PosTopUp::where('reference_no', $refNo)->update(['status' => 'FAILED']);

            if (!$request->wantsJson()) return redirect()->back()->with('error', $errMsg);
            return response()->json(['success' => false, 'message' => $errMsg], 400);

        } catch (\Exception $e) {
            Log::error("[DANA TOPUP] Error: " . $e->getMessage());
            if (!$request->wantsJson()) return redirect()->back()->with('error', 'System Error');
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * 3. LOGIC PEMBAYARAN DOKU (EXISTING)
     */
    private function processDokuPayment($request, $user, $tenantId)
    {
        try {
            $dokuService = new DokuJokulService();
            $referenceNo = 'POSTOPUP-' . $user->id . '-' . time() . '-' . rand(100, 999);

            $customerData = [
                'name' => $user->name, 'email' => $user->email, 'phone' => $user->phone ?? '08123456789'
            ];

            $paymentUrl = $dokuService->createPayment($referenceNo, $request->amount, $customerData);

            PosTopUp::create([
                'tenant_id' => $tenantId, 'affiliate_id' => $user->id, 'reference_no' => $referenceNo,
                'amount' => $request->amount, 'unique_code' => 0, 'total_amount' => $request->amount,
                'status' => 'PENDING', 'payment_method' => 'DOKU',
                'response_payload' => ['payment_url' => $paymentUrl]
            ]);

            session(['doku_url' => $paymentUrl]);

            if (!$request->wantsJson()) return redirect()->away($paymentUrl);
            return response()->json(['success' => true, 'url' => $paymentUrl]);

        } catch (\Exception $e) {
            Log::error("LOG POS DOKU Error: " . $e->getMessage());
            if (!$request->wantsJson()) return redirect()->back()->with('error', 'Gagal: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * 4. CEK STATUS & SMART REDIRECT (CORE LOGIC YANG ANDA MINTA)
     * Dipanggil oleh Frontend (Polling) setiap beberapa detik
     */
    public function checkStatus(Request $request)
    {
        try {
            $invoice = $request->input('invoice');

            // Logika Fallback Default
            $currentHost = $request->getHost();
            $currentSubdomain = explode('.', $currentHost)[0];
            $appDomain = env('APP_URL_DOMAIN', 'tokosancaka.com'); // Config Domain Utama
            $scheme = $request->secure() ? 'https://' : 'http://';

            // --- A. JIKA ADA INVOICE (Cek Transaksi Spesifik) ---
            if ($invoice) {
                // Cari Transaksi + Load Data Tenantnya
                // Pastikan model PosTopUp Anda punya method tenant() => return $this->belongsTo(Tenant::class);
                // Jika tidak ada relasi Eloquent, kita pakai Query Builder manual di bawah
                $topup = PosTopUp::where('reference_no', $invoice)->first();

                if ($topup && in_array($topup->status, ['SUCCESS', 'PAID'])) {

                    // [SMART REDIRECT LOGIC]
                    // Jangan pakai url()->previous(), tapi bangun URL berdasarkan Tenant ID transaksi

                    $targetRedirect = url()->previous(); // Default aman

                    // Ambil Subdomain Tenant Pemilik Transaksi
                    $trxTenant = DB::table('tenants')->where('id', $topup->tenant_id)->first();

                    if ($trxTenant) {
                        // Construct URL: https://[subdomain_tenant].tokosancaka.com/dashboard
                        $targetRedirect = $scheme . $trxTenant->subdomain . '.' . $appDomain . '/dashboard';
                    }

                    // Refresh Saldo Auth User (untuk update UI realtime)
                    $currentBalance = 0;
                    if (Auth::check()) {
                        $currentBalance = Auth::user()->fresh()->saldo;
                    }

                    return response()->json([
                        'active' => true,
                        'status' => 'success',
                        'message' => 'Top Up Berhasil!',
                        'current_balance' => $currentBalance,
                        'redirect_url' => $targetRedirect // <-- INI URL CERDASNYA
                    ]);
                }

                // Jika masih Pending
                return response()->json(['active' => false, 'status' => 'pending']);
            }

            // --- B. JIKA TANPA INVOICE (Cek Status Tenant dari URL saat ini) ---
            // Biasanya dipakai di Landing Page untuk cek apakah toko aktif/tidak
            $tenant = DB::table('tenants')->where('subdomain', $currentSubdomain)->first();

            if ($tenant && $tenant->status === 'active') {
                return response()->json([
                    'active' => true,
                    'message' => 'Akun Aktif',
                    'redirect_url' => $scheme . $tenant->subdomain . '.' . $appDomain . '/dashboard'
                ]);
            }

            return response()->json(['active' => false]);

        } catch (Throwable $e) {
            return response()->json(['active' => false, 'error' => $e->getMessage()], 200);
        }
    }

    /**
     * Helper Signature DANA
     */
    private function generateSignature($stringToSign) {
        $privateKeyContent = config('services.dana.private_key');
        // Bersihkan format key
        $cleanKey = preg_replace('/-{5}(BEGIN|END) PRIVATE KEY-{5}|\r|\n|\s/', '', $privateKeyContent);
        $formattedKey = "-----BEGIN PRIVATE KEY-----\n" . chunk_split($cleanKey, 64, "\n") . "-----END PRIVATE KEY-----";

        $binarySignature = "";
        openssl_sign($stringToSign, $binarySignature, $formattedKey, OPENSSL_ALGO_SHA256);
        return base64_encode($binarySignature);
    }
}
