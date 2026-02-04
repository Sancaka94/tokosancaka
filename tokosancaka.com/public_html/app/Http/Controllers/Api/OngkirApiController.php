<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Services\KiriminAjaService; // Menggunakan Service yang Anda berikan
use Exception;
use Illuminate\Support\Facades\Http; 

class OngkirApiController extends Controller
{
    protected $kiriminAjaService;

    public function __construct(KiriminAjaService $kiriminAjaService)
    {
        $this->kiriminAjaService = $kiriminAjaService;
    }

    /**
     * Menggunakan OpenStreetMap Nominatim untuk Geocoding
     * [PERBAIKAN] Mengembalikan 'lon' agar konsisten
     */
    public function geocode($address)
    {
        $url = "https://nominatim.openstreetmap.org/search";

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'TokoSancakaApp/1.0 (support@tokosancaka.com)', // User-Agent Anda
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
                    'lon' => (float) $data[0]['lon'], // Menggunakan 'lon'
                ];
            }

            Log::warning('Geocoding (Nominatim) failed or returned empty', [
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
     * Menangani pencarian alamat (v6.1)
     * (Kode Anda sudah OK, tidak diubah)
     */
    public function searchAddress(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'search' => 'required|string|min:3',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        try {
            $result = $this->kiriminAjaService->searchAddress($request->input('search'));
            
            if (isset($result['status']) && $result['status'] === true && isset($result['data'])) {
                
                $mappedResults = array_map(function($item) {
                    $subdistrictName = $item['subdistrict_name'] ?? null;
                    $districtName = $item['district_name'] ?? null;
                    $cityName = $item['city_name'] ?? null;
                    $provinceName = $item['province_name'] ?? null;
                    $zipCode = $item['zip_code'] ?? null;

                    return [
                        'district_id' => $item['district_id'] ?? null,
                        'subdistrict_id' => $item['subdistrict_id'] ?? null,
                        'lat' => $item['lat'] ?? null,
                        'lon' => $item['long'] ?? null, // 'lon' dari API KiriminAja

                        'subdistrict_name' => $subdistrictName,
                        'district_name' => $districtName,
                        'city_name' => $cityName,
                        'province_name' => $provinceName,

                        'full_address' => implode(', ', array_filter([
                            $subdistrictName, $districtName, $cityName, $provinceName
                        ])) . ($zipCode ? ' ' . $zipCode : '')
                    ];
                }, $result['data']);
                
                return response()->json($mappedResults);
            }
            
            return response()->json([]);

        } catch (Exception $e) {
            Log::error('KiriminAja Address Search Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Menangani pengecekan ongkos kirim
     * [PERBAIKAN TOTAL PADA FUNGSI INI]
     */
    public function checkCost(Request $request)
    {
        Log::info('--- MEMULAI CEK ONGKIR (API HOMEPAGE) ---');
        Log::info('Payload Diterima: ' . json_encode($request->all()));

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
            
            // Ambil nilai barang asli
            $itemValue = (int)($payload['item_value'] ?? 0);

            // --- 1. Panggil Layanan Express & Cargo (v6.1) ---
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
                    $itemValue, // Kirim nilai asli (0 diizinkan)
                    null, 
                    'regular', 
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
            
            // --- 2. Panggil Layanan Instant (v4) ---

            // [PERBAIKAN 1: 'item_value' minimal 1]
            $instantItemValue = ($itemValue < 1) ? 1 : $itemValue;

            Log::info('Mencoba Geocode Asal: ' . $payload['origin_text']);
            $originCoords = $this->geocode($payload['origin_text']);
            
            Log::info('Mencoba Geocode Tujuan: ' . $payload['destination_text']);
            $destinationCoords = $this->geocode($payload['destination_text']);

            if ($originCoords && $destinationCoords) {
                Log::info('Geocode BERHASIL. Asal: ' . json_encode($originCoords) . ', Tujuan: ' . json_encode($destinationCoords));
                Log::info('Item Value untuk Instant: ' . $instantItemValue);

                try {
                    // Panggil getInstantPricing (8 argumen, sama seperti CekOngkirController)
                    $instantResponse = $this->kiriminAjaService->getInstantPricing(
                        $originCoords['lat'],
                        $originCoords['lon'], // Menggunakan 'lon' dari geocode
                        $payload['origin_text'],
                        $destinationCoords['lat'],
                        $destinationCoords['lon'], // Menggunakan 'lon' dari geocode
                        $payload['destination_text'],
                        $payload['weight'],
                        $instantItemValue // Menggunakan nilai yang sudah diperbaiki
                    );

                    Log::info('Hasil MENTAH API Instant: ' . json_encode($instantResponse));

                    if (isset($instantResponse['status']) && $instantResponse['status'] === true) {
                        $rawInstantData = $instantResponse['result'] ?? [];
                        // [PERBAIKAN 2: Format data agar sesuai JS]
                        $instantServices = $this->formatInstantResponse($rawInstantData);
                        Log::info('Hasil FORMAT API Instant: ' . json_encode($instantServices));
                    } else {
                        Log::warning('API Instant Gagal atau Status False.', $instantResponse ?? []);
                    }

                } catch (Exception $e) {
                    Log::error('KiriminAja Instant Cost Error: ' . $e->getMessage(), $payload);
                }
            } else {
                Log::error('Geocode GAGAL, skipping instant check.');
            }
            
            // 3. Hitung berat final jika belum di-set
            if (!isset($finalWeight)) {
                 $volumeWeight = (($payload['length'] ?? 1) * ($payload['width'] ?? 1) * ($payload['height'] ?? 1)) / 6000 * 1000;
                 $finalWeight = max((int) $payload['weight'], $volumeWeight);
            }
            
            // 4. Kembalikan data gabungan
            Log::info('--- SELESAI CEK ONGKIR (API HOMEPAGE) ---');
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
     * [FUNGSI HELPER BARU]
     * Memformat hasil API Instant agar seragam.
     */
    private function formatInstantResponse(array $apiResult): array
    {
        $formattedServices = [];
        foreach ($apiResult as $courier) {
            // $courier['name'] adalah "gosend" atau "grab_express"
            $courierName = $courier['name']; 
            
            // [PERBAIKAN] Ganti 'grab_express' menjadi 'grab' agar cocok dengan nama file Anda
            if ($courierName == 'grab_express') {
                $courierName = 'grab';
            }
            $courierName = ucwords(str_replace('_', ' ', $courier['name']));
            
            if (isset($courier['costs']) && is_array($courier['costs'])) {
                foreach ($courier['costs'] as $cost) {
                    $formattedServices[] = [
                        // e.g., "Gosend Instant"
                        'courierName'  => $courier['name'], // 'gosend' (untuk logo)
                        'service_name' => $courierName . ' ' . ucwords($cost['service_type']), 
                        'service_type' => ucwords($cost['service_type']), // "Instant" atau "Sameday"
                        'etd'          => $cost['estimation'] ?? 'N/A',
                        
                        // 'cost' atau 'final_price' akan dicek oleh JS
                        'cost'         => $cost['price']['total_price'] ?? 0, 
                        'final_price'  => $cost['price']['total_price'] ?? 0, 
                        'price'        => $cost['price'] // Sertakan objek harga asli jika JS membutuhkannya
                    ];
                }
            }
        }
        return $formattedServices;
    }
}