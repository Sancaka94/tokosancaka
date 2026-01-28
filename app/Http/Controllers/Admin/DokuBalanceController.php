<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\DokuJokulService;
use Illuminate\Support\Facades\Log;
use App\Models\Api; // <--- WAJIB: Import Model API Anda

class DokuBalanceController extends Controller
{
    /**
     * Menampilkan halaman saldo Akun Doku Utama (Admin).
     * Data diambil Dinamis dari Database (Tabel Apis).
     */
    public function index(DokuJokulService $dokuService)
    {
        // 1. Cek Mode Saat Ini dari Database (Default: sandbox)
        // Asumsi: Anda menggunakan helper getValue seperti di kode sebelumnya
        $mode = Api::getValue('DOKU_MODE', 'global', 'sandbox');

        // 2. Tentukan Scope berdasarkan Mode
        $scope = ($mode === 'production') ? 'production' : 'sandbox';

        // 3. Ambil Main SAC ID dari Database sesuai Scope
        $mainSacId = Api::getValue('DOKU_MAIN_SAC_ID', $scope);

        $balanceData = null;
        $error = null;

        // Logika Validasi
        if (empty($mainSacId)) {
            $error = "Konfigurasi DOKU_MAIN_SAC_ID (Mode: $mode) belum ditemukan di Database.";
            Log::error($error);
        } else {
            try {
                // 4. Panggil API Doku
                // Pastikan Service Anda juga menggunakan Kredensial (Client ID/Secret) dari DB
                $response = $dokuService->getBalance($mainSacId);

                if (($response['success'] ?? false) === true && isset($response['data']['balance'])) {
                    $balanceData = $response['data'];
                } else {
                    $error = $response['message'] ?? 'Gagal mengambil data saldo dari Doku.';

                    // Log error detail tapi sembunyikan data sensitif jika perlu
                    Log::error('Gagal getBalance Akun Utama (DB)', [
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

        return view('admin.doku.balance', [
            'mainSacId' => $mainSacId,
            'mode' => $mode, // Kirim mode ke view agar admin tahu ini saldo Sandbox/Live
            'balanceData' => $balanceData,
            'error' => $error
        ]);
    }
}
