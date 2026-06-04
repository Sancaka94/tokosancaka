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
            'ticketType'    => 'required|string',
            'paxClass'      => 'required|string',
            'vehicleType'   => 'required|string',
            'roomClass'     => 'required|string',
            'originPort'    => 'required|string',
            'destinationPort' => 'required|string',
            'departStartDate' => 'required|string',
            'departEndDate' => 'required|string',
            'accessToken'   => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::warning("ShipDlu Schedule Validasi Gagal: ", $validator->errors()->toArray());
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        $payload = [
            'ticketType'    => $request->ticketType,
            'paxClass'      => $request->paxClass,
            'vehicleType'   => $request->vehicleType,
            'roomClass'     => $request->roomClass,
            'originPort'    => $request->originPort,
            'destinationPort' => $request->destinationPort,
            'departStartDate' => date('Y-m-d\TH:i:s', strtotime($request->departStartDate)),
            'departEndDate' => date('Y-m-d\TH:i:s', strtotime($request->departEndDate)),
            'userID'        => $this->darmawisataUserId,
            'accessToken'   => $request->accessToken
        ];

        Log::info("Payload to Darmawisata [ShipDlu/Schedule]: ", $payload);
        $response = $this->forwardRequest('ShipDlu/Schedule', $payload);

        // Log opsional (bisa di-comment jika response terlalu panjang)
        // Log::info("Response Darmawisata [ShipDlu/Schedule]: " . $response->getContent());

        return $response;
    }

    public function shipDluSelectSchedule(Request $request)
    {
        Log::info("\n========== [SHIP DLU SELECT SCHEDULE - START] ==========");
        Log::info("Payload Request Mobile: ", $request->all());

        $validator = Validator::make($request->all(), [
            'originPort'    => 'required|string',
            'destinationPort' => 'required|string',
            'shipNumber'    => 'required|string',
            'departDate'    => 'required|string',
            'fares'         => 'required|array',
            'shipID'        => 'required|string',
            'accessToken'   => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::warning("ShipDlu Select Schedule Validasi Gagal: ", $validator->errors()->toArray());
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        $payload = [
            'originPort'    => $request->originPort,
            'destinationPort' => $request->destinationPort,
            'shipNumber'    => $request->shipNumber,
            'departDate'    => date('Y-m-d\TH:i:s', strtotime($request->departDate)), // Ensure ISO 8601 format
            'fares'         => $request->fares,
            'shipID'        => $request->shipID,
            'userID'        => $this->darmawisataUserId,
            'accessToken'   => $request->accessToken
        ];

        Log::info("Payload to Darmawisata [ShipDlu/SelectDLUSchedule]: ", $payload);
        $response = $this->forwardRequest('ShipDlu/SelectDLUSchedule', $payload);

        // Log opsional (bisa di-comment jika response terlalu panjang)
        // Log::info("Response Darmawisata [ShipDlu/SelectDLUSchedule]: " . $response->getContent());

        return $response;
    }

    public function shipDluPrice(Request $request)
    {
        Log::info("\n========== [SHIP DLU PRICE - START] ==========");
        Log::info("Payload Request Mobile: ", $request->all());

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
            Log::warning("ShipDlu Price Validasi Gagal: ", $validator->errors()->toArray());
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        $payload = [
            'originPort'      => $request->originPort,
            'destinationPort' => $request->destinationPort,
            'shipNumber'      => $request->shipNumber,
            'listPax'         => $request->listPax ?? [],
            'listVehicle'     => $request->listVehicle ?? [],
            'listRoom'        => $request->listRoom ?? [],
            'departDate'      => date('Y-m-d\TH:i:s', strtotime($request->departDate)),
            'fares'           => $request->fares,
            'shipID'          => $request->shipID,
            'userID'          => $this->darmawisataUserId,
            'accessToken'     => $request->accessToken,
        ];

        Log::info("Payload to Darmawisata [ShipDlu/Price]: ", $payload);
        $response = $this->forwardRequest('ShipDlu/Price', $payload);

        // Log opsional (bisa di-comment jika response terlalu panjang)
        // Log::info("Response Darmawisata [ShipDlu/Price]: " . $response->getContent());

        return $response;
    }

    public function shipDluGetEticket(Request $request)
    {
        Log::info("\n========== [SHIP DLU GET ETICKET - START] ==========");
        Log::info("Payload Request Mobile: ", $request->all());

        $validator = Validator::make($request->all(), [
            'bookingNumber' => 'required|string',
            'accessToken'   => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::warning("ShipDlu Get Eticket Validasi Gagal: ", $validator->errors()->toArray());
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        $payload = [
            'bookingNumber' => $request->bookingNumber,
            'userID'        => $this->darmawisataUserId,
            'accessToken'   => $request->accessToken,
        ];

        Log::info("Payload to Darmawisata [ShipDlu/GetEticket]: ", $payload);
        $response = $this->forwardRequest('ShipDlu/GetEticket', $payload);

        // Log opsional (bisa di-comment jika response terlalu panjang)
        // Log::info("Response Darmawisata [ShipDlu/GetEticket]: " . $response->getContent());

        return $response;
    }

    public function shipDluIssued(Request $request)
    {
        Log::info("\n========== [SHIP DLU ISSUED - START] ==========");
        Log::info("Payload Request Mobile: ", $request->all());

        // 1. Validate the incoming request payload
        $validator = Validator::make($request->all(), [
            'originPort'      => 'required|string',
            'destinationPort' => 'required|string',
            'shipNumber'      => 'required|string',
            'departDate'      => 'required|string|date', // 'date' rule to ensure it's a valid date string
            'listPax'         => 'nullable|array',
            'listVehicle'     => 'nullable|array',
            'bookerData'      => 'required|array',
            'bookerData.name' => 'required|string',
            'bookerData.phone'=> 'required|string',
            'numCode'         => 'required|string',
            'listRoom'        => 'nullable|array',
            'shipID'          => 'required|string',
            'accessToken'     => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::warning("ShipDlu Issued Validasi Gagal: ", $validator->errors()->toArray());
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        $orderId = null;
        try {
            // STEP A: SIMPAN DRAFT KE DATABASE LOKAL
            // Ini adalah panggilan Issued langsung, jadi kita menyimpannya dengan status 'PENDING_ISSUED'.
            // Jika panggilan API Darmawisata gagal, kita dapat menandainya sebagai 'FAILED'.
            Log::info("Proses simpan PENDING_ISSUED ke database lokal...");

            $orderId = DB::transaction(function () use ($request) {
                // Persiapkan data yang akan disimpan sebagai JSON string
                $bookerDataJson = json_encode($request->bookerData);
                $listPaxJson = json_encode($request->listPax ?? []);
                $listVehicleJson = json_encode($request->listVehicle ?? []);
                $listRoomJson = json_encode($request->listRoom ?? []);

                $id = DB::table('ship_dlu_orders')->insertGetId([
                    'user_id'           => $request->user()->id_pengguna ?? ($request->user()->id ?? null),
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
                    'status'            => 'PENDING_ISSUED', // Status awal
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
                return $id;
            });

            Log::info("Ship DLU Order PENDING_ISSUED berhasil dibuat. Local ID: " . $orderId);

            // STEP B: RAKIT PAYLOAD UNTUK DARMAWISATA
            $dwPayload = [
                'originPort'      => $request->originPort,
                'destinationPort' => $request->destinationPort,
                'shipNumber'      => $request->shipNumber,
                'departDate'      => date('Y-m-d\TH:i:s', strtotime($request->departDate)), // Format ISO 8601
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

            // STEP D: UPDATE DATABASE LOKAL BERDASARKAN RESPON DARMAWISATA
            if (isset($json['status']) && $json['status'] === 'SUCCESS') {
                $totalPrice = (float) ($json['ticketPrice'] ?? 0); // Ambil harga total dari respon Darmawisata

                $user = $request->user();
                if (!$user) {
                    Log::error("User tidak ditemukan untuk Issued Kapal DLU (ID: $orderId). Tidak dapat memotong saldo.");
                    DB::table('ship_dlu_orders')->where('id', $orderId)->update(['status' => 'FAILED_NO_USER', 'updated_at' => now()]);
                    return response()->json(['status' => 'FAILED', 'message' => 'User tidak terautentikasi. Silakan login kembali.'], 401);
                }

                $currentUser = DB::table('Pengguna')->where('id_pengguna', $user->id_pengguna)->first();
                if (!$currentUser || $currentUser->saldo < $totalPrice) {
                    Log::error("Gagal Issued Kapal DLU karena saldo User Lokal tidak cukup. Order ID: $orderId. Butuh: $totalPrice, Saldo: " . ($currentUser->saldo ?? 'N/A'));
                    DB::table('ship_dlu_orders')->where('id', $orderId)->update(['status' => 'FAILED_INSUFFICIENT_FUNDS', 'updated_at' => now()]);
                    return response()->json(['status' => 'FAILED', 'message' => 'Saldo tidak cukup untuk Issued tiket Kapal DLU.'], 400);
                }

                // Potong Saldo User & Agen dalam transaksi database untuk menjaga integritas data
                DB::transaction(function () use ($currentUser, $totalPrice, $orderId, $json) {
                    // Potong saldo pengguna
                    DB::table('Pengguna')->where('id_pengguna', $currentUser->id_pengguna)->decrement('saldo', $totalPrice);
                    // Potong saldo agen/admin (sesuai contoh TrainIssued)
                    DB::table('Pengguna')->where('id_pengguna', 4)->decrement('balance_iak', $totalPrice);

                    // Update data order lokal dengan informasi dari respon Darmawisata
                    DB::table('ship_dlu_orders')->where('id', $orderId)->update([
                        'booking_number'      => $json['bookingNumber'] ?? null,
                        'sales_price'         => $json['salesPrice'] ?? 0,
                        'member_discount'     => $json['memberDiscount'] ?? 0,
                        'ship_markup'         => $json['shipMarkup'] ?? 0,
                        'ticket_price'        => $json['ticketPrice'] ?? 0,
                        'issued_time_limit'   => isset($json['issuedDateTimeLimit']) ? date('Y-m-d H:i:s', strtotime($json['issuedDateTimeLimit'])) : null,
                        'booking_time'        => isset($json['bookingDateTime']) ? date('Y-m-d H:i:s', strtotime($json['bookingDateTime'])) : null,
                        'status'              => 'ISSUED',
                        'updated_at'          => now(),
                    ]);
                });

                Log::info("Ship DLU Order ISSUED SUKSES. Booking Number: " . ($json['bookingNumber'] ?? 'N/A') . " | Saldo terpotong: " . $totalPrice);

                return response()->json([
                    'status'      => 'SUCCESS',
                    'bookingNumber' => $json['bookingNumber'] ?? 'N/A',
                    'message'     => 'Tiket Kapal DLU Berhasil Dicetak (LUNAS) dan Saldo Terpotong!',
                    'data'        => $json
                ]);

            } else {
                // Darmawisata API mengembalikan status FAILED
                $message = $json['respMessage'] ?? 'Kapal DLU menolak penerbitan tiket.';

                // Update status order lokal menjadi FAILED
                if ($orderId) {
                    DB::table('ship_dlu_orders')->where('id', $orderId)->update(['status' => 'FAILED', 'updated_at' => now()]);
                }

                // Tangani pesan error spesifik jika diperlukan, mirip dengan contoh TrainIssued
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
            // Jika terjadi kesalahan sistem, pastikan order lokal ditandai sebagai FAILED jika sudah dibuat.
            if ($orderId) {
                DB::table('ship_dlu_orders')->where('id', $orderId)->update(['status' => 'FAILED_SYSTEM_ERROR', 'updated_at' => now()]);
            }
            return response()->json(['status' => 'FAILED', 'message' => 'System Error: ' . $e->getMessage()], 500);
        }
    }

    public function shipDluClassTypes(Request $request)
    {
        Log::info("\n========== [SHIP DLU CLASS TYPES - START] ==========");
        Log::info("Payload Request Mobile: ", $request->all());

        $validator = Validator::make($request->all(), [
            'accessToken' => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::warning("ShipDlu Class Types Validasi Gagal: ", $validator->errors()->toArray());
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        $payload = [
            'userID' => $this->darmawisataUserId,
            'accessToken' => $request->accessToken
        ];

        Log::info("Payload to Darmawisata [ShipDlu/ClassTypes]: ", $payload);
        $response = $this->forwardRequest('ShipDlu/ClassTypes', $payload);

        // Log opsional (bisa di-comment jika response terlalu panjang)
        // Log::info("Response Darmawisata [ShipDlu/ClassTypes]: " . $response->getContent());

        return $response;
    }

    public function shipDlueVehicleTypes(Request $request)
    {
        Log::info("\n========== [SHIP DLU VEHICLE TYPES - START] ==========");
        Log::info("Payload Request Mobile: ", $request->all());

        $validator = Validator::make($request->all(), [
            'accessToken' => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::warning("ShipDlu Vehicle Types Validasi Gagal: ", $validator->errors()->toArray());
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        $payload = [
            'userID' => $this->darmawisataUserId,
            'accessToken' => $request->accessToken
        ];

        Log::info("Payload to Darmawisata [ShipDlu/VehicleTypes]: ", $payload);
        $response = $this->forwardRequest('ShipDlu/VehicleTypes', $payload);

        // Log opsional (bisa di-comment jika response terlalu panjang)
        // Log::info("Response Darmawisata [ShipDlu/VehicleTypes]: " . $response->getContent());

        return $response;
    }

    public function shipDluTicketTypes(Request $request)
    {
        Log::info("\n========== [SHIP DLU TICKET TYPES - START] ==========");
        Log::info("Payload Request Mobile: ", $request->all());

        $validator = Validator::make($request->all(), [
            'accessToken' => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::warning("ShipDlu Ticket Types Validasi Gagal: ", $validator->errors()->toArray());
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        $payload = [
            'userID' => $this->darmawisataUserId,
            'accessToken' => $request->accessToken
        ];

        Log::info("Payload to Darmawisata [ShipDlu/TicketTypes]: ", $payload);
        $response = $this->forwardRequest('ShipDlu/TicketTypes', $payload);

        // Log opsional (bisa di-comment jika response terlalu panjang)
        // Log::info("Response Darmawisata [ShipDlu/TicketTypes]: " . $response->getContent());

        return $response;
    }

    public function shipDluRoomClasses(Request $request)
    {
        Log::info("\n========== [SHIP DLU ROOM CLASSES - START] ==========");
        Log::info("Payload Request Mobile: ", $request->all());

        $validator = Validator::make($request->all(), [
            'accessToken' => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::warning("ShipDlu Room Classes Validasi Gagal: ", $validator->errors()->toArray());
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        $payload = [
            'userID' => $this->darmawisataUserId,
            'accessToken' => $request->accessToken
        ];

        Log::info("Payload to Darmawisata [ShipDlu/RoomClasses]: ", $payload);
        $response = $this->forwardRequest('ShipDlu/RoomClasses', $payload);

        // Log opsional (bisa di-comment jika response terlalu panjang)
        // Log::info("Response Darmawisata [ShipDlu/RoomClasses]: " . $response->getContent());

        return $response;
    }

    public function shipDluHistory(Request $request)
    {
        Log::info("\n========== [SHIP DLU HISTORY - START] ==========");

        try {
            $user = $request->user();

            // Ambil data riwayat dari database lokal berdasarkan user yang login
            $orders = DB::table('ship_dlu_orders')
                ->where('user_id', $user->id_pengguna ?? $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            // Format data agar sesuai dengan interface yang digunakan di mobile
            $formattedData = $orders->map(function ($order) {
                return [
                    'id'            => $order->id,
                    'bookingNumber' => $order->booking_number ?? 'PROSES',
                    'shipName'      => $order->ship_number ?? null,
                    'subClass'      => $order->ship_markup ?? null,
                    'bookingDate'   => $order->booking_time ?? $order->created_at,
                    'bookingTime'   => $order->booking_time ?? null,
                    'origin'        => $order->origin_port ?? null,
                    'destination'   => $order->destination_port ?? null,
                    'departDate'    => $order->depart_date ?? null,
                    'departTime'    => $order->depart_time ?? null,
                    'paxAdult'      => isset($order->pax_counts) ? ($order->pax_counts['adult'] ?? 0) : 0,
                    'paxChild'      => isset($order->pax_counts) ? ($order->pax_counts['child'] ?? 0) : 0,
                    'paxInfant'     => isset($order->pax_counts) ? ($order->pax_counts['infant'] ?? 0) : 0,
                    'status'        => $order->status,
                    'totalFare'     => (float) ($order->sales_price ?? 0),
                    'ticketPrice'   => (float) ($order->ticket_price ?? 0),
                    'paymentMethod' => $order->payment_method ?? 'SALDO',
                    'paymentUrl'    => $order->payment_url ?? null,
                    'timeLimit'     => $order->issued_time_limit ?? null,
                ];
            });

            Log::info("Berhasil mengambil " . $formattedData->count() . " riwayat transaksi kapal DLU.");

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