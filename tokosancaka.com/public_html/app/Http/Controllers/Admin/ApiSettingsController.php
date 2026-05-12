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
        $kaMode           = Api::getValue('KIRIMINAJA_MODE', 'global', 'staging');
        $tripayMode       = Api::getValue('TRIPAY_MODE', 'global', 'sandbox');
        $dokuEnv          = Api::getValue('DOKU_ENV', 'global', 'sandbox');
        $iakMode          = Api::getValue('IAK_MODE', 'global', 'development');
        $dharmawisataMode = Api::getValue('DHARMAWISATA_MODE', 'global', 'development'); // Tambahan Darmawisata

        // 2. Siapkan Struktur Data Lengkap (Active Mode + Data per Environment)

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
            'sac_id' => Api::getValue('DOKU_MAIN_SAC_ID', 'global'),
        ];

        $iak = [
            'mode' => $iakMode,
            'development' => [
                'user_hp'           => Api::getValue('IAK_USER_HP', 'development'),
                'api_key'           => Api::getValue('IAK_API_KEY', 'development'),
                'prepaid_base_url'  => Api::getValue('IAK_PREPAID_BASE_URL', 'development'),
                'postpaid_base_url' => Api::getValue('IAK_POSTPAID_BASE_URL', 'development'),
            ],
            'production' => [
                'user_hp'           => Api::getValue('IAK_USER_HP', 'production'),
                'api_key'           => Api::getValue('IAK_API_KEY', 'production'),
                'prepaid_base_url'  => Api::getValue('IAK_PREPAID_BASE_URL', 'production'),
                'postpaid_base_url' => Api::getValue('IAK_POSTPAID_BASE_URL', 'production'),
            ]
        ];

       // --- TAMBAHAN DARMAWISATA ---
        $dharmawisata = [
            'mode' => $dharmawisataMode,
            'development' => [
                'user_id'      => Api::getValue('DHARMAWISATA_USER_ID', 'development'),
                'access_token' => Api::getValue('DHARMAWISATA_ACCESS_TOKEN', 'development'),
                'base_url'     => Api::getValue('DHARMAWISATA_BASE_URL', 'development'),
                'static_token' => Api::getValue('DHARMAWISATA_STATIC_TOKEN', 'development'), // TAMBAHAN BARU
                'password'     => Api::getValue('DHARMAWISATA_PASSWORD', 'development'),     // TAMBAHAN BARU
            ],
            'production' => [
                'user_id'      => Api::getValue('DHARMAWISATA_USER_ID', 'production'),
                'access_token' => Api::getValue('DHARMAWISATA_ACCESS_TOKEN', 'production'),
                'base_url'     => Api::getValue('DHARMAWISATA_BASE_URL', 'production'),
                'static_token' => Api::getValue('DHARMAWISATA_STATIC_TOKEN', 'production'), // TAMBAHAN BARU
                'password'     => Api::getValue('DHARMAWISATA_PASSWORD', 'production'),     // TAMBAHAN BARU
            ]
        ];

        $fonnte = [
            'api_key' => Api::getValue('FONNTE_API_KEY', 'global'),
        ];

        // Tambahkan variable $dharmawisata ke compact
        return view('admin.settings.api_settings', compact('kiriminaja', 'tripay', 'doku', 'iak', 'fonnte', 'dharmawisata'));
    }

    public function update(Request $request)
    {
        $type = $request->input('type');

        try {
            if ($type === 'kiriminaja') {
                $env = $request->kiriminaja_mode;

                Api::setValue('KIRIMINAJA_MODE', $env, 'kiriminaja', 'global');

                $baseUrl = $request->kiriminaja_base_url;
                if (empty($baseUrl)) {
                    $baseUrl = ($env === 'production') ? 'https://client.kiriminaja.com' : 'https://tdev.kiriminaja.com';
                }

                Api::setValue('KIRIMINAJA_TOKEN', $request->kiriminaja_token, 'kiriminaja', $env);
                Api::setValue('KIRIMINAJA_BASE_URL', $baseUrl, 'kiriminaja', $env);

            } elseif ($type === 'tripay') {
                $env = $request->tripay_mode;

                Api::setValue('TRIPAY_MODE', $env, 'tripay', 'global');

                Api::setValue('TRIPAY_MERCHANT_CODE', $request->tripay_merchant_code, 'tripay', $env);
                Api::setValue('TRIPAY_API_KEY', $request->tripay_api_key, 'tripay', $env);
                Api::setValue('TRIPAY_PRIVATE_KEY', $request->tripay_private_key, 'tripay', $env);

            } elseif ($type === 'doku') {
                $env = $request->doku_env;

                Api::setValue('DOKU_ENV', $env, 'doku', 'global');

                Api::setValue('DOKU_CLIENT_ID', $request->doku_client_id, 'doku', $env);
                Api::setValue('DOKU_SECRET_KEY', $request->doku_secret_key, 'doku', $env);
                Api::setValue('DOKU_PUBLIC_KEY', $request->doku_public_key, 'doku', $env);
                Api::setValue('MERCHANT_PRIVATE_KEY', $request->merchant_private_key, 'doku', $env);

                if ($request->has('doku_main_sac_id')) {
                    Api::setValue('DOKU_MAIN_SAC_ID', $request->doku_main_sac_id, 'doku', 'global');
                }

            } elseif ($type === 'iak') {
                $env = $request->iak_mode;

                Api::setValue('IAK_MODE', $env, 'iak', 'global');

                $prepaidUrl = $request->iak_prepaid_base_url;
                if (empty($prepaidUrl)) {
                    $prepaidUrl = ($env === 'production') ? 'https://prepaid.iak.id' : 'https://prepaid.iak.dev';
                }

                $postpaidUrl = $request->iak_postpaid_base_url;
                if (empty($postpaidUrl)) {
                    $postpaidUrl = ($env === 'production') ? 'https://mobilepulsa.net' : 'https://testpostpaid.mobilepulsa.net';
                }

                Api::setValue('IAK_USER_HP', $request->iak_user_hp, 'iak', $env);
                Api::setValue('IAK_API_KEY', $request->iak_api_key, 'iak', $env);
                Api::setValue('IAK_PREPAID_BASE_URL', $prepaidUrl, 'iak', $env);
                Api::setValue('IAK_POSTPAID_BASE_URL', $postpaidUrl, 'iak', $env);

            // --- TAMBAHAN DARMAWISATA UPDATE ---
            } elseif ($type === 'dharmawisata') {
                $env = $request->dharmawisata_mode; // development atau production

                Api::setValue('DHARMAWISATA_MODE', $env, 'dharmawisata', 'global');

                $baseUrl = $request->dharmawisata_base_url;
                if (empty($baseUrl)) {
                    $baseUrl = 'https://uat-backup.darmawisataindonesiah2h.co.id:7080/h2h/'; // Base URL default darmawisata
                }

                Api::setValue('DHARMAWISATA_USER_ID', $request->dharmawisata_user_id, 'dharmawisata', $env);
                Api::setValue('DHARMAWISATA_ACCESS_TOKEN', $request->dharmawisata_access_token, 'dharmawisata', $env);
                Api::setValue('DHARMAWISATA_BASE_URL', $baseUrl, 'dharmawisata', $env);

                // --- 2 BARIS TAMBAHAN BARU UNTUK AUTO-RECONNECT ---
                Api::setValue('DHARMAWISATA_STATIC_TOKEN', $request->dharmawisata_static_token, 'dharmawisata', $env);
                Api::setValue('DHARMAWISATA_PASSWORD', $request->dharmawisata_password, 'dharmawisata', $env);

            } elseif ($type === 'fonnte') {
                Api::setValue('FONNTE_API_KEY', $request->fonnte_api_key, 'fonnte', 'global');
            }

            return back()->with('success', 'Konfigurasi ' . strtoupper($type) . ' berhasil diperbarui untuk mode ' . strtoupper($request->input("{$type}_mode") ?? 'GLOBAL') . '.');

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menyimpan: ' . $e->getMessage());
        }
    }

    public function toggle(Request $request)
    {
        try {
            $currentMode = Api::getValue('KIRIMINAJA_MODE', 'global', 'staging');

            if ($currentMode === 'production') {
                $targetKA           = 'staging';
                $targetTripay       = 'sandbox';
                $targetDoku         = 'sandbox';
                $targetIAK          = 'development';
                $targetDharmawisata = 'development'; // Tambahan Darmawisata
                $label              = 'SANDBOX / STAGING / DEVELOPMENT';
            } else {
                $targetKA           = 'production';
                $targetTripay       = 'production';
                $targetDoku         = 'production';
                $targetIAK          = 'production';
                $targetDharmawisata = 'production'; // Tambahan Darmawisata
                $label              = 'PRODUCTION (LIVE)';
            }

            Api::setValue('KIRIMINAJA_MODE', $targetKA, 'kiriminaja', 'global');
            Api::setValue('TRIPAY_MODE', $targetTripay, 'tripay', 'global');
            Api::setValue('DOKU_ENV', $targetDoku, 'doku', 'global');
            Api::setValue('IAK_MODE', $targetIAK, 'iak', 'global');
            Api::setValue('DHARMAWISATA_MODE', $targetDharmawisata, 'dharmawisata', 'global'); // Tambahan Darmawisata

            event(new SystemModeUpdated($targetKA));

            return back()->with('success', "Mode API berhasil diubah ke: <b>$label</b>");

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengubah mode: ' . $e->getMessage());
        }
    }

    public function toggleApi(Request $request)
    {
        try {
            $isProduction = filter_var($request->input('is_production'), FILTER_VALIDATE_BOOLEAN);

            if ($isProduction == true) {
                $targetKA           = 'production';
                $targetTripay       = 'production';
                $targetDoku         = 'production';
                $targetIAK          = 'production';
                $targetDharmawisata = 'production'; // Tambahan Darmawisata
                $label              = 'PRODUCTION (LIVE)';
            } else {
                $targetKA           = 'staging';
                $targetTripay       = 'sandbox';
                $targetDoku         = 'sandbox';
                $targetIAK          = 'development';
                $targetDharmawisata = 'development'; // Tambahan Darmawisata
                $label              = 'SANDBOX / MAINTENANCE';
            }

            Api::setValue('KIRIMINAJA_MODE', $targetKA, 'kiriminaja', 'global');
            Api::setValue('TRIPAY_MODE', $targetTripay, 'tripay', 'global');
            Api::setValue('DOKU_ENV', $targetDoku, 'doku', 'global');
            Api::setValue('IAK_MODE', $targetIAK, 'iak', 'global');
            Api::setValue('DHARMAWISATA_MODE', $targetDharmawisata, 'dharmawisata', 'global'); // Tambahan Darmawisata

            event(new SystemModeUpdated($targetKA));

            return response()->json([
                'success' => true,
                'message' => "Sistem berhasil diubah ke mode $label",
                'mode'    => $targetKA
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah database: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getSystemMode()
    {
        try {
            $currentMode = Api::getValue('KIRIMINAJA_MODE', 'global', 'staging');

            return response()->json([
                'success' => true,
                'mode'    => $currentMode
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil status sistem'
            ], 500);
        }
    }
}
