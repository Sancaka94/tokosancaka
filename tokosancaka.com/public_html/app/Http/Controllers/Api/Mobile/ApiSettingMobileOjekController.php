<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Api;
use Illuminate\Support\Facades\Log;

class ApiSettingMobileOjekController extends Controller
{
    /**
     * GET /api/mobile/settings/harga-ojek
     * Endpoint untuk menarik data pengaturan tarif ojek ke dalam form aplikasi mobile.
     */
    public function getSettings()
    {
        try {
            $zonasi = [
                'zona_1' => [
                    'wilayah' => Api::getValue('ZONA_1_WILAYAH', 'global', 'Sumatera, Bali, Jawa Timur, Jawa Tengah, Jawa Barat, Yogyakarta, Banten'),
                    'tarif_minimal' => Api::getValue('ZONA_1_TARIF_MINIMAL', 'global', 8000),
                    'tarif_per_km' => Api::getValue('ZONA_1_TARIF_PER_KM', 'global', 2000),
                ],
                'zona_2' => [
                    'wilayah' => Api::getValue('ZONA_2_WILAYAH', 'global', 'Jakarta, Bogor, Depok, Tangerang, Bekasi'),
                    'tarif_minimal' => Api::getValue('ZONA_2_TARIF_MINIMAL', 'global', 10200),
                    'tarif_per_km' => Api::getValue('ZONA_2_TARIF_PER_KM', 'global', 2550),
                ],
                'zona_3' => [
                    'wilayah' => Api::getValue('ZONA_3_WILAYAH', 'global', 'Kalimantan, Sulawesi, Nusa Tenggara, Maluku, Papua'),
                    'tarif_minimal' => Api::getValue('ZONA_3_TARIF_MINIMAL', 'global', 9200),
                    'tarif_per_km' => Api::getValue('ZONA_3_TARIF_PER_KM', 'global', 2300),
                ],
            ];

            $data = [
                'mapbox_public_token' => Api::getValue('MAPBOX_PUBLIC_TOKEN', 'global', ''),
                'mapbox_secret_token' => Api::getValue('MAPBOX_SECRET_TOKEN', 'global', ''),

                'base_fare' => Api::getValue('SANCAKA_EXPRESS_BASE_FARE', 'global', 3000),
                'price_per_km' => Api::getValue('SANCAKA_EXPRESS_PER_KM', 'global', 1000),
                'price_per_kg' => Api::getValue('SANCAKA_EXPRESS_PER_KG', 'global', 1000),
                'volume_divisor' => Api::getValue('SANCAKA_EXPRESS_VOLUME_DIVISOR', 'global', 6000),
                'cod_fee_percent' => Api::getValue('SANCAKA_EXPRESS_COD_FEE_PERCENT', 'global', 3),

                'zonasi' => $zonasi,

                'ojek_base_fare' => Api::getValue('SANCAKA_OJEK_BASE_FARE', 'global', 5000),
                'ojek_price_per_km' => Api::getValue('SANCAKA_OJEK_PER_KM', 'global', 2500),
            ];

            return response()->json([
                'success' => true,
                'data' => $data,
            ], 200);

        } catch (\Exception $e) {
            Log::error("Gagal load setting tarif ojek di API Mobile: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data pengaturan',
            ], 500);
        }
    }

    /**
     * PUT/POST /api/mobile/settings/harga-ojek/update
     * Endpoint untuk menimpa/update data database dari form aplikasi mobile.
     */
    public function updateSettings(Request $request)
    {
        try {
            // 1. Simpan Token Mapbox
            if ($request->has('mapbox_public_token')) {
                Api::setValue('MAPBOX_PUBLIC_TOKEN', trim(strip_tags($request->mapbox_public_token)), 'mapbox', 'global');
            }
            if ($request->has('mapbox_secret_token')) {
                Api::setValue('MAPBOX_SECRET_TOKEN', trim(strip_tags($request->mapbox_secret_token)), 'mapbox', 'global');
            }

            // 2. Simpan Tarif Sancaka Express
            if ($request->has('base_fare')) Api::setValue('SANCAKA_EXPRESS_BASE_FARE', $request->base_fare, 'mapbox', 'global');
            if ($request->has('price_per_km')) Api::setValue('SANCAKA_EXPRESS_PER_KM', $request->price_per_km, 'mapbox', 'global');
            if ($request->has('price_per_kg')) Api::setValue('SANCAKA_EXPRESS_PER_KG', $request->price_per_kg, 'mapbox', 'global');
            if ($request->has('volume_divisor')) Api::setValue('SANCAKA_EXPRESS_VOLUME_DIVISOR', $request->volume_divisor, 'mapbox', 'global');
            if ($request->has('cod_fee_percent')) Api::setValue('SANCAKA_EXPRESS_COD_FEE_PERCENT', $request->cod_fee_percent, 'mapbox', 'global');

            // 3. Simpan Tarif Zonasi Ojol
            if ($request->has('zona_1_wilayah')) {
                Api::setValue('ZONA_1_WILAYAH', $request->zona_1_wilayah, 'mapbox', 'global');
                Api::setValue('ZONA_1_TARIF_MINIMAL', $request->zona_1_tarif_minimal, 'mapbox', 'global');
                Api::setValue('ZONA_1_TARIF_PER_KM', $request->zona_1_tarif_per_km, 'mapbox', 'global');
            }
            if ($request->has('zona_2_wilayah')) {
                Api::setValue('ZONA_2_WILAYAH', $request->zona_2_wilayah, 'mapbox', 'global');
                Api::setValue('ZONA_2_TARIF_MINIMAL', $request->zona_2_tarif_minimal, 'mapbox', 'global');
                Api::setValue('ZONA_2_TARIF_PER_KM', $request->zona_2_tarif_per_km, 'mapbox', 'global');
            }
            if ($request->has('zona_3_wilayah')) {
                Api::setValue('ZONA_3_WILAYAH', $request->zona_3_wilayah, 'mapbox', 'global');
                Api::setValue('ZONA_3_TARIF_MINIMAL', $request->zona_3_tarif_minimal, 'mapbox', 'global');
                Api::setValue('ZONA_3_TARIF_PER_KM', $request->zona_3_tarif_per_km, 'mapbox', 'global');
            }

            // 4. Simpan Tarif Default / Fallback (Ojek Biasa)
            if ($request->has('ojek_base_fare')) Api::setValue('SANCAKA_OJEK_BASE_FARE', $request->ojek_base_fare, 'mapbox', 'global');
            if ($request->has('ojek_price_per_km')) Api::setValue('SANCAKA_OJEK_PER_KM', $request->ojek_price_per_km, 'mapbox', 'global');

            Log::info("Pengaturan Tarif Sancaka & Mapbox berhasil diperbarui via API Mobile.");

            // Mengembalikan respons JSON Success ke React Native
            return response()->json([
                'success' => true,
                'message' => 'Pengaturan Harga Sancaka dan Zonasi berhasil disimpan!'
            ], 200);

        } catch (\Exception $e) {
            Log::error("Gagal update setting harga ojek via API Mobile: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat menyimpan pengaturan.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
