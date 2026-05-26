<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\PesananController as AdminPesananController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Customer\TopUpController;

use App\Models\Order;
use App\Models\TopUp;
use App\Models\Transaction;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DanaWebhookController extends Controller
{
    /**
     * =========================================================
     * WEBHOOK NOTIFICATION DANA
     * =========================================================
     */
    public function handleNotify(Request $request)
    {
        Log::info('[DANA WEBHOOK] HIT', [
            'ip' => $request->ip(),
            'payload' => $request->all(),
        ]);

        try {

            $data = $request->all();

            // =====================================================
            // AMBIL REFERENCE NUMBER
            // =====================================================
            $refNo =
                $data['originalPartnerReferenceNo']
                ?? $data['partnerReferenceNo']
                ?? null;

            $latestStatus =
                $data['latestTransactionStatus']
                ?? null;

            $amountVal =
                $data['amount']['value']
                ?? '0.00';

            if (!$refNo) {

                Log::warning('[DANA WEBHOOK] REF NO EMPTY');

                return response()->json([
                    'responseCode' => '4005601',
                    'responseMessage' => 'Reference Not Found'
                ], 400);
            }

            // =====================================================
            // NORMALISASI FORMAT
            // =====================================================
            $refNo = $this->normalizeReference($refNo);

            Log::info('[DANA WEBHOOK] NORMALIZED REF', [
                'ref' => $refNo
            ]);

            // =====================================================
            // STATUS MAPPING
            // =====================================================
            $isSuccess = ($latestStatus === '00');

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

                Log::info('[DANA WEBHOOK] ROUTE MARKETPLACE', [
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

                Log::info('[DANA WEBHOOK] ROUTE TOPUP', [
                    'ref' => $refNo
                ]);

                TopUpController::processTopUpCallback(
                    $refNo,
                    $internalStatus,
                    $amountVal
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

                Log::info('[DANA WEBHOOK] ROUTE PPOB', [
                    'ref' => $refNo
                ]);

                $trx = Transaction::where('ref_id', $refNo)
                    ->orWhere('tr_id', str_replace('PASCA', '', $refNo))
                    ->first();

                if ($trx) {

                    $trx->payment_status = $internalStatus;
                    $trx->paid_at = now();
                    $trx->save();

                    Log::info('[DANA WEBHOOK] PPOB UPDATED', [
                        'trx' => $trx->id
                    ]);
                }

                return $this->respondSuccessDANA();
            }

            // =====================================================
            // LEGACY PESANAN
            // =====================================================
            if (Str::startsWith($refNo, 'SCK-')) {

                Log::info('[DANA WEBHOOK] ROUTE LEGACY', [
                    'ref' => $refNo
                ]);

                AdminPesananController::processPesananCallback(
                    $refNo,
                    $internalStatus,
                    $data
                );

                return $this->respondSuccessDANA();
            }

            // =====================================================
            // FALLBACK
            // =====================================================
            Log::warning('[DANA WEBHOOK] UNKNOWN REF', [
                'ref' => $refNo
            ]);

            return $this->respondSuccessDANA();

        } catch (Exception $e) {

            Log::error('[DANA WEBHOOK] ERROR', [
                'message' => $e->getMessage(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'responseCode' => '5005601',
                'responseMessage' => 'Internal Server Error'
            ], 500);
        }
    }

    /**
     * =========================================================
     * UNIVERSAL PAYMENT RESULT PAGE
     * =========================================================
     */
    public function returnPage(Request $request)
    {
        Log::info('[DANA RETURN PAGE]', [
            'query' => $request->all(),
            'full_url' => $request->fullUrl(),
            'user_agent' => $request->userAgent(),
        ]);

        try {

            // =====================================================
            // AMBIL REF
            // =====================================================
            $refNo =
                $request->partnerReferenceNo
                ?? $request->originalPartnerReferenceNo
                ?? $request->bizNo
                ?? $request->id
                ?? '';

            if (!$refNo) {

                Log::warning('[DANA RETURN] REF EMPTY');

                return redirect('/')
                    ->with('error', 'Transaksi tidak ditemukan.');
            }

            // =====================================================
            // NORMALISASI
            // =====================================================
            $refNo = $this->normalizeReference($refNo);

            Log::info('[DANA RETURN] REF NORMALIZED', [
                'ref' => $refNo
            ]);

            // =====================================================
            // DETECT MOBILE / EXPO
            // =====================================================
            $isMobile = $request->header('X-Platform') === 'mobile';

            // fallback user-agent
            if (!$isMobile) {

                $isMobile = preg_match(
                    '/Android|iPhone|iPad|Mobile/i',
                    $request->userAgent()
                );
            }

            Log::info('[DANA RETURN] PLATFORM', [
                'is_mobile' => $isMobile
            ]);

            // =====================================================
            // TOPUP
            // =====================================================
            $topup = TopUp::where('reference_id', $refNo)->first();

            if ($topup) {

                Log::info('[DANA RETURN] TOPUP FOUND');

                // MOBILE
                if (
                    $isMobile ||
                    $topup->platform === 'mobile'
                ) {

                    return redirect()->away(
                        'sancakaexpress://topup-success/' . $refNo
                    );
                }

                // WEB
                return redirect()->route(
                    'customer.topup.show',
                    ['topup' => $topup->id]
                );
            }

            // =====================================================
            // MARKETPLACE ORDER
            // =====================================================
            $order = Order::where('invoice_number', $refNo)
                ->first();

            if ($order) {

                Log::info('[DANA RETURN] ORDER FOUND');

                // MOBILE
                if (
                    $isMobile ||
                    $order->platform === 'mobile'
                ) {

                    return redirect()->away(
                        'sancakaexpress://order-success/' . $refNo
                    );
                }

                // WEB
                return redirect()->to(
                    'https://tokosancaka.com/customer/pesanan/riwayat-belanja'
                )->with(
                    'success',
                    'Pembayaran berhasil diproses.'
                );
            }

            // =====================================================
            // PPOB
            // =====================================================
            $trx = Transaction::where('ref_id', $refNo)
                ->orWhere('tr_id', str_replace('PASCA', '', $refNo))
                ->first();

            if ($trx) {

                Log::info('[DANA RETURN] PPOB FOUND');

                // MOBILE
                if (
                    $isMobile ||
                    $trx->platform === 'mobile'
                ) {

                    return redirect()->away(
                        'sancakaexpress://ppob-success/' . $refNo
                    );
                }

                // WEB
                return redirect()->to(
                    'https://tokosancaka.com/riwayatppob'
                )->with(
                    'success',
                    'Pembayaran PPOB berhasil.'
                );
            }

            // =====================================================
            // FALLBACK
            // =====================================================
            Log::warning('[DANA RETURN] DATA NOT FOUND', [
                'ref' => $refNo
            ]);

            return redirect('/')
                ->with(
                    'success',
                    'Transaksi berhasil diproses.'
                );

        } catch (Exception $e) {

            Log::error('[DANA RETURN PAGE ERROR]', [
                'message' => $e->getMessage(),
                'line' => $e->getLine()
            ]);

            return redirect('/')
                ->with(
                    'error',
                    'Terjadi kesalahan saat redirect pembayaran.'
                );
        }
    }

    /**
     * =========================================================
     * NORMALIZE REFERENCE
     * =========================================================
     */
    private function normalizeReference($refNo)
    {
        if (
            Str::startsWith($refNo, 'SCKORD') &&
            !str_contains($refNo, '-')
        ) {

            return 'SCK-ORD-' . substr($refNo, 6);
        }

        if (
            Str::startsWith($refNo, 'TOPUP') &&
            !str_contains($refNo, '-')
        ) {

            return 'TOPUP-' . substr($refNo, 5);
        }

        return $refNo;
    }

    /**
     * =========================================================
     * STANDARD SUCCESS RESPONSE SNAP DANA
     * =========================================================
     */
    private function respondSuccessDANA()
    {
        return response()->json([
            'responseCode' => '2005600',
            'responseMessage' => 'Successful'
        ])->withHeaders([
            'Content-Type' => 'application/json',
            'X-TIMESTAMP' => Carbon::now('Asia/Jakarta')
                ->format('Y-m-d\TH:i:sP')
        ]);
    }
}
