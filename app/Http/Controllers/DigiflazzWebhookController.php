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
     * Helper: Cek apakah produk PASCABAYAR (PLN Bulanan, PDAM, BPJS, dll)
     * Berdasarkan SKU Code umum. Sesuaikan dengan prefix SKU Digiflazz Anda.
     */
    private function _isPostpaid($sku) {
        $sku = strtoupper($sku);
        $postpaidPrefixes = ['PLNPOST', 'PDAM', 'BPJS', 'GAS', 'SPEEDY', 'TELKOM', 'HALO'];
        
        foreach ($postpaidPrefixes as $prefix) {
            if (Str::startsWith($sku, $prefix)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Helper: Kirim Notifikasi WA via Fonnte (Private)
     */
    private function _sendWhatsappNotificationSN(PpobTransaction $trx, string $sn)
    {
        try {
            $user = User::find($trx->user_id); 
            
            if (!$user) {
                 Log::error("Data Agent (Users) tidak ditemukan untuk user_id: " . $trx->user_id);
                 return false;
            }

            $agentWa = $this->_sanitizePhoneNumber($user->no_wa ?? $user->no_hp ?? null);
            $customerWa = $this->_sanitizePhoneNumber($trx->customer_wa ?? null); 
            
            $fmt = function($val) { return number_format($val, 0, ',', '.'); };
            
            // --- DATA TOKO AGENT ---
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

            // Kirim WA
            if ($agentWa) FonnteService::sendMessage($agentWa, $messageAgent);
            if ($customerWa) FonnteService::sendMessage($customerWa, $messageCustomer);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('WA Notification SN PPOB Error: ' . $e->getMessage(), ['trx_id' => $trx->id]);
            return false;
        }
    }

    public function handle(Request $request)
    {
        Log::info('Webhook Masuk:', $request->all());

        // 2. VERIFIKASI SIGNATURE
        $secret = 'SancakaSecretKey2025'; 
        $incomingSignature = $request->header('X-Hub-Signature');
        $payload = $request->getContent();
        $localSignature = 'sha1=' . hash_hmac('sha1', $payload, $secret);

        if ($incomingSignature && !hash_equals($localSignature, (string)$incomingSignature)) {
            Log::warning("Signature Tidak Cocok (Mode Testing).");
        }

        // 3. PARSING DATA
        $data = json_decode($payload, true);
        if (isset($data['ping'])) return response()->json(['status' => 'pong']);

        $trxData = $data['data'] ?? $data;
        $refId   = $trxData['ref_id'] ?? $trxData['order_id'] ?? null;

        if (!$refId) {
            return response()->json(['status' => 'failed', 'message' => 'No Ref ID found'], 400);
        }

        $status  = $trxData['status'] ?? 'Pending';
        $sn      = $trxData['sn'] ?? '';
        $message = $trxData['message'] ?? '';
        $price   = $trxData['price'] ?? 0; // Harga Modal dari Webhook
        $desc    = $trxData['desc'] ?? null;
        $rc      = $trxData['rc'] ?? null;

        Log::info("Proses Transaksi ID: $refId | Status: $status | RC: $rc | Price Webhook: $price");

        // 4. DATABASE TRANSACTION
        DB::beginTransaction();
        try {
            $transaction = PpobTransaction::where('order_id', $refId)->lockForUpdate()->first();

            if (!$transaction) {
                DB::rollBack();
                return response()->json(['status' => 'not found'], 404);
            }

            if (in_array($transaction->status, ['Success', 'Failed'])) {
                DB::rollBack();
                return response()->json(['status' => 'already processed']);
            }

            // Update Info Dasar
            $transaction->sn = $sn;
            $transaction->message = $message;
            $transaction->rc = $rc;
            if (!empty($desc)) {
                $transaction->desc = is_array($desc) ? json_encode($desc) : $desc;
            }
            
            // ====================================================================
            // [FIXED] LOGIKA UPDATE HARGA (MODAL)
            // ====================================================================
            if ($price > 0) {
                $isPostpaid = $this->_isPostpaid($transaction->buyer_sku_code);
                $currentSellingPrice = $transaction->selling_price;
                
                // Cek Anomali Pascabayar:
                // Jika produk Pascabayar DAN harga webhook sangat kecil (misal 2500) 
                // SEDANGKAN harga jual tinggi (misal 70.000), maka webhook hanya mengirim FEE ADMIN.
                // Jangan update harga modal jika ini terjadi.
                
                $isPriceAnomaly = $isPostpaid && ($price < 5000) && ($currentSellingPrice > 10000);

                if (!$isPriceAnomaly) {
                    // Hanya update jika BUKAN anomali (Data valid / Prabayar Normal)
                    $transaction->price = $price; 
                    $transaction->profit = $transaction->selling_price - $price;
                    Log::info("Harga Modal Updated: $price");
                } else {
                    Log::info("Skip Update Harga Modal (Deteksi Pascabayar Admin Fee Only): $price");
                }
            }
            // ====================================================================

            // LOGIKA STATUS
            $rcStr = (string) $rc; 
            
            if ($rcStr === '00' || $status === 'Sukses' || $status === 'Success') {
                $transaction->status = 'Success';
            }
            elseif (in_array($rcStr, ['03', '99']) || $status === 'Pending') {
                $transaction->status = 'Pending';
            }
            else {
                $transaction->status = 'Failed';
                
                // AUTO REFUND
                if (in_array(strtoupper($transaction->payment_method), ['SALDO', 'SALDO_AGEN'])) {
                    $user = User::find($transaction->user_id);
                    if ($user) {
                        $refundAmount = $transaction->selling_price;
                        $user->increment('saldo', $refundAmount);
                        $transaction->message .= " [Saldo Dikembalikan]";
                    }
                }
            }

            $transaction->save();

            // KIRIM NOTIFIKASI WA
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