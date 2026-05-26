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
            // LEGACY PESANAN
            // =====================================================
            if (Str::startsWith($refNo, 'SCK-')) {

                Log::info('[DANA WEBHOOK] LEGACY PESANAN', [
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
     * UNIVERSAL RETURN PAGE
     * =========================================================
     */
    public function returnPage(Request $request)
    {
        Log::info('[DANA RETURN PAGE]', [
            'query'      => $request->all(),
            'full_url'   => $request->fullUrl(),
            'user_agent' => $request->userAgent(),
        ]);

        try {

            // =====================================================
            // GET REF
            // =====================================================
            $refNo = $request->partnerReferenceNo
                ?? $request->originalPartnerReferenceNo
                ?? $request->bizNo
                ?? $request->id
                ?? '';

            if (!$refNo) {

                Log::warning('[DANA RETURN] REF EMPTY');

                return redirect('/')
                    ->with(
                        'error',
                        'Transaksi tidak ditemukan.'
                    );
            }

            // =====================================================
            // NORMALIZE REF
            // =====================================================
            $refNo = $this->normalizeReference($refNo);

            Log::info('[DANA RETURN] NORMALIZED', [
                'ref' => $refNo
            ]);

            // =====================================================
            // DETECT MOBILE
            // =====================================================
            $isMobile =
                $request->header('X-Platform') === 'mobile';

            if (!$isMobile) {

                $isMobile = preg_match(
                    '/Android|iPhone|iPad|Mobile/i',
                    $request->userAgent()
                );
            }

            Log::info('[DANA RETURN] PLATFORM', [
                'is_mobile' => (bool) $isMobile
            ]);

            // =====================================================
            // PPOB
            // =====================================================
            $trx = Transaction::where('ref_id', $refNo)
            ->orWhere(
                'reference_id', // Changed from 'tr_id'
                str_replace('PASCA', '', $refNo)
            )
            ->first();

            if ($trx) {

                Log::info('[DANA RETURN] PPOB FOUND', [
                    'id' => $trx->id ?? null
                ]);

                if (
                    $isMobile ||
                    ($trx->platform ?? null) === 'mobile'
                ) {

                    return redirect()->away(
                        'sancakaexpress://ppob-success/' . $refNo
                    );
                }

                return redirect()->to(
                    'https://tokosancaka.com/riwayatppob'
                )->with(
                    'success',
                    'Pembayaran PPOB berhasil.'
                );
            }

            // =====================================================
            // TOPUP
            // =====================================================
            $topup = TopUp::where('transaction_id', $refNo)
            ->first();

            if ($topup) {

                Log::info('[DANA RETURN] TOPUP FOUND', [
                    'id' => $topup->id ?? null
                ]);

                if (
                    $isMobile ||
                    ($topup->platform ?? null) === 'mobile'
                ) {

                    return redirect()->away(
                        'sancakaexpress://topup-success/' . $refNo
                    );
                }

                if (
                    \Route::has('customer.topup.show')
                ) {

                    return redirect()->route(
                        'customer.topup.show',
                        ['topup' => $topup->id]
                    );
                }

                return redirect('/')->with(
                    'success',
                    'Topup berhasil diproses.'
                );
            }

            // =====================================================
            // MARKETPLACE ORDER
            // =====================================================
            $order = Order::where(
                'invoice_number',
                $refNo
            )->first();

            if ($order) {

                Log::info('[DANA RETURN] ORDER FOUND', [
                    'id' => $order->id ?? null
                ]);

                if (
                    $isMobile ||
                    ($order->platform ?? null) === 'mobile'
                ) {

                    return redirect()->away(
                        'sancakaexpress://order-success/' . $refNo
                    );
                }

                return redirect()->to(
                    'https://tokosancaka.com/customer/pesanan/riwayat-belanja'
                )->with(
                    'success',
                    'Pembayaran marketplace berhasil.'
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
                'line'    => $e->getLine(),
            ]);

            return redirect('/')
                ->with(
                    'error',
                    'Terjadi kesalahan redirect pembayaran.'
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
