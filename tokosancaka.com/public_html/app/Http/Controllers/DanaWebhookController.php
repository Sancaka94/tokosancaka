<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\NotifikasiUmum;
use App\Events\AdminNotificationEvent;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\TopUp;
use App\Models\User;
use App\Models\Pesanan;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\Affiliate;
use App\Services\KiriminAjaService;
use App\Services\DokuJokulService;
use App\Services\FonnteService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;

// IMPORT CONTROLLER TERKAIT UNTUK CALLBACK ROUTING
use App\Http\Controllers\Admin\PesananController as AdminPesananController;
use App\Http\Controllers\Customer\TopUpController;

class DanaWebhookController extends Controller
{
    /**
     * MAIN HANDLER - GERBANG UTAMA WEBHOOK DANA (FINISH NOTIFY v1.0)
     */
    public function handleNotify(Request $request)
    {
        // 1. Log Payload Masuk murni untuk monitoring debugging
        Log::info("[DANA-WEBHOOK] Hit Masuk:", [
            'ip' => $request->ip(),
            'payload' => $request->all()
        ]);

        try {
            $data = $request->all();

            // Ambil RefNo berdasarkan Dokumentasi Finish Notify DANA SNAP
            $refNo = $data['originalPartnerReferenceNo'] ?? $data['partnerReferenceNo'] ?? null;
            $latestStatus = $data['latestTransactionStatus'] ?? null;
            $amountVal = $data['amount']['value'] ?? '0.00';

            if (empty($refNo)) {
                return response()->json(['res' => 'NO_REF'], 400);
            }

            // Status sukses murni dua digit dari DANA ("00")
            $isDanaSuccess = ($latestStatus === '00');

            // ====================================================================
            // 🛠️ FIX STRIP MISMATCH: Normalisasi format SCKORD dan TOPUP
            // ====================================================================
            if (Str::startsWith($refNo, 'SCKORD') && !str_contains($refNo, '-')) {
                $restOfInvoice = substr($refNo, 6);
                $refNo = 'SCK-ORD-' . $restOfInvoice;
                Log::info("[DANA-WEBHOOK] Format invoice dinormalisasi untuk database: " . $refNo);
            }
            // 🔥 TAMBAHAN UNTUK TOPUP 🔥
            elseif (Str::startsWith($refNo, 'TOPUP') && !str_contains($refNo, '-')) {
                $restOfInvoice = substr($refNo, 5); // Potong 5 huruf "TOPUP"
                $refNo = 'TOPUP-' . $restOfInvoice;
                Log::info("[DANA-WEBHOOK] Format invoice TOPUP dinormalisasi untuk database: " . $refNo);
            }
            // ====================================================================

            // =============================================================
            // ROUTING LOGIC: Tentukan Tipe Transaksi Berdasarkan Pola Invoice
            // =============================================================

            // Skenario 1: SKENARIO BELANJA BARANG MARKETPLACE (SCK-ORD- atau ORD-)
            if (Str::startsWith($refNo, 'SCK-ORD-') || Str::startsWith($refNo, 'ORD-')) {
                Log::info('Routing DANA callback to processOrderCallback (Marketplace)', ['ref' => $refNo]);

                $internalStatus = $isDanaSuccess ? 'PAID' : 'FAILED';

                $checkoutCtrl = app(\App\Http\Controllers\CheckoutController::class);
                $checkoutCtrl->processOrderCallback($refNo, $internalStatus, $data);

                return $this->respondSuccessDANA();
            }

            // Skenario 2: SKENARIO TOPUP SALDO CUSTOMER / MEMBER (TOPUP-)
            elseif (Str::startsWith($refNo, 'TOPUP-') || Str::startsWith($refNo, 'DEP-') || str_contains($refNo, 'TOPUP')) {
                Log::info('Routing DANA callback to TopUpController', ['ref' => $refNo, 'amount' => $amountVal]);

                $internalStatus = $isDanaSuccess ? 'PAID' : 'FAILED';

                // Menggunakan static call karena fungsi processTopUpCallback berbentuk static
                TopUpController::processTopUpCallback($refNo, $internalStatus, $amountVal);

                return $this->respondSuccessDANA();
            }

            // Skenario 3: DATA PESANAN BARANG LAMA / LEGACY (Prefix: SCK- murni)
            elseif (Str::startsWith($refNo, 'SCK-')) {
                Log::info('Routing DANA callback to AdminPesananController (Legacy)', ['ref' => $refNo]);

                $internalStatus = $isDanaSuccess ? 'PAID' : 'FAILED';
                AdminPesananController::processPesananCallback($refNo, $internalStatus, $data);

                return $this->respondSuccessDANA();
            }

            // =============================================================
            // FALLBACK SAFETY NET (UNTUK BYPASS TRANSAKSI MANDIRI TESTING)
            // =============================================================
            Log::info("[DANA-WEBHOOK] ID Uji Coba Mandiri DANA Sandbox tidak dikenali: $refNo. Auto bypass.");
            return $this->respondSuccessDANA();

        } catch (Exception $e) {
            Log::error("[DANA-WEBHOOK] Fatal Error: " . $e->getMessage() . " | Line: " . $e->getLine());
            return response()->json(['responseCode' => '5005601', 'message' => 'Internal Error'], 500);
        }
    }

