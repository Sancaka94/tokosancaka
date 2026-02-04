<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DokuJokulService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant; // Pastikan Import Model
use Throwable;

class TenantPaymentController extends Controller
{
    public function generateUrl(Request $request)
    {
        // 1. Coba ambil dari Middleware
        $tenant = $request->get('current_tenant');

        // 2. [FIX] Jika null (karena lewat jalur API), cari manual berdasarkan Subdomain Host
        if (!$tenant) {
            $host = $request->getHost();
            $subdomain = explode('.', $host)[0];

            // Cek di Database
            $tenant = Tenant::where('subdomain', $subdomain)->first();
        }

        // 3. Jika masih tidak ketemu juga, baru error 404
        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant tidak ditemukan untuk subdomain: ' . ($subdomain ?? 'unknown')
            ], 404);
        }

        try {
            $dokuService = new DokuJokulService();

            // Generate Invoice Unik
            $invoice = 'REN-' . $tenant->subdomain . '-' . time() . '-' . rand(1000, 9999);

            $paymentUrl = $dokuService->createPayment($invoice, $request->amount);

            // Simpan session (opsional, mungkin tidak ngefek di API tapi aman dilakukan)
            session(['doku_url' => $paymentUrl]);



            return response()->json([
                'success' => true,
                'url' => $paymentUrl
            ]);
        } catch (\Exception $e) {
            Log::error("LOG LOG: Gagal generate DOKU: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // Tambahkan di App/Http/Controllers/TenantPaymentController.php

/**
     * 2. Fungsi Cek Status (GET)
     */
    public function checkStatus(Request $request)
    {
        try {
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
            // Jangan return 500 di check-status agar polling JS tidak merah
            return response()->json([
                'active' => false,
                'error' => $e->getMessage()
            ], 200);
        }
    }
}
