<?php

namespace App\Services;

use App\Models\User;
use App\Models\TopUp;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class OrderRefundService
{
    /**
     * Kembalikan dana pembatalan resi ke saldo Pengguna secara atomik & idempotent.
     * Berlaku untuk semua metode pembayaran (Saldo & Payment Gateway) KECUALI CASH & COD.
     *
     * @param string      $invoiceNumber Nomor invoice unik order
     * @param int|null    $userId        ID pengguna pemilik order (Pengguna.id_pengguna)
     * @param float|int   $amount        Nominal yang akan dikembalikan ke saldo
     * @param string|null $paymentMethod Metode pembayaran awal
     * @param string|null $previousStatus Status order sebelum dibatalkan (untuk validasi lunas)
     * @param string      $source        Sumber pemicu (misal: 'WEBHOOK-KA' atau 'API-MOBILE')
     * @return float Nominal yang berhasil di-refund (0 jika dilewati/tidak memenuhi syarat)
     */
    public function refundToUserSaldo(
        string $invoiceNumber,
        ?int $userId,
        $amount,
        ?string $paymentMethod,
        ?string $previousStatus = null,
        string $source = 'WEBHOOK-KA'
    ): float {
        $invoiceNumber = trim($invoiceNumber);
        $pm = strtoupper(trim((string) $paymentMethod));
        $nominal = (float) $amount;

        // 1. Validasi dasar
        if (empty($invoiceNumber) || empty($userId) || $nominal <= 0) {
            Log::warning("[{$source}] Refund dilewati: Data tidak lengkap (Invoice: {$invoiceNumber}, User: {$userId}, Nominal: {$nominal}).");
            return 0;
        }

        // 2. PENGECUALIAN: Tolak jika pembayaran CASH atau COD (Bayar di Tempat)
        $excludedMethods = [
            'CASH',
            'TUNAI',
            'COD',
            '#COD_ONGKIR',
            '#COD_BARANG',
            'CODBARANG',
            'COD ONGKIR',
            'COD BARANG',
        ];

        if ($pm === '' || in_array($pm, $excludedMethods, true) || str_contains($pm, 'COD')) {
            Log::info("[{$source}] Refund dilewati untuk {$invoiceNumber}: Metode pembayaran '{$paymentMethod}' dikecualikan (CASH/COD).");
            return 0;
        }

        // 3. Validasi status sebelumnya: Pastikan order Payment Gateway memang sudah dibayar (bukan belum bayar/kadaluarsa)
        $unpaidStatuses = [
            'menunggu pembayaran',
            'unpaid',
            'pending',
            'kadaluarsa',
            'expired',
            'gagal bayar',
            'failed',
        ];

        if ($previousStatus !== null && in_array(strtolower(trim($previousStatus)), $unpaidStatuses, true)) {
            Log::info("[{$source}] Refund dilewati untuk {$invoiceNumber}: Status sebelumnya '{$previousStatus}' (belum lunas).");
            return 0;
        }

        // 4. Eksekusi dalam DB Transaction + Row Lock (Idempotent & Anti Double-Refund)
        return DB::transaction(function () use ($invoiceNumber, $userId, $nominal, $paymentMethod, $source) {
            $refundTrxId = 'RFND-' . $invoiceNumber;

            // Cek apakah invoice ini sudah pernah di-refund sebelumnya
            $alreadyRefundedInTopUp = TopUp::where('transaction_id', $refundTrxId)->exists();
            $alreadyRefundedInTrx = Schema::hasTable('transactions')
                ? Transaction::where('reference_id', $invoiceNumber)->where('type', 'refund')->exists()
                : false;

            if ($alreadyRefundedInTopUp || $alreadyRefundedInTrx) {
                Log::info("[{$source}] 🛡️ Refund untuk {$invoiceNumber} dilewati karena sudah diproses sebelumnya (Idempotent).");
                return 0;
            }

            // Kunci baris user agar saldo akurat saat terjadi transaksi bersamaan
            $user = User::where('id_pengguna', $userId)->lockForUpdate()->first();
            if (!$user) {
                Log::error("[{$source}] ❌ Gagal Refund {$invoiceNumber}: Pengguna ID {$userId} tidak ditemukan.");
                return 0;
            }

            // Tambahkan saldo ke Pengguna
            $user->increment('saldo', $nominal);

            // Catat mutasi ke tabel TopUp (sebagai kunci idempotency utama & riwayat saldo)
            TopUp::create([
                'customer_id'    => $user->id_pengguna,
                'amount'         => $nominal,
                'status'         => 'success',
                'payment_method' => 'Refund Batal (' . $paymentMethod . ')',
                'transaction_id' => $refundTrxId,
                'reference_id'   => $invoiceNumber,
                'created_at'     => now()->timezone('Asia/Jakarta'),
            ]);

            // Catat juga ke tabel Transaction jika tersedia (untuk sinkronisasi riwayat mobile)
            try {
                if (Schema::hasTable('transactions')) {
                    Transaction::create([
                        'user_id'        => $user->id_pengguna,
                        'amount'         => $nominal,
                        'type'           => 'refund',
                        'status'         => 'success',
                        'payment_method' => $paymentMethod,
                        'reference_id'   => $invoiceNumber,
                        'description'    => 'Refund Pembatalan Resi ' . $invoiceNumber,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning("[{$source}] Catatan log Transaction opsional gagal untuk {$invoiceNumber}: " . $e->getMessage());
            }

            Log::info("[{$source}] 💸 Refund Saldo BERHASIL | Invoice: {$invoiceNumber} | User ID: {$user->id_pengguna} | Metode: {$paymentMethod} | Nominal: Rp {$nominal}");

            return $nominal;
        });
    }
}
