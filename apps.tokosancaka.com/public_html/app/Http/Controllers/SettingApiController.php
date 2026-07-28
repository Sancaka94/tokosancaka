<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SettingApi;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Artisan;
// use App\Events\SystemModeUpdated; // Silakan di-uncomment jika kamu butuh event ini

class SettingApiController extends Controller
{
    /**
     * PRIVATE HELPER: Fungsi untuk menyimpan ke database SettingApi
     * Mengubah sistem Api::setValue menjadi SettingApi::updateOrCreate
     */
    private function getApiValue($key, $default = null)
    {
        $data = SettingApi::where('key', $key)->first();
        return $data ? $data->value : $default;
    }

    private function setApiValue($key, $value)
    {
        SettingApi::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    public function index()
    {
        // 1. WAJIB ADA: Ambil semua data setting jadikan array associative
        $settings = SettingApi::pluck('value', 'key')->toArray();
        $danaMode = $settings['dana_production_mode'] ?? '0';

        // 2. Ambil Mode Global yang sedang aktif
        $appDebug           = config('app.debug');
        $kaMode             = $this->getApiValue('KIRIMINAJA_MODE', 'staging');
        $tripayMode         = $this->getApiValue('TRIPAY_MODE', 'sandbox');
        $dokuEnv            = $this->getApiValue('DOKU_ENV', 'sandbox');
        $iakMode            = $this->getApiValue('IAK_MODE', 'development');
        $dharmawisataMode   = $this->getApiValue('DHARMAWISATA_MODE', 'development');
        $danaEnv            = $danaMode == '1' ? 'production' : 'sandbox'; // Pakai $danaEnv untuk array
        $midtransMode       = $this->getApiValue('MIDTRANS_MODE', 'sandbox');
        $lalamoveMode       = $this->getApiValue('LALAMOVE_MODE', 'sandbox');
        $paypalMode         = $this->getApiValue('PAYPAL_MODE', 'sandbox');
        $delivereeMode      = $this->getApiValue('DELIVEREE_MODE', 'sandbox');
        $ipaymuMode         = $this->getApiValue('IPAYMU_MODE', 'sandbox');
        $mandiriMode        = $this->getApiValue('MANDIRI_MODE', 'sandbox');
        $autokirimMode      = $this->getApiValue('AUTOKIRIM_MODE', 'sandbox');

        // KUNCI ANTI CRASH: Paksa ke sandbox kalau databasenya nyangkut di nilai lain
        if (!in_array($mandiriMode, ['sandbox', 'production'])) {
            $mandiriMode = 'sandbox';
        }

        $kiriminaja = [
            'mode' => $kaMode,
            'staging' => [
                'token'    => $this->getApiValue('KIRIMINAJA_TOKEN_STAGING'),
                'base_url' => $this->getApiValue('KIRIMINAJA_BASE_URL_STAGING'),
            ],
            'production' => [
                'token'    => $this->getApiValue('KIRIMINAJA_TOKEN_PRODUCTION'),
                'base_url' => $this->getApiValue('KIRIMINAJA_BASE_URL_PRODUCTION'),
            ]
        ];

        $tripay = [
            'mode' => $tripayMode,
            'sandbox' => [
                'merchant_code' => $this->getApiValue('TRIPAY_MERCHANT_CODE_SANDBOX'),
                'api_key'       => $this->getApiValue('TRIPAY_API_KEY_SANDBOX'),
                'private_key'   => $this->getApiValue('TRIPAY_PRIVATE_KEY_SANDBOX'),
            ],
            'production' => [
                'merchant_code' => $this->getApiValue('TRIPAY_MERCHANT_CODE_PRODUCTION'),
                'api_key'       => $this->getApiValue('TRIPAY_API_KEY_PRODUCTION'),
                'private_key'   => $this->getApiValue('TRIPAY_PRIVATE_KEY_PRODUCTION'),
            ]
        ];

        $doku = [
            'env' => $dokuEnv,
            'sandbox' => [
                'client_id'           => $this->getApiValue('DOKU_CLIENT_ID_SANDBOX'),
                'secret_key'          => $this->getApiValue('DOKU_SECRET_KEY_SANDBOX'),
                'public_key'          => $this->getApiValue('DOKU_PUBLIC_KEY_SANDBOX'),
                'merchant_private_key'=> $this->getApiValue('MERCHANT_PRIVATE_KEY_SANDBOX'),
            ],
            'production' => [
                'client_id'           => $this->getApiValue('DOKU_CLIENT_ID_PRODUCTION'),
                'secret_key'          => $this->getApiValue('DOKU_SECRET_KEY_PRODUCTION'),
                'public_key'          => $this->getApiValue('DOKU_PUBLIC_KEY_PRODUCTION'),
                'merchant_private_key'=> $this->getApiValue('MERCHANT_PRIVATE_KEY_PRODUCTION'),
            ],
            'sac_id' => $this->getApiValue('DOKU_MAIN_SAC_ID'),
        ];

        $iak = [
            'mode' => $iakMode,
            'development' => [
                'user_hp'           => $this->getApiValue('IAK_USER_HP_DEVELOPMENT'),
                'api_key'           => $this->getApiValue('IAK_API_KEY_DEVELOPMENT'),
                'prepaid_base_url'  => $this->getApiValue('IAK_PREPAID_BASE_URL_DEVELOPMENT'),
                'postpaid_base_url' => $this->getApiValue('IAK_POSTPAID_BASE_URL_DEVELOPMENT'),
            ],
            'production' => [
                'user_hp'           => $this->getApiValue('IAK_USER_HP_PRODUCTION'),
                'api_key'           => $this->getApiValue('IAK_API_KEY_PRODUCTION'),
                'prepaid_base_url'  => $this->getApiValue('IAK_PREPAID_BASE_URL_PRODUCTION'),
                'postpaid_base_url' => $this->getApiValue('IAK_POSTPAID_BASE_URL_PRODUCTION'),
            ]
        ];

        $dharmawisata = [
            'mode' => $dharmawisataMode,
            'development' => [
                'user_id'      => $this->getApiValue('DHARMAWISATA_USER_ID_DEVELOPMENT'),
                'access_token' => $this->getApiValue('DHARMAWISATA_ACCESS_TOKEN_DEVELOPMENT'),
                'base_url'     => $this->getApiValue('DHARMAWISATA_BASE_URL_DEVELOPMENT'),
                'static_token' => $this->getApiValue('DHARMAWISATA_STATIC_TOKEN_DEVELOPMENT'),
                'password'     => $this->getApiValue('DHARMAWISATA_PASSWORD_DEVELOPMENT'),
            ],
            'production' => [
                'user_id'      => $this->getApiValue('DHARMAWISATA_USER_ID_PRODUCTION'),
                'access_token' => $this->getApiValue('DHARMAWISATA_ACCESS_TOKEN_PRODUCTION'),
                'base_url'     => $this->getApiValue('DHARMAWISATA_BASE_URL_PRODUCTION'),
                'static_token' => $this->getApiValue('DHARMAWISATA_STATIC_TOKEN_PRODUCTION'),
                'password'     => $this->getApiValue('DHARMAWISATA_PASSWORD_PRODUCTION'),
            ]
        ];

        $fonnte = [
            'api_key' => $this->getApiValue('FONNTE_API_KEY'),
        ];

        $zonasi = [
            'zona_1' => [
                'wilayah' => $this->getApiValue('ZONA_1_WILAYAH', 'Sumatera, Bali, Jawa Timur, Jawa Tengah, Jawa Barat, Yogyakarta, Banten'),
                'tarif_minimal' => $this->getApiValue('ZONA_1_TARIF_MINIMAL', 8000),
                'tarif_per_km' => $this->getApiValue('ZONA_1_TARIF_PER_KM', 2000),
            ],
            'zona_2' => [
                'wilayah' => $this->getApiValue('ZONA_2_WILAYAH', 'Jakarta, Bogor, Depok, Tangerang, Bekasi'),
                'tarif_minimal' => $this->getApiValue('ZONA_2_TARIF_MINIMAL', 10200),
                'tarif_per_km' => $this->getApiValue('ZONA_2_TARIF_PER_KM', 2550),
            ],
            'zona_3' => [
                'wilayah' => $this->getApiValue('ZONA_3_WILAYAH', 'Kalimantan, Sulawesi, Nusa Tenggara, Maluku, Papua'),
                'tarif_minimal' => $this->getApiValue('ZONA_3_TARIF_MINIMAL', 9200),
                'tarif_per_km' => $this->getApiValue('ZONA_3_TARIF_PER_KM', 2300),
            ],
        ];

        $mapbox = [
            'public_token'    => $this->getApiValue('MAPBOX_PUBLIC_TOKEN', env('MAPBOX_PUBLIC_TOKEN')),
            'secret_token'    => $this->getApiValue('MAPBOX_SECRET_TOKEN', env('MAPBOX_SECRET_TOKEN')),
            'base_fare'       => $this->getApiValue('SANCAKA_EXPRESS_BASE_FARE', 3000),
            'price_per_km'    => $this->getApiValue('SANCAKA_EXPRESS_PER_KM', 1000),
            'price_per_kg'    => $this->getApiValue('SANCAKA_EXPRESS_PER_KG', 1000),
            'volume_divisor'  => $this->getApiValue('SANCAKA_EXPRESS_VOLUME_DIVISOR', 6000),
            'cod_fee_percent' => $this->getApiValue('SANCAKA_EXPRESS_COD_FEE_PERCENT', 3),
            'ojek_base_fare'    => $this->getApiValue('SANCAKA_OJEK_BASE_FARE', 5000),
            'ojek_price_per_km' => $this->getApiValue('SANCAKA_OJEK_PER_KM', 2500),
            'zonasi' => $zonasi,
            'komisi' => [
                'admin_type'    => $this->getApiValue('KOMISI_ADMIN_TYPE', 'percent'),
                'admin_amount'  => $this->getApiValue('KOMISI_ADMIN_AMOUNT', 0),
                'driver_type'   => $this->getApiValue('KOMISI_DRIVER_TYPE', 'percent'),
                'driver_amount' => $this->getApiValue('KOMISI_DRIVER_AMOUNT', 10),
                'pajak_percent' => $this->getApiValue('KOMISI_PAJAK_PERCENT', 0),
                'biaya_nominal' => $this->getApiValue('KOMISI_BIAYA_NOMINAL', 0),
                'biaya_ket'     => $this->getApiValue('KOMISI_BIAYA_KETERANGAN', 'Biaya Layanan Sancaka'),
            ]
        ];

        $dana = [
            'mode' => $danaEnv, // <-- PERUBAHAN DI SINI
            'sandbox' => [
                'merchant_id'   => $this->getApiValue('dana_sandbox_merchant_id'),
                'client_id'     => $this->getApiValue('dana_sandbox_client_id'),
                'client_secret' => $this->getApiValue('dana_sandbox_client_secret'),
                'private_key'   => $this->getApiValue('dana_sandbox_private_key'),
                'public_key'    => $this->getApiValue('dana_sandbox_public_key'),
            ],
            'production' => [
                'merchant_id'   => $this->getApiValue('dana_prod_merchant_id'),
                'client_id'     => $this->getApiValue('dana_prod_client_id'),
                'client_secret' => $this->getApiValue('dana_prod_client_secret'),
                'private_key'   => $this->getApiValue('dana_prod_private_key'),
                'public_key'    => $this->getApiValue('dana_prod_public_key'),
            ]
        ];

        $midtrans = [
            'mode' => $midtransMode,
            'sandbox' => [
                'merchant_id'        => $this->getApiValue('MIDTRANS_MERCHANT_ID_SANDBOX'),
                'client_key'         => $this->getApiValue('MIDTRANS_CLIENT_KEY_SANDBOX'),
                'server_key'         => $this->getApiValue('MIDTRANS_SERVER_KEY_SANDBOX'),
                'snap_client_id'     => $this->getApiValue('MIDTRANS_SNAP_CLIENT_ID_SANDBOX'),
                'snap_client_secret' => $this->getApiValue('MIDTRANS_SNAP_CLIENT_SECRET_SANDBOX'),
            ],
            'production' => [
                'merchant_id'        => $this->getApiValue('MIDTRANS_MERCHANT_ID_PRODUCTION'),
                'client_key'         => $this->getApiValue('MIDTRANS_CLIENT_KEY_PRODUCTION'),
                'server_key'         => $this->getApiValue('MIDTRANS_SERVER_KEY_PRODUCTION'),
                'snap_client_id'     => $this->getApiValue('MIDTRANS_SNAP_CLIENT_ID_PRODUCTION'),
                'snap_client_secret' => $this->getApiValue('MIDTRANS_SNAP_CLIENT_SECRET_PRODUCTION'),
            ]
        ];

        $lalamove = [
            'mode' => $lalamoveMode,
            'sandbox' => [
                'api_key'    => $this->getApiValue('LALAMOVE_API_KEY_SANDBOX'),
                'api_secret' => $this->getApiValue('LALAMOVE_API_SECRET_SANDBOX'),
            ],
            'production' => [
                'api_key'    => $this->getApiValue('LALAMOVE_API_KEY_PRODUCTION'),
                'api_secret' => $this->getApiValue('LALAMOVE_API_SECRET_PRODUCTION'),
            ]
        ];

        $paypal = [
            'mode' => $paypalMode,
            'sandbox' => [
                'client_id'  => $this->getApiValue('PAYPAL_CLIENT_ID_SANDBOX'),
                'secret_1'   => $this->getApiValue('PAYPAL_SECRET_1_SANDBOX'),
                'secret_2'   => $this->getApiValue('PAYPAL_SECRET_2_SANDBOX'),
                'webhook_id' => $this->getApiValue('PAYPAL_WEBHOOK_ID_SANDBOX'),
            ],
            'production' => [
                'client_id'  => $this->getApiValue('PAYPAL_CLIENT_ID_PRODUCTION'),
                'secret_1'   => $this->getApiValue('PAYPAL_SECRET_1_PRODUCTION'),
                'secret_2'   => $this->getApiValue('PAYPAL_SECRET_2_PRODUCTION'),
                'webhook_id' => $this->getApiValue('PAYPAL_WEBHOOK_ID_PRODUCTION'),
            ]
        ];

        $deliveree = [
            'mode' => $delivereeMode,
            'sandbox' => [
                'company_id'  => $this->getApiValue('DELIVEREE_COMPANY_ID_SANDBOX'),
                'api_key'     => $this->getApiValue('DELIVEREE_API_KEY_SANDBOX'),
                'webhook_url' => $this->getApiValue('DELIVEREE_WEBHOOK_URL_SANDBOX'),
                'base_url'    => $this->getApiValue('DELIVEREE_BASE_URL_SANDBOX'),
            ],
            'production' => [
                'company_id'  => $this->getApiValue('DELIVEREE_COMPANY_ID_PRODUCTION'),
                'api_key'     => $this->getApiValue('DELIVEREE_API_KEY_PRODUCTION'),
                'webhook_url' => $this->getApiValue('DELIVEREE_WEBHOOK_URL_PRODUCTION'),
                'base_url'    => $this->getApiValue('DELIVEREE_BASE_URL_PRODUCTION'),
            ]
        ];

        $ipaymu = [
            'mode' => $ipaymuMode,
            'sandbox' => [
                'va'      => $this->getApiValue('IPAYMU_VA_SANDBOX'),
                'api_key' => $this->getApiValue('IPAYMU_API_KEY_SANDBOX'),
            ],
            'production' => [
                'va'      => $this->getApiValue('IPAYMU_VA_PRODUCTION'),
                'api_key' => $this->getApiValue('IPAYMU_API_KEY_PRODUCTION'),
            ]
        ];

        $mandiri = [
            'mode' => $mandiriMode,
            'sandbox' => [
                'client_id'     => $this->getApiValue('MANDIRI_CLIENT_ID_SANDBOX'),
                'client_secret' => $this->getApiValue('MANDIRI_CLIENT_SECRET_SANDBOX'),
                'partner_id'    => $this->getApiValue('MANDIRI_PARTNER_ID_SANDBOX'),
                'private_key'   => $this->getApiValue('MANDIRI_PRIVATE_KEY_SANDBOX'),
            ],
            'production' => [
                'client_id'     => $this->getApiValue('MANDIRI_CLIENT_ID_PRODUCTION'),
                'client_secret' => $this->getApiValue('MANDIRI_CLIENT_SECRET_PRODUCTION'),
                'partner_id'    => $this->getApiValue('MANDIRI_PARTNER_ID_PRODUCTION'),
                'private_key'   => $this->getApiValue('MANDIRI_PRIVATE_KEY_PRODUCTION'),
            ]
        ];

        $autokirim = [
            'mode' => $autokirimMode,
            'sandbox' => [
                'token'    => $this->getApiValue('AUTOKIRIM_TOKEN_SANDBOX'),
                'base_url' => $this->getApiValue('AUTOKIRIM_BASE_URL_SANDBOX', 'https://api-dev.autokirim.com'),
            ],
            'production' => [
                'token'    => $this->getApiValue('AUTOKIRIM_TOKEN_PRODUCTION'),
                'base_url' => $this->getApiValue('AUTOKIRIM_BASE_URL_PRODUCTION'),
            ]
        ];

        return view('admin.settingapi.index', compact('appDebug', 'kiriminaja', 'tripay', 'doku', 'iak', 'fonnte', 'dharmawisata', 'dana', 'midtrans', 'lalamove', 'paypal', 'deliveree', 'ipaymu', 'mandiri', 'mapbox', 'autokirim', 'danaMode', 'settings'));
    }

    public function update(Request $request)
    {
        $type = $request->input('type');

        try {
            if ($type === 'kiriminaja') {
                $env = $request->kiriminaja_mode;
                $this->setApiValue('KIRIMINAJA_MODE', $env);
                $baseUrl = $request->kiriminaja_base_url;
                if (empty($baseUrl)) {
                    $baseUrl = ($env === 'production') ? 'https://client.kiriminaja.com' : 'https://tdev.kiriminaja.com';
                }
                $this->setApiValue('KIRIMINAJA_TOKEN_'.strtoupper($env), $request->kiriminaja_token);
                $this->setApiValue('KIRIMINAJA_BASE_URL_'.strtoupper($env), $baseUrl);

            } elseif ($type === 'tripay') {
                $env = $request->tripay_mode;
                $this->setApiValue('TRIPAY_MODE', $env);
                $this->setApiValue('TRIPAY_MERCHANT_CODE_'.strtoupper($env), $request->tripay_merchant_code);
                $this->setApiValue('TRIPAY_API_KEY_'.strtoupper($env), $request->tripay_api_key);
                $this->setApiValue('TRIPAY_PRIVATE_KEY_'.strtoupper($env), $request->tripay_private_key);

            } elseif ($type === 'doku') {
                $env = $request->doku_env;
                $this->setApiValue('DOKU_ENV', $env);
                $this->setApiValue('DOKU_CLIENT_ID_'.strtoupper($env), $request->doku_client_id);
                $this->setApiValue('DOKU_SECRET_KEY_'.strtoupper($env), $request->doku_secret_key);
                $this->setApiValue('DOKU_PUBLIC_KEY_'.strtoupper($env), $request->doku_public_key);
                $this->setApiValue('MERCHANT_PRIVATE_KEY_'.strtoupper($env), $request->merchant_private_key);

                if ($request->has('doku_main_sac_id')) {
                    $this->setApiValue('DOKU_MAIN_SAC_ID', $request->doku_main_sac_id);
                }

            } elseif ($type === 'iak') {
                $env = $request->iak_mode;
                $this->setApiValue('IAK_MODE', $env);
                $prepaidUrl = $request->iak_prepaid_base_url;
                if (empty($prepaidUrl)) {
                    $prepaidUrl = ($env === 'production') ? 'https://prepaid.iak.id' : 'https://prepaid.iak.dev';
                }
                $postpaidUrl = $request->iak_postpaid_base_url;
                if (empty($postpaidUrl)) {
                    $postpaidUrl = ($env === 'production') ? 'https://mobilepulsa.net' : 'https://testpostpaid.mobilepulsa.net';
                }
                $this->setApiValue('IAK_USER_HP_'.strtoupper($env), $request->iak_user_hp);
                $this->setApiValue('IAK_API_KEY_'.strtoupper($env), $request->iak_api_key);
                $this->setApiValue('IAK_PREPAID_BASE_URL_'.strtoupper($env), $prepaidUrl);
                $this->setApiValue('IAK_POSTPAID_BASE_URL_'.strtoupper($env), $postpaidUrl);

            } elseif ($type === 'dharmawisata') {
                $env = $request->dharmawisata_mode;
                $this->setApiValue('DHARMAWISATA_MODE', $env);

                $baseUrl = $request->dharmawisata_base_url;
                if (empty($baseUrl)) {
                    $baseUrl = ($env === 'production')
                        ? 'https://www.darmawisataindonesiah2h.co.id/'
                        : 'https://uat-backup.darmawisataindonesiah2h.co.id:7080/h2h/';
                }

                $this->setApiValue('DHARMAWISATA_USER_ID_'.strtoupper($env), $request->dharmawisata_user_id);
                $this->setApiValue('DHARMAWISATA_ACCESS_TOKEN_'.strtoupper($env), $request->dharmawisata_access_token);
                $this->setApiValue('DHARMAWISATA_BASE_URL_'.strtoupper($env), $baseUrl);
                $this->setApiValue('DHARMAWISATA_STATIC_TOKEN_'.strtoupper($env), $request->dharmawisata_static_token);
                $this->setApiValue('DHARMAWISATA_PASSWORD_'.strtoupper($env), $request->dharmawisata_password);

            } elseif ($type === 'fonnte') {
                $this->setApiValue('FONNTE_API_KEY', $request->fonnte_api_key);

            // --- MAPBOX & SANCAKA EXPRESS ---
            } elseif ($type === 'mapbox') {
                $this->setApiValue('MAPBOX_PUBLIC_TOKEN', trim(strip_tags($request->mapbox_public_token)));
                $this->setApiValue('MAPBOX_SECRET_TOKEN', trim(strip_tags($request->mapbox_secret_token)));

                $this->setApiValue('SANCAKA_EXPRESS_BASE_FARE', $request->base_fare);
                $this->setApiValue('SANCAKA_EXPRESS_PER_KM', $request->price_per_km);
                $this->setApiValue('SANCAKA_EXPRESS_PER_KG', $request->price_per_kg);
                $this->setApiValue('SANCAKA_EXPRESS_VOLUME_DIVISOR', $request->volume_divisor);
                $this->setApiValue('SANCAKA_EXPRESS_COD_FEE_PERCENT', $request->cod_fee_percent);

                if ($request->has('zona_1_wilayah')) {
                    $this->setApiValue('ZONA_1_WILAYAH', $request->zona_1_wilayah);
                    $this->setApiValue('ZONA_1_TARIF_MINIMAL', $request->zona_1_tarif_minimal);
                    $this->setApiValue('ZONA_1_TARIF_PER_KM', $request->zona_1_tarif_per_km);
                }
                if ($request->has('zona_2_wilayah')) {
                    $this->setApiValue('ZONA_2_WILAYAH', $request->zona_2_wilayah);
                    $this->setApiValue('ZONA_2_TARIF_MINIMAL', $request->zona_2_tarif_minimal);
                    $this->setApiValue('ZONA_2_TARIF_PER_KM', $request->zona_2_tarif_per_km);
                }
                if ($request->has('zona_3_wilayah')) {
                    $this->setApiValue('ZONA_3_WILAYAH', $request->zona_3_wilayah);
                    $this->setApiValue('ZONA_3_TARIF_MINIMAL', $request->zona_3_tarif_minimal);
                    $this->setApiValue('ZONA_3_TARIF_PER_KM', $request->zona_3_tarif_per_km);
                }

                if ($request->has('ojek_base_fare')) {
                    $this->setApiValue('SANCAKA_OJEK_BASE_FARE', $request->ojek_base_fare);
                }
                if ($request->has('ojek_price_per_km')) {
                    $this->setApiValue('SANCAKA_OJEK_PER_KM', $request->ojek_price_per_km);
                }

                if ($request->has('komisi_admin_type')) {
                    $this->setApiValue('KOMISI_ADMIN_TYPE', $request->komisi_admin_type);
                    $this->setApiValue('KOMISI_ADMIN_AMOUNT', $request->komisi_admin_amount);
                    $this->setApiValue('KOMISI_DRIVER_TYPE', $request->komisi_driver_type);
                    $this->setApiValue('KOMISI_DRIVER_AMOUNT', $request->komisi_driver_amount);
                    $this->setApiValue('KOMISI_PAJAK_PERCENT', $request->komisi_pajak_percent);
                    $this->setApiValue('KOMISI_BIAYA_NOMINAL', $request->komisi_biaya_nominal);
                    $this->setApiValue('KOMISI_BIAYA_KETERANGAN', $request->komisi_biaya_ket);
                }

                Log::info("LOG LOG: Pengaturan Mapbox & Sancaka Express (Zonasi) berhasil diperbarui.");

            } elseif ($type === 'dana') {
                $env = $request->dana_mode;
                $isProdMode = ($env === 'production') ? '1' : '0';
                $this->setApiValue('dana_production_mode', $isProdMode);

                if ($env === 'production') {
                    $this->setApiValue('dana_prod_merchant_id', $request->dana_merchant_id);
                    $this->setApiValue('dana_prod_client_id', $request->dana_client_id);
                    $this->setApiValue('dana_prod_client_secret', $request->dana_client_secret);
                    $this->setApiValue('dana_prod_private_key', $request->dana_private_key);
                    $this->setApiValue('dana_prod_public_key', $request->dana_public_key);
                } else {
                    $this->setApiValue('dana_sandbox_merchant_id', $request->dana_merchant_id);
                    $this->setApiValue('dana_sandbox_client_id', $request->dana_client_id);
                    $this->setApiValue('dana_sandbox_client_secret', $request->dana_client_secret);
                    $this->setApiValue('dana_sandbox_private_key', $request->dana_private_key);
                    $this->setApiValue('dana_sandbox_public_key', $request->dana_public_key);
                }

            } elseif ($type === 'midtrans') {
                $env = $request->midtrans_mode;
                $this->setApiValue('MIDTRANS_MODE', $env);
                $this->setApiValue('MIDTRANS_MERCHANT_ID_'.strtoupper($env), $request->midtrans_merchant_id);
                $this->setApiValue('MIDTRANS_CLIENT_KEY_'.strtoupper($env), $request->midtrans_client_key);
                $this->setApiValue('MIDTRANS_SERVER_KEY_'.strtoupper($env), $request->midtrans_server_key);
                $this->setApiValue('MIDTRANS_SNAP_CLIENT_ID_'.strtoupper($env), $request->midtrans_snap_client_id);
                $this->setApiValue('MIDTRANS_SNAP_CLIENT_SECRET_'.strtoupper($env), $request->midtrans_snap_client_secret);

            } elseif ($type === 'lalamove') {
                $env = $request->lalamove_mode;
                $this->setApiValue('LALAMOVE_MODE', $env);
                $this->setApiValue('LALAMOVE_API_KEY_'.strtoupper($env), $request->lalamove_api_key);
                $this->setApiValue('LALAMOVE_API_SECRET_'.strtoupper($env), $request->lalamove_api_secret);

            } elseif ($type === 'paypal') {
                $env = $request->paypal_mode;
                $this->setApiValue('PAYPAL_MODE', $env);
                $this->setApiValue('PAYPAL_CLIENT_ID_'.strtoupper($env), $request->paypal_client_id);
                $this->setApiValue('PAYPAL_SECRET_1_'.strtoupper($env), $request->paypal_secret_1);
                $this->setApiValue('PAYPAL_SECRET_2_'.strtoupper($env), $request->paypal_secret_2);
                if ($request->has('paypal_webhook_id')) {
                    $this->setApiValue('PAYPAL_WEBHOOK_ID_'.strtoupper($env), $request->paypal_webhook_id);
                }

            } elseif ($type === 'deliveree') {
                $env = $request->deliveree_mode;
                $this->setApiValue('DELIVEREE_MODE', $env);
                $this->setApiValue('DELIVEREE_COMPANY_ID_'.strtoupper($env), $request->deliveree_company_id);
                $this->setApiValue('DELIVEREE_API_KEY_'.strtoupper($env), $request->deliveree_api_key);

                $baseUrl = $request->deliveree_base_url;
                if (empty($baseUrl)) {
                    $baseUrl = ($env === 'production')
                        ? 'https://api.deliveree.com/public_api/v10'
                        : 'https://api.sandbox.deliveree.com/public_api/v10';
                }
                $this->setApiValue('DELIVEREE_BASE_URL_'.strtoupper($env), $baseUrl);

                if ($request->has('deliveree_webhook_url')) {
                    $this->setApiValue('DELIVEREE_WEBHOOK_URL_'.strtoupper($env), $request->deliveree_webhook_url);
                }

            } elseif ($type === 'ipaymu') {
                $env = $request->ipaymu_mode;
                $this->setApiValue('IPAYMU_MODE', $env);
                $this->setApiValue('IPAYMU_VA_'.strtoupper($env), $request->ipaymu_va);
                $this->setApiValue('IPAYMU_API_KEY_'.strtoupper($env), $request->ipaymu_api_key);

            } elseif ($type === 'mandiri') {
                $env = $request->mandiri_mode;
                if (empty($env) || !in_array($env, ['sandbox', 'production'])) {
                    $env = 'sandbox';
                }
                $this->setApiValue('MANDIRI_MODE', $env);
                $this->setApiValue('MANDIRI_CLIENT_ID_'.strtoupper($env), trim(strip_tags($request->mandiri_client_id)));
                $this->setApiValue('MANDIRI_CLIENT_SECRET_'.strtoupper($env), trim(strip_tags($request->mandiri_client_secret)));
                $this->setApiValue('MANDIRI_PARTNER_ID_'.strtoupper($env), trim(strip_tags($request->mandiri_partner_id)));
                if ($request->has('mandiri_private_key')) {
                    $this->setApiValue('MANDIRI_PRIVATE_KEY_'.strtoupper($env), $request->mandiri_private_key);
                }

            } elseif ($type === 'autokirim') {
                $env = $request->autokirim_mode;
                $this->setApiValue('AUTOKIRIM_MODE', $env);

                $baseUrl = $request->autokirim_base_url;
                if (empty($baseUrl)) {
                    $baseUrl = ($env === 'production') ? 'https://api.autokirim.com' : 'https://api-dev.autokirim.com';
                }

                $this->setApiValue('AUTOKIRIM_TOKEN_'.strtoupper($env), $request->autokirim_token);
                $this->setApiValue('AUTOKIRIM_BASE_URL_'.strtoupper($env), $baseUrl);
            }

            Log::info("Konfigurasi API {$type} berhasil disimpan.");

            // ROUTE BALIK SESUAI VIEW
            return back()->with('success', 'Konfigurasi ' . strtoupper($type) . ' berhasil diperbarui untuk mode ' . strtoupper($request->input("{$type}_mode") ?? 'GLOBAL') . '.');

        } catch (\Exception $e) {
            Log::error("Gagal menyimpan pengaturan API {$type}: " . $e->getMessage());
            return back()->with('error', 'Gagal menyimpan: ' . $e->getMessage());
        }
    }

    public function toggle(Request $request)
    {
        try {
            $currentMode = SettingApi::where('key', 'KIRIMINAJA_MODE')->value('value') ?? 'staging';

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
                $targetDeliveree    = 'sandbox';
                $targetIpaymu       = 'sandbox';
                $targetMandiri      = 'sandbox';
                $targetAutokirim    = 'sandbox';
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
                $targetDeliveree    = 'production';
                $targetIpaymu       = 'production';
                $targetMandiri      = 'production';
                $targetAutokirim    = 'production';
                $label              = 'PRODUCTION (LIVE)';
            }

            $this->setApiValue('KIRIMINAJA_MODE', $targetKA);
            $this->setApiValue('TRIPAY_MODE', $targetTripay);
            $this->setApiValue('DOKU_ENV', $targetDoku);
            $this->setApiValue('IAK_MODE', $targetIAK);
            $this->setApiValue('DHARMAWISATA_MODE', $targetDharmawisata);
            $this->setApiValue('dana_production_mode', $targetDana);
            $this->setApiValue('MIDTRANS_MODE', $targetMidtrans);
            $this->setApiValue('DELIVEREE_MODE', $targetDeliveree);
            $this->setApiValue('IPAYMU_MODE', $targetIpaymu);
            $this->setApiValue('MANDIRI_MODE', $targetMandiri);
            $this->setApiValue('LALAMOVE_MODE', $targetLalamove);
            $this->setApiValue('PAYPAL_MODE', $targetPaypal);
            $this->setApiValue('AUTOKIRIM_MODE', $targetAutokirim);

            Log::info("Sistem API Global Mode diubah secara manual ke: {$label}");

            return back()->with('success', "Mode API berhasil diubah ke: <b>$label</b>");

        } catch (\Exception $e) {
            Log::error("Gagal melakukan toggle mode API: " . $e->getMessage());
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
                $targetDeliveree    = 'production';
                $targetIpaymu       = 'production';
                $targetMandiri      = 'production';
                $targetAutokirim    = 'production';
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
                $targetDeliveree    = 'sandbox';
                $targetIpaymu       = 'sandbox';
                $targetMandiri      = 'sandbox';
                $targetAutokirim    = 'sandbox';
                $label              = 'SANDBOX / MAINTENANCE';
            }

            $this->setApiValue('KIRIMINAJA_MODE', $targetKA);
            $this->setApiValue('TRIPAY_MODE', $targetTripay);
            $this->setApiValue('DOKU_ENV', $targetDoku);
            $this->setApiValue('IAK_MODE', $targetIAK);
            $this->setApiValue('DHARMAWISATA_MODE', $targetDharmawisata);
            $this->setApiValue('dana_production_mode', $targetDana);
            $this->setApiValue('MIDTRANS_MODE', $targetMidtrans);
            $this->setApiValue('DELIVEREE_MODE', $targetDeliveree);
            $this->setApiValue('IPAYMU_MODE', $targetIpaymu);
            $this->setApiValue('MANDIRI_MODE', $targetMandiri);
            $this->setApiValue('LALAMOVE_MODE', $targetLalamove);
            $this->setApiValue('PAYPAL_MODE', $targetPaypal);
            $this->setApiValue('AUTOKIRIM_MODE', $targetAutokirim);

            Log::info("Sistem API Mode di-toggle via API ke: {$label}");

            return response()->json([
                'success' => true,
                'message' => "Sistem berhasil diubah ke mode $label",
                'mode'    => $targetKA
            ], 200);

        } catch (\Exception $e) {
            Log::error("Gagal melakukan toggle mode API (AJAX): " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah database: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getSystemMode()
    {
        try {
            $currentMode = SettingApi::where('key', 'KIRIMINAJA_MODE')->value('value') ?? 'staging';

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

    private function setEnvValue($key, $value)
    {
        $path = base_path('.env');

        if (file_exists($path)) {
            $valueString = $value ? 'true' : 'false';
            $envContent = file_get_contents($path);
            $envContent = preg_replace("/^{$key}=.*/m", "{$key}={$valueString}", $envContent);
            file_put_contents($path, $envContent);
        }
    }

    public function toggleAppDebug(Request $request)
    {
        try {
            $isDebug = filter_var($request->input('app_debug'), FILTER_VALIDATE_BOOLEAN);
            $this->setEnvValue('APP_DEBUG', $isDebug);
            Artisan::call('config:clear');

            $statusLabel = $isDebug ? 'AKTIF (TRUE)' : 'MATI (FALSE)';
            Log::info("APP_DEBUG berhasil diubah menjadi: {$statusLabel} oleh sistem.");

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Laravel Debugger berhasil diubah menjadi $statusLabel"
                ], 200);
            }

            return back()->with('success', "Laravel Debugger berhasil diubah menjadi $statusLabel");

        } catch (\Exception $e) {
            Log::error("Gagal mengubah APP_DEBUG: " . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengubah APP_DEBUG: ' . $e->getMessage()
                ], 500);
            }
            return back()->with('error', 'Gagal mengubah APP_DEBUG: ' . $e->getMessage());
        }
    }

    private function getRegionFromCoordinates($lng, $lat, $token)
    {
        try {
            $url = "https://api.mapbox.com/geocoding/v5/mapbox.places/{$lng},{$lat}.json";
            $response = Http::get($url, [
                'access_token' => $token,
                'types' => 'region,place',
                'limit' => 2
            ]);

            if ($response->successful() && !empty($response['features'])) {
                $regions = [];
                foreach ($response['features'] as $feature) {
                    $regions[] = strtolower($feature['text']);
                }
                return $regions;
            }
        } catch (\Exception $e) {
            Log::error("LOG LOG: [API MAPBOX] Gagal Reverse Geocoding: " . $e->getMessage());
        }
        return [];
    }

    public function cek_tarif(Request $request)
    {
        Log::info("=== [API MAPBOX] REQUEST CEK TARIF MASUK ===");
        Log::info("LOG LOG: Payload:", $request->all());

        $latAsal = $request->input('sender_lat');
        $lngAsal = $request->input('sender_lng');
        $latTujuan = $request->input('receiver_lat');
        $lngTujuan = $request->input('receiver_lng');
        $layanan = $request->input('layanan');
        $beratGram = (float) $request->input('weight', 1000);

        if (!$latAsal || !$lngAsal || !$latTujuan || !$lngTujuan) {
            Log::warning("LOG LOG: [API MAPBOX] Koordinat tidak lengkap.");
            return response()->json(['status' => false, 'message' => 'Koordinat tidak lengkap.']);
        }

        // Hindari query berulang
        $settings = SettingApi::pluck('value', 'key')->toArray();
        $mapboxToken = $settings['MAPBOX_SECRET_TOKEN'] ?? env('MAPBOX_TOKEN');

        if (empty($mapboxToken)) {
            Log::error("LOG LOG: [API MAPBOX] Mapbox Token kosong di database!");
        }

        // 1. Tembak rute ke Mapbox
        $url = "https://api.mapbox.com/directions/v5/mapbox/driving/{$lngAsal},{$latAsal};{$lngTujuan},{$latTujuan}";

        try {
            $response = Http::get($url, [
                'access_token' => $mapboxToken,
                'geometries'   => 'geojson',
                'overview'     => 'simplified'
            ]);

            if (!$response->successful() || empty($response['routes'][0])) {
                Log::error("LOG LOG: [API MAPBOX] Gagal Merespons: ", $response->json() ?? []);
                return response()->json(['status' => false, 'message' => 'Gagal mendapatkan rute dari Mapbox']);
            }

            $route = $response['routes'][0];
            $distanceKm = $route['distance'] / 1000;
            $durationMin = ceil($route['duration'] / 60);

            Log::info("LOG LOG: [API MAPBOX] Jarak: {$distanceKm} KM | Waktu: {$durationMin} Menit");

            if ($layanan == 'ojek_online') {
                // TAHAP 2: LOGIKA ZONASI (100% DINAMIS)
                $zona1Wilayah = strtolower($settings['ZONA_1_WILAYAH'] ?? 'sumatera, bali, jawa timur, jawa tengah, jawa barat, yogyakarta, banten');
                $zona2Wilayah = strtolower($settings['ZONA_2_WILAYAH'] ?? 'jakarta, bogor, depok, tangerang, bekasi, jabodetabek');
                $zona3Wilayah = strtolower($settings['ZONA_3_WILAYAH'] ?? 'kalimantan, sulawesi, nusa tenggara, maluku, papua');

                // Deteksi Wilayah dari Koordinat
                $detectedRegions = $this->getRegionFromCoordinates($lngAsal, $latAsal, $mapboxToken);
                Log::info("LOG LOG: Wilayah Terdeteksi: ", $detectedRegions);

                $selectedZone = null;
                foreach ($detectedRegions as $region) {
                    if (str_contains($zona2Wilayah, $region)) {
                        $selectedZone = 2; break;
                    } elseif (str_contains($zona1Wilayah, $region)) {
                        $selectedZone = 1; break;
                    } elseif (str_contains($zona3Wilayah, $region)) {
                        $selectedZone = 3; break;
                    }
                }

                if ($selectedZone === 2) {
                    Log::info("LOG LOG: Masuk Zona II");
                    $baseFare   = (float) ($settings['ZONA_2_TARIF_MINIMAL'] ?? 10200);
                    $pricePerKm = (float) ($settings['ZONA_2_TARIF_PER_KM'] ?? 2550);
                } elseif ($selectedZone === 3) {
                    Log::info("LOG LOG: Masuk Zona III");
                    $baseFare   = (float) ($settings['ZONA_3_TARIF_MINIMAL'] ?? 9200);
                    $pricePerKm = (float) ($settings['ZONA_3_TARIF_PER_KM'] ?? 2300);
                } elseif ($selectedZone === 1) {
                    Log::info("LOG LOG: Masuk Zona I");
                    $baseFare   = (float) ($settings['ZONA_1_TARIF_MINIMAL'] ?? 8000);
                    $pricePerKm = (float) ($settings['ZONA_1_TARIF_PER_KM'] ?? 2000);
                } else {
                    Log::info("LOG LOG: Zona tidak terdeteksi, menggunakan fallback default");
                    $baseFare   = (float) ($settings['SANCAKA_OJEK_BASE_FARE'] ?? 8000);
                    $pricePerKm = (float) ($settings['SANCAKA_OJEK_PER_KM'] ?? 2000);
                }

                $calculatedFare = $distanceKm * $pricePerKm;
                $totalCost = ($calculatedFare < $baseFare) ? $baseFare : $calculatedFare;

            } else {
                // LAYANAN SAMEDAY / EXPRESS
                $baseFare = (float) ($settings['SANCAKA_EXPRESS_BASE_FARE'] ?? 3000);
                $pricePerKm = (float) ($settings['SANCAKA_EXPRESS_PER_KM'] ?? 1000);
                $pricePerKg = (float) ($settings['SANCAKA_EXPRESS_PER_KG'] ?? 1000);

                $weightKg = max(1, ceil($beratGram / 1000));
                $totalCost = $baseFare + ($distanceKm * $pricePerKm) + ($weightKg * $pricePerKg);
            }

            $finalCost = (int) (ceil($totalCost / 500) * 500);

            Log::info("LOG LOG: Tarif Final Dihitung: Rp " . $finalCost);

            return response()->json([
                'status' => true,
                'data' => [
                    'jarak_km' => round($distanceKm, 2),
                    'waktu_menit' => $durationMin,
                    'tarif_final' => $finalCost
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("LOG LOG: [API MAPBOX] EXCEPTION CRASH: " . $e->getMessage() . " | Trace: " . $e->getTraceAsString());
            return response()->json([
                'status' => false,
                'message' => 'Internal Server Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
