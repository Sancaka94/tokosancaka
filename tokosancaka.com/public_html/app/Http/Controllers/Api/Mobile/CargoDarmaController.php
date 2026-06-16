<?php

namespace App\Http\Controllers\Api\Mobile;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class CargoDarmaController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * =========================================================
     * 1. GET SUPPLIER LIST
     * =========================================================
     */
    public function supplierList(Request $request)
    {
        Log::info("\n========== [CARGO SUPPLIER LIST - START] ==========");
        $validator = Validator::make($request->all(), ['accessToken' => 'required|string']);
        
        if ($validator->fails()) {
            Log::warning("Validasi Gagal:", $validator->errors()->toArray());
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        try {
            $payload = ['accessToken' => $request->accessToken];
            Log::info("PAYLOAD REQUEST: ", ['accessToken' => '***HIDDEN***']);
            
            $response = $this->forwardRequest('Cargo/Supplier', $payload);
            
            Log::info("RESPONSE DARMAWISATA: " . substr($response->getContent(), 0, 500) . "... [TRUNCATED]");
            return $response;
            
        } catch (\Exception $e) {
            Log::error("FATAL ERROR [Cargo/Supplier]: " . $e->getMessage());
            return response()->json(['status' => 'FAILED', 'message' => 'Internal Server Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * =========================================================
     * 2. GET PICKUP LOCATION
     * =========================================================
     */
    public function pickupLocation(Request $request)
    {
        Log::info("\n========== [CARGO PICKUP LOCATION - START] ==========");
        $validator = Validator::make($request->all(), [
            'supplierName' => 'required|string',
            'keyword'      => 'required|string',
            'accessToken'  => 'required|string',
        ]);
        
        if ($validator->fails()) return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);

        try {
            $payload = $request->only(['supplierName', 'keyword', 'accessToken']);
            Log::info("PAYLOAD REQUEST: ", ['supplierName' => $payload['supplierName'], 'keyword' => $payload['keyword']]);

            $response = $this->forwardRequest('Cargo/PickupLocation', $payload);
            
            Log::info("RESPONSE DARMAWISATA: " . substr($response->getContent(), 0, 500) . "... [TRUNCATED]");
            return $response;

        } catch (\Exception $e) {
            Log::error("FATAL ERROR [Cargo/PickupLocation]: " . $e->getMessage());
            return response()->json(['status' => 'FAILED', 'message' => 'Internal Server Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * =========================================================
     * 3. GET DESTINATION AREA
     * =========================================================
     */
    public function destinationArea(Request $request)
    {
        Log::info("\n========== [CARGO DESTINATION AREA - START] ==========");
        $validator = Validator::make($request->all(), [
            'supplierName' => 'required|string',
            'keyword'      => 'required|string',
            'accessToken'  => 'required|string',
        ]);
        
        if ($validator->fails()) return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);

        try {
            $payload = $request->only(['supplierName', 'keyword', 'accessToken']);
            Log::info("PAYLOAD REQUEST: ", ['supplierName' => $payload['supplierName'], 'keyword' => $payload['keyword']]);

            $response = $this->forwardRequest('Cargo/DestinationArea', $payload);
            
            Log::info("RESPONSE DARMAWISATA: " . substr($response->getContent(), 0, 500) . "... [TRUNCATED]");
            return $response;

        } catch (\Exception $e) {
            Log::error("FATAL ERROR [Cargo/DestinationArea]: " . $e->getMessage());
            return response()->json(['status' => 'FAILED', 'message' => 'Internal Server Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * =========================================================
     * 4. GET ADDITIONAL COST
     * =========================================================
     */
    public function additionalCost(Request $request)
    {
        Log::info("\n========== [CARGO ADDITIONAL COST - START] ==========");
        $validator = Validator::make($request->all(), [
            'supplierName' => 'required|string',
            'accessToken'  => 'required|string',
        ]);
        if ($validator->fails()) return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);

        try {
            $payload = $request->only(['supplierName', 'accessToken']);
            Log::info("PAYLOAD REQUEST: ", ['supplierName' => $payload['supplierName']]);

            $response = $this->forwardRequest('Cargo/AdditionalCost', $payload);
            Log::info("RESPONSE DARMAWISATA: " . $response->getContent());
            return $response;

        } catch (\Exception $e) {
            Log::error("FATAL ERROR [Cargo/AdditionalCost]: " . $e->getMessage());
            return response()->json(['status' => 'FAILED', 'message' => 'Internal Server Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * =========================================================
     * 5. GET REFERENCE
     * =========================================================
     */
    public function reference(Request $request)
    {
        Log::info("\n========== [CARGO REFERENCE - START] ==========");
        $validator = Validator::make($request->all(), [
            'supplierName' => 'required|string',
            'accessToken'  => 'required|string',
        ]);
        if ($validator->fails()) return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);

        try {
            $payload = $request->only(['supplierName', 'accessToken']);
            Log::info("PAYLOAD REQUEST: ", ['supplierName' => $payload['supplierName']]);

            $response = $this->forwardRequest('Cargo/Reference', $payload);
            Log::info("RESPONSE DARMAWISATA: " . $response->getContent());
            return $response;

        } catch (\Exception $e) {
            Log::error("FATAL ERROR [Cargo/Reference]: " . $e->getMessage());
            return response()->json(['status' => 'FAILED', 'message' => 'Internal Server Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * =========================================================
     * 6. GET CONTENT
     * =========================================================
     */
    public function content(Request $request)
    {
        Log::info("\n========== [CARGO CONTENT - START] ==========");
        $validator = Validator::make($request->all(), [
            'supplierName' => 'required|string',
            'accessToken'  => 'required|string',
        ]);
        if ($validator->fails()) return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);

        try {
            $payload = $request->only(['supplierName', 'accessToken']);
            Log::info("PAYLOAD REQUEST: ", ['supplierName' => $payload['supplierName']]);

            $response = $this->forwardRequest('Cargo/Content', $payload);
            Log::info("RESPONSE DARMAWISATA: " . $response->getContent());
            return $response;

        } catch (\Exception $e) {
            Log::error("FATAL ERROR [Cargo/Content]: " . $e->getMessage());
            return response()->json(['status' => 'FAILED', 'message' => 'Internal Server Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * =========================================================
     * 7. GET HANDLING
     * =========================================================
     */
    public function handling(Request $request)
    {
        Log::info("\n========== [CARGO HANDLING - START] ==========");
        $validator = Validator::make($request->all(), [
            'supplierName' => 'required|string',
            'accessToken'  => 'required|string',
        ]);
        if ($validator->fails()) return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);

        try {
            $payload = $request->only(['supplierName', 'accessToken']);
            Log::info("PAYLOAD REQUEST: ", ['supplierName' => $payload['supplierName']]);

            $response = $this->forwardRequest('Cargo/Handling', $payload);
            Log::info("RESPONSE DARMAWISATA: " . $response->getContent());
            return $response;

        } catch (\Exception $e) {
            Log::error("FATAL ERROR [Cargo/Handling]: " . $e->getMessage());
            return response()->json(['status' => 'FAILED', 'message' => 'Internal Server Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * =========================================================
     * 8. GET GOODS
     * =========================================================
     */
    public function goods(Request $request)
    {
        Log::info("\n========== [CARGO GOODS - START] ==========");
        $validator = Validator::make($request->all(), [
            'supplierName' => 'required|string',
            'contentID'    => 'required|string',
            'accessToken'  => 'required|string',
        ]);
        if ($validator->fails()) return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);

        try {
            $payload = $request->only(['supplierName', 'contentID', 'accessToken']);
            Log::info("PAYLOAD REQUEST: ", ['supplierName' => $payload['supplierName'], 'contentID' => $payload['contentID']]);

            $response = $this->forwardRequest('Cargo/Goods', $payload);
            Log::info("RESPONSE DARMAWISATA: " . $response->getContent());
            return $response;

        } catch (\Exception $e) {
            Log::error("FATAL ERROR [Cargo/Goods]: " . $e->getMessage());
            return response()->json(['status' => 'FAILED', 'message' => 'Internal Server Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * =========================================================
     * 9. GET HANDLING SURCHARGE
     * =========================================================
     */
    public function handlingSurcharge(Request $request)
    {
        Log::info("\n========== [CARGO HANDLING SURCHARGE - START] ==========");
        $validator = Validator::make($request->all(), [
            'supplierName' => 'required|string',
            'handlingID'   => 'required|string',
            'accessToken'  => 'required|string',
        ]);
        if ($validator->fails()) return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);

        try {
            $payload = $request->only(['supplierName', 'handlingID', 'accessToken']);
            Log::info("PAYLOAD REQUEST: ", ['supplierName' => $payload['supplierName'], 'handlingID' => $payload['handlingID']]);

            $response = $this->forwardRequest('Cargo/HandlingSurcharge', $payload);
            Log::info("RESPONSE DARMAWISATA: " . $response->getContent());
            return $response;

        } catch (\Exception $e) {
            Log::error("FATAL ERROR [Cargo/HandlingSurcharge]: " . $e->getMessage());
            return response()->json(['status' => 'FAILED', 'message' => 'Internal Server Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * =========================================================
     * 10. GET TARIFF (CEK ONGKIR CARGO)
     * =========================================================
     */
    public function tariff(Request $request)
    {
        Log::info("\n========== [CARGO TARIFF - START] ==========");
        $validator = Validator::make($request->all(), [
            'supplierName'      => 'required|string',
            'pickupLocationID'  => 'required|string',
            'destinationAreaID' => 'required|string',
            'handlingID'        => 'nullable|string',
            'pieces'            => 'required|array',
            'accessToken'       => 'required|string',
        ]);
        if ($validator->fails()) {
            Log::warning("Validasi Tariff Gagal: ", $validator->errors()->toArray());
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        try {
            $payload = $request->only([
                'supplierName', 'pickupLocationID', 'destinationAreaID', 'handlingID', 'pieces', 'accessToken'
            ]);
            // Pastikan handlingID tidak null
            if (!isset($payload['handlingID']) || is_null($payload['handlingID'])) {
                $payload['handlingID'] = "";
            }

            Log::info("PAYLOAD REQUEST TARIFF: ", json_decode(json_encode($payload), true));

            $response = $this->forwardRequest('Cargo/Tariff', $payload);
            
            Log::info("RESPONSE DARMAWISATA TARIFF: " . substr($response->getContent(), 0, 800) . "... [TRUNCATED]");
            return $response;

        } catch (\Exception $e) {
            Log::error("FATAL ERROR [Cargo/Tariff]: " . $e->getMessage());
            return response()->json(['status' => 'FAILED', 'message' => 'Internal Server Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * =========================================================
     * 11. GET PRICE DETAIL
     * =========================================================
     */
    public function priceDetail(Request $request)
    {
        Log::info("\n========== [CARGO PRICE DETAIL - START] ==========");
        $validator = Validator::make($request->all(), [
            'supplierName'       => 'required|string',
            'pickupLocationID'   => 'required|string',
            'destinationAreaID'  => 'required|string',
            'shipmentID'         => 'required|string',
            'serviceID'          => 'required|string',
            'isUseInsurance'     => 'required|boolean',
            'goodsValue'         => 'required|numeric',
            'pieces'             => 'required|array',
            'handlingSurcharges' => 'nullable|array',
            'accessToken'        => 'required|string',
        ]);
        if ($validator->fails()) return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);

        try {
            $payload = $request->all();
            Log::info("PAYLOAD REQUEST PRICE DETAIL: ", json_decode(json_encode($payload), true));

            $response = $this->forwardRequest('Cargo/PriceDetail', $payload);
            Log::info("RESPONSE DARMAWISATA PRICE DETAIL: " . $response->getContent());
            return $response;

        } catch (\Exception $e) {
            Log::error("FATAL ERROR [Cargo/PriceDetail]: " . $e->getMessage());
            return response()->json(['status' => 'FAILED', 'message' => 'Internal Server Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * =========================================================
     * 12. CREATE BOOKING CARGO
     * =========================================================
     */
    public function booking(Request $request)
    {
        Log::info("\n========== [CARGO BOOKING - START] ==========");
        $validator = Validator::make($request->all(), [
            'contentID'          => 'required|string',
            'goodsID'            => 'required|string',
            'pickupName'         => 'required|string',
            'pickupAddress'      => 'required|string',
            'receiverName'       => 'required|string',
            'receiverAddress'    => 'required|string',
            'supplierName'       => 'required|string',
            'pickupLocationID'   => 'required|string',
            'destinationAreaID'  => 'required|string',
            'shipmentID'         => 'required|string',
            'serviceID'          => 'required|string',
            'pieces'             => 'required|array',
            'accessToken'        => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::warning("Validasi Booking Gagal: ", $validator->errors()->toArray());
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        try {
            $payload = $request->all();
            Log::info("PAYLOAD REQUEST BOOKING: ", json_decode(json_encode($payload), true));

            $response = $this->forwardRequest('Cargo/Booking', $payload);
            
            Log::info("RESPONSE DARMAWISATA BOOKING: " . $response->getContent());
            return $response;

        } catch (\Exception $e) {
            Log::error("FATAL ERROR [Cargo/Booking]: " . $e->getMessage());
            return response()->json(['status' => 'FAILED', 'message' => 'Internal Server Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * =========================================================
     * 13. GET BOOKING LIST
     * =========================================================
     */
    public function bookingList(Request $request)
    {
        Log::info("\n========== [CARGO BOOKING LIST - START] ==========");
        $validator = Validator::make($request->all(), [
            'startDate'   => 'required|date',
            'endDate'     => 'required|date',
            'accessToken' => 'required|string',
        ]);
        if ($validator->fails()) return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);

        try {
            $payload = $request->only(['startDate', 'endDate', 'accessToken']);
            Log::info("PAYLOAD REQUEST BOOKING LIST: ", ['startDate' => $payload['startDate'], 'endDate' => $payload['endDate']]);

            $response = $this->forwardRequest('Cargo/BookingList', $payload);
            
            Log::info("RESPONSE DARMAWISATA BOOKING LIST: " . substr($response->getContent(), 0, 800) . "... [TRUNCATED]");
            return $response;

        } catch (\Exception $e) {
            Log::error("FATAL ERROR [Cargo/BookingList]: " . $e->getMessage());
            return response()->json(['status' => 'FAILED', 'message' => 'Internal Server Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * =========================================================
     * 14. GET BOOKING DETAIL
     * =========================================================
     */
    public function bookingDetail(Request $request)
    {
        Log::info("\n========== [CARGO BOOKING DETAIL - START] ==========");
        $validator = Validator::make($request->all(), [
            'sttNumber'   => 'required|string',
            'orderID'     => 'required|string',
            'bookingDate' => 'required|date',
            'accessToken' => 'required|string',
        ]);
        if ($validator->fails()) return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);

        try {
            $payload = $request->only(['sttNumber', 'orderID', 'bookingDate', 'accessToken']);
            Log::info("PAYLOAD REQUEST BOOKING DETAIL: ", json_decode(json_encode($payload), true));

            $response = $this->forwardRequest('Cargo/BookingDetail', $payload);
            Log::info("RESPONSE DARMAWISATA BOOKING DETAIL: " . $response->getContent());
            return $response;

        } catch (\Exception $e) {
            Log::error("FATAL ERROR [Cargo/BookingDetail]: " . $e->getMessage());
            return response()->json(['status' => 'FAILED', 'message' => 'Internal Server Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * =========================================================
     * 15. TRACKING CARGO / PELACAKAN RESI
     * =========================================================
     */
    public function tracking(Request $request)
    {
        Log::info("\n========== [CARGO TRACKING - START] ==========");

        $validator = Validator::make($request->all(), [
            'supplierName' => 'required|string',
            'sttNumber'    => 'required|string',
            'accessToken'  => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::warning("Validasi Tracking Gagal: ", $validator->errors()->toArray());
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $userId = $user->id_pengguna ?? $user->id;

        try {
            $payload = [
                'supplierName' => $request->supplierName,
                'sttNumber'    => $request->sttNumber,
                'accessToken'  => $request->accessToken
            ];

            Log::info("PAYLOAD REQUEST TRACKING: ", ['supplier' => $payload['supplierName'], 'stt' => $payload['sttNumber']]);

            $response = $this->forwardRequest('Cargo/Tracking', $payload);
            $responseContent = $response->getContent();
            
            Log::info("RESPONSE DARMAWISATA TRACKING: " . substr($responseContent, 0, 1000) . "... [TRUNCATED]");
            
            $json = json_decode($responseContent, true);

            // Simpan riwayat jika respons json ada status SUCCESS
            if (is_array($json) && isset($json['status']) && $json['status'] === 'SUCCESS') {
                $info = $json['infos'][0] ?? null;
                $destination = $info['destination'] ?? '-';
                
                DB::table('dw_cargo_histories')->updateOrInsert(
                    [
                        'user_id'       => $userId,
                        'stt_number'    => $request->sttNumber,
                        'supplier_name' => $request->supplierName,
                    ],
                    [
                        'destination'   => $destination,
                        'last_tracked'  => now(),
                        'created_at'    => DB::raw('COALESCE(created_at, NOW())'),
                        'updated_at'    => now(),
                    ]
                );
            }

            return response()->json($json ?? ['status' => 'FAILED', 'message' => 'Gagal membaca response provider']);

        } catch (\Exception $e) {
            Log::error("FATAL ERROR [Cargo/Tracking]: " . $e->getMessage());
            return response()->json(['status' => 'FAILED', 'message' => 'Internal Server Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * =========================================================
     * 16. GET HISTORY TRACKING LOKAL
     * =========================================================
     */
    public function history(Request $request)
    {
        Log::info("\n========== [CARGO GET HISTORY LOKAL - START] ==========");
        $user = $request->user();
        $userId = $user->id_pengguna ?? $user->id;

        try {
            $history = DB::table('dw_cargo_histories')
                ->where('user_id', $userId)
                ->orderBy('last_tracked', 'desc')
                ->get();

            return response()->json(['status' => 'SUCCESS', 'data' => $history]);
        } catch (\Exception $e) {
            Log::error("FATAL ERROR [Cargo/History]: " . $e->getMessage());
            return response()->json(['status' => 'FAILED', 'message' => 'Gagal mengambil riwayat DB: ' . $e->getMessage()], 500);
        }
    }

    /**
     * =========================================================
     * 17. HAPUS HISTORY (BULK DELETE KHUSUS ADMIN)
     * =========================================================
     */
    public function bulkDestroyHistory(Request $request)
    {
        Log::info("\n========== [CARGO BULK DELETE HISTORY - START] ==========");
        $user = $request->user();
        $userId = $user->id_pengguna ?? $user->id;

        if ($userId != 4) {
            return response()->json(['status' => 'FAILED', 'message' => 'Akses ditolak.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'ids'   => 'required|array',
            'ids.*' => 'integer'
        ]);

        if ($validator->fails()) return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);

        try {
            DB::table('dw_cargo_histories')->whereIn('id', $request->ids)->delete();
            Log::info("BERHASIL HAPUS " . count($request->ids) . " DATA HISTORY CARGO.");
            return response()->json(['status' => 'SUCCESS', 'message' => 'Riwayat berhasil dihapus']);
        } catch (\Exception $e) {
            Log::error("FATAL ERROR [Cargo/BulkDelete]: " . $e->getMessage());
            return response()->json(['status' => 'FAILED', 'message' => 'Gagal menghapus data di database: ' . $e->getMessage()], 500);
        }
    }
}