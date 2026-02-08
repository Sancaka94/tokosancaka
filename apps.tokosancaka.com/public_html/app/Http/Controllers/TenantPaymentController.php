<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DokuJokulService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Tenant;
use App\Models\PosTopUp; // Pastikan Model ini sudah dibuat (Koneksi mysql_second)
use Throwable;

class TenantPaymentController extends Controller
{
    /**
     * 1. Generate URL Pembayaran DOKU & Simpan ke DB POS (top_ups)
     */
    public function generateUrl(Request $request)
    {
        // 1. Validasi Input
        $request->validate([
            'amount' => 'required|numeric|min:10000',
        ]);

        // 2. Cek User Login (Bisa Admin Tenant atau Member Affiliate)
        $user = Auth::user();

        if (!$user) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }
            return redirect()->route('login')->with('error', 'Sesi habis, silakan login kembali.');
        }

        // 3. Ambil Tenant ID
        // Jika user punya kolom tenant_id, pakai itu. Jika tidak, cari manual dari subdomain.
        $tenantId = $user->tenant_id ?? null;

        if (!$tenantId) {
            $host = $request->getHost();
            $subdomain = explode('.', $host)[0];
            // Cari ID tenant di database second (sesuai konteks POS)
            $tenantData = DB::connection('mysql_second')->table('tenants')->where('subdomain', $subdomain)->first();
            $tenantId = $tenantData ? $tenantData->id : 1; // Default 1 jika tidak ketemu
        }

        try {
            $dokuService = new DokuJokulService();

            // 4. Generate Invoice Unik
            // Format: POSTOPUP-{USER_ID}-{TIMESTAMP}-{RANDOM}
            // Prefix 'POSTOPUP-' ini PENTING agar Webhook tahu harus update ke database mysql_second
            $referenceNo = 'POSTOPUP-' . $user->id . '-' . time() . '-' . rand(100, 999);

            // 5. Siapkan Data Customer untuk DOKU
            $customerData = [
                'name'  => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? '08123456789'
            ];

            // 6. Request Payment URL ke API DOKU
            $paymentUrl = $dokuService->createPayment($referenceNo, $request->amount, $customerData);

            // 7. Simpan Transaksi ke Database POS (mysql_second)
            // Menggunakan Model PosTopUp yang sudah disetting connection = 'mysql_second'
            PosTopUp::create([
                'tenant_id'      => $tenantId,
                'affiliate_id'   => $user->id,        // ID User yang melakukan topup
                'reference_no'   => $referenceNo,     // Invoice DOKU
                'amount'         => $request->amount,
                'unique_code'    => 0,                // Tidak butuh kode unik
                'total_amount'   => $request->amount,
                'status'         => 'PENDING',
                'payment_method' => 'DOKU',
                'response_payload' => [               // Simpan URL untuk referensi
                    'payment_url' => $paymentUrl,
                    'generated_by' => 'TenantPaymentController',
                    'user_role'    => $user->role ?? 'unknown'
                ]
            ]);

            // Simpan di session (opsional)
            session(['doku_url' => $paymentUrl]);

            // 8. Return Response (Cerdas: Redirect Browser atau JSON API)

            // Jika request dari Browser (Submit Form Modal), langsung Redirect
            if (!$request->wantsJson()) {
                return redirect()->away($paymentUrl);
            }

            // Jika request dari API/AJAX, return JSON
            return response()->json([
                'success' => true,
                'url'     => $paymentUrl
            ]);

        } catch (\Exception $e) {
            Log::error("LOG POS: Gagal generate TopUp DOKU: " . $e->getMessage());

            if (!$request->wantsJson()) {
                return redirect()->back()->with('error', 'Gagal memproses pembayaran: ' . $e->getMessage());
            }
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * 2. Fungsi Cek Status (Untuk Polling Frontend / Cek Redirect)
     */
    public function checkStatus(Request $request)
    {
        try {
            // Ambil invoice dari parameter request
            $invoice = $request->input('invoice');

            if ($invoice) {
                // Cari data di DB Second menggunakan Model PosTopUp
                $topup = PosTopUp::where('reference_no', $invoice)->first();

                // Cek jika status SUDAH SUKSES
                if ($topup && in_array($topup->status, ['SUCCESS', 'PAID'])) {
                    return response()->json([
                        'active' => true,
                        'status' => 'success',
                        'message' => 'Top Up Berhasil!',
                        'current_balance' => Auth::user()->fresh()->saldo, // Ambil saldo terbaru
                        'redirect_url' => url()->previous()
                    ]);
                }
                // Jika masih pending
                return response()->json(['active' => false, 'status' => 'pending']);
            }

            // Fallback: Logic cek tenant active (Untuk halaman login/register)
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
