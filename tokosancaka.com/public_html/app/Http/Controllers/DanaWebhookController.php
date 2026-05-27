<?php

namespace App\Http\Controllers;
use Exception;

use App\Http\Controllers\Admin\PesananController as AdminPesananController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Customer\TopUpController;

use App\Models\Order;
use App\Models\TopUp;
use App\Models\Transaction;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Str;

class DanaWebhookController extends Controller
{
    /**
     * =========================================================
     * DANA WEBHOOK NOTIFICATION
     * =========================================================
     */
    public function handleNotify(Request $request)
    {
        Log::info('[DANA WEBHOOK] HIT', [
            'ip'      => $request->ip(),
            'payload' => $request->all(),
        ]);

        try {

            $data = $request->all();

            // =====================================================
            // AMBIL REFERENCE
            // =====================================================
            $refNo = $data['originalPartnerReferenceNo']
                ?? $data['partnerReferenceNo']
                ?? null;

            $latestStatus = $data['latestTransactionStatus'] ?? null;

            $amountValue = $data['amount']['value'] ?? '0.00';

            if (!$refNo) {

                Log::warning('[DANA WEBHOOK] REF EMPTY');

                return response()->json([
                    'responseCode'    => '4005601',
                    'responseMessage' => 'Reference Not Found'
                ], 400);
            }

            // =====================================================
            // NORMALIZE REF
            // =====================================================
            $refNo = $this->normalizeReference($refNo);

            Log::info('[DANA WEBHOOK] NORMALIZED', [
                'ref'    => $refNo,
                'status' => $latestStatus
            ]);

            // =====================================================
            // MAP STATUS
            // =====================================================
            $isSuccess = ($latestStatus === '00' || strtoupper($latestStatus) === 'SUCCESS');

            $internalStatus = $isSuccess
                ? 'PAID'
                : 'FAILED';

            // =====================================================
            // MARKETPLACE ORDER
            // =====================================================
            if (
                Str::startsWith($refNo, 'SCK-ORD-') ||
                Str::startsWith($refNo, 'ORD-')
            ) {

                Log::info('[DANA WEBHOOK] MARKETPLACE ORDER', [
                    'ref' => $refNo
                ]);

                app(CheckoutController::class)
                    ->processOrderCallback(
                        $refNo,
                        $internalStatus,
                        $data
                    );

                return $this->respondSuccessDANA();
            }

            // =====================================================
            // TOPUP
            // =====================================================
            if (
                Str::startsWith($refNo, 'TOPUP-') ||
                Str::startsWith($refNo, 'DEP-')
            ) {

                Log::info('[DANA WEBHOOK] TOPUP', [
                    'ref' => $refNo
                ]);

                TopUpController::processTopUpCallback(
                    $refNo,
                    $internalStatus,
                    $amountValue
                );

                return $this->respondSuccessDANA();
            }

            // =====================================================
            // PPOB
            // =====================================================
            if (
                Str::startsWith($refNo, 'P') ||
                Str::startsWith($refNo, 'PASCA')
            ) {

                Log::info('[DANA WEBHOOK] PPOB', [
                    'ref' => $refNo
                ]);

                $trx = Transaction::where('ref_id', $refNo)
                ->orWhere(
                    'reference_id', // Changed from 'tr_id'
                    str_replace('PASCA', '', $refNo)
                )
                ->first();

                if ($trx) {

                    $trx->payment_status = $internalStatus;

                    if ($isSuccess) {
                        $trx->paid_at = now();
                    }

                    $trx->save();

                    Log::info('[DANA WEBHOOK] PPOB UPDATED', [
                        'id'     => $trx->id ?? null,
                        'ref_id' => $trx->ref_id ?? null
                    ]);
                } else {

                    Log::warning('[DANA WEBHOOK] PPOB NOT FOUND', [
                        'ref' => $refNo
                    ]);
                }

                return $this->respondSuccessDANA();
            }

            // =====================================================
            // LEGACY PESANAN (FIXED)
            // =====================================================
            if (Str::startsWith($refNo, 'SCK-')) {
                Log::info('[DANA WEBHOOK] Proses Pesanan: ' . $refNo . ' Status: ' . $internalStatus);

                // 1. Cari pesanan di tabel Pesanan berdasarkan nomor_invoice
                $pesanan = \App\Models\Pesanan::where('nomor_invoice', $refNo)->first();

                if ($pesanan) {
                    // 2. Jika status DANA sukses
                    if ($isSuccess) {

                        // [FIX]: Update tabel Transaction agar status tidak nyangkut pending
                        \App\Models\Transaction::where('reference_id', $refNo)
                            ->update(['status' => 'success', 'payment_status' => 'PAID']);

                        // [FIX]: Ubah ke 'Pesanan Dibuat', bukan 'Selesai'
                        $pesanan->update([
                            'status'         => 'Pesanan Dibuat',
                            'status_pesanan' => 'Pesanan Dibuat',
                            'updated_at'     => now()
                        ]);

                        Log::info('[DANA WEBHOOK] Pesanan ' . $refNo . ' lunas, diupdate ke Pesanan Dibuat.');

                        // 3. (PENTING) Buka komentar ini jika ingin resi otomatis keluar saat lunas
                        // app(\App\Http\Controllers\Admin\PesananController::class)->triggerResi($pesanan);
                    } else {
                        // Jika pembayaran gagal
                        \App\Models\Transaction::where('reference_id', $refNo)
                            ->update(['status' => 'failed', 'payment_status' => 'FAILED']);

                        $pesanan->update([
                            'status'         => 'Dibatalkan',
                            'status_pesanan' => 'Dibatalkan'
                        ]);

                        Log::info('[DANA WEBHOOK] Pesanan ' . $refNo . ' Gagal/Dibatalkan.');
                    }
                } else {
                    Log::error('[DANA WEBHOOK] Pesanan tidak ditemukan di tabel Pesanan: ' . $refNo);
                }

                return $this->respondSuccessDANA();
            }

            // =====================================================
            // UNKNOWN
            // =====================================================
            Log::warning('[DANA WEBHOOK] UNKNOWN REF', [
                'ref' => $refNo
            ]);

            return $this->respondSuccessDANA();

        } catch (Exception $e) {

            Log::error('[DANA WEBHOOK] ERROR', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
            ]);

            return response()->json([
                'responseCode'    => '5005601',
                'responseMessage' => 'Internal Server Error'
            ], 500);
        }
    }

   /**
     * =========================================================
     * UNIVERSAL RETURN PAGE (DANA CALLBACK/RETURN)
     * =========================================================
     */
    public function returnPage(Request $request)
    {
        Log::info('[DANA RETURN PAGE] Hit', [
            'query'      => $request->all(),
            'full_url'   => $request->fullUrl(),
            'user_agent' => $request->userAgent(),
        ]);

        try {
            // 1. AMBIL REF DENGAN PRIORITAS: URL -> SESSION -> TERAKHIR
            $refNo = $request->query('trx_id')
                  ?? $request->partnerReferenceNo
                  ?? $request->bizNo
                  ?? $request->id
                  ?? session('last_dana_ref')
                  ?? '';

            if (!$refNo) {
                Log::warning('[DANA RETURN] REF EMPTY - Tidak ditemukan referensi di URL maupun Session');
                return redirect('/')->with('error', 'Transaksi tidak ditemukan.');
            }

            // 2. NORMALIZE REF
            $refNo = $this->normalizeReference($refNo);
            Log::info('[DANA RETURN] NORMALIZED REF: ' . $refNo);

            // 3. DETECT PLATFORM (Mobile vs Web)
            $isMobile = ($request->header('X-Platform') === 'mobile' ||
                         preg_match('/Android|iPhone|iPad|Mobile/i', $request->userAgent()));

            // 4. CEK PPOB
            $trx = Transaction::where('ref_id', $refNo)
                ->orWhere('reference_id', str_replace('PASCA', '', $refNo))
                ->first();


            if ($trx) {
                Session::forget('last_dana_ref'); // Bersihkan session
                if ($isMobile) {
                    return redirect()->away('sancakaexpress://riwayatppob/' . $refNo);
                }
                return redirect()->to('https://tokosancaka.com/riwayatppob')->with('success', 'Pembayaran PPOB berhasil.');
            }

            // 5. CEK TOPUP
            $topup = TopUp::where('transaction_id', $refNo)->first();
            if ($topup) {
                Session::forget('last_dana_ref');
                if ($isMobile) {
                    return redirect()->away('sancakaexpress://topup-success/' . $refNo);
                }
                return redirect('/')->with('success', 'Topup berhasil diproses.');
            }

            // 6. CEK MARKETPLACE ORDER
            $order = Order::where('invoice_number', $refNo)->first();
            if ($order) {
                Session::forget('last_dana_ref');
                if ($isMobile) {
                    // Redirect ke riwayat pesanan sesuai permintaanmu
                    return redirect()->away('sancakaexpress://riwayatpesanan');
                }
                return redirect()->to('https://tokosancaka.com/customer/pesanan/riwayat-belanja')
                    ->with('success', 'Pembayaran berhasil.');
            }

            // 7. FALLBACK
            Log::warning('[DANA RETURN] DATA NOT FOUND untuk Ref: ' . $refNo);
            return redirect('/')->with('success', 'Transaksi berhasil diproses.');

        } catch (Exception $e) {
            Log::error('[DANA RETURN PAGE ERROR]', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
            ]);

            return redirect('/')->with('error', 'Terjadi kesalahan redirect pembayaran.');
        }
    }

    private function normalizeReference($refNo)
    {
        if (Str::startsWith($refNo, 'SCKORD') && !str_contains($refNo, '-')) {
            return 'SCK-ORD-' . substr($refNo, 6);
        }

        if (Str::startsWith($refNo, 'TOPUP') && !str_contains($refNo, '-')) {
            return 'TOPUP-' . substr($refNo, 5);
        }

        // [TAMBAHAN FIX] Kembalikan format SCK20251118IP52X4 menjadi SCK-20251118-IP52X4
        if (preg_match('/^SCK(\d{8})([A-Z0-9]+)$/', $refNo, $matches)) {
            return 'SCK-' . $matches[1] . '-' . $matches[2];
        }

        return trim($refNo);
    }

    /**
     * =========================================================
     * STANDARD SUCCESS RESPONSE DANA SNAP
     * =========================================================
     */
    private function respondSuccessDANA()
    {
        return response()->json([
            'responseCode'    => '2005600',
            'responseMessage' => 'Successful'
        ])->withHeaders([
            'Content-Type' => 'application/json',
            'X-TIMESTAMP'  => Carbon::now('Asia/Jakarta')
                ->format('Y-m-d\TH:i:sP')
        ]);
    }
}
