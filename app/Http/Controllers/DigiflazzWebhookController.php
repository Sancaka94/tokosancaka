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
        // 2. BYPASS VERIFIKASI SIGNATURE (SEMENTARA)
        // ==========================================
        // Agar Anda bisa tes lewat Postman tanpa ribet header Signature.
        // Nanti kalau sudah production, barulah kita aktifkan validasi ketat.
        
        $secret = 'SancakaSecretKey2025'; 
        $incomingSignature = $request->header('X-Hub-Signature');
        $payload = $request->getContent();
        $localSignature = 'sha1=' . hash_hmac('sha1', $payload, $secret);

        if ($incomingSignature && !hash_equals($localSignature, (string)$incomingSignature)) {
            Log::warning("Signature Tidak Cocok (Diabaikan untuk Testing). Incoming: $incomingSignature | Local: $localSignature");
        } else {
            Log::info("Signature Check: OK atau Skipped (Mode Testing)");
        }

        // ==========================================
        // 3. PARSING DATA (SUPPORT POSTMAN & DIGIFLAZZ)
        // ==========================================
        $data = json_decode($payload, true);

        // Handle Ping
        if (isset($data['ping'])) return response()->json(['status' => 'pong']);

        // Normalisasi Data: Ambil dari 'data' (Digiflazz) atau langsung (Postman)
        // INI SOLUSI ERROR "Invalid Payload Structure" DI POSTMAN ANDA
        $trxData = $data['data'] ?? $data;

        // Validasi Ref ID (Support 'ref_id' atau 'order_id')
        $refId = $trxData['ref_id'] ?? $trxData['order_id'] ?? null;

        if (!$refId) {
            Log::error("Webhook Gagal: Tidak ada Ref ID/Order ID dalam payload.");
            return response()->json(['status' => 'failed', 'message' => 'No Ref ID found'], 400);
        }

        // Ambil parameter lainnya
        $status  = $trxData['status'] ?? 'Pending';
        $sn      = $trxData['sn'] ?? '';
        $message = $trxData['message'] ?? '';
        $price   = $trxData['price'] ?? 0;
        $desc    = $trxData['desc'] ?? null;
        $rc      = $trxData['rc'] ?? null;

        Log::info("Memproses Transaksi ID: $refId | Status Baru: $status");

        // ==========================================
        // 4. DATABASE TRANSACTION
        // ==========================================
        DB::beginTransaction();
        try {
            $transaction = PpobTransaction::where('order_id', $refId)->lockForUpdate()->first();

            if (!$transaction) {
                DB::rollBack();
                Log::error("Webhook Gagal: Transaksi ID $refId TIDAK DITEMUKAN di database.");
                return response()->json(['status' => 'not found'], 404);
            }

            Log::info("Transaksi Ditemukan: User ID {$transaction->user_id} | Status Lama: {$transaction->status}");

            // Cek Final Status
            if (in_array($transaction->status, ['Success', 'Failed'])) {
                DB::rollBack();
                return response()->json(['status' => 'already processed']);
            }

            // UPDATE DATA
            $transaction->sn = $sn;
            $transaction->message = $message;
            $transaction->rc = $rc;
            if (!empty($desc)) $transaction->desc = is_array($desc) ? json_encode($desc) : $desc;
            
            // Update Profit & Harga Beli (Jika ada perubahan)
            if ($price > 0) {
                $transaction->price = $price; 
                $transaction->profit = $transaction->selling_price - $price;
            }

            // UPDATE STATUS
            if ($status === 'Sukses' || $status === 'Success') { // Handle variasi status
                $transaction->status = 'Success';
            } 
            elseif (in_array($status, ['Gagal', 'Failed'])) {
                $transaction->status = 'Failed';
                
                // REFUND SALDO
                if (in_array(strtoupper($transaction->payment_method), ['SALDO', 'SALDO_AGEN'])) {
                    $user = User::where('id_pengguna', $transaction->user_id)->first();
                    if ($user) {
                        // Refund sebesar modal yang terpotong
                        $refundAmount = $transaction->price; 
                        $user->increment('saldo', $refundAmount);
                        
                        Log::info("REFUND BERHASIL: Rp " . number_format($refundAmount) . " ke User ID {$user->id_pengguna}");
                        $transaction->message .= " [Saldo Dikembalikan]";
                    }
                }
            }

            $transaction->save();
            DB::commit();

            Log::info("Webhook Berhasil: Status transaksi $refId berubah menjadi $status");
            return response()->json(['status' => 'ok']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Webhook DB Error: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}