<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TopUpController extends Controller
{
    /**
     * API: MENGAMBIL RIWAYAT TOP UP
     */
    public function history(Request $request)
    {
        try {
            $user = Auth::user();

            // 1. Inisialisasi Query dasar (Hanya ambil yang typenya topup)
            $query = Transaction::where('type', 'topup');

            // 2. Logika Pengecekan Admin
            // Jika ID pengguna BUKAN 4, maka batasi data hanya untuk user tersebut
            if ($user->id_pengguna != 4) {
                $query->where('user_id', $user->id_pengguna);
            }

            // 3. Eksekusi query dengan sorting dan paginasi
            $transactions = $query->orderBy('created_at', 'desc')->paginate(15);

            // 4. Format data agar lebih mudah dibaca oleh React Native
            $formattedData = collect($transactions->items())->map(function ($trx) {
                return [
                    'id' => $trx->id,
                    'user_id' => $trx->user_id, // Ditambahkan agar admin bisa membedakan pemilik transaksi
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
}
