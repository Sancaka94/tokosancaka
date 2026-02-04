<?php

namespace App\Http\Controllers; // Pastikan namespace benar

use App\Http\Controllers\Controller;
use App\Services\DokuSacService;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WithdrawalController extends Controller
{
    /**
     * Request Withdrawal (Tarik Dana)
     */
    public function requestWithdrawal(Request $request)
    {
        // 1. Validasi Input (Wajib)
        $request->validate([
            'amount' => 'required|numeric|min:10000', // Minimal withdraw misal 10rb
            'bank_code' => 'required|string', // Kode Bank DOKU (Contoh: BNINIDJA untuk BCA)
            'bank_account_number' => 'required|numeric',
            'bank_account_name' => 'required|string',
        ]);

        $user = Auth::user();
        $amount = (int) $request->input('amount');

        // Pastikan User punya SAC ID (Disimpan saat Create Account DOKU)
        $sacId = $user->doku_sac_id;

        if (!$sacId) {
            return back()->with('error', 'Akun Anda belum terhubung ke DOKU Sub Account. Hubungi Admin.');
        }

        // 2. Cek Saldo LOKAL (Database Laravel)
        if ($user->balance < $amount) {
            return back()->with('error', 'Saldo dompet tidak mencukupi.');
        }

        // 3. (Optional) Cek Saldo REAL di DOKU sebelum tarik
        // Ini mencegah saldo di DB Lokal ada, tapi saldo di DOKU kosong (belum settlement)
        $sacService = new DokuSacService();
        $dokuBalance = $sacService->getBalance($sacId);

        if ($dokuBalance['status']) {
            $availableDoku = (int) ($dokuBalance['data']['balance']['available'] ?? 0);
            if ($availableDoku < $amount) {
                return back()->with('error', 'Saldo settlement (siap cair) di DOKU belum mencukupi. Mohon tunggu dana settled.');
            }
        } else {
            // Jika gagal cek saldo DOKU, batalkan transaksi demi keamanan
            return back()->with('error', 'Gagal mengecek saldo server: ' . $dokuBalance['message']);
        }

        $invoiceNumber = 'WD-' . time() . '-' . $user->id;

        // 4. Kurangi saldo LOKAL dan buat catatan (LOCK SEMENTARA)
        DB::beginTransaction();
        try {
            // Lock user row untuk mencegah race condition (double withdraw cepat)
            $lockedUser = \App\Models\User::where('id', $user->id)->lockForUpdate()->first();

            if ($lockedUser->balance < $amount) {
                DB::rollBack();
                return back()->with('error', 'Saldo tidak cukup.');
            }

            // Kurangi saldo
            $lockedUser->decrement('balance', $amount);

            // Catat transaksi
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'amount' => -$amount,
                'type' => 'withdrawal',
                'status' => 'processing', // Status awal
                'reference_id' => $invoiceNumber,
                'description' => 'Penarikan ke ' . $request->bank_code . ' - ' . $request->bank_account_number
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Withdrawal DB Error: " . $e->getMessage());
            return back()->with('error', 'Gagal memproses database lokal.');
        }

        // 5. Panggil DOKU untuk kirim uang SUNGGUHAN (Payout)
        $beneficiary = [
            'bank_code' => $request->input('bank_code'),
            'bank_account_number' => $request->input('bank_account_number'),
            'bank_account_name' => $request->input('bank_account_name')
        ];

        $payoutResponse = $sacService->sendPayout(
            $sacId,
            $invoiceNumber,
            $amount,
            $beneficiary
        );

        // 6. Update Status Berdasarkan Respon API
        // Kita query ulang transaksi
        $trx = Transaction::where('reference_id', $invoiceNumber)->first();

        if ($payoutResponse['status']) {
            // SUKSES TERKIRIM KE BANK
            $trx->update(['status' => 'success']);

            return back()->with('success', 'Penarikan berhasil! Dana sedang diproses bank.');
        } else {
            // GAGAL TERKIRIM -> REFUND SALDO LOKAL
            Log::error("Payout Failed for {$invoiceNumber}: " . $payoutResponse['message']);

            DB::beginTransaction();
            try {
                // Kembalikan uang user
                $user->increment('balance', $amount);

                $trx->update([
                    'status' => 'failed',
                    'description' => $trx->description . ' (Gagal: ' . $payoutResponse['message'] . ')'
                ]);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack(); // Bahaya jika rollback refund gagal, perlu log manual/alert
                Log::critical("CRITICAL: Gagal refund saldo user {$user->id} untuk trx {$invoiceNumber}");
            }

            return back()->with('error', 'Penarikan gagal diproses DOKU: ' . $payoutResponse['message']);
        }
    }
}
