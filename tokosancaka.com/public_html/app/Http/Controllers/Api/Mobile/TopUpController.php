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
            $user = Auth::user() ?? auth('sanctum')->user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Silakan login kembali.'], 401);
            }

            // Mengambil transaksi khusus topup milik user yang sedang login
            $transactions = Transaction::where('user_id', $user->id_pengguna ?? $user->id)
                ->where('type', 'topup')
                ->orderBy('created_at', 'desc')
                ->paginate(15);

            // Format data agar lebih mudah dibaca oleh React Native
            $formattedData = collect($transactions->items())->map(function ($trx) {
                return [
                    'id'             => $trx->id,
                    'reference_id'   => $trx->reference_id,
                    'amount'         => $trx->amount,
                    'status'         => strtolower($trx->status), // 'pending', 'success', 'failed'
                    'payment_method' => $trx->payment_method,
                    'payment_url'    => $trx->payment_url,
                    // Format tanggal (contoh: 04 Apr 2026, 14:30)
                    'created_at'     => $trx->created_at->format('d M Y, H:i'),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Berhasil mengambil riwayat top up.',
                'data'    => $formattedData
            ]);

        } catch (\Exception $e) {
            Log::error('API TopUp History Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil riwayat.'
            ], 500);
        }
    }

   /**
     * API: MEMBUAT TRANSAKSI TOP UP BARU (DARI MOBILE APP)
     */
    public function request(Request $request)
    {
        $request->validate([
            'amount'         => 'required|numeric|min:10000',
            'payment_method' => 'required|string'
        ]);

        $user = Auth::user() ?? auth('sanctum')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Silakan login kembali.'], 401);
        }

        DB::beginTransaction();

        try {
            $amount        = $request->amount;
            $paymentMethod = strtoupper($request->payment_method);
            $userId        = $user->id_pengguna ?? $user->id;

            // 1. Generate Reference ID Unik (Format: TOPUP-XXXXXX)
            do {
                $referenceId = 'TOPUP-' . strtoupper(Str::random(8));
            } while (Transaction::where('reference_id', $referenceId)->exists());

            $paymentUrl = null;
            $isManual   = false;
            $status     = 'pending';

            // ====================================================================
            // 2. CEGAT METODE PEMBAYARAN AGAR SESUAI DENGAN ATURAN SISTEM
            // ====================================================================

            // A. JIKA METODE GATEWAY (Arahkan ke Web Portal)
            if ($paymentMethod === 'GATEWAY') {
                // Gunakan No WA, jika tidak ada gunakan Email, jika tidak ada gunakan ID
                $akun = $user->no_wa ?? $user->email ?? $userId;

                // Set Payment URL menuju ke halaman portal web Sancaka
                $paymentUrl = url('/pembayaran?akun=' . urlencode($akun));
            }

            // B. JIKA METODE CASH (Khusus Admin / ID 4)
            elseif ($paymentMethod === 'CASH') {
                if ($userId != 4) {
                    throw new \Exception("Akses Ditolak: Metode CASH hanya untuk Admin.");
                }

                // Flag is_manual = true agar React Native menampilkan alert "Transfer Manual"
                $isManual = true;

                // Opsional (Saat ini dikomentari):
                // Jika ingin CASH otomatis Lunas & Saldo bertambah saat diklik.
                /*
                $status = 'success';
                $user->saldo += $amount;
                $user->save();
                */
            }

            // C. FALLBACK (Metode selain di atas paksa menjadi GATEWAY)
            else {
                $paymentMethod = 'GATEWAY';
                $akun          = $user->no_wa ?? $user->email ?? $userId;
                $paymentUrl    = url('/pembayaran?akun=' . urlencode($akun));
            }

            // ====================================================================
            // 3. SIMPAN KE DATABASE
            // ====================================================================
            $transaction = Transaction::create([
                'user_id'        => $userId,
                'reference_id'   => $referenceId,
                'type'           => 'topup',
                'amount'         => $amount,
                'status'         => $status,
                'payment_method' => $paymentMethod,
                'payment_url'    => $paymentUrl,
            ]);

            DB::commit();

            Log::info("API MOBILE: Request Top Up berhasil dibuat: {$referenceId} via {$paymentMethod} (User ID: {$userId})");

            // ====================================================================
            // 4. RETURN RESPONSE KE REACT NATIVE
            // ====================================================================
            return response()->json([
                'success' => true,
                'message' => 'Berhasil membuat tagihan Top Up.',
                'data' => [
                    'reference_id'   => $transaction->reference_id,
                    'amount'         => $transaction->amount,
                    'status'         => $transaction->status,
                    'payment_method' => $transaction->payment_method,
                    'payment_url'    => $transaction->payment_url,
                    'is_manual'      => $isManual // Dibaca oleh RN untuk buka browser / pindah halaman
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('API TopUp Request Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses top up: ' . $e->getMessage()
            ], 500);
        }
    }
}
