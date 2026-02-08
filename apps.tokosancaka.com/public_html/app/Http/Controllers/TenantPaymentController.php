<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DokuJokulService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Tenant;
use App\Models\TopUp;
use Throwable;

class TenantPaymentController extends Controller
{
    /**
     * 1. Generate URL Pembayaran DOKU & Simpan ke DB top_ups
     */
    public function generateUrl(Request $request)
    {
        // Validasi input nominal
        $request->validate([
            'amount' => 'required|numeric|min:10000',
        ]);

        // Ambil User yang sedang login
        $user = Auth::user();
        if (!$user) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }
            return redirect()->route('login')->with('error', 'Sesi habis, silakan login kembali.');
        }

        // 1. Logika Tenant
        $tenantId = 1;
        $tenant = $request->get('current_tenant');

        if (!$tenant) {
            $host = $request->getHost();
            $subdomain = explode('.', $host)[0];
            $tenantData = Tenant::where('subdomain', $subdomain)->first();
            if ($tenantData) {
                $tenantId = $tenantData->id;
            }
        } else {
            $tenantId = $tenant->id;
        }

        try {
            $dokuService = new DokuJokulService();

            // 2. Generate Reference No
            $referenceNo = 'DEP-DOKU-' . time() . rand(100, 999);

            // 3. Siapkan Data Customer
            $customerData = [
                'name'  => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? '08123456789'
            ];

            // 4. Request Payment URL ke DOKU
            $paymentUrl = $dokuService->createPayment($referenceNo, $request->amount, $customerData);

            // 5. Simpan Transaksi ke Database
            TopUp::create([
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
                    'generated_by' => 'TenantPaymentController'
                ]
            ]);

            session(['doku_url' => $paymentUrl]);

            // ============================================================
            // [FIX] LOGIKA CABANG: REDIRECT VS JSON
            // ============================================================

            // Jika request datang dari Form HTML (Browser), langsung Redirect ke DOKU
            if (!$request->wantsJson()) {
                return redirect()->away($paymentUrl);
            }

            // Jika request datang dari API/AJAX, kembalikan JSON
            return response()->json([
                'success' => true,
                'url'     => $paymentUrl
            ]);

        } catch (\Exception $e) {
            Log::error("LOG LOG: Gagal generate TopUp DOKU: " . $e->getMessage());

            // Error handling untuk Browser
            if (!$request->wantsJson()) {
                return redirect()->back()->with('error', 'Gagal memproses pembayaran: ' . $e->getMessage());
            }

            // Error handling untuk API
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * 2. Fungsi Cek Status (GET) - Tetap return JSON karena dipanggil via JS Polling
     */
    public function checkStatus(Request $request)
    {
        try {
            $invoice = $request->input('invoice');

            if ($invoice) {
                $topup = TopUp::where('reference_no', $invoice)->first();

                if ($topup && in_array($topup->status, ['SUCCESS', 'PAID'])) {
                    return response()->json([
                        'active' => true,
                        'status' => 'success',
                        'message' => 'Top Up Berhasil!',
                        'redirect_url' => url()->previous()
                    ]);
                }
                return response()->json(['active' => false, 'status' => 'pending']);
            }

            // Fallback Logic
            $host = $request->getHost();
            $subdomain = explode('.', $host)[0];

            $tenant = DB::connection('mysql_second')
                        ->table('tenants')
                        ->where('subdomain', $subdomain)
                        ->first();

            if ($tenant && $tenant->status === 'active') {
                return response()->json([
                    'active' => true,
                    'message' => 'Akun Aktif',
                    'redirect_url' => "https://{$tenant->subdomain}.tokosancaka.com/dashboard"
                ]);
            }

            return response()->json(['active' => false]);

        } catch (Throwable $e) {
            return response()->json([
                'active' => false,
                'error' => $e->getMessage()
            ], 200);
        }
    }
}
