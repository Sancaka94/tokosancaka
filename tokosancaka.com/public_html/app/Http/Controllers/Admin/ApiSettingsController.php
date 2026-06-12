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
        $dharmawisataMode = Api::getValue('DHARMAWISATA_MODE', 'global', 'development');
        $danaProductionMode = Api::getValue('dana_production_mode', 'global', '0');
        $danaMode           = $danaProductionMode == '1' ? 'production' : 'sandbox';
        $midtransMode     = Api::getValue('MIDTRANS_MODE', 'global', 'sandbox');
        $lalamoveMode     = Api::getValue('LALAMOVE_MODE', 'global', 'sandbox');
        $paypalMode       = Api::getValue('PAYPAL_MODE', 'global', 'sandbox');
        $delivereeMode    = Api::getValue('DELIVEREE_MODE', 'global', 'sandbox');

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
                'static_token' => Api::getValue('DHARMAWISATA_STATIC_TOKEN', 'development'),
                'password'     => Api::getValue('DHARMAWISATA_PASSWORD', 'development'),
            ],
            'production' => [
                'user_id'      => Api::getValue('DHARMAWISATA_USER_ID', 'production'),
                'access_token' => Api::getValue('DHARMAWISATA_ACCESS_TOKEN', 'production'),
                'base_url'     => Api::getValue('DHARMAWISATA_BASE_URL', 'production'),
                'static_token' => Api::getValue('DHARMAWISATA_STATIC_TOKEN', 'production'),
                'password'     => Api::getValue('DHARMAWISATA_PASSWORD', 'production'),
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
                'public_key'    => Api::getValue('dana_sandbox_public_key', 'sandbox'),
            ],
            'production' => [
                'merchant_id'   => Api::getValue('dana_prod_merchant_id', 'production'),
                'client_id'     => Api::getValue('dana_prod_client_id', 'production'),
                'client_secret' => Api::getValue('dana_prod_client_secret', 'production'),
                'private_key'   => Api::getValue('dana_prod_private_key', 'production'),
                'public_key'    => Api::getValue('dana_prod_public_key', 'production'),
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

        // --- TAMBAHAN ARRAY PAYPAL ---
        $paypal = [
            'mode' => $paypalMode,
            'sandbox' => [
                'client_id'  => Api::getValue('PAYPAL_CLIENT_ID', 'sandbox'),
                'secret_1'   => Api::getValue('PAYPAL_SECRET_1', 'sandbox'),
                'secret_2'   => Api::getValue('PAYPAL_SECRET_2', 'sandbox'),
                'webhook_id' => Api::getValue('PAYPAL_WEBHOOK_ID', 'sandbox'),
            ],
            'production' => [
                'client_id'  => Api::getValue('PAYPAL_CLIENT_ID', 'production'),
                'secret_1'   => Api::getValue('PAYPAL_SECRET_1', 'production'),
                'secret_2'   => Api::getValue('PAYPAL_SECRET_2', 'production'),
                'webhook_id' => Api::getValue('PAYPAL_WEBHOOK_ID', 'production'),
            ]
        ];

        // --- TAMBAHAN ARRAY DELIVEREE ---
        $deliveree = [
            'mode' => $delivereeMode,
            'sandbox' => [
                'company_id'  => Api::getValue('DELIVEREE_COMPANY_ID', 'sandbox'),
                'api_key'     => Api::getValue('DELIVEREE_API_KEY', 'sandbox'),
                'webhook_url' => Api::getValue('DELIVEREE_WEBHOOK_URL', 'sandbox'),
                'base_url'    => Api::getValue('DELIVEREE_BASE_URL', 'sandbox'), // BARU
            ],
            'production' => [
                'company_id'  => Api::getValue('DELIVEREE_COMPANY_ID', 'production'),
                'api_key'     => Api::getValue('DELIVEREE_API_KEY', 'production'),
                'webhook_url' => Api::getValue('DELIVEREE_WEBHOOK_URL', 'production'),
                'base_url'    => Api::getValue('DELIVEREE_BASE_URL', 'production'), // BARU
            ]
        ];

        return view('admin.settings.api_settings', compact('kiriminaja', 'tripay', 'doku', 'iak', 'fonnte', 'dharmawisata', 'dana', 'midtrans', 'lalamove', 'paypal', 'deliveree'));
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

            } elseif ($type === 'dharmawisata') {
                $env = $request->dharmawisata_mode;
                Api::setValue('DHARMAWISATA_MODE', $env, 'dharmawisata', 'global');
                $baseUrl = $request->dharmawisata_base_url;
                if (empty($baseUrl)) {
                    $baseUrl = 'https://uat-backup.darmawisataindonesiah2h.co.id:7080/h2h/';
                }
                Api::setValue('DHARMAWISATA_USER_ID', $request->dharmawisata_user_id, 'dharmawisata', $env);
                Api::setValue('DHARMAWISATA_ACCESS_TOKEN', $request->dharmawisata_access_token, 'dharmawisata', $env);
                Api::setValue('DHARMAWISATA_BASE_URL', $baseUrl, 'dharmawisata', $env);
                Api::setValue('DHARMAWISATA_STATIC_TOKEN', $request->dharmawisata_static_token, 'dharmawisata', $env);
                Api::setValue('DHARMAWISATA_PASSWORD', $request->dharmawisata_password, 'dharmawisata', $env);

            } elseif ($type === 'fonnte') {
                Api::setValue('FONNTE_API_KEY', $request->fonnte_api_key, 'fonnte', 'global');

            } elseif ($type === 'dana') {
                $env = $request->dana_mode;
                $isProdMode = ($env === 'production') ? '1' : '0';
                Api::setValue('dana_production_mode', $isProdMode, 'dana', 'global');

                if ($env === 'production') {
                    Api::setValue('dana_prod_merchant_id', $request->dana_merchant_id, 'dana', 'production');
                    Api::setValue('dana_prod_client_id', $request->dana_client_id, 'dana', 'production');
                    Api::setValue('dana_prod_client_secret', $request->dana_client_secret, 'dana', 'production');
                    Api::setValue('dana_prod_private_key', $request->dana_private_key, 'dana', 'production');
                    Api::setValue('dana_prod_public_key', $request->dana_public_key, 'dana', 'production');
                } else {
                    Api::setValue('dana_sandbox_merchant_id', $request->dana_merchant_id, 'dana', 'sandbox');
                    Api::setValue('dana_sandbox_client_id', $request->dana_client_id, 'dana', 'sandbox');
                    Api::setValue('dana_sandbox_client_secret', $request->dana_client_secret, 'dana', 'sandbox');
                    Api::setValue('dana_sandbox_private_key', $request->dana_private_key, 'dana', 'sandbox');
                    Api::setValue('dana_sandbox_public_key', $request->dana_public_key, 'dana', 'sandbox');
                }

            } elseif ($type === 'midtrans') {
                $env = $request->midtrans_mode;
                Api::setValue('MIDTRANS_MODE', $env, 'midtrans', 'global');
                Api::setValue('MIDTRANS_MERCHANT_ID', $request->midtrans_merchant_id, 'midtrans', $env);
                Api::setValue('MIDTRANS_CLIENT_KEY', $request->midtrans_client_key, 'midtrans', $env);
                Api::setValue('MIDTRANS_SERVER_KEY', $request->midtrans_server_key, 'midtrans', $env);
                Api::setValue('MIDTRANS_SNAP_CLIENT_ID', $request->midtrans_snap_client_id, 'midtrans', $env);
                Api::setValue('MIDTRANS_SNAP_CLIENT_SECRET', $request->midtrans_snap_client_secret, 'midtrans', $env);
            
            // LOG LOG
            } elseif ($type === 'lalamove') {
                $env = $request->lalamove_mode;
                Api::setValue('LALAMOVE_MODE', $env, 'lalamove', 'global');
                Api::setValue('LALAMOVE_API_KEY', $request->lalamove_api_key, 'lalamove', $env);
                Api::setValue('LALAMOVE_API_SECRET', $request->lalamove_api_secret, 'lalamove', $env);
            
            } elseif ($type === 'paypal') {
                $env = $request->paypal_mode;
                
                Api::setValue('PAYPAL_MODE', $env, 'paypal', 'global');
                Api::setValue('PAYPAL_CLIENT_ID', $request->paypal_client_id, 'paypal', $env);
                Api::setValue('PAYPAL_SECRET_1', $request->paypal_secret_1, 'paypal', $env);
                Api::setValue('PAYPAL_SECRET_2', $request->paypal_secret_2, 'paypal', $env);
                
                if ($request->has('paypal_webhook_id')) {
                    Api::setValue('PAYPAL_WEBHOOK_ID', $request->paypal_webhook_id, 'paypal', $env);
                }

            // --- TAMBAHAN UPDATE DELIVEREE ---
            } elseif ($type === 'deliveree') {
                $env = $request->deliveree_mode;
                Api::setValue('DELIVEREE_MODE', $env, 'deliveree', 'global');
                Api::setValue('DELIVEREE_COMPANY_ID', $request->deliveree_company_id, 'deliveree', $env);
                Api::setValue('DELIVEREE_API_KEY', $request->deliveree_api_key, 'deliveree', $env);
                
                // Set Base URL dinamis. Jika form dikosongkan, gunakan default v10 dari dokumentasi.
                $baseUrl = $request->deliveree_base_url;
                if (empty($baseUrl)) {
                    $baseUrl = ($env === 'production') 
                        ? 'https://api.deliveree.com/public_api/v10' 
                        : 'https://api.sandbox.deliveree.com/public_api/v10';
                }
                Api::setValue('DELIVEREE_BASE_URL', $baseUrl, 'deliveree', $env);

                if ($request->has('deliveree_webhook_url')) {
                    Api::setValue('DELIVEREE_WEBHOOK_URL', $request->deliveree_webhook_url, 'deliveree', $env);
                }
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
                $targetMidtrans     = 'sandbox';
                $targetLalamove     = 'sandbox';
                $targetPaypal       = 'sandbox';
                $targetDeliveree    = 'sandbox'; // TAMBAHAN DELIVEREE
                $label              = 'SANDBOX / STAGING / DEVELOPMENT';
            } else {
                $targetKA           = 'production';
                $targetTripay       = 'production';
                $targetDoku         = 'production';
                $targetIAK          = 'production';
                $targetDharmawisata = 'production';
                $targetDana         = '1'; // 1 = Production DANA
                $targetMidtrans     = 'production';
                $targetLalamove     = 'production';
                $targetPaypal       = 'production';
                $targetDeliveree    = 'production'; // TAMBAHAN DELIVEREE
                $label              = 'PRODUCTION (LIVE)';
            }

            Api::setValue('KIRIMINAJA_MODE', $targetKA, 'kiriminaja', 'global');
            Api::setValue('TRIPAY_MODE', $targetTripay, 'tripay', 'global');
            Api::setValue('DOKU_ENV', $targetDoku, 'doku', 'global');
            Api::setValue('IAK_MODE', $targetIAK, 'iak', 'global');
            Api::setValue('DHARMAWISATA_MODE', $targetDharmawisata, 'dharmawisata', 'global');
            Api::setValue('dana_production_mode', $targetDana, 'dana', 'global');
            Api::setValue('MIDTRANS_MODE', $targetMidtrans, 'midtrans', 'global');
            Api::setValue('DELIVEREE_MODE', $targetDeliveree, 'deliveree', 'global'); // TAMBAHAN DELIVEREE
            
            // LOG LOG
            Api::setValue('LALAMOVE_MODE', $targetLalamove, 'lalamove', 'global');
            Api::setValue('PAYPAL_MODE', $targetPaypal, 'paypal', 'global');

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
                $targetDana         = '1'; 
                $targetMidtrans     = 'production';
                $targetLalamove     = 'production';
                $targetPaypal       = 'production';
                $targetDeliveree    = 'production'; // TAMBAHAN DELIVEREE
                $label              = 'PRODUCTION (LIVE)';
            } else {
                $targetKA           = 'staging';
                $targetTripay       = 'sandbox';
                $targetDoku         = 'sandbox';
                $targetIAK          = 'development';
                $targetDharmawisata = 'development';
                $targetDana         = '0'; 
                $targetMidtrans     = 'sandbox';
                $targetLalamove     = 'sandbox';
                $targetPaypal       = 'sandbox';
                $targetDeliveree    = 'sandbox'; // TAMBAHAN DELIVEREE
                $label              = 'SANDBOX / MAINTENANCE';
            }

            Api::setValue('KIRIMINAJA_MODE', $targetKA, 'kiriminaja', 'global');
            Api::setValue('TRIPAY_MODE', $targetTripay, 'tripay', 'global');
            Api::setValue('DOKU_ENV', $targetDoku, 'doku', 'global');
            Api::setValue('IAK_MODE', $targetIAK, 'iak', 'global');
            Api::setValue('DHARMAWISATA_MODE', $targetDharmawisata, 'dharmawisata', 'global');
            Api::setValue('dana_production_mode', $targetDana, 'dana', 'global');
            Api::setValue('MIDTRANS_MODE', $targetMidtrans, 'midtrans', 'global');
            Api::setValue('DELIVEREE_MODE', $targetDeliveree, 'deliveree', 'global'); // TAMBAHAN DELIVEREE
            
            // LOG LOG
            Api::setValue('LALAMOVE_MODE', $targetLalamove, 'lalamove', 'global');
            Api::setValue('PAYPAL_MODE', $targetPaypal, 'paypal', 'global');

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