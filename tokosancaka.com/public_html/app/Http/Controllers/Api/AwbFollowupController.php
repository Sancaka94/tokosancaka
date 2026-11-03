<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Pesanan;

class AwbFollowupController extends Controller
{
    /** Gatekeeper bot (fail-closed, constant-time). */
    private function _botAuthorized(Request $request): bool
    {
        $botSecret = (string) config('services.bot_order.secret');
        $provided = (string) $request->header('X-Bot-Secret', '');

        return $botSecret !== '' && $provided !== '' && hash_equals($botSecret, $provided);
    }

    /**
     * Daftar pesanan yang menunggu AWB (pickup sudah terdaftar, resi belum ada).
     */
    public function pendingAwb(Request $request)
    {
        if (!$this->_botAuthorized($request)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access'], 401);
        }

        $rows = Pesanan::query()
            ->whereNotNull('shipping_ref')
            ->where('shipping_ref', '!=', '')
            ->where(function ($q) {
                $q->whereNull('resi_aktual')->orWhere('resi_aktual', '');
            })
            ->whereIn('status_pesanan', ['Menunggu Pickup', 'Pembayaran Lunas'])
            ->orderBy('updated_at')
            ->limit(40)
            ->get(['nomor_invoice', 'shipping_ref', 'sender_phone', 'receiver_phone', 'created_at']);

        $orders = [];
        foreach ($rows as $row) {
            // Nomor tujuan notifikasi: pengirim (yang memesan via bot).
            $phone = $this->_normalizeWaPhone((string) ($row->sender_phone ?: $row->receiver_phone));

            $orders[] = [
                'invoice'      => $row->nomor_invoice,
                'shipping_ref' => $row->shipping_ref,
                'phone'        => $phone,
                'created_at'   => optional($row->created_at)->toIso8601String(),
            ];
        }

        return response()->json([
            'status' => 'success',
            'total'  => count($orders),
            'orders' => $orders,
        ]);
    }

    /**
     * Simpan AWB (resi aktual) ke pesanan.
     */
    public function simpanAwb(Request $request)
    {
        if (!$this->_botAuthorized($request)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access'], 401);
        }

        $validated = $request->validate([
            'invoice' => 'required|string|max:64',
            'awb'     => 'required|string|max:64',
        ]);

        $awb = preg_replace('/[^A-Za-z0-9\-._]/', '', $validated['awb']);

        $pesanan = Pesanan::where('nomor_invoice', $validated['invoice'])->first();
        if (!$pesanan) {
            return response()->json(['status' => 'error', 'message' => 'Pesanan tidak ditemukan'], 404);
        }

        // Idempoten: jangan timpa AWB yang sudah ada.
        if (!empty($pesanan->resi_aktual)) {
            return response()->json([
                'status' => 'already_set',
                'awb'    => $pesanan->resi_aktual,
            ]);
        }

        $pesanan->resi = $awb;
        $pesanan->resi_aktual = $awb;
        $pesanan->save();

        Log::info('[AWB-FOLLOWUP] AWB tersimpan', [
            'invoice' => $validated['invoice'],
            'awb'     => $awb,
        ]);

        return response()->json(['status' => 'success', 'awb' => $awb]);
    }

    /**
     * Kirim teks WhatsApp ke satu nomor (dipakai cron notifikasi).
     */
    public function kirimTeks(Request $request)
    {
        if (!$this->_botAuthorized($request)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access'], 401);
        }

        $validated = $request->validate([
            'phone_number' => 'required|string|min:8|max:20',
            'message'      => 'required|string|max:2000',
        ]);

        $phone = $this->_normalizeWaPhone($validated['phone_number']);
        if ($phone === '') {
            return response()->json(['status' => 'error', 'message' => 'Nomor tidak valid'], 422);
        }

        try {
            $sent = app(BotOrderController::class)
                ->sendWhatsAppTextPublic($phone, $validated['message']);

            return response()->json([
                'status' => $sent ? 'success' : 'error',
                'sent'   => (bool) $sent,
            ], $sent ? 200 : 502);
        } catch (\Throwable $e) {
            Log::error('[AWB-FOLLOWUP] Gagal kirim teks: ' . $e->getMessage());

            return response()->json(['status' => 'error', 'message' => 'Gagal mengirim'], 502);
        }
    }

    /** Normalisasi nomor ke 62xxxxxxxxxx. */
    private function _normalizeWaPhone(string $value): string
    {
        $digits = preg_replace('/[^0-9]/', '', $value);
        if ($digits === '') {
            return '';
        }
        if (str_starts_with($digits, '0')) {
            return '62' . substr($digits, 1);
        }
        if (str_starts_with($digits, '8')) {
            return '62' . $digits;
        }

        return $digits;
    }
}
