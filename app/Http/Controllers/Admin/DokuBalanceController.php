<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\DokuJokulService;
use Illuminate\Support\Facades\Log;

class DokuBalanceController extends Controller
{
    /**
     * Menampilkan halaman saldo Akun Doku Utama (Admin).
     */
    public function index(DokuJokulService $dokuService)
    {
        $mainSacId = config('doku.main_sac_id');
        $balanceData = null;
        $error = null;

        if (empty($mainSacId)) {
            $error = "DOKU_MAIN_SAC_ID belum di-set di file .env atau config/doku.php.";
            Log::error($error);
        } else {
            try {
                // Panggil API Doku untuk cek saldo Akun Utama
                $response = $dokuService->getBalance($mainSacId);

                if ($response['success'] === true && isset($response['data']['balance'])) {
                    $balanceData = $response['data'];
                } else {
                    $error = $response['message'] ?? 'Gagal mengambil data saldo dari Doku.';
                    Log::error('Gagal getBalance untuk Akun Utama Doku', [
                        'sac_id' => $mainSacId, 
                        'response' => $response
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Exception saat getBalance Akun Utama Doku', [
                    'sac_id' => $mainSacId, 
                    'error' => $e->getMessage()
                ]);
                $error = 'Terjadi kesalahan server saat menghubungi Doku: ' . $e->getMessage();
            }
        }

        return view('admin.doku.balance', [
            'mainSacId' => $mainSacId,
            'balanceData' => $balanceData,
            'error' => $error
        ]);
    }
}