<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\DokuJokulService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use App\Models\Api;

class DokuBalanceController extends Controller
{
    public function index()
    {
        // 1. AMBIL KONFIGURASI DARI DATABASE
        $dbEnv = Api::where('key', 'DOKU_ENV')->value('value');
        $mode = (strtolower($dbEnv) === 'production') ? 'production' : 'sandbox';

        // Ambil ID Akun Utama (Cari di Scope dulu, lalu Global)
        $mainSacId = Api::where('key', 'DOKU_MAIN_SAC_ID')->where('environment', $mode)->value('value');
        if (empty($mainSacId)) {
            $mainSacId = Api::where('key', 'DOKU_MAIN_SAC_ID')->where('environment', 'global')->value('value');
        }

        // 2. TERAPKAN KONFIGURASI AWAL (Biasanya Production sesuai DB Anda)
        $this->applyDokuConfig($mode);

        // 3. EKSEKUSI API & LOGIKA AUTO-SWITCH
        $dokuService = new DokuJokulService();
        $balanceData = null;
        $error = null;
        $isAutoSwitched = false;

        if (empty($mainSacId)) {
            $error = "ID Akun Utama (DOKU_MAIN_SAC_ID) tidak ditemukan.";
        } else {
            try {
                // COBA REQUEST PERTAMA
                $response = $dokuService->getBalance($mainSacId);

                // --- LOGIKA CERDAS V2 (LEBIH FLEKSIBEL) ---
                // Cek jika GAGAL + Mode PRODUCTION + Pesan Error mengandung "not found"
                $pesanError = $response['message'] ?? '';

                if (
                    $mode === 'production' &&
                    (!isset($response['success']) || !$response['success']) &&
                    (stripos($pesanError, 'not found') !== false) // <--- PERBAIKAN DI SINI
                ) {
                    Log::warning("DOKU Balance: Gagal di Production (404 Not Found). Mengaktifkan AUTO-SWITCH ke Sandbox...");

                    // 1. Paksa ubah config ke SANDBOX
                    $this->applyDokuConfig('sandbox');
                    $mode = 'sandbox';
                    $isAutoSwitched = true;

                    // 2. Buat Service Baru & Coba Lagi
                    $dokuService = new DokuJokulService();

                    // 3. Cari ID Sandbox (jika ID Production beda dengan Sandbox)
                    // Fallback lagi ke global jika di sandbox kosong
                    $sandboxId = Api::where('key', 'DOKU_MAIN_SAC_ID')->where('environment', 'sandbox')->value('value');
                    if(empty($sandboxId)) {
                         $sandboxId = Api::where('key', 'DOKU_MAIN_SAC_ID')->where('environment', 'global')->value('value');
                    }

                    $response = $dokuService->getBalance($sandboxId);
                    $mainSacId = $sandboxId; // Update ID untuk tampilan View
                }
                // ------------------------------------------

                if (($response['success'] ?? false) === true && isset($response['data']['balance'])) {
                    $balanceData = $response['data'];
                } else {
                    $error = $response['message'] ?? 'Gagal mengambil data saldo.';
                }

            } catch (\Exception $e) {
                $error = 'Exception: ' . $e->getMessage();
            }
        }

        // 4. KIRIM KE VIEW
        return view('admin.doku.balance', [
            'mainSacId'   => $mainSacId,
            'mode'        => $mode,
            'balanceData' => $balanceData,
            'error'       => $error,
            'autoSwitched'=> $isAutoSwitched
        ]);
    }

    private function applyDokuConfig($scope)
    {
        if ($scope === 'production') {
            Config::set('doku.url', 'https://api.doku.com');
        } else {
            Config::set('doku.url', 'https://api-sandbox.doku.com');
        }
        Config::set('doku.mode', $scope);

        // Ambil Credential DB
        Config::set('doku.client_id', Api::where('key', 'DOKU_CLIENT_ID')->where('environment', $scope)->value('value'));
        Config::set('doku.secret_key', Api::where('key', 'DOKU_SECRET_KEY')->where('environment', $scope)->value('value'));

        // Fallback SAC Credential
        $sacClient = Api::where('key', 'DOKU_SAC_CLIENT_ID')->where('environment', $scope)->value('value');
        $sacSecret = Api::where('key', 'DOKU_SAC_SECRET_KEY')->where('environment', $scope)->value('value');

        Config::set('doku.sac_client_id', $sacClient ?: Config::get('doku.client_id'));
        Config::set('doku.sac_secret_key', $sacSecret ?: Config::get('doku.secret_key'));
    }
}
