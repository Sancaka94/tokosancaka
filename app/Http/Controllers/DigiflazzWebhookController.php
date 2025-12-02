<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\User; // Sesuaikan dengan model user Anda
// use App\Models\Transaction; // Sesuaikan jika Anda punya model transaksi PPOB

class DigiflazzWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // 1. Ambil Data dari Digiflazz
        // Digiflazz mengirim JSON di body request
        $data = $request->all();
        
        // Log data masuk untuk debugging (bisa dihapus nanti)
        Log::info('Digiflazz Webhook:', $data);

        // Pastikan ada data yang dikirim
        if (!isset($data['data'])) {
            return response()->json(['status' => 'failed', 'message' => 'Invalid data'], 400);
        }

        $trxData = $data['data'];
        $refId = $trxData['ref_id'];     // ID Transaksi kita (TRX-USERID-TIMESTAMP)
        $status = $trxData['status'];    // Sukses, Gagal, Pending
        $sn = $trxData['sn'];            // Serial Number (bukti sukses)
        $message = $trxData['message'];  // Pesan error jika gagal
        $price = $trxData['price'];      // Harga modal yang terpotong

        // 2. Verifikasi Signature (Keamanan)
        // Rumus: md5(username + apiKey + ref_id)
        // $secret = env('DIGIFLAZZ_KEY');
        // $username = env('DIGIFLAZZ_USERNAME');
        // $signature = md5($username . $secret . $refId);
        
        // Cek header X-Hub-Signature jika ada, atau abaikan dulu untuk tahap awal
        
        // 3. Proses Transaksi di Database Lokal
        // Karena di contoh controller sebelumnya kita belum simpan ke tabel 'transactions',
        // di sini kita hanya akan melakukan log/notifikasi sederhana.
        // Nanti Anda perlu buat tabel 'ppob_transactions' untuk mencatat ini.

        if ($status === 'Sukses') {
            // Transaksi Berhasil
            Log::info("PPOB Sukses: RefID $refId, SN: $sn");
            
            // TODO: Update status transaksi di database Anda jadi 'success'
            // $trx = Transaction::where('ref_id', $refId)->first();
            // if ($trx) { $trx->update(['status' => 'success', 'sn' => $sn]); }

        } elseif ($status === 'Gagal') {
            // Transaksi Gagal -> REFUND SALDO USER
            Log::info("PPOB Gagal: RefID $refId. Pesan: $message");

            // Ambil User ID dari RefID (TRX-123-99999)
            $parts = explode('-', $refId);
            if (count($parts) >= 2) {
                $userId = $parts[1];
                $user = User::find($userId);

                if ($user) {
                    // Kembalikan Saldo (Refund)
                    // Hati-hati: Pastikan belum pernah direfund sebelumnya!
                    // Cek di database transaksi Anda.
                    
                    // Contoh logika refund sederhana (perlu tabel transaksi untuk validasi ganda):
                    // $user->increment('saldo', $hargaJualProduk); 
                    
                    Log::warning("Perlu Refund Manual untuk User ID $userId (RefID: $refId)");
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }
}