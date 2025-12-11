<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\PpobTransaction; 
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Services\FonnteService; 
use Illuminate\Support\Str; 

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
     * Helper: Kirim Notifikasi WA via Fonnte (Private)
     */
    private function _sendWhatsappNotificationSN(PpobTransaction $trx, string $sn)
    {
        try {
            // 1. Ambil Data Agent (Penjual) 
            // Asumsi: $trx->user_id adalah PK di tabel Pengguna (id_pengguna/id)
            $user = User::find($trx->user_id); 
            
            // Cek apakah data user/agent ditemukan
            if (!$user) {
                 Log::error("Data Agent (Users) tidak ditemukan untuk user_id: " . $trx->user_id);
                 return false;
            }

            // Ambil WA Agent & Customer
            $agentWa = $this->_sanitizePhoneNumber($user->no_wa ?? $user->no_hp ?? null);
            $customerWa = $this->_sanitizePhoneNumber($trx->customer_wa ?? null); 
            
            $fmt = function($val) { return number_format($val, 0, ',', '.'); };
            
            if (empty($customerWa)) {
                Log::warning("Notifikasi WA Pembeli GAGAL dikirim: customer_wa TIDAK TERSIMPAN di transaksi: " . $trx->order_id);
            }
            
            // --- DATA TOKO AGENT DARI DATABASE PENGGUNA (USERS) ---
            $storeName = $user->store_name ?? 'Sancaka Express';
            $storeAddress = $user->address_detail ?? 'Kantor Pusat Sancaka Express';
            $storePhone = $this->_sanitizePhoneNumber($user->no_wa ?? null) ?? '628819435180'; 

            // ===============================================
            // 1. SUSUN PESAN UNTUK AGENT
            // ===============================================
            $messageAgent = "[NOTIF AGENT - SN] Transaksi {$trx->order_id} Sukses.\n\n" .
            "*✅ Transaksi PPOB Sukses!*\n" .
            "------------------------------------\n" .
            "Produk: {$trx->buyer_sku_code}\n" .
            "Tujuan: {$trx->customer_no}\n" .
            "Harga Jual: Rp {$fmt($trx->selling_price)}\n" .
            "*Serial Number (SN):*\n" .
            "*{$sn}*\n" .
            "------------------------------------\n" .
            "Saldo Baru: Rp " . $fmt($user->saldo ?? 0); 

            // ===============================================
            // 2. SUSUN PESAN UNTUK CUSTOMER
            // ===============================================
            $messageCustomer = "*Halo Pelanggan {$storeName} 👋*\n\n" .
            "Transaksi PPOB Anda telah Berhasil diproses!\n\n" .
            "*✅ DETAIL TRANSAKSI*\n" .
            "------------------------------------\n" .
            "Produk: {$trx->buyer_sku_code}\n" .
            "Nomor Tujuan: {$trx->customer_no}\n" .
            "Harga Jual: Rp {$fmt($trx->selling_price)}\n" .
            "*Serial Number (SN):*\n" .
            "*{$sn}*\n" .
            "------------------------------------\n\n" .
            "Terima kasih telah bertransaksi.\n" .
            "Jika ada kendala, hubungi:\n\n" .
            "*Toko: {$storeName}*\n" .
            "*WA/Telp: {$storePhone}*\n" .
            "*Alamat: {$storeAddress}*\n\n" .
            "Manajemen {$storeName}. 🙏";

            // --- 3. KIRIM KE AGENT ---
            if ($agentWa) {
                FonnteService::sendMessage($agentWa, $messageAgent);
                Log::info('PPOB SN sent via WA to Agent.', ['ref_id' => $trx->order_id, 'agent_wa' => $agentWa]);
            }

            // --- 4. KIRIM KE PEMBELI ---
            if ($customerWa) {
                FonnteService::sendMessage($customerWa, $messageCustomer);
                Log::info('PPOB SN sent via WA to Customer.', ['ref_id' => $trx->order_id, 'customer_wa' => $customerWa]);
            } 
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('WA Notification SN PPOB Error: ' . $e->getMessage(), ['trx_id' => $trx->id]);
            return false;
        }
    }

    public function handle(Request $request)
    {
        // 1. LOG RAW DATA (Untuk Debugging)
        Log::info('Webhook Masuk:', $request->all());

        // ==========================================
        // 2. VERIFIKASI SIGNATURE
        // ==========================================
        $secret = 'SancakaSecretKey2025'; // Ganti dengan Secret Key Webhook Anda
        $incomingSignature = $request->header('X-Hub-Signature');
        $payload = $request->getContent();
        $localSignature = 'sha1=' . hash_hmac('sha1', $payload, $secret);

        // Jika signature ada dan tidak cocok, log warning (namun tetap proses jika testing)
        if ($incomingSignature && !hash_equals($localSignature, (string)$incomingSignature)) {
            Log::warning("Signature Tidak Cocok (Mode Testing). Incoming: $incomingSignature | Local: $localSignature");
        }

        // ==========================================
        // 3. PARSING DATA
        // ==========================================
        $data = json_decode($payload, true);

        // Handle Ping Test dari Digiflazz
        if (isset($data['ping'])) return response()->json(['status' => 'pong']);

        $trxData = $data['data'] ?? $data;
        $refId   = $trxData['ref_id'] ?? $trxData['order_id'] ?? null;

        if (!$refId) {
            Log::error("Webhook Gagal: No Ref ID.");
            return response()->json(['status' => 'failed', 'message' => 'No Ref ID found'], 400);
        }

        // Ambil Data Penting
        $status  = $trxData['status'] ?? 'Pending';
        $sn      = $trxData['sn'] ?? '';
        $message = $trxData['message'] ?? '';
        $price   = $trxData['price'] ?? 0;
        $desc    = $trxData['desc'] ?? null;
        $rc      = $trxData['rc'] ?? null; // Response Code

        Log::info("Proses Transaksi ID: $refId | Status: $status | RC: $rc");

        // ==========================================
        // 4. DATABASE TRANSACTION
        // ==========================================
        DB::beginTransaction();
        try {
            // Cari Transaksi & Lock Row (Mencegah Race Condition)
            $transaction = PpobTransaction::where('order_id', $refId)->lockForUpdate()->first();

            if (!$transaction) {
                DB::rollBack();
                Log::error("Webhook: Transaksi $refId tidak ditemukan.");
                return response()->json(['status' => 'not found'], 404);
            }

            // Cek jika sudah Final (Success/Failed), abaikan webhook jika status sudah final
            if (in_array($transaction->status, ['Success', 'Failed'])) {
                DB::rollBack();
                Log::info("Webhook Ignored: Transaction $refId already finalized as {$transaction->status}");
                return response()->json(['status' => 'already processed']);
            }

            // Update Info Dasar
            $transaction->sn = $sn;
            $transaction->message = $message;
            $transaction->rc = $rc;
            if (!empty($desc)) {
                $transaction->desc = is_array($desc) ? json_encode($desc) : $desc;
            }
            
            // Update Profit (Jika harga modal berubah dari estimasi awal)
            if ($price > 0) {
                $transaction->price = $price; 
                $transaction->profit = $transaction->selling_price - $price;
            }

            // ==========================================
            // LOGIKA STATUS BERDASARKAN RC
            // ==========================================
            $rcStr = (string) $rc; 
            
            // 1. SUKSES (RC 00)
            if ($rcStr === '00' || $status === 'Sukses' || $status === 'Success') {
                $transaction->status = 'Success';
            }
            
            // 2. PENDING (RC 03, 99)
            elseif (in_array($rcStr, ['03', '99']) || $status === 'Pending') {
                $transaction->status = 'Pending';
            }

            // 3. GAGAL (RC Lainnya: 40-59, dll)
            else {
                $transaction->status = 'Failed';
                
                // --- AUTO REFUND SALDO ---
                // Hanya jika metode pembayaran menggunakan SALDO
                if (in_array(strtoupper($transaction->payment_method), ['SALDO', 'SALDO_AGEN'])) {
                    $user = User::find($transaction->user_id);
                    
                    if ($user) {
                        // Refund sejumlah yang dipotong (Selling Price)
                        $refundAmount = $transaction->selling_price;
                        $user->increment('saldo', $refundAmount);
                        
                        Log::info("REFUND SUCCESS: User {$user->id} +Rp " . number_format($refundAmount));
                        $transaction->message .= " [Saldo Dikembalikan]";
                    } else {
                        Log::error("REFUND GAGAL: User ID {$transaction->user_id} tidak ditemukan.");
                    }
                }
            }

            $transaction->save();

            // ==========================================
            // 5. KIRIM NOTIFIKASI WA SETELAH SUKSES
            // ==========================================
            // Hanya kirim jika status sukses & SN ada
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