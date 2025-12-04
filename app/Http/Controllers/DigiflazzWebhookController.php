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
        // ==========================================
        // 1. VERIFIKASI KEAMANAN (SECRET KEY)
        // ==========================================
        // Pastikan Secret Key ini SAMA PERSIS dengan di Dashboard Digiflazz
        $secret = config('services.digiflazz.secret_key') ?? 'SancakaSecretKey2025'; 
        
        $incomingSignature = $request->header('X-Hub-Signature');
        $payload = $request->getContent();
        $localSignature = 'sha1=' . hash_hmac('sha1', $payload, $secret);

        if (!hash_equals($localSignature, $incomingSignature)) {
            Log::warning("Webhook Ditolak: Signature Salah! IP: " . $request->ip());
            // return response()->json(['status' => 'failed', 'message' => 'Invalid Signature'], 401);
        }

        // ==========================================
        // 2. PROSES DATA
        // ==========================================
        $data = json_decode($payload, true);
        
        // Cek Ping Event (Tes Koneksi)
        if (isset($data['ping'])) {
            return response()->json(['status' => 'pong']);
        }

        if (!isset($data['data'])) {
            return response()->json(['status' => 'failed', 'message' => 'No data'], 400);
        }

        $trxData = $data['data'];
        $refId   = $trxData['ref_id'];   // ID Order Kita (TRX-...)
        $status  = $trxData['status'];   // Sukses, Gagal, Pending
        $sn      = $trxData['sn'] ?? ''; // Token Listrik / SN Pulsa
        $message = $trxData['message'];  // Pesan status
        $price   = $trxData['price'] ?? 0; // Harga beli real
        
        // [TAMBAHAN] Ambil Data Deskripsi (Rincian Tagihan Pasca)
        $desc    = $trxData['desc'] ?? null; 

        // Cari Transaksi
        $transaction = PpobTransaction::where('order_id', $refId)->first();

        if (!$transaction) {
            Log::error("Webhook: Transaksi ID $refId tidak ditemukan.");
            return response()->json(['status' => 'not found'], 404);
        }

        // Cek apakah status sudah final? (Mencegah double proses)
        if (in_array($transaction->status, ['Success', 'Failed', 'Sukses', 'Gagal'])) {
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
            
            // [TAMBAHAN PENTING] Update kolom 'desc' jika ada data rincian baru dari webhook
            if (!empty($desc)) {
                $transaction->desc = $desc; // Pastikan kolom 'desc' di model dicasting 'array'
            }

            // Update Harga Modal (Jika berubah dari estimasi awal)
            if ($price > 0) {
                $transaction->price = $price;
                $transaction->profit = $transaction->selling_price - $price;
            }

            // STATUS SUKSES
            if ($status === 'Sukses') {
                $transaction->status = 'Success'; // Gunakan standar 'Success' di DB Anda
                
                // Opsional: Kirim Notifikasi WA/Email Sukses ke User
                // NotificationService::sendSuccess($transaction);
            } 
            // STATUS GAGAL (REFUND)
            elseif ($status === 'Gagal') {
                $transaction->status = 'Failed'; // Gunakan standar 'Failed' di DB Anda
                
                // Logika Auto Refund Saldo
                // Cek dulu apakah metode bayarnya 'saldo' agar tidak double refund (jika bayar pakai QRIS tripay biasanya refund manual/beda flow)
                if ($transaction->payment_method === 'saldo') {
                    $user = $transaction->user;
                    if ($user) {
                        $refundAmount = $transaction->selling_price;
                        $user->increment('saldo', $refundAmount);
                        
                        Log::info("REFUND BERHASIL: User {$user->nama_lengkap} (ID: {$user->id_pengguna}) senilai Rp $refundAmount. RefID: $refId");
                        $transaction->message .= " (Saldo Dikembalikan)";
                    }
                }
            }

            $transaction->save();
            DB::commit();
            
            Log::info("Webhook Success: $refId status updated to $status");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Webhook Error Processing: " . $e->getMessage());
            return response()->json(['status' => 'error'], 500);
        }

        return response()->json(['status' => 'ok']);
    }
}