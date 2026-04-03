<?php

namespace App\Http\Controllers\Api\Mobile; // Pastikan namespace ini sesuai dengan folder Anda

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Services\KiriminAjaService; // Pastikan Anda sudah membuat service ini
use Exception;
use Illuminate\Support\Facades\Http;

class OngkirController extends Controller
{
    protected $kiriminAjaService;

    public function __construct(KiriminAjaService $kiriminAjaService)
    {
        $this->kiriminAjaService = $kiriminAjaService;
    }

    /**
     * Menggunakan OpenStreetMap Nominatim untuk Geocoding (Mencari Latitude/Longitude dari Alamat Teks)
     */
    public function geocode($address)
    {
        $url = "https://nominatim.openstreetmap.org/search";

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'TokoSancakaApp/1.0 (support@tokosancaka.com)',
                'Accept'     => 'application/json',
            ])->timeout(10)->get($url, [
                'q'          => $address,
                'format'     => 'json',
                'limit'      => 1,
                'countrycodes' => 'id'
            ]);

            $data = $response->json();

            if ($response->successful() && !empty($data) && isset($data[0])) {
                return [
                    'lat' => (float) $data[0]['lat'],
                    'lon' => (float) $data[0]['lon'],
                ];
            }

            Log::warning('Geocoding (Nominatim) gagal atau kosong', [
                'address' => $address, 'status'  => $response->status(), 'body'    => $response->body(),
            ]);
            return null;
        } catch (Exception $e) {
            Log::error('Geocoding (Nominatim) Exception', [
                'address' => $address, 'error'   => $e->getMessage()
            ]);
            return null;
        }
    }


    /**
     * Menangani pengecekan ongkos kirim (Menggabungkan Layanan Express/Cargo & Instant)
     */
    public function checkCost(Request $request)
    {
        Log::info('--- MEMULAI CEK ONGKIR (API MOBILE) ---');
        Log::info('Payload Diterima: ' . json_encode($request->all()));

        // 1. Validasi Input dari Mobile App
        $validator = Validator::make($request->all(), [
            // Fields untuk Express/Cargo
            'origin_id' => 'required',
            'origin_subdistrict_id' => 'required',
            'destination_id' => 'required',
            'destination_subdistrict_id' => 'required',
            'weight' => 'required|numeric|min:1',
            'length' => 'nullable|numeric|min:1',
            'width' => 'nullable|numeric|min:1',
            'height' => 'nullable|numeric|min:1',
            'item_value' => 'nullable|numeric',
            'insurance' => 'nullable',
            'service_type' => 'nullable|string',

            // Fields untuk Instant (Geocode)
            'origin_text' => 'required|string',
            'destination_text' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        try {
            $payload = $validator->validated();

            $instantServices = [];
            $expressCargoServices = [];

            // Ambil nilai barang asli (Default 0 jika tidak diisi)
            $itemValue = (int)($payload['item_value'] ?? 0);

            // =========================================================================
            // A. PANGGIL LAYANAN EXPRESS & CARGO (KiriminAja v6.1)
            // =========================================================================
            try {
                Log::info('Mencoba API Express/Cargo...');
                $expressCargoResponse = $this->kiriminAjaService->getExpressPricing(
                    $payload['origin_id'],
                    $payload['origin_subdistrict_id'],
                    $payload['destination_id'],
                    $payload['destination_subdistrict_id'],
                    $payload['weight'],
                    $payload['length'] ?? 1,
                    $payload['width'] ?? 1,
                    $payload['height'] ?? 1,
                    $itemValue,
                    null,
                    'regular', // atau 'trucking' jika cargo
                    $request->has('insurance') ? 1 : 0
                );

                if (isset($expressCargoResponse['status']) && $expressCargoResponse['status'] === true && isset($expressCargoResponse['results'])) {
                    $expressCargoServices = $expressCargoResponse['results'];
                    $finalWeight = $expressCargoResponse['final_weight'] ?? $payload['weight'];
                    Log::info('API Express/Cargo BERHASIL. Ditemukan ' . count($expressCargoServices) . ' layanan.');
                } else {
                     Log::warning('API Express/Cargo GAGAL.', $expressCargoResponse ?? []);
                }

            } catch (Exception $e) {
                Log::error('KiriminAja Express/Cargo Cost Error: ' . $e->getMessage(), $payload);
            }

            // =========================================================================
            // B. PANGGIL LAYANAN INSTANT (Membutuhkan Konversi Alamat ke Koordinat)
            // =========================================================================
            $instantItemValue = ($itemValue < 1) ? 1 : $itemValue; // Minimal 1 untuk Instant

            Log::info('Mencoba Geocode Asal: ' . $payload['origin_text']);
            $originCoords = $this->geocode($payload['origin_text']);

            Log::info('Mencoba Geocode Tujuan: ' . $payload['destination_text']);
            $destinationCoords = $this->geocode($payload['destination_text']);

            if ($originCoords && $destinationCoords) {
                Log::info('Geocode BERHASIL.');

                try {
                    $instantResponse = $this->kiriminAjaService->getInstantPricing(
                        $originCoords['lat'],
                        $originCoords['lon'],
                        $payload['origin_text'],
                        $destinationCoords['lat'],
                        $destinationCoords['lon'],
                        $payload['destination_text'],
                        $payload['weight'],
                        $instantItemValue
                    );

                    if (isset($instantResponse['status']) && $instantResponse['status'] === true) {
                        $rawInstantData = $instantResponse['result'] ?? [];
                        // Format hasil agar siap dikonsumsi React Native
                        $instantServices = $this->formatInstantResponse($rawInstantData);
                        Log::info('Hasil FORMAT API Instant berhasil dibuat.');
                    } else {
                        Log::warning('API Instant Gagal atau Status False.', $instantResponse ?? []);
                    }

                } catch (Exception $e) {
                    Log::error('KiriminAja Instant Cost Error: ' . $e->getMessage(), $payload);
                }
            } else {
                Log::error('Geocode GAGAL, melewatkan pencarian harga instant.');
            }

            // =========================================================================
            // C. HITUNG BERAT FINAL (Volumetrik)
            // =========================================================================
            if (!isset($finalWeight)) {
                $category = (isset($payload['service_type']) && $payload['service_type'] === 'cargo') ? 'trucking' : 'regular';

                $weightInput = (int) $payload['weight'];
                $lengthInput = (int) ($payload['length'] ?? 1);
                $widthInput  = (int) ($payload['width'] ?? 1);
                $heightInput = (int) ($payload['height'] ?? 1);

                $volumetricWeight = 0;

                if ($lengthInput > 0 && $widthInput > 0 && $heightInput > 0) {
                    $pembagi = ($category === 'trucking') ? 4000 : 6000;
                    $volumetricWeight = ($lengthInput * $widthInput * $heightInput) / $pembagi * 1000;
                }

                $finalWeight = max($weightInput, $volumetricWeight);
            }

            // =========================================================================
            // D. KEMBALIKAN RESPONSE JSON KE MOBILE
            // =========================================================================
            Log::info('--- SELESAI CEK ONGKIR (API MOBILE) ---');
            return response()->json([
                'success' => true,
                'final_weight' => ceil($finalWeight),
                'data' => [
                    'instant' => $instantServices,
                    'express_cargo' => $expressCargoServices
                ]
            ]);

        } catch (Exception $e) {
            Log::error('KiriminAja Check Cost Error (Fatal): ' . $e->getMessage(), ['request_data' => $request->all()]);
            return response()->json(['success' => false, 'message' => 'Gagal mengambil data ongkos kirim: ' . $e->getMessage()], 500);
        }
    }

    /**
     * [HELPER] Memformat hasil API Instant agar seragam dengan Express Cargo
     */
    private function formatInstantResponse(array $apiResult): array
    {
        $formattedServices = [];
        foreach ($apiResult as $courier) {
            $courierName = $courier['name'];

            if ($courierName == 'grab_express') {
                $courierName = 'grab';
            }
            $courierName = ucwords(str_replace('_', ' ', $courier['name']));

            if (isset($courier['costs']) && is_array($courier['costs'])) {
                foreach ($courier['costs'] as $cost) {
                    $formattedServices[] = [
                        'courierName'  => $courier['name'],
                        'service_name' => $courierName . ' ' . ucwords($cost['service_type']),
                        'service_type' => ucwords($cost['service_type']),
                        'etd'          => $cost['estimation'] ?? 'N/A',
                        'cost'         => $cost['price']['total_price'] ?? 0,
                        'final_price'  => $cost['price']['total_price'] ?? 0,
                        'price'        => $cost['price']
                    ];
                }
            }
        }
        return $formattedServices;
    }
}
