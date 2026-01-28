<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\DokuJokulService;
use Illuminate\Support\Facades\Log;
// use App\Models\Api; // <-- HAPUS INI: Tidak perlu lagi karena sudah ada di AppServiceProvider

class DokuBalanceController extends Controller
{
    /**
     * Menampilkan halaman saldo Akun Doku Utama (Admin).
     */
    public function index(DokuJokulService $dokuService)
    {
        // 1. Ambil Konfigurasi dari Config (yang sudah di-inject oleh AppServiceProvider)
        $mode = config('doku.mode'); // 'sandbox' atau 'production'
        $mainSacId = config('doku.main_sac_id');

        $balanceData = null;
        $error = null;

        // 2. Logika Validasi
        if (empty($mainSacId)) {
            $error = "Konfigurasi DOKU_MAIN_SAC_ID belum ditemukan. Pastikan sudah diatur di Database/Env.";
            Log::error($error);
        } else {
            try {
                // 3. Panggil API Doku
                $response = $dokuService->getBalance($mainSacId);

                if (($response['success'] ?? false) === true && isset($response['data']['balance'])) {
                    $balanceData = $response['data'];
                } else {
                    $error = $response['message'] ?? 'Gagal mengambil data saldo dari Doku.';

                    Log::error('Gagal getBalance Akun Utama', [
                        'mode' => $mode,
                        'sac_id' => $mainSacId,
                        'response_msg' => $error
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Exception getBalance Akun Utama', [
                    'sac_id' => $mainSacId,
                    'error' => $e->getMessage()
                ]);
                $error = 'Terjadi kesalahan server: ' . $e->getMessage();
            }
        }

        // 4. Return ke View
        return view('admin.doku.balance', [
            'mainSacId' => $mainSacId,
            'mode' => $mode,
            'balanceData' => $balanceData,
            'error' => $error
        ]);
    }
}
