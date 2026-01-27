<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Events\SystemModeUpdated;
use App\Models\Api;

class ApiSettingsController extends Controller
{
    public function index()
    {
        // 1. Ambil Mode Global yang sedang aktif
        $kaMode     = Api::getValue('KIRIMINAJA_MODE', 'global', 'staging');
        $tripayMode = Api::getValue('TRIPAY_MODE', 'global', 'sandbox');
        $dokuEnv    = Api::getValue('DOKU_ENV', 'global', 'sandbox');

        // 2. Siapkan Struktur Data Lengkap (Active Mode + Data per Environment)
        // Kita kirim 'staging' DAN 'production' sekaligus agar frontend bisa switch real-time.

        $kiriminaja = [
            'mode' => $kaMode,
            'staging' => [
                'token'    => Api::getValue('KIRIMINAJA_TOKEN', 'staging'),
                'base_url' => Api::getValue('KIRIMINAJA_BASE_URL', 'staging'),
            ],
            'production' => [
                'token'    => Api::getValue('KIRIMINAJA_TOKEN', 'production'),
                'base_url' => Api::getValue('KIRIMINAJA_BASE_URL', 'production'),
            ]
        ];

        $tripay = [
            'mode' => $tripayMode,
            'sandbox' => [
                'merchant_code' => Api::getValue('TRIPAY_MERCHANT_CODE', 'sandbox'),
                'api_key'       => Api::getValue('TRIPAY_API_KEY', 'sandbox'),
                'private_key'   => Api::getValue('TRIPAY_PRIVATE_KEY', 'sandbox'),
            ],
            'production' => [
                'merchant_code' => Api::getValue('TRIPAY_MERCHANT_CODE', 'production'),
                'api_key'       => Api::getValue('TRIPAY_API_KEY', 'production'),
                'private_key'   => Api::getValue('TRIPAY_PRIVATE_KEY', 'production'),
            ]
        ];

        $doku = [
            'env' => $dokuEnv,
            'sandbox' => [
                'client_id'           => Api::getValue('DOKU_CLIENT_ID', 'sandbox'),
                'secret_key'          => Api::getValue('DOKU_SECRET_KEY', 'sandbox'),
                'public_key'          => Api::getValue('DOKU_PUBLIC_KEY', 'sandbox'),
                'merchant_private_key'=> Api::getValue('MERCHANT_PRIVATE_KEY', 'sandbox'),
            ],
            'production' => [
                'client_id'           => Api::getValue('DOKU_CLIENT_ID', 'production'),
                'secret_key'          => Api::getValue('DOKU_SECRET_KEY', 'production'),
                'public_key'          => Api::getValue('DOKU_PUBLIC_KEY', 'production'),
                'merchant_private_key'=> Api::getValue('MERCHANT_PRIVATE_KEY', 'production'),
            ],
            // SAC ID diasumsikan global, tidak berubah per env (tapi bisa disesuaikan)
            'sac_id' => Api::getValue('DOKU_MAIN_SAC_ID', 'global'),

        ];

        $fonnte = [
            'api_key' => Api::getValue('FONNTE_API_KEY', 'global'),
        ];

        return view('admin.settings.api_settings', compact('kiriminaja', 'tripay', 'doku', 'fonnte'));
    }

