<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\PpobTransaction;
use Illuminate\Support\Facades\DB;

class DigiflazzWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // 1. Definisikan Secret Key (SAMA PERSIS dengan di Dashboard Digiflazz)
        // Sebaiknya taruh di .env: DIGIFLAZZ_WEBHOOK_SECRET=SancakaSecure2025
        $secret = 'SancakaSecretKey2025'; // Ganti dengan secret buatan Anda

        // 2. Ambil Signature dari Header
        $incomingSignature = $request->header('X-Hub-Signature');
        
        // 3. Ambil Raw Content (Payload)
        $postData = $request->getContent();

        // 4. Hitung Signature Lokal
        // Rumus dari dokumentasi: HMAC-SHA1
        $localSignature = 'sha1=' . hash_hmac('sha1', $postData, $secret);

        // 5. Verifikasi: Apakah Signature Cocok?
        // Gunakan hash_equals untuk keamanan (mencegah timing attack)
        if (!hash_equals($localSignature, $incomingSignature)) {
            Log::warning("Webhook Digiflazz Ditolak: Signature Salah! Masuk: $incomingSignature | Lokal: $localSignature");
            return response()->json(['status' => 'failed', 'message' => 'Invalid Signature'], 401);
        }

        // --- JIKA LOLOS VERIFIKASI, PROSES DATA ---

        $data = json_decode($postData, true); // Decode manual karena kita pakai getContent()
        
        // Log data masuk (Opsional, matikan jika sudah production)
        // Log::info('Digiflazz Webhook Data:', $data);

        if (!isset($data['data'])) {
            return response()->json(['status' => 'failed', 'message' => 'No data'], 400);
        }

        $trxData = $data['data'];
        $refId   = $trxData['ref_id'];
        $status  = $trxData['status']; // Sukses, Gagal, Pending
        $sn      = $trxData['sn'] ?? '';
        $message = $trxData['message'];
        $price   = $trxData['price'] ?? 0; 

        // 6. Cari Transaksi di Database
        $transaction = PpobTransaction::where('order_id', $refId)->first();

        if (!$transaction) {
            Log::error("Webhook: Transaksi ID $refId tidak ditemukan.");
            return response()->json(['status' => 'not found'], 404);
        }

        // 7. Cek Jika Status Sudah Final (Agar tidak diproses ganda)
        if (in_array($transaction->status, ['Sukses', 'Gagal'])) {
            return response()->json(['status' => 'already processed']);
        }

        DB::beginTransaction();
        try {
            // Update Data Transaksi
            $transaction->sn = $sn;
            $transaction->message = $message;
            
            // Update harga beli real jika ada perubahan harga dari Digiflazz
            if($price > 0) {
                $transaction->price = $price;
                // Hitung ulang profit: Harga Jual - Harga Beli Baru
                $transaction->profit = $transaction->selling_price - $price;
            }

            if ($status === 'Sukses') {
                $transaction->status = 'Sukses';
                // Opsional: Kirim notifikasi WA ke user "Pulsa sukses masuk"
            } 
            elseif ($status === 'Gagal') {
                $transaction->status = 'Gagal';
                
                // === AUTO REFUND LOGIC ===
                $user = $transaction->user;
                if ($user) {
                    // Kembalikan Saldo ke User
                    $user->increment('saldo', $transaction->selling_price);
                    
                    Log::info("REFUND BERHASIL: User {$user->id} senilai {$transaction->selling_price} (Order: $refId)");
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