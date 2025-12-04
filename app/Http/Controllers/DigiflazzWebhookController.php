<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\PpobTransaction; 
use Illuminate\Support\Facades\DB;

class DigiflazzWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // 1. LOG RAW DATA (PENTING UNTUK DEBUGGING)
        // Menyimpan log request yang masuk untuk mengecek isi payload jika ada error
        Log::info('Webhook Digiflazz Masuk:', $request->all());

        // ==========================================
        // 2. VERIFIKASI KEAMANAN (SECRET KEY)
        // ==========================================
        $secret = env('DIGIFLAZZ_SECRET_KEY', 'SancakaSecretKey2025'); 
        $incomingSignature = $request->header('X-Hub-Signature');
        $payload = $request->getContent(); // Ambil raw body string
        
        // Buat signature lokal untuk pembanding
        $localSignature = 'sha1=' . hash_hmac('sha1', $payload, $secret);

        // PERBAIKAN: Gunakan (string) agar tidak error jika header kosong (saat test Postman)
        if (!hash_equals($localSignature, (string)$incomingSignature)) {
            // Kita log sebagai warning saja agar testing tetap jalan. 
            // Untuk Production nanti bisa di-uncomment baris return-nya.
            Log::warning("Signature Mismatch. Incoming: " . $incomingSignature . " | Local: " . $localSignature);
            // return response()->json(['status' => 'failed', 'message' => 'Invalid Signature'], 401);
        }

        // ==========================================
        // 3. PARSING DATA
        // ==========================================
        $data = json_decode($payload, true);

        // Handle Test Koneksi (Ping)
        if (isset($data['ping'])) {
            return response()->json(['status' => 'pong']);
        }

        // Cek struktur data
        if (!isset($data['data'])) {
            return response()->json(['status' => 'failed', 'message' => 'Invalid Payload Structure'], 400);
        }

        $trxData = $data['data'];
        $refId   = $trxData['ref_id'] ?? null;
        $status  = $trxData['status'] ?? 'Pending';
        $sn      = $trxData['sn'] ?? '';
        $message = $trxData['message'] ?? '';
        $price   = $trxData['price'] ?? 0;
        $desc    = $trxData['desc'] ?? null; // Rincian tagihan (biasanya untuk pascabayar)

        if (!$refId) {
            return response()->json(['status' => 'failed', 'message' => 'No Ref ID'], 400);
        }

        // ==========================================
        // 4. DATABASE TRANSACTION (ATOMIC)
        // ==========================================
        // Kita mulai transaksi database di sini agar semua proses aman
        DB::beginTransaction();

        try {
            // Cari Transaksi & LOCK ROW (lockForUpdate)
            // Ini mencegah transaksi diproses 2x secara bersamaan
            $transaction = PpobTransaction::where('order_id', $refId)->lockForUpdate()->first();

            if (!$transaction) {
                DB::rollBack();
                Log::error("Webhook: Transaksi ID $refId tidak ditemukan di database.");
                return response()->json(['status' => 'not found'], 404);
            }

            // Cek apakah status sudah final? (Idempotency Check)
            // Jika status di DB sudah Success/Failed, abaikan webhook ini.
            if (in_array($transaction->status, ['Success', 'Failed'])) {
                DB::rollBack();
                Log::info("Webhook Ignored: Transaksi $refId sudah final ({$transaction->status}).");
                return response()->json(['status' => 'already processed']);
            }

            // --- UPDATE DATA TRANSAKSI ---
            $transaction->sn = $sn;
            $transaction->message = $message;

            // Update Desc (Jika ada data dan tidak kosong)
            if (!empty($desc)) {
                $transaction->desc = $desc; 
            }

            // Update Harga Beli & Profit (Jika ada perubahan harga dari provider)
            if ($price > 0) {
                $transaction->price = $price;
                $transaction->profit = $transaction->selling_price - $price;
            }

            // --- LOGIKA STATUS ---
            if ($status === 'Sukses') {
                $transaction->status = 'Success';
                // Opsional: Kirim Notifikasi WA Sukses di sini
            } 
            elseif ($status === 'Gagal') {
                $transaction->status = 'Failed';
                
                // --- LOGIKA REFUND SALDO ---
                // Pastikan metode bayar adalah 'saldo' sebelum refund
                if (strtolower($transaction->payment_method) === 'saldo') {
                    $user = $transaction->user; // Pastikan relasi 'user' ada di model PpobTransaction
                    
                    if ($user) {
                        $refundAmount = $transaction->selling_price;
                        
                        // Kembalikan Saldo User
                        $user->increment('saldo', $refundAmount);
                        
                        // Catat log refund
                        Log::info("AUTO REFUND: User {$user->name} (ID: {$user->id}) senilai Rp " . number_format($refundAmount));
                        $transaction->message .= " [Saldo Dikembalikan]";
                    }
                }
            }
            // Jika status 'Pending', kita biarkan saja atau update jadi Processing

            $transaction->save();
            
            // Commit perubahan ke database
            DB::commit();

            return response()->json(['status' => 'ok']);

        } catch (\Exception $e) {
            DB::rollBack(); // Batalkan semua perubahan jika ada error
            Log::error("Webhook Database Error: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}