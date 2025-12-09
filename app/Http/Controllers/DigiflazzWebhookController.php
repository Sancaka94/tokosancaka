<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\PpobTransaction; 
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Services\FonnteService; // <<< TAMBAHKAN INI
use Illuminate\Support\Str; // Diperlukan untuk _sanitizePhoneNumber

class DigiflazzWebhookController extends Controller
{
    /**
     * Helper untuk membersihkan dan memformat nomor HP menjadi 62xxxx.
     */
    private function _sanitizePhoneNumber(string $phone): ?string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (empty($phone)) return null;
    
        if (Str::startsWith($phone, '08')) {
            return '62' . substr($phone, 1);
        }
        if (Str::startsWith($phone, '62')) {
            return $phone;
        }
        if (strlen($phone) > 8 && !Str::startsWith($phone, '0')) {
            return '62' . $phone;
        }
        return $phone;
    }


    /**
     * Mengirim notifikasi WA berisi SN transaksi PPOB.
     */
    private function _sendWhatsappNotificationSN(PpobTransaction $trx, string $sn)
    {
        try {
            $agent = User::find($trx->user_id); 
            
            // Nomor WA Agent (yang login saat transaksi)
            $agentWa = $this->_sanitizePhoneNumber($agent->no_wa ?? $agent->phone ?? null);
            // Nomor WA Pembeli (diambil dari input di kasir)
            $customerWa = $this->_sanitizePhoneNumber($trx->customer_wa ?? $trx->customer_no); 
            
            $fmt = function($val) { return number_format($val, 0, ',', '.'); };
            
            if (empty($customerWa)) {
                Log::warning("Notifikasi WA SN PPOB gagal: customer_wa kosong di transaksi: " . $trx->order_id);
                return false;
            }

            // --- 1. SUSUN PESAN ---
            $message = "*✅ Transaksi PPOB Sukses! (Order: {$trx->order_id})*\n";
            $message .= "------------------------------------\n";
            $message .= "Produk: {$trx->buyer_sku_code}\n";
            $message .= "Nomor Tujuan: {$trx->customer_no}\n";
            $message .= "Harga Jual: Rp " . $fmt($trx->selling_price) . "\n";
            $message .= "*Serial Number (SN):*\n*{$sn}*\n";
            $message .= "------------------------------------\n";
            $message .= "Terima kasih telah bertransaksi di Sancaka Express. 🙏";

            // --- 2. KIRIM KE AGENT (AUTH USER) ---
            if ($agentWa) {
                FonnteService::sendMessage($agentWa, "[NOTIF AGENT - SN] Transaksi {$trx->order_id} Sukses. \n\n" . $message);
            }

            // --- 3. KIRIM KE PEMBELI (NOMOR WA YANG DI-INPUT) ---
            FonnteService::sendMessage($customerWa, "[NOTIF PEMBELI - SN] " . $message);
            
            Log::info('PPOB SN sent via WA.', ['ref_id' => $trx->order_id, 'customer_wa' => $customerWa]);
            return true;
            
        } catch (\Exception $e) {
            Log::error('WA Notification SN PPOB Error: ' . $e->getMessage(), ['trx_id' => $trx->id]);
            return false;
        }
    }

    public function handle(Request $request)
    {
        // 1. LOG RAW DATA
        Log::info('Webhook Masuk:', $request->all());

        // ==========================================
        // 2. VERIFIKASI SIGNATURE
        // ==========================================
        $secret = 'SancakaSecretKey2025'; 
        $incomingSignature = $request->header('X-Hub-Signature');
        $payload = $request->getContent();
        $localSignature = 'sha1=' . hash_hmac('sha1', $payload, $secret);

        if ($incomingSignature && !hash_equals($localSignature, (string)$incomingSignature)) {
            Log::warning("Signature Tidak Cocok (Mode Testing). Incoming: $incomingSignature | Local: $localSignature");
        } else {
            Log::info("Signature Check: OK (atau Mode Testing)");
        }

        // ==========================================
        // 3. PARSING DATA
        // ==========================================
        $data = json_decode($payload, true);

        // Handle Ping
        if (isset($data['ping'])) return response()->json(['status' => 'pong']);

        $trxData = $data['data'] ?? $data;
        $refId   = $trxData['ref_id'] ?? $trxData['order_id'] ?? null;

        if (!$refId) {
            Log::error("Webhook Gagal: No Ref ID.");
            return response()->json(['status' => 'failed', 'message' => 'No Ref ID found'], 400);
        }

        // Ambil Data
        $status  = $trxData['status'] ?? 'Pending';
        $sn      = $trxData['sn'] ?? '';
        $message = $trxData['message'] ?? '';
        $price   = $trxData['price'] ?? 0;
        $desc    = $trxData['desc'] ?? null;
        $rc      = $trxData['rc'] ?? null; // Response Code (Penting!)

        Log::info("Proses Transaksi ID: $refId | Status: $status | RC: $rc");

        // ==========================================
        // 4. DATABASE TRANSACTION
        // ==========================================
        DB::beginTransaction();
        try {
            $transaction = PpobTransaction::where('order_id', $refId)->lockForUpdate()->first();

            if (!$transaction) {
                DB::rollBack();
                Log::error("Webhook: Transaksi $refId tidak ditemukan.");
                return response()->json(['status' => 'not found'], 404);
            }

            // Cek jika sudah Final (Success/Failed)
            if (in_array($transaction->status, ['Success', 'Failed'])) {
                DB::rollBack();
                return response()->json(['status' => 'already processed']);
            }

            // Update Info Dasar
            $transaction->sn = $sn;
            $transaction->message = $message;
            $transaction->rc = $rc;
            if (!empty($desc)) $transaction->desc = is_array($desc) ? json_encode($desc) : $desc;
            
            // Update Profit (Jika harga berubah)
            if ($price > 0) {
                $transaction->price = $price; 
                $transaction->profit = $transaction->selling_price - $price;
            }

            // ==========================================
            // LOGIKA STATUS BERDASARKAN RC (LEBIH AKURAT)
            // ==========================================
            $rcStr = (string) $rc; // Pastikan string untuk perbandingan
            $isSuccess = false; // Flag untuk WA

            // 1. SUKSES (RC 00)
            if ($rcStr === '00' || $status === 'Sukses' || $status === 'Success') {
                $transaction->status = 'Success';
                $isSuccess = true;
            }
            
            // 2. PENDING (RC 03, 99)
            elseif (in_array($rcStr, ['03', '99']) || $status === 'Pending') {
                $transaction->status = 'Pending';
            }

            // 3. GAGAL (Semua RC selain 00, 03, 99)
            // Ini menangani RC 40-59 (Saldo kurang, nomor salah, gangguan, dll)
            else {
                $transaction->status = 'Failed';
                
                // AUTO REFUND SALDO
                if (in_array(strtoupper($transaction->payment_method), ['SALDO', 'SALDO_AGEN'])) {
                    $user = User::where('id_pengguna', $transaction->user_id)->first();
                    if ($user) {
                        $refundAmount = $transaction->price; 
                        $user->increment('saldo', $refundAmount);
                        
                        Log::info("REFUND: User {$user->id_pengguna} +Rp " . number_format($refundAmount));
                        $transaction->message .= " [Saldo Dikembalikan]";
                    }
                }
            }

           $transaction->save();

            // ==========================================
            // 5. KIRIM NOTIFIKASI WA SETELAH SUKSES
            // ==========================================
            if ($transaction->status === 'Success' && !empty($sn)) {
                $this->_sendWhatsappNotificationSN($transaction, $sn);
            }

            DB::commit();

            return response()->json(['status' => 'ok']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Webhook Error: " . $e->getMessage());
            return response()->json(['status' => 'error'], 500);
        }
    }
}