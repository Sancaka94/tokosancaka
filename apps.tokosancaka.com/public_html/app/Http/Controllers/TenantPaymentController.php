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
     * URL CALLBACK PUSAT (HARUS SAMA DENGAN DANA GATEWAY)
     * Ini alamat controller "Satpam" di domain utama.
     */
    private const CENTRAL_CALLBACK_URL = 'https://apps.tokosancaka.com/dana/callback';

    /**
     * 1. GENERATE URL (ENTRY POINT)
     * Menentukan metode pembayaran (DANA vs DOKU)
     */
    public function generateUrl(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:10000',
            'payment_method' => 'nullable|in:DOKU,DANA'
        ]);

        $user = Auth::user();
        if (!$user) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }
            return redirect()->route('login')->with('error', 'Sesi habis.');
        }

        // A. DETEKSI TENANT
        $tenantId = $user->tenant_id ?? null;
        if (!$tenantId) {
            $host = $request->getHost(); // misal: bakso.tokosancaka.com
            $subdomain = explode('.', $host)[0];
            $tenantData = DB::table('tenants')->where('subdomain', $subdomain)->first();
            $tenantId = $tenantData ? $tenantData->id : 1;
        }

        // B. PILIH METODE
        $method = $request->payment_method ?? 'DOKU';

        if ($method === 'DANA') {
            return $this->processDanaPayment($request, $user, $tenantId);
        } else {
            return $this->processDokuPayment($request, $user, $tenantId);
        }
    }

    /**
     * 2. LOGIC START BINDING (INTEGRASI GATEWAY PUSAT)
     * Mengarahkan Admin Toko ke DANA dengan "Titipan Pesan" (State)
     */
    public function startBinding(Request $request)
    {
        $user = Auth::user();

        // 1. Ambil Subdomain Tenant Saat Ini
        $subdomain = explode('.', $request->getHost())[0];
        $tenantId  = $user->tenant_id ?? 1;

        // 2. Buat State untuk Gateway Pusat
        // Format: ACTION-USERID-SUBDOMAIN-TENANTID
        // Gateway Pusat akan membaca ini untuk tahu kemana harus redirect balik
        $state = "BIND_TENANT-{$user->id}-{$subdomain}-{$tenantId}";

        // 3. Setup URL DANA
        $clientId = config('services.dana.x_partner_id');

        // PENTING: Redirect URL harus ke Gateway Pusat (Fixed URL)
        // Jangan gunakan route() lokal karena subdomainnya akan berubah-ubah
        $encodedCallback = urlencode(self::CENTRAL_CALLBACK_URL);

        $requestId = Str::uuid();

        // 4. Redirect ke DANA
        $danaUrl = "https://m.sandbox.dana.id/d/portal/oauth?partnerId={$clientId}&scopes=QUERY_BALANCE,MINI_DANA,DEFAULT_BASIC_PROFILE&requestId={$requestId}&redirectUrl={$encodedCallback}&state={$state}&terminalType=WEB";

        Log::info("[TENANT BINDING] Redirecting to DANA via Central Gateway. State: $state");

        return redirect()->away($danaUrl);
    }

    /**
     * 3. LOGIC PEMBAYARAN DANA (ACQUIRING)
     */
    private function processDanaPayment($request, $user, $tenantId)
    {
        Log::info("[DANA TOPUP] Start Tenant User: {$user->id}");

        // Cek Token di Tabel Users (Admin Toko)
        $accessToken = $user->dana_access_token;

        if (!$accessToken) {
            // Jika belum binding, arahkan ke startBinding
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akun DANA belum terhubung.',
                    'action'  => 'BIND_REQUIRED',
                    'url'     => route('tenant.dana.start') // Pastikan route ini ada di web.php tenant
                ], 400);
            }
            return redirect()->route('tenant.dana.start')->with('error', 'Hubungkan akun DANA Anda terlebih dahulu.');
        }

        // Setup Payload Order DANA
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
            // Simpan Log Transaksi
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

            // Request API
            $response = Http::post('https://api.sandbox.dana.id/dana/acquiring/order/create.htm', [
                "request"   => $payload['request'],
                "signature" => $signature
            ]);

            $result = $response->json();
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
            $errMsg = $resBody['resultInfo']['resultMsg'] ?? 'Gagal membuat order.';
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
     * 4. LOGIC PEMBAYARAN DOKU (EXISTING)
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
     * 5. CEK STATUS & SMART REDIRECT
     * Mengarahkan user kembali ke subdomain tenant yang benar
     */
    public function checkStatus(Request $request)
    {
        try {
            $invoice = $request->input('invoice');

            // Konfigurasi Domain
            $appDomain = env('APP_URL_DOMAIN', 'tokosancaka.com');
            $scheme = $request->secure() ? 'https://' : 'http://';

            // Fallback Subdomain (jika invoice kosong)
            $currentSubdomain = explode('.', $request->getHost())[0];

            // A. CEK TRANSAKSI SPESIFIK
            if ($invoice) {
                $topup = PosTopUp::where('reference_no', $invoice)->first();

                if ($topup && in_array($topup->status, ['SUCCESS', 'PAID'])) {

                    // --- SMART REDIRECT START ---
                    // Cari Tenant pemilik transaksi
                    $trxTenant = DB::table('tenants')->where('id', $topup->tenant_id)->first();

                    // URL Default
                    $redirectUrl = url()->previous();

                    if ($trxTenant) {
                        // Paksa URL ke Dashboard Subdomain Tenant yang Benar
                        // Contoh: https://bakso.tokosancaka.com/dashboard
                        $redirectUrl = $scheme . $trxTenant->subdomain . '.' . $appDomain . '/dashboard';
                    }
                    // --- SMART REDIRECT END ---

                    // Refresh Saldo Auth (untuk update UI real-time)
                    $currentBalance = 0;
                    if (Auth::check()) {
                        $currentBalance = Auth::user()->fresh()->saldo;
                    }

                    return response()->json([
                        'active' => true,
                        'status' => 'success',
                        'message' => 'Top Up Berhasil!',
                        'current_balance' => $currentBalance,
                        'redirect_url' => $redirectUrl
                    ]);
                }

                return response()->json(['active' => false, 'status' => 'pending']);
            }

            // B. CEK STATUS TENANT (LANDING PAGE)
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
     * HELPER: SIGNATURE DANA
     */
    private function generateSignature($stringToSign) {
        $privateKeyContent = config('services.dana.private_key');
        $cleanKey = preg_replace('/-{5}(BEGIN|END) PRIVATE KEY-{5}|\r|\n|\s/', '', $privateKeyContent);
        $formattedKey = "-----BEGIN PRIVATE KEY-----\n" . chunk_split($cleanKey, 64, "\n") . "-----END PRIVATE KEY-----";

        $binarySignature = "";
        openssl_sign($stringToSign, $binarySignature, $formattedKey, OPENSSL_ALGO_SHA256);
        return base64_encode($binarySignature);
    }
}
