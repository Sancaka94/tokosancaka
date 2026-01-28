<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\DokuJokulService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use App\Models\Api; // Wajib import Model API

class DokuBalanceController extends Controller
{
    public function index()
    {
        // ------------------------------------------------------------------
        // LANGKAH 1: AMBIL KONFIGURASI MENTAH DARI DATABASE
        // ------------------------------------------------------------------

        // Cek Mode (Default ke Sandbox jika tidak ada)
        $dbEnv = Api::where('key', 'DOKU_ENV')->value('value');
        $mode = (strtolower($dbEnv) === 'production') ? 'production' : 'sandbox';

        // Logika Cerdas: Ambil Main SAC ID (Cari di Scope dulu, lalu Global)
        $mainSacId = Api::where('key', 'DOKU_MAIN_SAC_ID')->where('environment', $mode)->value('value');
        if (empty($mainSacId)) {
            // Fallback: Jika di production/sandbox kosong, ambil dari global (Baris 23 DB Anda)
            $mainSacId = Api::where('key', 'DOKU_MAIN_SAC_ID')->where('environment', 'global')->value('value');
        }

        // ------------------------------------------------------------------
        // LANGKAH 2: PAKSA CONFIG LARAVEL (OVERRIDE APPSERVICEPROVIDER)
        // ------------------------------------------------------------------
        $this->applyDokuConfig($mode);

        // ------------------------------------------------------------------
        // LANGKAH 3: EKSEKUSI API DENGAN LOGIKA "AUTO-RETRY"
        // ------------------------------------------------------------------

        // Buat instance service BARU agar membaca config yang baru kita set
        $dokuService = new DokuJokulService();
        $balanceData = null;
        $error = null;
        $isAutoSwitched = false;

        if (empty($mainSacId)) {
            $error = "ID Akun Utama (DOKU_MAIN_SAC_ID) tidak ditemukan di Database (Scope: $mode/Global).";
        } else {
            try {
                // PERCOBAAN PERTAMA (Sesuai Database)
                $response = $dokuService->getBalance($mainSacId);

                // --- LOGIKA CERDAS: AUTO-SWITCH KE SANDBOX JIKA GAGAL DI PRODUCTION ---
                if (
                    $mode === 'production' &&
                    (!isset($response['success']) || !$response['success']) &&
                    (isset($response['data']['error']['code']) && $response['data']['error']['code'] == 'not_found')
                ) {
                    Log::warning("DOKU Balance: Gagal di Production (404). Mencoba beralih otomatis ke Sandbox...");

                    // 1. Ubah Mode ke Sandbox secara paksa
                    $this->applyDokuConfig('sandbox');
                    $mode = 'sandbox'; // Update variabel untuk View
                    $isAutoSwitched = true;

                    // 2. Re-create Service & Retry
                    $dokuService = new DokuJokulService();
                    $response = $dokuService->getBalance($mainSacId);
                }
                // -----------------------------------------------------------------------

                if (($response['success'] ?? false) === true && isset($response['data']['balance'])) {
                    $balanceData = $response['data'];
                } else {
                    $error = $response['message'] ?? 'Gagal mengambil data saldo.';

                    // Cek detail error
                    if (isset($response['data']['error']['message'])) {
                        $error .= " (" . $response['data']['error']['message'] . ")";
                    }
                }

            } catch (\Exception $e) {
                $error = 'Exception: ' . $e->getMessage();
            }
        }

        // ------------------------------------------------------------------
        // LANGKAH 4: KIRIM KE VIEW
        // ------------------------------------------------------------------
        return view('admin.doku.balance', [
            'mainSacId'   => $mainSacId,
            'mode'        => $mode,
            'balanceData' => $balanceData,
            'error'       => $error,
            'autoSwitched'=> $isAutoSwitched // Info tambahan untuk View
        ]);
    }

    /**
     * Helper Private untuk Memaksa Config DOKU di Runtime
     */
    private function applyDokuConfig($scope)
    {
        // Set URL
        if ($scope === 'production') {
            Config::set('doku.url', 'https://api.doku.com');
            Config::set('doku.production_url', 'https://api.doku.com');
        } else {
            Config::set('doku.url', 'https://api-sandbox.doku.com');
            Config::set('doku.sandbox_url', 'https://api-sandbox.doku.com');
        }

        // Set Mode
        Config::set('doku.mode', $scope);

        // Ambil Credential dari DB sesuai scope yang diminta
        Config::set('doku.client_id', Api::where('key', 'DOKU_CLIENT_ID')->where('environment', $scope)->value('value'));
        Config::set('doku.secret_key', Api::where('key', 'DOKU_SECRET_KEY')->where('environment', $scope)->value('value'));

        // Fallback SAC Credential
        $sacClient = Api::where('key', 'DOKU_SAC_CLIENT_ID')->where('environment', $scope)->value('value');
        $sacSecret = Api::where('key', 'DOKU_SAC_SECRET_KEY')->where('environment', $scope)->value('value');

        Config::set('doku.sac_client_id', $sacClient ?: Config::get('doku.client_id'));
        Config::set('doku.sac_secret_key', $sacSecret ?: Config::get('doku.secret_key'));
    }
}
