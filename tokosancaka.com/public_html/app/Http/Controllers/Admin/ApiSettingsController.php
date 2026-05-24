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
        $danaProductionMode = Api::getValue('dana_production_mode', 'global', '0');
        $danaMode = $danaProductionMode == '1' ? 'production' : 'sandbox';
        
        // --- TAMBAHAN MIDTRANS ---
        $midtransMode     = Api::getValue('MIDTRANS_MODE', 'global', 'sandbox');

        // --- TAMBAHAN LALAMOVE ---
        // LOG LOG
        $lalamoveMode     = Api::getValue('LALAMOVE_MODE', 'global', 'sandbox');

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

       // --- TAMBAHAN ARRAY DANA ---
        $dana = [
            'mode' => $danaMode,
            'sandbox' => [
                'merchant_id'   => Api::getValue('dana_sandbox_merchant_id', 'sandbox'),
                'client_id'     => Api::getValue('dana_sandbox_client_id', 'sandbox'),
                'client_secret' => Api::getValue('dana_sandbox_client_secret', 'sandbox'),
                'private_key'   => Api::getValue('dana_sandbox_private_key', 'sandbox'),
                'public_key'    => Api::getValue('dana_sandbox_public_key', 'sandbox'), // <-- TAMBAHKAN BARIS INI
            ],
            'production' => [
                'merchant_id'   => Api::getValue('dana_prod_merchant_id', 'production'),
                'client_id'     => Api::getValue('dana_prod_client_id', 'production'),
                'client_secret' => Api::getValue('dana_prod_client_secret', 'production'),
                'private_key'   => Api::getValue('dana_prod_private_key', 'production'),
                'public_key'    => Api::getValue('dana_prod_public_key', 'production'), // <-- TAMBAHKAN BARIS INI
            ]
        ];

        // --- TAMBAHAN ARRAY MIDTRANS ---
        $midtrans = [
            'mode' => $midtransMode,
            'sandbox' => [
                'merchant_id'        => Api::getValue('MIDTRANS_MERCHANT_ID', 'sandbox'),
                'client_key'         => Api::getValue('MIDTRANS_CLIENT_KEY', 'sandbox'),
                'server_key'         => Api::getValue('MIDTRANS_SERVER_KEY', 'sandbox'),
                'snap_client_id'     => Api::getValue('MIDTRANS_SNAP_CLIENT_ID', 'sandbox'),
                'snap_client_secret' => Api::getValue('MIDTRANS_SNAP_CLIENT_SECRET', 'sandbox'),
            ],
            'production' => [
                'merchant_id'        => Api::getValue('MIDTRANS_MERCHANT_ID', 'production'),
                'client_key'         => Api::getValue('MIDTRANS_CLIENT_KEY', 'production'),
                'server_key'         => Api::getValue('MIDTRANS_SERVER_KEY', 'production'),
                'snap_client_id'     => Api::getValue('MIDTRANS_SNAP_CLIENT_ID', 'production'),
                'snap_client_secret' => Api::getValue('MIDTRANS_SNAP_CLIENT_SECRET', 'production'),
            ]
        ];

        // --- TAMBAHAN ARRAY LALAMOVE ---
        // LOG LOG
        $lalamove = [
            'mode' => $lalamoveMode,
            'sandbox' => [
                'api_key'    => Api::getValue('LALAMOVE_API_KEY', 'sandbox'),
                'api_secret' => Api::getValue('LALAMOVE_API_SECRET', 'sandbox'),
            ],
            'production' => [
                'api_key'    => Api::getValue('LALAMOVE_API_KEY', 'production'),
                'api_secret' => Api::getValue('LALAMOVE_API_SECRET', 'production'),
            ]
        ];

        // Tambahkan variabel $dana, $midtrans, dan $lalamove ke compact
        return view('admin.settings.api_settings', compact('kiriminaja', 'tripay', 'doku', 'iak', 'fonnte', 'dharmawisata', 'dana', 'midtrans', 'lalamove'));
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

           // --- TAMBAHAN UPDATE DANA ---
            } elseif ($type === 'dana') {
                $env = $request->dana_mode; // Menangkap 'sandbox' atau 'production' dari form
                $isProdMode = ($env === 'production') ? '1' : '0';

                // Simpan Mode Global (0 untuk Sandbox, 1 untuk Production)
                Api::setValue('dana_production_mode', $isProdMode, 'dana', 'global');

                // Simpan Kredensial sesuai Mode yang dipilih
                if ($env === 'production') {
                    Api::setValue('dana_prod_merchant_id', $request->dana_merchant_id, 'dana', 'production');
                    Api::setValue('dana_prod_client_id', $request->dana_client_id, 'dana', 'production');
                    Api::setValue('dana_prod_client_secret', $request->dana_client_secret, 'dana', 'production');
                    Api::setValue('dana_prod_private_key', $request->dana_private_key, 'dana', 'production');
                    Api::setValue('dana_prod_public_key', $request->dana_public_key, 'dana', 'production'); // <-- TAMBAHKAN BARIS INI
                } else {
                    Api::setValue('dana_sandbox_merchant_id', $request->dana_merchant_id, 'dana', 'sandbox');
                    Api::setValue('dana_sandbox_client_id', $request->dana_client_id, 'dana', 'sandbox');
                    Api::setValue('dana_sandbox_client_secret', $request->dana_client_secret, 'dana', 'sandbox');
                    Api::setValue('dana_sandbox_private_key', $request->dana_private_key, 'dana', 'sandbox');
                    Api::setValue('dana_sandbox_public_key', $request->dana_public_key, 'dana', 'sandbox'); // <-- TAMBAHKAN BARIS INI
                }

            // --- TAMBAHAN UPDATE MIDTRANS ---
            } elseif ($type === 'midtrans') {
                $env = $request->midtrans_mode; // 'sandbox' atau 'production'

                Api::setValue('MIDTRANS_MODE', $env, 'midtrans', 'global');

                Api::setValue('MIDTRANS_MERCHANT_ID', $request->midtrans_merchant_id, 'midtrans', $env);
                Api::setValue('MIDTRANS_CLIENT_KEY', $request->midtrans_client_key, 'midtrans', $env);
                Api::setValue('MIDTRANS_SERVER_KEY', $request->midtrans_server_key, 'midtrans', $env);
                Api::setValue('MIDTRANS_SNAP_CLIENT_ID', $request->midtrans_snap_client_id, 'midtrans', $env);
                Api::setValue('MIDTRANS_SNAP_CLIENT_SECRET', $request->midtrans_snap_client_secret, 'midtrans', $env);
            
            // --- TAMBAHAN UPDATE LALAMOVE ---
            // LOG LOG
            } elseif ($type === 'lalamove') {
                $env = $request->lalamove_mode; // 'sandbox' atau 'production'

                Api::setValue('LALAMOVE_MODE', $env, 'lalamove', 'global');

                Api::setValue('LALAMOVE_API_KEY', $request->lalamove_api_key, 'lalamove', $env);
                Api::setValue('LALAMOVE_API_SECRET', $request->lalamove_api_secret, 'lalamove', $env);
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
                $targetDharmawisata = 'development';
                $targetDana         = '0'; // 0 = Sandbox DANA
                $targetMidtrans     = 'sandbox'; // --- TAMBAHAN MIDTRANS ---
                $targetLalamove     = 'sandbox'; // --- TAMBAHAN LALAMOVE ---
                $label              = 'SANDBOX / STAGING / DEVELOPMENT';
            } else {
                $targetKA           = 'production';
                $targetTripay       = 'production';
                $targetDoku         = 'production';
                $targetIAK          = 'production';
                $targetDharmawisata = 'production';
                $targetDana         = '1'; // 1 = Production DANA
                $targetMidtrans     = 'production'; // --- TAMBAHAN MIDTRANS ---
                $targetLalamove     = 'production'; // --- TAMBAHAN LALAMOVE ---
                $label              = 'PRODUCTION (LIVE)';
            }

            Api::setValue('KIRIMINAJA_MODE', $targetKA, 'kiriminaja', 'global');
            Api::setValue('TRIPAY_MODE', $targetTripay, 'tripay', 'global');
            Api::setValue('DOKU_ENV', $targetDoku, 'doku', 'global');
            Api::setValue('IAK_MODE', $targetIAK, 'iak', 'global');
            Api::setValue('DHARMAWISATA_MODE', $targetDharmawisata, 'dharmawisata', 'global');
            
            // --- TAMBAHAN TOGGLE DANA ---
            Api::setValue('dana_production_mode', $targetDana, 'dana', 'global');

            // --- TAMBAHAN TOGGLE MIDTRANS ---
            Api::setValue('MIDTRANS_MODE', $targetMidtrans, 'midtrans', 'global');

            // --- TAMBAHAN TOGGLE LALAMOVE ---
            // LOG LOG
            Api::setValue('LALAMOVE_MODE', $targetLalamove, 'lalamove', 'global');

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
                $targetDharmawisata = 'production';
                $targetDana         = '1'; // Production DANA
                $targetMidtrans     = 'production'; // --- TAMBAHAN MIDTRANS ---
                $targetLalamove     = 'production'; // --- TAMBAHAN LALAMOVE ---
                $label              = 'PRODUCTION (LIVE)';
            } else {
                $targetKA           = 'staging';
                $targetTripay       = 'sandbox';
                $targetDoku         = 'sandbox';
                $targetIAK          = 'development';
                $targetDharmawisata = 'development';
                $targetDana         = '0'; // Sandbox DANA
                $targetMidtrans     = 'sandbox'; // --- TAMBAHAN MIDTRANS ---
                $targetLalamove     = 'sandbox'; // --- TAMBAHAN LALAMOVE ---
                $label              = 'SANDBOX / MAINTENANCE';
            }

            Api::setValue('KIRIMINAJA_MODE', $targetKA, 'kiriminaja', 'global');
            Api::setValue('TRIPAY_MODE', $targetTripay, 'tripay', 'global');
            Api::setValue('DOKU_ENV', $targetDoku, 'doku', 'global');
            Api::setValue('IAK_MODE', $targetIAK, 'iak', 'global');
            Api::setValue('DHARMAWISATA_MODE', $targetDharmawisata, 'dharmawisata', 'global');
            
            // --- TAMBAHAN TOGGLE API DANA ---
            Api::setValue('dana_production_mode', $targetDana, 'dana', 'global');

            // --- TAMBAHAN TOGGLE API MIDTRANS ---
            Api::setValue('MIDTRANS_MODE', $targetMidtrans, 'midtrans', 'global');

            // --- TAMBAHAN TOGGLE API LALAMOVE ---
            // LOG LOG
            Api::setValue('LALAMOVE_MODE', $targetLalamove, 'lalamove', 'global');

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