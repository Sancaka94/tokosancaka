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
        Log::info("\n========== [CARGO SUPPLIER LIST] ==========");
        $validator = Validator::make($request->all(), ['accessToken' => 'required|string']);
        if ($validator->fails()) return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);

        return $this->forwardRequest('Cargo/Supplier', ['accessToken' => $request->accessToken]);
    }

    /**
     * =========================================================
     * 2. GET PICKUP LOCATION
     * =========================================================
     */
    public function pickupLocation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'supplierName' => 'required|string',
            'keyword'      => 'required|string',
            'accessToken'  => 'required|string',
        ]);
        if ($validator->fails()) return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);

        return $this->forwardRequest('Cargo/PickupLocation', $request->only(['supplierName', 'keyword', 'accessToken']));
    }

    /**
     * =========================================================
     * 3. GET DESTINATION AREA
     * =========================================================
     */
    public function destinationArea(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'supplierName' => 'required|string',
            'keyword'      => 'required|string',
            'accessToken'  => 'required|string',
        ]);
        if ($validator->fails()) return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);

        return $this->forwardRequest('Cargo/DestinationArea', $request->only(['supplierName', 'keyword', 'accessToken']));
    }

    /**
     * =========================================================
     * 4. GET ADDITIONAL COST
     * =========================================================
     */
    public function additionalCost(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'supplierName' => 'required|string',
            'accessToken'  => 'required|string',
        ]);
        if ($validator->fails()) return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);

        return $this->forwardRequest('Cargo/AdditionalCost', $request->only(['supplierName', 'accessToken']));
    }

    /**
     * =========================================================
     * 5. GET REFERENCE
     * =========================================================
     */
    public function reference(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'supplierName' => 'required|string',
            'accessToken'  => 'required|string',
        ]);
        if ($validator->fails()) return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);

        return $this->forwardRequest('Cargo/Reference', $request->only(['supplierName', 'accessToken']));
    }

    /**
     * =========================================================
     * 6. GET CONTENT
     * =========================================================
     */
    public function content(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'supplierName' => 'required|string',
            'accessToken'  => 'required|string',
        ]);
        if ($validator->fails()) return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);

        return $this->forwardRequest('Cargo/Content', $request->only(['supplierName', 'accessToken']));
    }

    /**
     * =========================================================
     * 7. GET HANDLING
     * =========================================================
     */
    public function handling(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'supplierName' => 'required|string',
            'accessToken'  => 'required|string',
        ]);
        if ($validator->fails()) return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);

        return $this->forwardRequest('Cargo/Handling', $request->only(['supplierName', 'accessToken']));
    }

    /**
     * =========================================================
     * 8. GET GOODS
     * =========================================================
     */
    public function goods(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'supplierName' => 'required|string',
            'contentID'    => 'required|string',
            'accessToken'  => 'required|string',
        ]);
        if ($validator->fails()) return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);

        return $this->forwardRequest('Cargo/Goods', $request->only(['supplierName', 'contentID', 'accessToken']));
    }

    /**
     * =========================================================
     * 9. GET HANDLING SURCHARGE
     * =========================================================
     */
    public function handlingSurcharge(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'supplierName' => 'required|string',
            'handlingID'   => 'required|string',
            'accessToken'  => 'required|string',
        ]);
        if ($validator->fails()) return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);

        return $this->forwardRequest('Cargo/HandlingSurcharge', $request->only(['supplierName', 'handlingID', 'accessToken']));
    }

    /**
     * =========================================================
     * 10. GET TARIFF (CEK ONGKIR CARGO)
     * =========================================================
     */
    public function tariff(Request $request)
    {
        Log::info("\n========== [CARGO TARIFF] ==========");
        $validator = Validator::make($request->all(), [
            'supplierName'      => 'required|string',
            'pickupLocationID'  => 'required|string',
            'destinationAreaID' => 'required|string',
            'handlingID'        => 'required|string',
            'pieces'            => 'required|array',
            'accessToken'       => 'required|string',
        ]);
        if ($validator->fails()) return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);

        return $this->forwardRequest('Cargo/Tariff', $request->only([
            'supplierName', 'pickupLocationID', 'destinationAreaID', 'handlingID', 'pieces', 'accessToken'
        ]));
    }

    /**
     * =========================================================
     * 11. GET PRICE DETAIL
     * =========================================================
     */
    public function priceDetail(Request $request)
    {
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

        return $this->forwardRequest('Cargo/PriceDetail', $request->all());
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
            'handlingID'         => 'required|string',
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
            // Field lainnya biarkan lewat jika ada, dokumen butuh sangat banyak field
        ]);

        if ($validator->fails()) return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);

        // TODO: Anda bisa menambahkan logika potong saldo Sancaka di sini sebelum hit ke Darmawisata
        // seperti yang dilakukan di PPOB.

        return $this->forwardRequest('Cargo/Booking', $request->all());
    }

    /**
     * =========================================================
     * 13. GET BOOKING LIST
     * =========================================================
     */
    public function bookingList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'startDate'   => 'required|date',
            'endDate'     => 'required|date',
            'accessToken' => 'required|string',
        ]);
        if ($validator->fails()) return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);

        return $this->forwardRequest('Cargo/BookingList', $request->only(['startDate', 'endDate', 'accessToken']));
    }

    /**
     * =========================================================
     * 14. GET BOOKING DETAIL
     * =========================================================
     */
    public function bookingDetail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sttNumber'   => 'required|string',
            'orderID'     => 'required|string',
            'bookingDate' => 'required|date',
            'accessToken' => 'required|string',
        ]);
        if ($validator->fails()) return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);

        return $this->forwardRequest('Cargo/BookingDetail', $request->only(['sttNumber', 'orderID', 'bookingDate', 'accessToken']));
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

        if ($validator->fails()) return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);

        $user = $request->user();
        $userId = $user->id_pengguna ?? $user->id;

        $payload = [
            'supplierName' => $request->supplierName,
            'sttNumber'    => $request->sttNumber,
            'accessToken'  => $request->accessToken
        ];

        try {
            $response = $this->forwardRequest('Cargo/Tracking', $payload);
            $json = json_decode($response->getContent(), true);

            if (isset($json['status']) && $json['status'] === 'SUCCESS') {
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

            return response()->json($json);

        } catch (\Exception $e) {
            Log::error("Cargo Tracking Error: " . $e->getMessage());
            return response()->json(['status' => 'FAILED', 'message' => 'System Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * =========================================================
     * 16. GET HISTORY TRACKING LOKAL
     * =========================================================
     */
    public function history(Request $request)
    {
        $user = $request->user();
        $userId = $user->id_pengguna ?? $user->id;

        try {
            $history = DB::table('dw_cargo_histories')
                ->where('user_id', $userId)
                ->orderBy('last_tracked', 'desc')
                ->get();

            return response()->json(['status' => 'SUCCESS', 'data' => $history]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'FAILED', 'message' => 'Gagal mengambil riwayat'], 500);
        }
    }

    /**
     * =========================================================
     * 17. HAPUS HISTORY (BULK DELETE KHUSUS ADMIN)
     * =========================================================
     */
    public function bulkDestroyHistory(Request $request)
    {
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
            return response()->json(['status' => 'SUCCESS', 'message' => 'Riwayat berhasil dihapus']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'FAILED', 'message' => 'Gagal menghapus data di database.'], 500);
        }
    }
}