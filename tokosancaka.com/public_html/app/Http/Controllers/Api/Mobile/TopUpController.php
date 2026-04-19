<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TopUpController extends Controller
{
    /**
     * API: MENGAMBIL RIWAYAT TOP UP
     */
    public function history(Request $request)
    {
        try {
            $user = Auth::user();

            // Mengambil transaksi khusus topup milik user yang sedang login
            $transactions = Transaction::where('user_id', $user->id_pengguna ?? $user->id)
                ->where('type', 'topup')
                ->orderBy('created_at', 'desc')
                ->paginate(15);

            // Format data agar lebih mudah dibaca oleh React Native
            $formattedData = collect($transactions->items())->map(function ($trx) {
                return [
                    'id' => $trx->id,
                    'reference_id' => $trx->reference_id,
                    'amount' => $trx->amount,
                    'status' => strtolower($trx->status), // 'pending', 'success', 'failed'
                    'payment_method' => $trx->payment_method,
                    'payment_url' => $trx->payment_url,
                    // Format tanggal (contoh: 04 Apr 2026, 14:30)
                    'created_at' => $trx->created_at->format('d M Y, H:i'),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedData,
                'message' => 'Berhasil mengambil riwayat top up.'
            ]);

        } catch (\Exception $e) {
            Log::error('API TopUp History Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada server.'
            ], 500);
        }
    }

    /**
     * API: MEMBUAT TRANSAKSI TOP UP BARU
     */
    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:10000',
            'payment_method' => 'required|string'
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        DB::beginTransaction();

        try {
            $amount = $request->amount;
            $paymentMethod = strtoupper($request->payment_method);

            // 1. Generate Reference ID Unik (Format: TOPUP-XXXXXX)
            do {
                $referenceId = 'TOPUP-' . strtoupper(Str::random(8));
            } while (Transaction::where('reference_id', $referenceId)->exists());

            $paymentUrl = null;
            $status = 'pending';

            // 2. Logika Pembayaran GATEWAY (Arahkan ke Web Portal)
            if ($paymentMethod === 'GATEWAY') {
                // Menggunakan No WA user sebagai parameter akun untuk halaman web
                // Fallback ke email atau ID jika No WA kosong
                $akun = $user->no_wa ?? $user->email ?? ($user->id_pengguna ?? $user->id);

                // Set Payment URL menuju ke halaman portal web Sancaka
                $paymentUrl = url('/pembayaran?akun=' . urlencode($akun));
            }

            // 3. Simpan ke Database
            $transaction = Transaction::create([
                'user_id' => $user->id_pengguna ?? $user->id,
                'reference_id' => $referenceId,
                'type' => 'topup',
                'amount' => $amount,
                'status' => $status,
                'payment_method' => $paymentMethod,
                'payment_url' => $paymentUrl,
            ]);

            DB::commit();

            Log::info("API MOBILE: Request Top Up berhasil dibuat: {$referenceId} via {$paymentMethod}");

            // 4. Return Response JSON ke Mobile
            return response()->json([
                'success' => true,
                'message' => 'Berhasil membuat tagihan Top Up.',
                'data' => [
                    'reference_id' => $transaction->reference_id,
                    'amount' => $transaction->amount,
                    'status' => $transaction->status,
                    'payment_method' => $transaction->payment_method,
                    'payment_url' => $transaction->payment_url,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('API TopUp Create Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses top up: ' . $e->getMessage()
            ], 500);
        }
    }
}
