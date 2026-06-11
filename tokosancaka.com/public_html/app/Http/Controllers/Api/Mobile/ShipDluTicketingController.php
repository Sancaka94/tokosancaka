<?php 

namespace App\Http\Controllers\Api\Mobile;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ShipDluTicketingController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function shipDluRoute(Request $request)
    {
        Log::info("\n========== [SHIP DLU ROUTE - START] ==========");
        $payload = ['userID' => $this->darmawisataUserId, 'accessToken' => $request->accessToken];
        return $this->forwardRequest('ShipDlu/Route', $payload);
    }

    public function shipDluSchedule(Request $request)
    {
        Log::info("\n========== [SHIP DLU SCHEDULE - START] ==========");
        Log::info("Payload Request Mobile: ", $request->all());

        $validator = Validator::make($request->all(), [
            'ticketType'      => 'required|string',
            'paxClass'        => 'required|string',
            'vehicleType'     => 'nullable|string',
            'roomClass'       => 'nullable|string',
            'originPort'      => 'required|string',
            'destinationPort' => 'required|string',
            'departStartDate' => 'required|string',
            'departEndDate'   => 'required|string',
            'accessToken'     => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::warning("ShipDlu Schedule Validasi Gagal: ", $validator->errors()->toArray());
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        $payload = [
            'ticketType'      => $request->ticketType,
            'paxClass'        => $request->paxClass,
            'vehicleType'     => $request->vehicleType ?? "",
            'roomClass'       => $request->roomClass ?? "",
            'originPort'      => $request->originPort,
            'destinationPort' => $request->destinationPort,
            // Format Wajib ISO 8601 dengan Timezone (+07:00) menggunakan huruf 'P'
            'departStartDate' => date('Y-m-d\T00:00:00P', strtotime($request->departStartDate)),
            'departEndDate'   => date('Y-m-d\T23:59:59P', strtotime($request->departEndDate)),
            'userID'          => $this->darmawisataUserId,
            'accessToken'     => $request->accessToken
        ];

        Log::info("Payload to Darmawisata [ShipDlu/Schedule]: ", $payload);
        return $this->forwardRequest('ShipDlu/Schedule', $payload);
    }

    public function shipDluSelectSchedule(Request $request)
    {
        Log::info("\n========== [SHIP DLU SELECT SCHEDULE - START] ==========");

        $validator = Validator::make($request->all(), [
            'originPort'      => 'required|string',
            'destinationPort' => 'required|string',
            'shipNumber'      => 'required|string',
            'departDate'      => 'required|string',
            'fares'           => 'required|array',
            'shipID'          => 'required|string',
            'accessToken'     => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        $payload = [
            'originPort'      => $request->originPort,
            'destinationPort' => $request->destinationPort,
            'shipNumber'      => $request->shipNumber,
            'departDate'      => date('c', strtotime($request->departDate)), // ISO 8601
            'fares'           => $request->fares,
            'shipID'          => $request->shipID,
            'userID'          => $this->darmawisataUserId,
            'accessToken'     => $request->accessToken
        ];

        Log::info("Payload to Darmawisata [ShipDlu/SelectDLUSchedule]: ", $payload);
        return $this->forwardRequest('ShipDlu/SelectDLUSchedule', $payload);
    }

    public function shipDluPrice(Request $request)
    {
        Log::info("\n========== [SHIP DLU PRICE - START] ==========");

        $validator = Validator::make($request->all(), [
            'originPort'      => 'required|string',
            'destinationPort' => 'required|string',
            'shipNumber'      => 'required|string',
            'departDate'      => 'required|string',
            'fares'           => 'required|array',
            'shipID'          => 'required|string',
            'accessToken'     => 'required|string',
            'listPax'         => 'nullable|array',
            'listVehicle'     => 'nullable|array',
            'listRoom'        => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        $payload = [
            'originPort'      => $request->originPort,
            'destinationPort' => $request->destinationPort,
            'shipNumber'      => $request->shipNumber,
            'listPax'         => $request->listPax ?? [],
            'listVehicle'     => $request->listVehicle ?? [],
            'listRoom'        => $request->listRoom ?? [],
            'departDate'      => date('c', strtotime($request->departDate)), // ISO 8601
            'fares'           => $request->fares,
            'shipID'          => $request->shipID,
            'userID'          => $this->darmawisataUserId,
            'accessToken'     => $request->accessToken,
        ];

        Log::info("Payload to Darmawisata [ShipDlu/Price]: ", $payload);
        return $this->forwardRequest('ShipDlu/Price', $payload);
    }

    public function shipDluGetEticket(Request $request)
    {
        Log::info("\n========== [SHIP DLU GET ETICKET - START] ==========");

        $validator = Validator::make($request->all(), [
            'bookingNumber' => 'required|string',
            'accessToken'   => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        $payload = [
            'bookingNumber' => $request->bookingNumber,
            'userID'        => $this->darmawisataUserId,
            'accessToken'   => $request->accessToken,
        ];

        return $this->forwardRequest('ShipDlu/GetEticket', $payload);
    }

    public function shipDluIssued(Request $request)
    {
        Log::info("\n========== [SHIP DLU ISSUED - START] ==========");
        Log::info("Payload Request Mobile: ", $request->all());

        // 1. Validate incoming request
        $validator = Validator::make($request->all(), [
            'originPort'      => 'required|string',
            'destinationPort' => 'required|string',
            'shipNumber'      => 'required|string',
            'departDate'      => 'required|string|date', 
            'listPax'         => 'nullable|array',
            'listVehicle'     => 'nullable|array',
            'bookerData'      => 'required|array',
            'bookerData.name' => 'required|string',
            'bookerData.phone'=> 'required|string',
            'numCode'         => 'required|string',
            'listRoom'        => 'nullable|array',
            'shipID'          => 'required|string',
            'accessToken'     => 'required|string',
            'totalAmount'     => 'required|numeric' // Terima tagihan total agen dari frontend
        ]);

        if ($validator->fails()) {
            Log::warning("ShipDlu Issued Validasi Gagal: ", $validator->errors()->toArray());
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        $orderId = null;
        try {
            // STEP A: SIMPAN DRAFT LOKAL
            Log::info("Proses simpan PENDING_ISSUED DLU ke database lokal...");

            $user = $request->user();
            $totalAmountAgent = (float) $request->totalAmount;

            if (!$user || $user->saldo < $totalAmountAgent) {
                return response()->json([
                    'status' => 'FAILED', 
                    'message' => 'Saldo tidak cukup atau sesi tidak valid. Butuh: Rp ' . number_format($totalAmountAgent, 0, ',', '.')
                ], 400);
            }

            $orderId = DB::transaction(function () use ($request, $user) {
                $bookerDataJson = json_encode($request->bookerData);
                $listPaxJson = json_encode($request->listPax ?? []);
                $listVehicleJson = json_encode($request->listVehicle ?? []);
                $listRoomJson = json_encode($request->listRoom ?? []);

                return DB::table('ship_dlu_orders')->insertGetId([
                    'user_id'           => $user->id_pengguna ?? $user->id,
                    'dw_access_token'   => $request->accessToken,
                    'origin_port'       => $request->originPort,
                    'destination_port'  => $request->destinationPort,
                    'ship_number'       => $request->shipNumber,
                    'depart_date'       => date('Y-m-d H:i:s', strtotime($request->departDate)),
                    'booker_data'       => $bookerDataJson,
                    'list_pax'          => $listPaxJson,
                    'list_vehicle'      => $listVehicleJson,
                    'list_room'         => $listRoomJson,
                    'num_code'          => $request->numCode,
                    'ship_id'           => $request->shipID,
                    'status'            => 'PENDING_ISSUED', 
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
            });

            // STEP B: RAKIT PAYLOAD DARMAWISATA
            $dwPayload = [
                'originPort'      => $request->originPort,
                'destinationPort' => $request->destinationPort,
                'shipNumber'      => $request->shipNumber,
                'departDate'      => date('c', strtotime($request->departDate)), // ISO 8601
                'listPax'         => $request->listPax ?? [],
                'listVehicle'     => $request->listVehicle ?? [],
                'bookerData'      => $request->bookerData,
                'numCode'         => $request->numCode,
                'listRoom'        => $request->listRoom ?? [],
                'shipID'          => $request->shipID,
                'userID'          => $this->darmawisataUserId,
                'accessToken'     => $request->accessToken,
            ];

            Log::info("Payload to Darmawisata [ShipDlu/Issued]: ", $dwPayload);

            // STEP C: TEMBAK API DARMAWISATA
            $response = $this->forwardRequest('ShipDlu/Issued', $dwPayload);
            $json = json_decode($response->getContent(), true);

            Log::info("Response Darmawisata [ShipDlu/Issued]: ", $json ?? ['error' => 'No JSON Response']);

            // ==============================================================
            // LOGIKA PROCESSED & TYPO DARI PUSAT (Mirip dengan tiket PELNI)
            // ==============================================================
            $isSuccess = isset($json['status']) && $json['status'] === 'SUCCESS';
            $isProcessed = isset($json['respMessage']) && str_contains(strtolower($json['respMessage']), 'processed');

            if ($isSuccess || $isProcessed) {
                // Harga dari pusat, atau fallback pakai yang sudah dihitung frontend
                $totalPrice = (float) ($json['ticketPrice'] ?? $json['salesPrice'] ?? $totalAmountAgent);

                DB::transaction(function () use ($user, $totalPrice, $orderId, $json, $isProcessed) {
                    // Potong saldo
                    DB::table('Pengguna')->where('id_pengguna', $user->id_pengguna)->decrement('saldo', $totalPrice);
                    DB::table('Pengguna')->where('id_pengguna', 4)->decrement('balance_iak', $totalPrice);

                    // Amankan Typo JSON dari API
                    $bookingDateTime = $json['bookingDateTime'] ?? $json['booking DateTime'] ?? null;
                    $issuedDateTimeLimit = $json['issuedDateTimeLimit'] ?? $json['issued DateTimeLimit'] ?? null;
                    $bookingNumber = $json['bookingNumber'] ?? $json['bokingNumber'] ?? null;
                    $finalStatus = $isProcessed ? 'PROCESSED' : 'ISSUED';

                    DB::table('ship_dlu_orders')->where('id', $orderId)->update([
                        'booking_number'      => $bookingNumber,
                        'sales_price'         => $json['salesPrice'] ?? $totalPrice,
                        'member_discount'     => $json['memberDiscount'] ?? 0,
                        'ship_markup'         => $json['shipMarkup'] ?? 0,
                        'ticket_price'        => $json['ticketPrice'] ?? $totalPrice,
                        'issued_time_limit'   => $issuedDateTimeLimit ? date('Y-m-d H:i:s', strtotime($issuedDateTimeLimit)) : null,
                        'booking_time'        => $bookingDateTime ? date('Y-m-d H:i:s', strtotime($bookingDateTime)) : null,
                        'status'              => $finalStatus,
                        'updated_at'          => now(),
                    ]);
                });

                $msg = $isProcessed ? 'Pembayaran berhasil! Tiket DLU sedang diproses oleh pusat.' : 'Tiket Kapal DLU Berhasil Dicetak (LUNAS)!';
                return response()->json([
                    'status'        => 'SUCCESS',
                    'bookingNumber' => $json['bookingNumber'] ?? $json['bokingNumber'] ?? 'PROSES',
                    'message'       => $msg,
                    'data'          => $json
                ]);

            } else {
                // API GAGAL
                $message = $json['respMessage'] ?? 'Kapal DLU menolak penerbitan tiket.';

                // TANGKAL TIKET SUDAH ISSUED
                if (str_contains(strtolower($message), "ticketed can't be issued") || str_contains(strtolower($message), 'already issued')) {
                    DB::table('ship_dlu_orders')->where('id', $orderId)->update([
                        'status'         => 'ISSUED',
                        'updated_at'     => now()
                    ]);
                    
                    return response()->json([
                        'status'          => 'SUCCESS',
                        'message'         => 'Sinkronisasi berhasil! Tiket DLU ini sebelumnya sudah sukses diterbitkan.',
                        'data'            => $json
                    ]);
                }

                if ($orderId) {
                    DB::table('ship_dlu_orders')->where('id', $orderId)->update(['status' => 'FAILED', 'updated_at' => now()]);
                }

                if (str_contains(strtolower($message), 'insufficient balance')) {
                    Log::error("FATAL: Gagal Issued Kapal DLU karena Saldo H2H Pusat Darmawisata Habis!");
                    return response()->json([
                        'status' => 'FAILED',
                        'message' => 'Tiket gagal diterbitkan: Saldo deposit pusat tidak cukup. Hubungi admin.'
                    ]);
                }

                return response()->json(['status' => 'FAILED', 'message' => 'Gagal Issued Kapal DLU: ' . $message]);
            }

        } catch (\Exception $e) {
            Log::error("FATAL ERROR [Ship DLU Issued]: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
            if ($orderId) {
                DB::table('ship_dlu_orders')->where('id', $orderId)->update(['status' => 'FAILED_SYSTEM_ERROR', 'updated_at' => now()]);
            }
            return response()->json(['status' => 'FAILED', 'message' => 'System Error: ' . $e->getMessage()], 500);
        }
    }

    public function shipDluClassTypes(Request $request)
    {
        $payload = ['userID' => $this->darmawisataUserId, 'accessToken' => $request->accessToken];
        return $this->forwardRequest('ShipDlu/ClassTypes', $payload);
    }

    public function shipDlueVehicleTypes(Request $request)
    {
        $payload = ['userID' => $this->darmawisataUserId, 'accessToken' => $request->accessToken];
        return $this->forwardRequest('ShipDlu/VehicleTypes', $payload);
    }

    public function shipDluTicketTypes(Request $request)
    {
        $payload = ['userID' => $this->darmawisataUserId, 'accessToken' => $request->accessToken];
        return $this->forwardRequest('ShipDlu/TicketTypes', $payload);
    }

    public function shipDluRoomClasses(Request $request)
    {
        $payload = ['userID' => $this->darmawisataUserId, 'accessToken' => $request->accessToken];
        return $this->forwardRequest('ShipDlu/RoomClasses', $payload);
    }

    public function shipDluHistory(Request $request)
    {
        Log::info("\n========== [SHIP DLU HISTORY - START] ==========");

        try {
            $user = $request->user();

            $orders = DB::table('ship_dlu_orders')
                ->where('user_id', $user->id_pengguna ?? $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            $formattedData = $orders->map(function ($order) {
                return [
                    'id'            => $order->id,
                    // Penyesuaian agar numCode bisa tertangkap untuk detail PDF
                    'bookingNumber' => $order->booking_number ?? $order->num_code ?? 'PROSES',
                    'numCode'       => $order->num_code ?? '',
                    'shipName'      => $order->ship_number ?? null,
                    'subClass'      => $order->ship_markup ?? null,
                    'bookingDate'   => $order->booking_time ?? $order->created_at,
                    'bookingTime'   => $order->booking_time ?? null,
                    'origin'        => $order->origin_port ?? null,
                    'destination'   => $order->destination_port ?? null,
                    'departDate'    => $order->depart_date ?? null,
                    'departTime'    => $order->depart_time ?? null,
                    'status'        => $order->status,
                    // Penyesuaian Harga dari backend
                    'totalFare'     => (float) ($order->sales_price ?? $order->ticket_price ?? 0),
                    'ticketPrice'   => (float) ($order->ticket_price ?? 0),
                    'paymentMethod' => $order->payment_method ?? 'SALDO',
                    'timeLimit'     => $order->issued_time_limit ?? null,
                ];
            });

            return response()->json([
                'status' => 'SUCCESS',
                'data'   => $formattedData
            ], 200);

        } catch (\Exception $e) {
            Log::error("FATAL ERROR [Ship DLU History]: " . $e->getMessage());
            return response()->json([
                'status'  => 'FAILED',
                'message' => 'Sistem Error saat memuat riwayat.'
            ], 500);
        }
    }

}