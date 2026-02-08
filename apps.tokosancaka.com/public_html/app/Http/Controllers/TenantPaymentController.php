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
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // 1. Logika Tenant (Untuk isi tenant_id)
        $tenantId = 1; // Default ke 1 sesuai contoh data Anda
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

            // 2. Generate Reference No (Format: DEP-DOKU-TIMESTAMP-RAND)
            // Format disesuaikan agar mirip dengan 'DEP-...' di database Anda
            $referenceNo = 'DEP-DOKU-' . time() . rand(100, 999);

            // 3. Siapkan Data Customer (Wajib untuk Doku)
            $customerData = [
                'name'  => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? '08123456789'
            ];

            // 4. Request Payment URL ke DOKU
            $paymentUrl = $dokuService->createPayment($referenceNo, $request->amount, $customerData);

            // 5. [PENTING] Simpan Transaksi ke Database (Tabel top_ups)
            TopUp::create([
                'tenant_id'      => $tenantId,
                'affiliate_id'   => $user->id,        // Mapping User ID ke affiliate_id
                'reference_no'   => $referenceNo,     // Mapping Invoice ke reference_no
                'amount'         => $request->amount,
                'unique_code'    => 0,                // Doku tidak butuh kode unik (0)
                'total_amount'   => $request->amount, // Total sama dengan amount (tanpa kode unik)
                'status'         => 'PENDING',
                'payment_method' => 'DOKU',
                'response_payload' => [               // Simpan URL disini karena tidak ada kolom payment_url
                    'payment_url' => $paymentUrl,
                    'generated_by' => 'TenantPaymentController'
                ]
            ]);

            // Simpan session (opsional)
            session(['doku_url' => $paymentUrl]);

            return response()->json([
                'success' => true,
                'url'     => $paymentUrl
            ]);

        } catch (\Exception $e) {
            Log::error("LOG LOG: Gagal generate TopUp DOKU: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * 2. Fungsi Cek Status (Untuk Polling Frontend)
     */
    public function checkStatus(Request $request)
    {
        try {
            // Ambil invoice/reference_no dari request frontend
            $invoice = $request->input('invoice'); // Di frontend kirimkan 'invoice' = reference_no

            if ($invoice) {
                // Cek Status Topup spesifik berdasarkan reference_no
                $topup = TopUp::where('reference_no', $invoice)->first();

                // Cek status SUCCESS (sesuaikan string di DB Anda jika 'PAID' atau 'SUCCESS')
                if ($topup && in_array($topup->status, ['SUCCESS', 'PAID'])) {
                    return response()->json([
                        'active' => true,
                        'status' => 'success',
                        'message' => 'Top Up Berhasil!',
                        'redirect_url' => url()->previous() // Reload halaman
                    ]);
                }
                // Jika masih pending
                return response()->json(['active' => false, 'status' => 'pending']);
            }

            // Fallback: Logic cek tenant active (Sesuai kode asli Anda)
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
