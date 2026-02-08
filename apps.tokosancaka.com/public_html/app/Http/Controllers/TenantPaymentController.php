<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DokuJokulService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\PosTopUp;
use Throwable;

class TenantPaymentController extends Controller
{
    public function generateUrl(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:10000',
        ]);

        $user = Auth::user();

        if (!$user) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }
            return redirect()->route('login')->with('error', 'Sesi habis.');
        }

        // 1. Ambil Tenant ID (Wajib ada sekarang)
        // Cek apakah user punya kolom tenant_id, atau cari manual
        $tenantId = $user->tenant_id ?? null;

        if (!$tenantId) {
            $host = $request->getHost();
            $subdomain = explode('.', $host)[0];
            $tenantData = DB::table('tenants')->where('subdomain', $subdomain)->first();
            $tenantId = $tenantData ? $tenantData->id : 1;
        }

        try {
            $dokuService = new DokuJokulService();

            // Prefix Invoice POSTOPUP
            $referenceNo = 'POSTOPUP-' . $user->id . '-' . time() . '-' . rand(100, 999);

            $customerData = [
                'name'  => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? '08123456789'
            ];

            $paymentUrl = $dokuService->createPayment($referenceNo, $request->amount, $customerData);

            // 2. Simpan ke DB dengan TENANT ID
            PosTopUp::create([
                'tenant_id'      => $tenantId, // [KEMBALIKAN INI]
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

            if (!$request->wantsJson()) {
                return redirect()->back()->with('error', 'Gagal: ' . $e->getMessage());
            }
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function checkStatus(Request $request)
    {
        try {
            $invoice = $request->input('invoice');

            if ($invoice) {
                // PosTopUp otomatis filter tenant_id karena ada Trait
                // Tapi untuk checkStatus by invoice, usually safe.
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
                return response()->json(['active' => false, 'status' => 'pending']);
            }

            // Fallback Logic
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
