<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\PpobTransaction; 
use Illuminate\Support\Facades\DB;
use App\Models\User;

class DigiflazzWebhookController extends Controller
{
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

            // 1. SUKSES (RC 00)
            if ($rcStr === '00' || $status === 'Sukses' || $status === 'Success') {
                $transaction->status = 'Success';
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
            DB::commit();

            return response()->json(['status' => 'ok']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Webhook Error: " . $e->getMessage());
            return response()->json(['status' => 'error'], 500);
        }
    }
}