<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DokuJokulService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\PosTopUp; // Pastikan model ini ada
use Throwable;

class TenantPaymentController extends Controller
{
    /**
     * KONFIGURASI URL CALLBACK PUSAT
     * Ini alamat controller "DanaGatewayController" di domain utama.
     * JANGAN DIUBAH KECUALI DOMAIN UTAMA BERUBAH.
     */
    private const CENTRAL_CALLBACK_URL = 'https://apps.tokosancaka.com/dana/callback';

    /**
     * 1. GENERATE URL (ENTRY POINT)
     * Menentukan metode pembayaran (DANA vs DOKU)
     */
    public function generateUrl(Request $request)
    {
        // Validasi Input
        $request->validate([
            'amount' => 'required|numeric|min:10000',
            'payment_method' => 'nullable|in:DOKU,DANA'
        ]);

        // Ambil User yang Login (Tabel Users / Tenant Admin)
        $user = Auth::user();

        if (!$user) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }
            return redirect()->route('login')->with('error', 'Sesi habis, silakan login kembali.');
        }

        // A. DETEKSI TENANT ID
        // Prioritas 1: Dari kolom tenant_id di user
        $tenantId = $user->tenant_id ?? null;

        // Prioritas 2: Dari Subdomain URL saat ini (Fallback)
        if (!$tenantId) {
            $host = $request->getHost(); // misal: bakso.tokosancaka.com
            $subdomain = explode('.', $host)[0];
            $tenantData = DB::table('tenants')->where('subdomain', $subdomain)->first();
            $tenantId = $tenantData ? $tenantData->id : 1; // Default ke ID 1 (Pusat) jika tidak ketemu
        }

        // B. PILIH METODE PEMBAYARAN
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
        $user = Auth::user(); // User dari tabel 'users'

        if (!$user) {
            return redirect('/login')->with('error', 'Silakan login terlebih dahulu.');
        }

        // 1. Ambil Subdomain Tenant Saat Ini (Agar nanti bisa pulang ke sini)
        $subdomain = explode('.', $request->getHost())[0];
        $tenantId  = $user->tenant_id ?? 1;

        // 2. Buat State Khusus Tenant untuk Gateway Pusat
        // Format: ACTION - USER_ID - SUBDOMAIN - TENANT_ID
        // Gateway Pusat akan membaca ini untuk tahu kemana harus redirect balik & tabel mana yang diupdate
        $state = "BIND_TENANT-{$user->id}-{$subdomain}-{$tenantId}";

        // 3. Setup URL DANA
        $clientId = config('services.dana.x_partner_id');

        // PENTING: Redirect URL harus ke Gateway Pusat (Fixed URL di Domain Utama)
        // Jangan gunakan route() lokal karena subdomainnya akan berubah-ubah
        $encodedCallback = urlencode(self::CENTRAL_CALLBACK_URL);

        $requestId = Str::uuid();

        // 4. Redirect ke DANA
        // Scopes: QUERY_BALANCE, MINI_DANA, DEFAULT_BASIC_PROFILE
        $danaUrl = "https://m.sandbox.dana.id/d/portal/oauth?partnerId={$clientId}&scopes=QUERY_BALANCE,MINI_DANA,DEFAULT_BASIC_PROFILE&requestId={$requestId}&redirectUrl={$encodedCallback}&state={$state}&terminalType=WEB";

        Log::info("[TENANT BINDING] Redirecting User ID {$user->id} to DANA via Central Gateway. State: $state");

        return redirect()->away($danaUrl);
    }

    /**
     * 3. LOGIC PEMBAYARAN DANA (ACQUIRING / DIRECT DEBIT)
     */
    private function processDanaPayment($request, $user, $tenantId)
    {
        Log::info("[DANA TOPUP] Start Tenant User: {$user->id} (Tenant: $tenantId)");

        // Cek Token di Tabel Users (Admin Toko)
        // Pastikan kolom 'dana_access_token' ada di tabel users
        $accessToken = $user->dana_access_token;

        if (!$accessToken) {
            // Jika belum binding, arahkan ke startBinding
            // Jika request AJAX (JSON), kirim response suruh redirect
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akun DANA belum terhubung.',
                    'action'  => 'BIND_REQUIRED',
                    'url'     => route('tenant.dana.start') // Pastikan route ini ada di web.php tenant
                ], 400);
            }
            // Jika request biasa, redirect langsung
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
                            "value" => number_format($request->amount, 2, '.', '') // Format string 2 desimal
                        ],
                        "merchantTransType" => "01",
                        "orderMemo" => "Topup Tenant ID: " . $tenantId
                    ],
                    "envInfo" => [
                        "sourcePlatform" => "IPG",
                        "terminalType"   => "SYSTEM"
                    ],
                    // Sertakan Access Token User
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
            // SIMPAN LOG TRANSAKSI (PENDING)
            // Catatan: 'affiliate_id' disini diisi User ID (Admin) karena ini tabel pos_topups
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

            // Request API DANA
            Log::info('[DANA TOPUP] Sending Request Tenant...', ['ref' => $refNo]);

            $response = Http::post('https://api.sandbox.dana.id/dana/acquiring/order/create.htm', [
                "request"   => $payload['request'],
                "signature" => $signature
            ]);

            $result = $response->json();
            Log::info('[DANA TOPUP] Response:', ['res' => $result]);

            $resBody = $result['response']['body'] ?? [];
            $resStatus = $resBody['resultInfo']['resultStatus'] ?? 'F';

            // JIKA BERHASIL CREATE ORDER (Dapat Checkout URL)
            if ($resStatus == 'S') {
                $checkoutUrl = $resBody['checkoutUrl'] ?? null;

                if ($checkoutUrl) {
                    // Update Log dengan Response sukses
                    PosTopUp::where('reference_no', $refNo)->update(['response_payload' => json_encode($result)]);

                    if (!$request->wantsJson()) {
                        return redirect()->away($checkoutUrl);
                    }
                    return response()->json(['success' => true, 'url' => $checkoutUrl]);
                }
            }

            // JIKA GAGAL
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
            // Prefix Ref No DOKU
            $referenceNo = 'POSTOPUP-' . $user->id . '-' . time() . '-' . rand(100, 999);

            $customerData = [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? '08123456789'
            ];

            $paymentUrl = $dokuService->createPayment($referenceNo, $request->amount, $customerData);

            // Simpan Transaksi DOKU
            PosTopUp::create([
                'tenant_id'      => $tenantId,
                'affiliate_id'   => $user->id, // User ID
                'reference_no'   => $referenceNo,
                'amount'         => $request->amount,
                'unique_code'    => 0,
                'total_amount'   => $request->amount,
                'status'         => 'PENDING',
                'payment_method' => 'DOKU',
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
     * Mengarahkan user kembali ke subdomain tenant yang benar setelah bayar
     */
    public function checkStatus(Request $request)
    {
        try {
            $invoice = $request->input('invoice');

            // Konfigurasi Domain Utama (Ganti sesuai .env)
            $appDomain = env('APP_URL_DOMAIN', 'tokosancaka.com');
            $scheme = $request->secure() ? 'https://' : 'http://';

            // Fallback Subdomain saat ini
            $currentSubdomain = explode('.', $request->getHost())[0];

            // A. JIKA ADA INVOICE (Cek Transaksi Spesifik)
            if ($invoice) {
                // Cari transaksi di tabel pos_topups
                $topup = PosTopUp::where('reference_no', $invoice)->first();

                // Jika Transaksi Ditemukan dan SUKSES
                if ($topup && in_array($topup->status, ['SUCCESS', 'PAID'])) {

                    // --- SMART REDIRECT LOGIC ---
                    $redirectUrl = url()->previous(); // Default (Refresh halaman)

                    // Cari Tenant pemilik transaksi ini
                    $trxTenant = DB::table('tenants')->where('id', $topup->tenant_id)->first();

                    if ($trxTenant) {
                        // Paksa URL ke Dashboard Subdomain Tenant yang Benar
                        // Contoh: https://bakso.tokosancaka.com/dashboard
                        $redirectUrl = $scheme . $trxTenant->subdomain . '.' . $appDomain . '/dashboard';
                    }
                    // -----------------------------

                    // Refresh Saldo User Login (untuk update UI real-time)
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

                // Jika masih Pending
                return response()->json(['active' => false, 'status' => 'pending']);
            }

            // B. JIKA TANPA INVOICE (Cek Status Tenant untuk Landing Page)
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
     * HELPER: SIGNATURE DANA (SHA256 with Private Key)
     */
    private function generateSignature($stringToSign) {
        $privateKeyContent = config('services.dana.private_key');

        // Bersihkan Key
        $cleanKey = preg_replace('/-{5}(BEGIN|END) PRIVATE KEY-{5}|\r|\n|\s/', '', $privateKeyContent);
        $formattedKey = "-----BEGIN PRIVATE KEY-----\n" . chunk_split($cleanKey, 64, "\n") . "-----END PRIVATE KEY-----";

        $binarySignature = "";
        openssl_sign($stringToSign, $binarySignature, $formattedKey, OPENSSL_ALGO_SHA256);
        return base64_encode($binarySignature);
    }
}
