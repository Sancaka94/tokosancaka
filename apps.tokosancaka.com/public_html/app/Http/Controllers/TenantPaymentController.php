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

// --- 1. IMPORT DANA SDK (WAJIB ADA) ---
use Dana\Widget\v1\Model\WidgetPaymentRequest;
use Dana\Widget\v1\Model\Money;
use Dana\Widget\v1\Model\UrlParam;
use Dana\Widget\v1\Model\WidgetPaymentRequestAdditionalInfo;
use Dana\Widget\v1\Model\EnvInfo;
use Dana\Widget\v1\Model\Order as DanaOrder;
use Dana\Widget\v1\Enum\PayMethod;
use Dana\Widget\v1\Enum\SourcePlatform;
use Dana\Widget\v1\Enum\TerminalType;
use Dana\Widget\v1\Enum\OrderTerminalType;
use Dana\Widget\v1\Enum\Type;
use Dana\Configuration;
use Dana\Env;
use Dana\Widget\v1\Api\WidgetApi;

class TenantPaymentController extends Controller
{
    private const CENTRAL_CALLBACK_URL = 'https://apps.tokosancaka.com/dana/callback';

    public function generateUrl(Request $request)
{
    $request->validate([
        'amount' => 'required|numeric|min:10000',
        'payment_method' => 'nullable|in:DOKU,DANA',
        'target_subdomain' => 'required' // Tambahkan validasi ini
    ]);

    // Cari user berdasarkan subdomain yang dikirim dari form redeem
    $user = \App\Models\User::where('username', $request->target_subdomain)->first();

    if (!$user) {
        return redirect()->back()->with('error', 'Data Toko tidak ditemukan. Silakan login kembali.');
    }

    $tenantId = $user->tenant_id ?? 1;
    $method = $request->payment_method ?? 'DOKU';

    if ($method === 'DANA') {
        return $this->processDanaPayment($request, $user, $tenantId);
    } else {
        return $this->processDokuPayment($request, $user, $tenantId);
    }
}

    public function startBinding(Request $request)
    {
        $user = Auth::user();
        $subdomain = explode('.', $request->getHost())[0];
        $tenantId  = $user->tenant_id ?? 1;

        $state = "BIND_TENANT-{$user->id}-{$subdomain}-{$tenantId}";

        $clientId = config('services.dana.x_partner_id');
        $encodedCallback = urlencode(self::CENTRAL_CALLBACK_URL);
        $requestId = Str::uuid();

        $danaUrl = "https://m.sandbox.dana.id/d/portal/oauth?partnerId={$clientId}&scopes=QUERY_BALANCE,MINI_DANA,DEFAULT_BASIC_PROFILE&requestId={$requestId}&redirectUrl={$encodedCallback}&state={$state}&terminalType=WEB";

        return redirect()->away($danaUrl);
    }

    /**
     * PROSES DANA (MENGGUNAKAN SDK - HASIL COPY DARI MEMBER)
     */
    private function processDanaPayment($request, $user, $tenantId)
    {
        Log::info("[DEPOSIT-LOG] Masuk Flow DANA (Tenant)", ['user_id' => $user->id]);

        // CEK TOKEN
        if (empty($user->dana_access_token)) {
            Log::warning("[DEPOSIT-LOG] DANA Token Missing", ['user_id' => $user->id]);
            // Jika AJAX
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Token DANA tidak ditemukan.'], 400);
            }
            return redirect()->route('tenant.dana.start')->with('error', 'Silakan hubungkan akun DANA Anda terlebih dahulu.');
        }

        // Gunakan nilai dari Input UI/Blade
        $realAmount = $request->amount;

        Log::info("[DEPOSIT-LOG] Set Amount Real", ['amount' => $realAmount]);

        // Config Check
        $merchantId = config('services.dana.merchant_id');
        if (empty($merchantId)) {
            Log::error("[DEPOSIT-LOG] Config Error: Merchant ID Kosong");
            return back()->with('error', 'Config Error: Merchant ID Missing.');
        }

