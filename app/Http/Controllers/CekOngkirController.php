<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\KiriminAjaService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;


class CekOngkirController extends Controller
{
    /**
     * Menampilkan halaman cek ongkir.
     */
    public function index()
    {
        return view('cek-ongkir');
    }

    /**
     * API untuk pencarian alamat menggunakan KiriminAjaService.
     */
    public function searchAddress(Request $request, KiriminAjaService $kiriminAja)
    {
        $search = $request->input('search');
        if (strlen($search) < 3) {
            return response()->json([]);
        }

        $result = $kiriminAja->searchAddress($search);

        if ($result['status']) {
            return response()->json($result['data']);
        }

        // Jika gagal, kirimkan pesan error dari service
        return response()->json(['message' => $result['message']], 500);
    }

    /**
     * Mengubah alamat teks menjadi koordinat.
     * Diambil dari referensi PesananController Anda.
     */
    private function geocode($address)
    {
        try {
            $url = "https://nominatim.openstreetmap.org/search?q=" . urlencode($address) . "&format=json&limit=1";
            $response = Http::withHeaders([
                'User-Agent' => 'TokoSancakaApp/1.0 (admin@tokosancaka.com)'
            ])->get($url)->json();
        
            if (!empty($response[0])) {
                return ['lat' => (float) $response[0]['lat'], 'long' => (float) $response[0]['lon']];
            }
        } catch (\Exception $e) {
            Log::error('Geocoding failed', ['address' => $address, 'error' => $e->getMessage()]);
        }
        return null;
    }
    
     /**
     * PERBAIKAN: Logika khusus untuk menangani pengecekan ongkir Instant.
     */
    private function checkInstantCost(Request $request, KiriminAjaService $kiriminAja)
    {
        $validator = Validator::make($request->all(), [
            'origin_text'      => 'required|string',
            'destination_text' => 'required|string',
            'weight'           => 'required|integer|min:1',
        ]);
        if ($validator->fails()) return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);

        $originCoords = $this->geocode($request->input('origin_text'));
        $destinationCoords = $this->geocode($request->input('destination_text'));

        if (!$originCoords || !$destinationCoords) {
            return response()->json(['success' => false, 'message' => 'Gagal menemukan koordinat untuk alamat yang diberikan.'], 400);
        }

        $result = $kiriminAja->getInstantPricing(
            $originCoords['lat'],
            $originCoords['long'],
            $request->input('origin_text'),
            $destinationCoords['lat'],
            $destinationCoords['long'],
            $request->input('destination_text'),
            (int) $request->input('weight'),
            (int) $request->input('item_value', 0)
        );

        if (isset($result['status']) && $result['status'] === true) {
            return response()->json([
                'success' => true, 
                'data' => $result['result'] ?? [], 
                'type' => 'instant'
            ]);
        }
        
        return response()->json(['success' => false, 'message' => $result['text'] ?? 'Gagal menghitung ongkir instant.'], 500);
    }


 
    /**
     * Mengambil semua jenis ongkir (Express, Cargo, Instant) dalam satu panggilan.
     */
    public function checkCost(Request $request, KiriminAjaService $kiriminAja)
    {
        $validator = Validator::make($request->all(), [
            'origin_text'      => 'required|string',
            'destination_text' => 'required|string',
            'origin_id' => 'required|integer',
            'origin_subdistrict_id' => 'required|integer',
            'destination_id' => 'required|integer',
            'destination_subdistrict_id' => 'required|integer',
            'weight' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        try {
            
            
            // --- PERBAIKAN LOGIKA INSTANT ---
            $instantResults = [];
            $originCoords = $this->geocode($request->input('origin_text'));
            $destinationCoords = $this->geocode($request->input('destination_text'));

            if ($originCoords && $destinationCoords) {
                // Memanggil metode `getInstantPricing` dengan 8 argumen yang benar
                $instantApiResult = $kiriminAja->getInstantPricing(
                    $originCoords['lat'], 
                    $originCoords['long'], 
                    $request->input('origin_text'),
                    $destinationCoords['lat'], 
                    $destinationCoords['long'], 
                    $request->input('destination_text'),
                    (int) $request->input('weight'), 
                    (int) $request->input('item_value', 0)
                );
                if (isset($instantApiResult['status']) && $instantApiResult['status'] === true) {
                    $instantResults = $instantApiResult['result'] ?? [];
                }
            }

            // --- 2. Ambil Hasil Express & Cargo ---
            $expressCargoResults = [];
            $expressApiResult = $kiriminAja->getExpressPricing(
                $request->input('origin_id'), $request->input('origin_subdistrict_id'),
                $request->input('destination_id'), $request->input('destination_subdistrict_id'),
                (int) $request->input('weight'), (int) $request->input('length', 1),
                (int) $request->input('width', 1), (int) $request->input('height', 1),
                (int) $request->input('item_value', 0), null, 'regular',
                $request->input('insurance') === 'on' ? 1 : 0
            );

            $final_weight = max(
                (int) $request->input('weight'),
                ((int)$request->input('length', 1) * (int)$request->input('width', 1) * (int)$request->input('height', 1)) / 6000 * 1000
            );

            if (isset($expressApiResult['status']) && $expressApiResult['status'] === true) {
                $expressCargoResults = $expressApiResult['results'] ?? [];
            }
            
            // --- 3. Kirim Hasil Gabungan ---
            return response()->json([
                'success'      => true,
                'final_weight' => ceil($final_weight),
                'data'         => [
                    'instant' => $instantResults,
                    'express_cargo' => $expressCargoResults
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('CheckCost Exception: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan internal.'], 500);
        }
    }
}

