<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\User; 
use App\Models\PpobTransaction; // <--- Pastikan Model ini sudah ada (Langkah 1)
use Illuminate\Support\Facades\DB;

class DigiflazzWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // ==========================================
        // 1. VERIFIKASI KEAMANAN (SECRET KEY)
        // ==========================================
        $secret = 'SancakaSecretKey2025'; // Ganti sesuai Secret di Dashboard Digiflazz
        $incomingSignature = $request->header('X-Hub-Signature');
        $payload = $request->getContent();
        
        // Rumus: HMAC-SHA1 dari body request
        $localSignature = 'sha1=' . hash_hmac('sha1', $payload, $secret);

        if (!hash_equals($localSignature, $incomingSignature)) {
            Log::warning("Webhook Ditolak: Signature Salah! IP: " . $request->ip());
            // return response()->json(['status' => 'failed', 'message' => 'Invalid Signature'], 401);
            // Note: Uncomment baris return di atas jika sudah Live Production
        }

        // ==========================================
        // 2. PROSES DATA
        // ==========================================
        $data = json_decode($payload, true);
        
        // Log data masuk untuk debugging
        Log::info('Digiflazz Webhook:', $data);

        if (!isset($data['data'])) {
            return response()->json(['status' => 'failed', 'message' => 'No data'], 400);
        }

        $trxData = $data['data'];
        $refId   = $trxData['ref_id'];   // ID Order Kita (TRX-...)
        $status  = $trxData['status'];   // Sukses, Gagal, Pending
        $sn      = $trxData['sn'] ?? ''; // Token Listrik / SN Pulsa
        $message = $trxData['message'];  // Pesan status
        $price   = $trxData['price'] ?? 0; // Harga beli real (update jika berubah)

        // Cari Transaksi di Database
        $transaction = PpobTransaction::where('order_id', $refId)->first();

        if (!$transaction) {
            Log::error("Webhook: Transaksi ID $refId tidak ditemukan di database lokal.");
            return response()->json(['status' => 'not found'], 404);
        }

        // Cek apakah status sudah final? Jika sudah Sukses/Gagal, jangan diproses lagi
        if (in_array($transaction->status, ['Sukses', 'Gagal'])) {
            return response()->json(['status' => 'already processed']);
        }

        // ==========================================
        // 3. UPDATE DATABASE & REFUND
        // ==========================================
        DB::beginTransaction();
        try {
            // Update Data Dasar
            $transaction->sn = $sn;
            $transaction->message = $message;
            
            // Update harga modal jika ada perubahan dari Digiflazz
            if ($price > 0) {
                $transaction->price = $price;
                // Hitung ulang profit
                $transaction->profit = $transaction->selling_price - $price;
            }

            if ($status === 'Sukses') {
                $transaction->status = 'Sukses';
                // Opsional: Kirim WA ke user "Pulsa Masuk SN: ..."
            } 
            elseif ($status === 'Gagal') {
                $transaction->status = 'Gagal';
                
                // --- LOGIKA AUTO REFUND ---
                // Kembalikan saldo user karena transaksi gagal
                $user = $transaction->user; // Mengambil relasi User
                if ($user) {
                    $refundAmount = $transaction->selling_price;
                    $user->increment('saldo', $refundAmount);
                    
                    Log::info("REFUND BERHASIL: User {$user->nama_lengkap} (ID: {$user->id_pengguna}) senilai Rp $refundAmount. RefID: $refId");
                }
            }

            $transaction->save();
            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Webhook Error Processing: " . $e->getMessage());
            return response()->json(['status' => 'error'], 500);
        }

        return response()->json(['status' => 'ok']);
    }
}