        // Init DB Transaction Record
        $refNo = 'DEP-T-' . time() . mt_rand(100, 999); // DEP-T (Tenant)

        Log::info("[DEPOSIT-LOG] Membuat Record DB (INIT)", ['ref_no' => $refNo]);

        // Masukkan ke tabel dana_transactions (Sesuai request untuk menyamakan)
        DB::table('dana_transactions')->insert([
            'tenant_id'    => $tenantId,
            'affiliate_id' => $user->id, // Simpan ID User Admin disini
            'type'         => 'DEPOSIT',
            'reference_no' => $refNo,
            'phone'        => $user->phone ?? '',
            'amount'       => $realAmount,
            'status'       => 'INIT',
            'created_at'   => now()
        ]);

        // Juga simpan ke PosTopUp agar logic checkStatus Tenant jalan
        PosTopUp::create([
            'tenant_id'      => $tenantId,
            'affiliate_id'   => $user->id,
            'reference_no'   => $refNo,
            'amount'         => $realAmount,
            'unique_code'    => 0,
            'total_amount'   => $realAmount,
            'status'         => 'PENDING',
            'payment_method' => 'DANA',
            'response_payload' => null
        ]);

        try {
            // 1. Config SDK
            Log::info("[DEPOSIT-LOG] Menginisialisasi SDK Configuration");
            $config = new Configuration();
            $config->setApiKey('PRIVATE_KEY', config('services.dana.private_key'));
            $config->setApiKey('X_PARTNER_ID', config('services.dana.x_partner_id'));
            $config->setApiKey('ORIGIN', config('services.dana.origin'));
            $config->setApiKey('DANA_ENV', Env::SANDBOX);

            $apiInstance = new WidgetApi(null, $config);

            // 2. Order
            $orderObj = new DanaOrder();
            $orderObj->setOrderTitle("Topup Saldo Toko"); // Ubah Judul Dikit
            $orderObj->setOrderMemo("Topup Admin ID " . $user->id);

            // 3. EnvInfo
            $envInfo = new EnvInfo();
            $envInfo->setSourcePlatform("IPG");
            $envInfo->setTerminalType("WEB");
            $envInfo->setWebsiteLanguage("ID");
            $envInfo->setClientIp("82.25.62.13");

            // 4. Additional Info
            $addInfo = new WidgetPaymentRequestAdditionalInfo();
            $addInfo->setProductCode("51051000100000000001");
            $addInfo->setOrder($orderObj);
            $addInfo->setEnvInfo($envInfo);

            // 5. Request Object
            $paymentRequest = new WidgetPaymentRequest();
            $paymentRequest->setMerchantId($merchantId);
            $paymentRequest->setPartnerReferenceNo($refNo);

            // Amount (Strict 2 Decimal)
            $amountString = number_format($realAmount, 2, '.', '');
            $money = new Money();
            $money->setValue($amountString);
            $money->setCurrency("IDR");
            $paymentRequest->setAmount($money);

            // --- REDIRECT URL LOGIC (TENANT) ---
            $urlParam = new UrlParam();

            // Arahkan ke Dashboard Tenant
            $returnUrl = route('dashboard');

            // Paksa HTTPS
            if (!str_contains($returnUrl, 'https://')) {
                $returnUrl = str_replace('http://', 'https://', $returnUrl);
            }

            Log::info('[DANA RETURN URL]', ['url' => $returnUrl]);

            $urlParam->setUrl($returnUrl);
            $urlParam->setType("PAY_RETURN");
            $urlParam->setIsDeeplink("Y");

            $paymentRequest->setUrlParams([$urlParam]);
            $paymentRequest->setAdditionalInfo($addInfo);

            // LOG PAYLOAD SDK
            Log::info("[DEPOSIT-LOG] SDK Request Payload Siap", [
                'merchant_id' => $merchantId,
                'ref_no' => $refNo,
                'amount' => $amountString
            ]);

            // 6. EKSEKUSI API
            Log::info("[DEPOSIT-LOG] Mengirim Request ke DANA...");
            $result = $apiInstance->widgetPayment($paymentRequest);
            Log::info("[DEPOSIT-LOG] Response Diterima dari DANA", ['response' => (array)$result]);

            // 7. HANDLE RESPONSE
            $redirectUrl = null;
            if (method_exists($result, 'getWebRedirectUrl')) {
                $redirectUrl = $result->getWebRedirectUrl();
            } elseif (isset($result->webRedirectUrl)) {
                $redirectUrl = $result->webRedirectUrl;
            }

            if ($redirectUrl) {
                Log::info("[DEPOSIT-LOG] Redirect URL ditemukan. Update DB PENDING.", ['url' => $redirectUrl]);

                // Update kedua tabel
                DB::table('dana_transactions')
                    ->where('reference_no', $refNo)
                    ->update([
                        'status' => 'PENDING',
                        'response_payload' => json_encode($result),
                        'updated_at' => now()
                    ]);

                PosTopUp::where('reference_no', $refNo)->update(['response_payload' => json_encode($result)]);

                // JIKA REQUEST JSON (AJAX)
                if ($request->wantsJson()) {
                    return response()->json(['success' => true, 'url' => $redirectUrl]);
                }

                return redirect($redirectUrl);
            } else {
                Log::error("[DEPOSIT-LOG] Redirect URL NULL", ['full_response' => json_encode($result)]);
                throw new \Exception("Empty Redirect URL form DANA Response.");
            }

        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();

            if (method_exists($e, 'getResponseBody')) {
                $body = $e->getResponseBody();
                Log::error("[DEPOSIT-LOG] SDK API ERROR BODY", (array)$body);
                if ($body && isset($body->responseMessage)) {
                    $code = $body->responseCode ?? '';
                    $errorMsg = "DANA ($code): " . $body->responseMessage;
                }
            }

            Log::error('[DEPOSIT-LOG] EXCEPTION THROWN: ' . $errorMsg);

            DB::table('dana_transactions')
                ->where('reference_no', $refNo)
                ->update([
                    'status' => 'FAILED',
                    'response_payload' => $errorMsg,
                    'updated_at' => now()
                ]);

            PosTopUp::where('reference_no', $refNo)->update(['status' => 'FAILED']);

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $errorMsg], 500);
            }

            return back()->with('error', $errorMsg);
        }
    }

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
                'tenant_id' => $tenantId,
                'affiliate_id' => $user->id,
                'reference_no' => $referenceNo,
                'amount' => $request->amount,
                'unique_code' => 0,
                'total_amount' => $request->amount,
                'status' => 'PENDING',
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

    public function checkStatus(Request $request)
    {
        try {
            $invoice = $request->input('invoice');
            $appDomain = env('APP_URL_DOMAIN', 'tokosancaka.com');
            $scheme = $request->secure() ? 'https://' : 'http://';
            $currentSubdomain = explode('.', $request->getHost())[0];

            if ($invoice) {
                // Cek PosTopUp (karena di processDanaPayment kita juga simpan ke PosTopUp)
                $topup = PosTopUp::where('reference_no', $invoice)->first();

                // Alternatif: Cek dana_transactions jika PosTopUp belum update
                if (!$topup) {
                     $danaTrx = DB::table('dana_transactions')->where('reference_no', $invoice)->first();
                     if ($danaTrx && $danaTrx->status == 'SUCCESS') {
                         return response()->json(['active' => true, 'status' => 'success', 'message' => 'Top Up Berhasil (DANA)']);
                     }
                }

                if ($topup && in_array($topup->status, ['SUCCESS', 'PAID'])) {
                    $redirectUrl = url()->previous();
                    $trxTenant = DB::table('tenants')->where('id', $topup->tenant_id)->first();

                    if ($trxTenant) {
                        $redirectUrl = $scheme . $trxTenant->subdomain . '.' . $appDomain . '/dashboard';
                    }

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

            // Fallback
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
}