    public function update(Request $request)
    {
        $type = $request->input('type');

        try {
            if ($type === 'kiriminaja') {
                $env = $request->kiriminaja_mode; // staging atau production

                // Simpan Mode Global
                Api::setValue('KIRIMINAJA_MODE', $env, 'kiriminaja', 'global');

                // Logic Base URL Auto jika kosong
                $baseUrl = $request->kiriminaja_base_url;
                if (empty($baseUrl)) {
                    $baseUrl = ($env === 'production') ? 'https://client.kiriminaja.com' : 'https://tdev.kiriminaja.com';
                }

                // Simpan data ke environment SPESIFIK yang dipilih user
                Api::setValue('KIRIMINAJA_TOKEN', $request->kiriminaja_token, 'kiriminaja', $env);
                Api::setValue('KIRIMINAJA_BASE_URL', $baseUrl, 'kiriminaja', $env);

            } elseif ($type === 'tripay') {
                $env = $request->tripay_mode; // sandbox atau production

                Api::setValue('TRIPAY_MODE', $env, 'tripay', 'global');

                Api::setValue('TRIPAY_MERCHANT_CODE', $request->tripay_merchant_code, 'tripay', $env);
                Api::setValue('TRIPAY_API_KEY', $request->tripay_api_key, 'tripay', $env);
                Api::setValue('TRIPAY_PRIVATE_KEY', $request->tripay_private_key, 'tripay', $env);

            } elseif ($type === 'doku') {
                $env = $request->doku_env; // sandbox atau production

                Api::setValue('DOKU_ENV', $env, 'doku', 'global');

                Api::setValue('DOKU_CLIENT_ID', $request->doku_client_id, 'doku', $env);
                Api::setValue('DOKU_SECRET_KEY', $request->doku_secret_key, 'doku', $env);
                Api::setValue('DOKU_PUBLIC_KEY', $request->doku_public_key, 'doku', $env);
                Api::setValue('MERCHANT_PRIVATE_KEY', $request->merchant_private_key, 'doku', $env);

                // ✅ 3. TAMBAHAN: Simpan DOKU MAIN SAC ID (Disimpan sebagai global)
                // Pastikan input di blade bernama: name="doku_main_sac_id"
                if ($request->has('doku_main_sac_id')) {
                    Api::setValue('DOKU_MAIN_SAC_ID', $request->doku_main_sac_id, 'doku', 'global');
                }

            } elseif ($type === 'fonnte') {
                Api::setValue('FONNTE_API_KEY', $request->fonnte_api_key, 'fonnte', 'global');
            }

            return back()->with('success', 'Konfigurasi ' . strtoupper($type) . ' berhasil diperbarui untuk mode ' . strtoupper($request->input("{$type}_mode") ?? 'GLOBAL') . '.');

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menyimpan: ' . $e->getMessage());
        }
    }

      /**
     * Method Baru: Toggle Global (Dipanggil dari Header atau Dashboard)
     * Mengubah mode semua layanan sekaligus.
     */
    public function toggle(Request $request)
    {
        try {
            // 1. Cek Mode KiriminAja saat ini sebagai acuan
            // Jika tidak ada setting, default ke 'staging'
            $currentMode = Api::getValue('KIRIMINAJA_MODE', 'global', 'staging');

            // 2. Tentukan Target Mode (Switch)
            // Jika sekarang Production, ubah semua ke Staging/Sandbox.
            // Jika sekarang Staging, ubah semua ke Production.
            if ($currentMode === 'production') {
                $targetKA     = 'staging';
                $targetTripay = 'sandbox';
                $targetDoku   = 'sandbox';
                $label        = 'SANDBOX / STAGING';
            } else {
                $targetKA     = 'production';
                $targetTripay = 'production';
                $targetDoku   = 'production';
                $label        = 'PRODUCTION (LIVE)';
            }

            // 3. Simpan Perubahan ke Database
            Api::setValue('KIRIMINAJA_MODE', $targetKA, 'kiriminaja', 'global');
            Api::setValue('TRIPAY_MODE', $targetTripay, 'tripay', 'global');
            Api::setValue('DOKU_ENV', $targetDoku, 'doku', 'global');


            // ✅ 4. SIARKAN EVENT REAL-TIME KE SEMUA USER
            // Ini akan mentrigger modal di dashboard customer tanpa refresh
            event(new SystemModeUpdated($targetKA));

            return back()->with('success', "Mode API berhasil diubah ke: <b>$label</b>");

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengubah mode: ' . $e->getMessage());
        }
    }

}