    /**
     * 👑 NEW FUNCTION: INTELLIGENT REDIRECT RETURN PAGE
     */
    public function returnPage(Request $request)
    {
        // Ambil referensi order yang dikirim DANA saat redirect balik ke web
        $refNo = $request->input('partnerReferenceNo') ?? $request->input('id') ?? '';

        Log::info('[DANA RETURN PAGE] User kembali dari DANA Portal.', ['raw_ref' => $refNo]);

        // ====================================================================
        // 🔥 NORMALISASI ID JUGA DITERAPKAN DI HALAMAN RETURN 🔥
        // ====================================================================
        if (Str::startsWith($refNo, 'SCKORD') && !str_contains($refNo, '-')) {
            $refNo = 'SCK-ORD-' . substr($refNo, 6);
        } elseif (Str::startsWith($refNo, 'TOPUP') && !str_contains($refNo, '-')) {
            $refNo = 'TOPUP-' . substr($refNo, 5);
        }

        // 1. Jika ini Transaksi Belanja Toko Marketplace (SCK-ORD-)
        if (Str::startsWith($refNo, 'SCK-ORD-') || str_contains($refNo, 'ORD')) {
            Log::info("[DANA RETURN] Redirecting user ke halaman Riwayat Belanja: " . $refNo);

            return redirect()->to('https://tokosancaka.com/customer/pesanan/riwayat-belanja')
                ->with('success', 'Pembayaran DANA Anda berhasil diproses! Silakan cek status dan nomor resi pengiriman Anda di bawah ini.');
        }

        // 2. Jika ini Transaksi Top Up Saldo Akun (TOPUP-)
        if (Str::startsWith($refNo, 'TOPUP-')) {
            Log::info("[DANA RETURN] Redirecting user ke halaman Detail Top Up: " . $refNo);
            return redirect()->route('customer.topup.show', ['topup' => $refNo])
                ->with('success', 'Top Up DANA Anda berhasil dan saldo akan masuk secara otomatis.');
        }

        // 3. Default Fallback (Jika ID tidak jelas / transaksi anonim)
        return redirect()->to('https://tokosancaka.com/customer/pesanan/riwayat-belanja')
            ->with('success', 'Transaksi DANA berhasil diselesaikan.');
    }

    /**
     * Helper Respon Standard SNAP DANA
     */
    private function respondSuccessDANA()
    {
        return response()->json([
            'responseCode' => '2005600',
            'responseMessage' => 'Successful'
        ])->withHeaders([
            'Content-Type' => 'application/json',
            'X-TIMESTAMP'  => Carbon::now('Asia/Jakarta')->format('Y-m-d\TH:i:sP')
        ]);
    }
}
