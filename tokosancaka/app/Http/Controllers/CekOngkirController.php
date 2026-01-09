<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\KiriminAjaService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CekOngkirController extends Controller
{
    public function index()
    {
        return view('cek-ongkir');
    }

    public function searchAddress(Request $request, KiriminAjaService $kiriminAja)
    {
        $search = $request->input('search');
        if (strlen($search) < 3) {
            return response()->json([]);
        }

        $result = $kiriminAja->searchAddress($search);

        if ($result === null) {
            return response()->json(['message' => 'Gagal terhubung ke layanan pencarian alamat.'], 500);
        }

        if (isset($result['status']) && $result['status']) {
            return response()->json($result['data']);
        }

        return response()->json([
            'message' => $result['text'] ?? 'Gagal mengambil data alamat.'
        ], 400);
    }

  private function geocode($address)
{
    // --- PERBAIKAN: Sederhanakan alamat ---
    // Ambil 3 bagian pertama (Desa, Kecamatan, Kota)
    $parts = explode(',', $address);
    $simpleAddress = implode(',', array_slice($parts, 0, 3));

    Log::info("Geocode (CekOngkir) mencoba: '{$simpleAddress}' (dari '{$address}')");
    // --- AKHIR PERBAIKAN ---

    try {
        $url = "https://nominatim.openstreetmap.org/search";

        $response = Http::withHeaders([
            'User-Agent' => 'TokoSancakaApp/1.0 (admin@tokosancaka.com)'
        ])
        ->timeout(30)
        ->get($url, [
            'q' => $simpleAddress,
            'format' => 'json',
            'limit' => 1,
            'countrycodes' => 'id'
        ]);

        if ($response->successful() && isset($response[0]['lat'])) {
            return [
                'lat' => (float) $response[0]['lat'],
                'long' => (float) $response[0]['lon']
            ];
        }

        Log::warning('Geocoding failed (CekOngkirController)', [
            'address' => $simpleAddress,
            'status' => $response->status(),
            'body' => $response->body()
        ]);

    } catch (\Exception $e) {
        Log::error('Geocoding exception (CekOngkirController)', [
            'address' => $address,
            'error' => $e->getMessage()
        ]);
    }

    return null;
}


    private function checkInstantCost(Request $request, KiriminAjaService $kiriminAja)
    {
        $validator = Validator::make($request->all(), [
            'origin_text'    => 'required|string',
            'destination_text' => 'required|string',
            'weight'         => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $originCoords = $this->geocode($request->origin_text);
        $destinationCoords = $this->geocode($request->destination_text);

        if (!$originCoords || !$destinationCoords) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menemukan koordinat untuk alamat yang diberikan.'
            ], 400);
        }

        $result = $kiriminAja->getInstantPricing(
            $originCoords['lat'],
            $originCoords['long'],
            $request->origin_text,
            $destinationCoords['lat'],
            $destinationCoords['long'],
            $request->destination_text,
            (int) $request->weight,
            (int) $request->input('item_value', 0)
        );

        if ($result === null) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal terhubung ke layanan ongkir instant.'
            ], 500);
        }

        if (isset($result['status']) && $result['status']) {
            return response()->json([
                'success' => true,
                'data'    => $result['result'] ?? [],
                'type'    => 'instant'
            ]);
        }

        if (isset($result['text']) && str_contains($result['text'], 'distance more than 40km')) {
            return response()->json([
                'success' => false,
                'message' => 'Mohon maaf jarak dengan kurir melebihi batas maksimal 40KM'
            ], 400);
        }

        return response()->json([
            'success' => false,
            'message' => $result['text'] ?? 'Gagal menghitung ongkir instant.'
        ], 500);
    }

    public function checkCost(Request $request, KiriminAjaService $kiriminAja)
    {
        $validator = Validator::make($request->all(), [
            'origin_text' => 'required|string',
            'destination_text' => 'required|string',
            'origin_id' => 'required|integer',
            'origin_subdistrict_id' => 'required|integer',
            'destination_id' => 'required|integer',
            'destination_subdistrict_id' => 'required|integer',
            'weight' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            $instantResults = [];
            $instantWarning = null;

            $originCoords = $this->geocode($request->origin_text);
            $destinationCoords = $this->geocode($request->destination_text);

            $itemValue = (int) $request->input('item_value', 0);

            if ($originCoords && $destinationCoords) {

                $instantItemValue = max($itemValue, 1);

                $instantApiResult = $kiriminAja->getInstantPricing(
                    $originCoords['lat'],
                    $originCoords['long'],
                    $request->origin_text,
                    $destinationCoords['lat'],
                    $destinationCoords['long'],
                    $request->destination_text,
                    (int) $request->weight,
                    $instantItemValue
                );

                if ($instantApiResult === null || !($instantApiResult['status'] ?? false)) {

                    if (isset($instantApiResult['text']) && str_contains($instantApiResult['text'], 'distance more than 40km')) {
                        $instantWarning = "Mohon maaf jarak dengan kurir melebihi batas maksimal 40KM";
                    }

                } else {
                    $rawInstantData = $instantApiResult['result'] ?? [];
                    $instantResults = $this->formatInstantResponse($rawInstantData);
                }

            } else {
                Log::warning('Geocoding gagal, skip ongkir instant.', [
                    'originCoords' => $originCoords,
                    'destinationCoords' => $destinationCoords
                ]);
            }

            $expressApiResult = $kiriminAja->getExpressPricing(
                $request->origin_id,
                $request->origin_subdistrict_id,
                $request->destination_id,
                $request->destination_subdistrict_id,
                (int) $request->weight,
                (int) $request->input('length', 1),
                (int) $request->input('width', 1),
                (int) $request->input('height', 1),
                $itemValue,
                null,
                'regular',
                $request->input('insurance') === 'on' ? 1 : 0
            );

            $volumeWeight = (
                (int)$request->input('length', 1) *
                (int)$request->input('width', 1) *
                (int)$request->input('height', 1)
            ) / 6000 * 1000;

            $final_weight = max((int) $request->weight, $volumeWeight);

            $expressCargoResults = [];

            if ($expressApiResult && ($expressApiResult['status'] ?? false)) {
                $expressCargoResults = $expressApiResult['results'] ?? [];
            }

            if (empty($instantResults) && empty($expressCargoResults)) {
                $errorMessage = $instantWarning
                    ?? $expressApiResult['text']
                    ?? ($instantApiResult['text'] ?? 'Gagal terhubung ke semua layanan pengiriman.');
                $statusCode = $instantWarning ? 400 : 500;

                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], $statusCode);
            }

            return response()->json([
                'success' => true,
                'final_weight' => ceil($final_weight),
                'data' => [
                    'instant' => $instantResults,
                    'express_cargo' => $expressCargoResults
                ],
                'warning' => $instantWarning
            ]);

        } catch (\Exception $e) {
            Log::error('CheckCost Exception: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan internal.'
            ], 500);
        }
    }

    private function formatInstantResponse(array $apiResult): array
    {
        $formatted = [];

        foreach ($apiResult as $courier) {

            $courierName = ucwords(str_replace('_', ' ', $courier['name']));

            if (!empty($courier['costs']) && is_array($courier['costs'])) {

                foreach ($courier['costs'] as $cost) {

                    $formatted[] = [
                        'service' => $courier['name'],
                        'service_name' => $courierName . ' ' . ucwords($cost['service_type']),
                        'service_type' => ucwords($cost['service_type']),
                        'etd' => $cost['estimation'] ?? 'N/A',
                        'cost' => $cost['price']['total_price'] ?? 0,
                        'final_price' => $cost['price']['total_price'] ?? 0,
                    ];
                }
            }
        }

        return $formatted;
    }
}
