<?php

namespace App\Http\Controllers\Api\Mobile;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ShipTicketingController extends BaseController
{
    public function __construct()
    {
    
        parent::__construct();
    }

    // ========================================================================
    // Ship Ticketing API method signatures (no logic)
    // ========================================================================


    public function shipRoute(Request $request)
    {
        Log::info("\n========== [SHIP ROUTE - START] ==========");

        $validator = Validator::make($request->all(), [
            'accessToken' => 'required|string'
        ]);

        if ($validator->fails()) {
            Log::warning('Ship Route validation failed: ', $validator->errors()->toArray());
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        $payload = [
            'userID'      => $this->darmawisataUserId ?? $request->userID ?? null,
            'accessToken' => $request->accessToken
        ];

        Log::info('Payload to Darmawisata [Ship/Route]: ', $payload);
        $response = $this->forwardRequest('Ship/Route', $payload);

        $json = json_decode($response->getContent(), true);

        // Backup master route data locally for fallback
        if (isset($json['status']) && $json['status'] === 'SUCCESS') {
            try {
                DB::table('ship_routes')->updateOrInsert(
                    ['user_id' => $payload['userID']],
                    [
                        'payload'    => json_encode($json),
                        'updated_at' => now(),
                        'created_at' => now()
                    ]
                );
                Log::info('Ship routes saved to DB backup.');
            } catch (\Exception $e) {
                Log::warning('Failed to save ship routes backup: ' . $e->getMessage());
            }
        }

        return $response;
    }

    public function shipSchedule(Request $request)
    {
        Log::info("\n========== [SHIP SCHEDULE - START] ==========");
        Log::info("Payload Request Mobile: ", $request->all());

        $validator = Validator::make($request->all(), [
            'originPort'      => 'required|string',
            'destinationPort' => 'required|string',
            'departStartDate' => 'required|string',
            'departEndDate'   => 'required|string',
            'accessToken'     => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::warning("Ship Schedule Validasi Gagal: ", $validator->errors()->toArray());
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        $payload = [
            'originPort'       => $request->originPort,
            'destinationPort'  => $request->destinationPort, // <-- WAJIB PAKAI SPASI DI TENGAH
            'departStartDate'  => date('c', strtotime($request->departStartDate)), // <-- PASTIKAN PAKAI date('c')
            'departEndDate'    => date('c', strtotime($request->departEndDate)),   // <-- PASTIKAN PAKAI date('c')
            'userID'           => $this->darmawisataUserId,
            'accessToken'      => $request->accessToken,
        ];

        Log::info("Payload to Darmawisata [Ship/Schedule]: ", $payload);
        $response = $this->forwardRequest('Ship/Schedule', $payload);

        // Log (bisa di-comment jika response terlalu panjang)
        Log::info("Response Darmawisata [Ship/Schedule]: " . $response->getContent());

        return $response;
    }

    public function shipAvailability(Request $request)
    {
        Log::info("\n========== [SHIP AVAILABILITY - START] ==========");
        Log::info("Payload Request Mobile: ", $request->all());

        $validator = Validator::make($request->all(), [
            'originPort'      => 'required|string',
            'originCall'      => 'required|integer',
            'destinationPort' => 'required|string',
            'destinationCall' => 'required|integer',
            'shipNumber'      => 'required|string',
            'departDate'      => 'required|string',
            'subClass'        => 'required|string',
            'pax'             => 'required|array',
            'accessToken'     => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::warning("Ship Availability Validasi Gagal: ", $validator->errors()->toArray());
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        $payload = [
            'originPort'      => $request->originPort,
            'originCall'      => $request->originCall,
            'destinationPort' => $request->destinationPort,
            'destinationCall' => $request->destinationCall,
            'shipNumber'      => $request->shipNumber,
            'departDate'      => date('Y-m-d\\TH:i:s', strtotime($request->departDate)), // Format sesuai Darmawisata
            'subClass'        => $request->subClass,
            'pax'             => $request->pax,
            'userID'          => $this->darmawisataUserId,
            'accessToken'     => $request->accessToken,
        ];

        Log::info("Payload to Darmawisata [Ship/Availability]: ", $payload);
        $response = $this->forwardRequest('Ship/Availability', $payload);

        // Log (bisa di-comment jika response terlalu panjang)
        Log::info("Response Darmawisata [Ship/Availability]: " . $response->getContent());

        return $response;
    }

    public function shipGetRoom(Request $request)
    {
        Log::info("\n========== [SHIP GET ROOM - START] ==========");
        Log::info("Payload Request Mobile: ", $request->all());

        $validator = Validator::make($request->all(), [
            'originPort'       => 'required|string',
            'originCall'       => 'required|integer',
            'destinationPort'  => 'required|string',
            'destinationCall'  => 'required|integer',
            'shipNumber'       => 'required|string',
            'departDate'       => 'required|string', // Will be formatted to Darmawisata's expected format
            'subClass'         => 'required|string',
            'pax'              => 'required|array',
            'pax.*.paxType'    => 'required|integer',
            'pax.*.paxGender'  => 'required|integer',
            'pax.*.paxTotal'   => 'required|integer',
            'ticketBuyerName'  => 'required|string',
            'ticketBuyerEmail' => 'required|email',
            'ticketBuyerAddress' => 'required|string',
            'ticketBuyerPhone' => 'required|string',
            'family'           => 'boolean', // Optional, will default to false if not present
            'accessToken'      => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::warning("Ship Get Room Validasi Gagal: ", $validator->errors()->toArray());
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        $payload = [
            'originPort'       => $request->originPort,
            'originCall'       => $request->originCall,
            'destinationPort'  => $request->destinationPort,
            'destinationCall'  => $request->destinationCall,
            'shipNumber'       => $request->shipNumber,
            'departDate'       => date('Y-m-d\TH:i:s', strtotime($request->departDate)), // Format sesuai Darmawisata
            'subClass'         => $request->subClass,
            'pax'              => $request->pax,
            'ticketBuyerName'  => $request->ticketBuyerName,
            'ticketBuyerEmail' => $request->ticketBuyerEmail,
            'ticketBuyerAddress' => $request->ticketBuyerAddress,
            'ticketBuyerPhone' => $request->ticketBuyerPhone,
            'family'           => (bool) ($request->family ?? false), // Ensure boolean type, default to false
            'userID'           => $this->darmawisataUserId,
            'accessToken'      => $request->accessToken,
        ];

        Log::info("Payload to Darmawisata [Ship/GetRoom]: ", $payload);
        $response = $this->forwardRequest('Ship/GetRoom', $payload);

        // Log (bisa di-comment jika response terlalu panjang)
        Log::info("Response Darmawisata [Ship/GetRoom]: " . $response->getContent());

        return $response;
    }

    public function shipBooking(Request $request)
    {
        Log::info("\n========== [SHIP BOOKING - START] ==========");
        Log::info("Payload Request Mobile: ", $request->all());

        $validator = Validator::make($request->all(), [
            'numCode'         => 'required|string',
            'originPort'      => 'required|string',
            'originCall'      => 'required|integer',
            'destinationPort' => 'required|string',
            'destinationCall' => 'required|integer',
            'shipNumber'      => 'required|string',
            'departDate'      => 'required|string',
            'paxDetails'      => 'required|array',
            'paxDetails.*.firstName' => 'required|string',
            'paxDetails.*.lastName'  => 'required|string',
            'paxDetails.*.birthDate' => 'required|string',
            'paxDetails.*.ID'        => 'required|string',
            'paxDetails.*.phone'     => 'required|string',
            'paxDetails.*.paxType'   => 'required|integer',
            'paxDetails.*.paxGender' => 'required|integer',
            'accessToken'     => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::warning("Ship Booking Validasi Gagal: ", $validator->errors()->toArray());
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        try {
            // STEP A: SIMPAN DATABASE STATUS DRAFT
            Log::info("Proses simpan DRAFT ke database lokal untuk kapal...");
            $orderId = DB::transaction(function () use ($request) {
                // Count pax types for initial order record
                $paxAdult = 0; $paxChild = 0; $paxInfant = 0;
                foreach ($request->paxDetails as $pax) {
                    if ($pax['paxType'] == 0) $paxAdult++;      // Adult
                    elseif ($pax['paxType'] == 1) $paxChild++;  // Child
                    elseif ($pax['paxType'] == 2) $paxInfant++; // Infant
                }

                $id = DB::table('ship_orders')->insertGetId([
                    'user_id'            => $request->user()->id_pengguna ?? null, // Assuming user is authenticated
                    'dw_access_token'    => $request->accessToken,
                    'num_code'           => $request->numCode,
                    'origin_port'        => $request->originPort,
                    'origin_call'        => $request->originCall,
                    'destination_port'   => $request->destinationPort,
                    'destination_call'   => $request->destinationCall,
                    'ship_number'        => $request->shipNumber,
                    'depart_date'        => date('Y-m-d H:i:s', strtotime($request->departDate)),
                    'pax_adult'          => $paxAdult,
                    'pax_child'          => $paxChild,
                    'pax_infant'         => $paxInfant,
                    'status'             => 'DRAFT',
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ]);

                foreach ($request->paxDetails as $pax) {
                    DB::table('ship_passengers')->insert([
                        'ship_order_id'  => $id,
                        'first_name'     => $pax['firstName'],
                        'last_name'      => $pax['lastName'],
                        'birth_date'     => date('Y-m-d H:i:s', strtotime($pax['birthDate'])),
                        'id_number'      => $pax['ID'],
                        'phone'          => $pax['phone'],
                        'pax_type'       => $pax['paxType'],
                        'pax_gender'     => $pax['paxGender'],
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ]);
                }
                return $id;
            });

            Log::info("Ship Order DRAFT berhasil dibuat. Local ID: " . $orderId);

            // STEP B: RAKIT PAYLOAD DARMAWISATA
            $dwPaxDetails = [];
            foreach ($request->paxDetails as $pax) {
                $dwPaxDetails[] = [
                    "firstName" => $pax['firstName'],
                    "lastName"  => $pax['lastName'],
                    "birthDate" => date('Y-m-d\TH:i:s', strtotime($pax['birthDate'])), // Format to ISO 8601
                    "ID"        => $pax['ID'],
                    "phone"     => $pax['phone'],
                    "paxType"   => $pax['paxType'],
                    "paxGender" => $pax['paxGender'],
                ];
            }

            $dwPayload = [
                "numCode"         => $request->numCode,
                "originPort"      => $request->originPort,
                "originCall"      => $request->originCall,
                "destinationPort" => $request->destinationPort,
                "destinationCall" => $request->destinationCall,
                "shipNumber"      => $request->shipNumber,
                "departDate"      => date('c', strtotime($request->departDate)),
                "paxDetails"      => $dwPaxDetails,
                "userID"          => $this->darmawisataUserId,
                "accessToken"     => $request->accessToken
            ];

            Log::info("Payload to Darmawisata [Ship/Booking]: ", $dwPayload);

            // No silent re-hit for Ship/Booking as it wasn't specified like in Train/Booking

            // STEP C: TEMBAK API
            $response = $this->forwardRequest('Ship/Booking', $dwPayload);
            $json = json_decode($response->getContent(), true);

            Log::info("Response Darmawisata [Ship/Booking]: ", $json ?? ['error' => 'No JSON Response']);

            // STEP D: UPDATE DATABASE LOKAL
            if (isset($json['status']) && $json['status'] === 'SUCCESS') {
                DB::table('ship_orders')->where('id', $orderId)->update([
                    'booking_number'         => $json['bookingNumber'] ?? null,
                    'issued_date_time_limit' => isset($json['issuedDateTimeLimit']) ? date('Y-m-d H:i:s', strtotime($json['issuedDateTimeLimit'])) : null,
                    'sales_price'            => $json['salesPrice'] ?? 0,
                    'member_discount'        => $json['memberDiscount'] ?? 0,
                    'ship_markup'            => $json['shipMarkup'] ?? 0,
                    'ticket_price'           => $json['ticketPrice'] ?? 0,
                    'booking_date_time'      => isset($json['bookingDateTime']) ? date('Y-m-d H:i:s', strtotime($json['bookingDateTime'])) : null,
                    'ship_name'              => $json['shipName'] ?? null,
                    'arrival_date'           => isset($json['arrivalDate']) ? date('Y-m-d H:i:s', strtotime($json['arrivalDate'])) : null,
                    'origin_name'            => $json['originName'] ?? null,
                    'destination_name'       => $json['destinationName'] ?? null,
                    'status'                 => 'HOLD',
                    'updated_at'             => now()
                ]);
                Log::info("Ship Order UPDATE ke HOLD sukses. Booking Number: " . ($json['bookingNumber'] ?? 'N/A'));

                // Update passenger details with deck, cabin, bed, ticket number, etc.
                if (isset($json['paxBookingDetails']) && is_array($json['paxBookingDetails'])) {
                    $localPassengers = DB::table('ship_passengers')->where('ship_order_id', $orderId)->get();
                    foreach ($json['paxBookingDetails'] as $dwPax) {
                        // Try to match Darmawisata passenger with local passenger using combination of names and birthdate
                        // This might need a more robust matching if names are not unique. For now, assume a simple match by name.
                        $matchedPax = $localPassengers->first(function($lp) use ($dwPax) {
                            // Matching by full name (first + last) and birthdate for better accuracy
                            $localFullName = strtolower($lp->first_name . ' ' . $lp->last_name);
                            $dwPaxFullName = strtolower($dwPax['paxName'] ?? '');
                            $localBirthDate = date('Y-m-d', strtotime($lp->birth_date));
                            $dwPaxBirthDate = isset($dwPax['birthDate']) ? date('Y-m-d', strtotime($dwPax['birthDate'])) : '';
                            return ($localFullName === $dwPaxFullName && $localBirthDate === $dwPaxBirthDate);
                        });

                        if ($matchedPax) {
                            DB::table('ship_passengers')->where('id', $matchedPax->id)->update([
                                'pax_name'       => $dwPax['paxName'] ?? null,
                                'deck'           => $dwPax['deck'] ?? null,
                                'cabin'          => $dwPax['cabin'] ?? null,
                                'bed'            => $dwPax['bed'] ?? null,
                                'fare'           => $dwPax['fare'] ?? 0,
                                'admin'          => $dwPax['admin'] ?? 0,
                                'ticket_number'  => $dwPax['ticketNumber'] ?? null,
                                'ticket_qr_code' => $dwPax['ticketQRCode'] ?? null,
                                'updated_at'     => now(),
                            ]);
                        } else {
                            Log::warning("Could not match Darmawisata passenger '" . ($dwPax['paxName'] ?? 'N/A') . "' with any local passenger for order ID {$orderId}.");
                        }
                    }
                }

                return response()->json($json);

            } else {
                $message = $json['respMessage'] ?? 'Kapal menolak pemesanan.';

                // Check for specific error messages if needed, similar to train
                // Example: insufficient balance, time limit expired, etc.
                if (str_contains(strtolower($message), 'insufficient balance')) {
                    Log::error("FATAL: Gagal Booking Kapal karena Saldo H2H Pusat Darmawisata Habis!");
                    return response()->json([
                        'status' => 'FAILED',
                        'message' => 'Pemesanan tiket kapal gagal: Saldo deposit pusat tidak cukup. Hubungi admin.'
                    ]);
                }

                // If booking failed, update local order status to FAILED
                DB::table('ship_orders')->where('id', $orderId)->update([
                    'status' => 'FAILED',
                    'dw_response_message' => $message, // Store Darmawisata's response message
                    'updated_at' => now()
                ]);
                Log::warning("Ship Booking Gagal. Local Order ID: {$orderId}. Message: {$message}");

                return response()->json(['status' => 'FAILED', 'message' => 'Gagal Booking Kapal: ' . $message]);
            }

        } catch (\Exception $e) {
            Log::error("FATAL ERROR [Ship Booking]: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
            // If a fatal error occurs before Darmawisata API call, or during database update
            // ensure the local order is marked as FAILED if it was created
            if (isset($orderId)) {
                DB::table('ship_orders')->where('id', $orderId)->update([
                    'status' => 'FAILED',
                    'dw_response_message' => 'System Error during booking: ' . $e->getMessage(),
                    'updated_at' => now()
                ]);
            }
            return response()->json(['status' => 'FAILED', 'message' => 'System Error: ' . $e->getMessage()], 500);
        }
    }

    public function shipBookingList(Request $request)
    {
        Log::info("\n========== [SHIP BOOKING LIST - START] ==========");
        Log::info("Payload Request Mobile: ", $request->all());

        $validator = Validator::make($request->all(), [
            'startDate'   => 'required|string',
            'endDate'     => 'required|string',
            'accessToken' => 'required|string',
            'filterBy'    => 'nullable|string',
        ]);

        if ($validator->fails()) {
            Log::warning("Ship Booking List Validasi Gagal: ", $validator->errors()->toArray());
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        $payload = [
            'filterBy'    => $request->filterBy ?? '0', // Default to '0' if not provided
            'startDate'   => date('Y-m-d\TH:i:s', strtotime($request->startDate)),
            'endDate'     => date('Y-m-d\TH:i:s', strtotime($request->endDate)),
            'userID'      => $this->darmawisataUserId,
            'accessToken' => $request->accessToken
        ];

        Log::info("Payload to Darmawisata [Ship/BookingList]: ", $payload);
        $response = $this->forwardRequest('Ship/BookingList', $payload);

        // Log (bisa di-comment jika response terlalu panjang)
        Log::info("Response Darmawisata [Ship/BookingList]: " . $response->getContent());

        return $response;
    }

    public function shipBookingDetail(Request $request)
    {
        Log::info("\n========== [SHIP BOOKING DETAIL - START] ==========");
        Log::info("Payload Request Mobile: ", $request->all());

        $validator = Validator::make($request->all(), [
            'numCode'       => 'required|string',
            'bookingDate'   => 'required|string',
            'bookingNumber' => 'required|string',
            'accessToken'   => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::warning("Ship Booking Detail Validasi Gagal: ", $validator->errors()->toArray());
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        try {
            $payload = [
                'numCode'       => $request->numCode,
                'bookingDate'   => date('Y-m-d\TH:i:s', strtotime($request->bookingDate)), // Format to ISO 8601
                'bookingNumber' => $request->bookingNumber,
                'userID'        => $this->darmawisataUserId,
                'accessToken'   => $request->accessToken
            ];

            Log::info("Payload to Darmawisata [Ship/BookingDetail]: ", $payload);

            // 1. Tembak API Darmawisata
            $response = $this->forwardRequest('Ship/BookingDetail', $payload);
            $json = json_decode($response->getContent(), true);

            Log::info("Response Darmawisata [Ship/BookingDetail]: ", $json ?? ['error' => 'No JSON Response']);

            // 2. LOGIKA BACKUP & FALLBACK
            if (isset($json['status']) && $json['status'] === 'SUCCESS') {
                // A. Jika API Darmawisata SUKSES, simpan/update payload utuh ke Database
                // Asumsi ada tabel `ship_booking_details` dengan kolom `booking_number`, `num_code`, `booking_date`, `payload`, `created_at`, `updated_at`
                DB::table('ship_booking_details')->updateOrInsert(
                    ['booking_number' => $request->bookingNumber],
                    [
                        'num_code'       => $request->numCode,
                        'booking_date'   => date('Y-m-d H:i:s', strtotime($request->bookingDate)), // Store original date for local reference
                        'payload'        => json_encode($json),
                        'updated_at'     => now()
                    ]
                );
                Log::info("Data detail tiket kapal Booking Number {$request->bookingNumber} berhasil disimpan ke DB Backup.");

                return $response; // Kembalikan data aslinya

            } else {
                // B. Jika API Darmawisata GAGAL (Token expired, timeout, dll)
                Log::warning("Darmawisata API Gagal mengambil detail tiket kapal. Alasan: " . ($json['respMessage'] ?? 'Unknown'));

                // Cek apakah kita punya backup data ini di database
                $backup = DB::table('ship_booking_details')->where('booking_number', $request->bookingNumber)->first();

                if ($backup && $backup->payload) {
                    Log::info("MENGGUNAKAN DATA BACKUP DARI DATABASE untuk Booking Number: {$request->bookingNumber}");

                    // Decode teks JSON dari database menjadi array PHP
                    $backupData = json_decode($backup->payload, true);

                    // Kembalikan response seolah-olah sukses
                    return response()->json($backupData, 200);
                }

                // Jika tidak ada backup sama sekali, kembalikan pesan error aslinya
                Log::error("Backup tidak ditemukan untuk Booking Number: {$request->bookingNumber}");
                return $response; // Return the original failed response from Darmawisata
            }
        } catch (\Exception $e) {
            Log::error("FATAL ERROR [Ship Booking Detail]: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
            return response()->json(['status' => 'FAILED', 'message' => 'System Error: ' . $e->getMessage()], 500);
        }
    }

    public function shipIssued(Request $request)
    {
        Log::info("\n========== [SHIP ISSUED - START] ==========");
        $validator = Validator::make($request->all(), ['order_id' => 'required|integer']);

        if ($validator->fails()) {
            Log::warning("Ship Issued Validasi Gagal: ", $validator->errors()->toArray());
            return response()->json(['status' => 'FAILED', 'message' => 'Order ID tidak valid'], 422);
        }

        try {
            $order = DB::table('ship_orders')->where('id', $request->order_id)->first();

            if (!$order) {
                Log::warning("Ship Issued Gagal: Pesanan tidak ditemukan untuk ID: " . $request->order_id);
                return response()->json(['status' => 'FAILED', 'message' => 'Pesanan tidak ditemukan.'], 404);
            }

            if ($order->status === 'ISSUED') {
                Log::info("Ship Issued: Tiket sudah Issued untuk Booking Number: " . ($order->booking_number ?? 'N/A'));
                return response()->json(['status' => 'FAILED', 'message' => 'Tiket sudah Issued.'], 400);
            }

            // ==========================================
            // VALIDASI Data Penting untuk Darmawisata
            // ==========================================
            if (empty($order->num_code) || empty($order->booking_number) || empty($order->booking_date_time) || empty($order->dw_access_token)) {
                Log::error('LOG LOG: [SHIP ISSUED] Gagal: Data booking tidak lengkap di database untuk order ID: ' . $order->id);
                return response()->json([
                    'status' => 'FAILED',
                    'message' => 'Data pemesanan kapal tidak lengkap. Pesanan ini kemungkinan gagal saat proses Booking.'
                ], 400);
            }

            // Pastikan format ISO 8601 (contoh: 2026-06-04T16:03:35+07:00) dan zona waktu
            $payloadIssued = [
                "numCode"     => $order->num_code,
                "bookingDate" => date('c', strtotime($order->booking_date_time)), // Use booking_date_time from DB, format to ISO 8601 with timezone
                "userID"      => $this->darmawisataUserId,
                "accessToken" => $order->dw_access_token
            ];

            Log::info("LOG LOG: [SHIP ISSUED] Payload to Darmawisata: ", $payloadIssued);
            $response = $this->forwardRequest('Ship/Issued', $payloadIssued);

            $json = json_decode($response->getContent(), true);

            Log::info("Response Darmawisata [Ship/Issued]: ", $json ?? ['error' => 'No JSON']);

            if (isset($json['status']) && $json['status'] === 'SUCCESS') {
                $amount = (float) $order->sales_price; // Use sales_price from booking response
                $user = $request->user();

                if (!$user) {
                    Log::error("Ship Issued Gagal: User tidak terautentikasi.");
                    return response()->json(['status' => 'FAILED', 'message' => 'User tidak terautentikasi.'], 401);
                }

                if ($user->saldo < $amount) {
                    Log::error("Gagal Issued Kapal karena saldo User Lokal tidak cukup. Butuh: $amount, Saldo: {$user->saldo} (User ID: {$user->id_pengguna})");
                    return response()->json(['status' => 'FAILED', 'message' => 'Saldo tidak cukup untuk Issued.'], 400);
                }

                // Potong Saldo User & Agen
                DB::table('Pengguna')->where('id_pengguna', $user->id_pengguna)->decrement('saldo', $amount);
                // Asumsi agen dengan id_pengguna 4, konsisten dengan contoh TrainIssued
                DB::table('Pengguna')->where('id_pengguna', 4)->decrement('balance_iak', $amount);
                Log::info("Saldo user {$user->id_pengguna} terpotong sebesar {$amount}. Saldo agen (ID 4) juga terpotong.");

                DB::table('ship_orders')->where('id', $order->id)->update([
                    'status'        => 'ISSUED',
                    'resp_time'     => isset($json['respTime']) ? date('Y-m-d H:i:s', strtotime($json['respTime'])) : null,
                    'booking_status'=> $json['bookingStatus'] ?? 'ISSUED', // Update booking status from Darmawisata
                    'updated_at'    => now()
                ]);

                Log::info("Ship Order ISSUED SUKSES. Booking Number: " . $order->booking_number . " | Saldo terpotong: " . $amount);

                // Mengembalikan response sesuai struktur yang diminta, dengan data Darmawisata di key 'data'
                return response()->json([
                    'status'        => 'SUCCESS',
                    'respMessage'   => 'Tiket Kapal Berhasil Dicetak (LUNAS) dan Saldo Terpotong!',
                    'originPort'    => $json['originPort'] ?? null,
                    'originCall'    => $json['originCall'] ?? null,
                    'destinationPort' => $json['destinationPort'] ?? null,
                    'destinationCall' => $json['destinationCall'] ?? null,
                    'shipNumber'    => $json['shipNumber'] ?? null,
                    'departDate'    => $json['departDate'] ?? null,
                    'bookingNumber' => $json['bookingNumber'] ?? null,
                    'bookingDateTime' => $json['bookingDateTime'] ?? null,
                    'bookingStatus' => $json['bookingStatus'] ?? null,
                    'respTime'      => $json['respTime'] ?? null,
                    'userID'        => $json['userID'] ?? null,
                    'accessToken'   => $json['accessToken'] ?? null
                ]);

            } else {
                $message = $json['respMessage'] ?? 'Darmawisata menolak penerbitan tiket kapal.';
                Log::warning("Ship Issued Gagal Darmawisata. Booking Number: " . ($order->booking_number ?? 'N/A') . " | Message: " . $message);

                if (str_contains(strtolower($message), 'insufficient balance')) {
                    Log::error("FATAL: Gagal Issued Kapal karena Saldo H2H Pusat Darmawisata Habis!");
                    return response()->json([
                        'status' => 'FAILED',
                        'message' => 'Tiket gagal diterbitkan: Saldo deposit pusat tidak cukup. Hubungi admin.'
                    ], 500); // 500 karena ini adalah masalah sistem kritis, bukan kesalahan input pengguna
                }

                // Jika Darmawisata response adalah FAILED, update status order lokal
                DB::table('ship_orders')->where('id', $order->id)->update([
                    'status'              => 'FAILED', // Tandai sebagai FAILED jika Darmawisata menolak
                    'dw_response_message' => $message,
                    'updated_at'          => now()
                ]);

                return response()->json(['status' => 'FAILED', 'message' => 'Gagal Issued Kapal: ' . $message], 400);
            }

        } catch (\Exception $e) {
            Log::error("FATAL ERROR [Ship Issued]: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
            // Jika terjadi kesalahan fatal, dan order ditemukan, coba perbarui statusnya ke FAILED
            if (isset($order) && $order->status !== 'ISSUED') {
                DB::table('ship_orders')->where('id', $order->id)->update([
                    'status'              => 'FAILED',
                    'dw_response_message' => 'System Error: ' . $e->getMessage(),
                    'updated_at'          => now()
                ]);
                Log::warning("Ship Order ID {$order->id} status updated to FAILED due to system error during Issued attempt.");
            }
            return response()->json(['status' => 'FAILED', 'message' => 'Sistem Error: ' . $e->getMessage()], 500);
        }
    }

    public function shipHistory(Request $request)
    {
        Log::info("\n========== [SHIP HISTORY - START] ==========");

        try {
            $user = $request->user();

            // Ambil data riwayat dari database lokal berdasarkan user yang login
            $orders = DB::table('ship_orders')
                ->where('user_id', $user->id_pengguna ?? $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            // Format data agar sesuai dengan interface yang digunakan di mobile
            $formattedData = $orders->map(function ($order) {
                return [
                    'id'            => $order->id,
                    'bookingNumber' => $order->booking_number ?? 'PROSES',
                    'shipName'      => $order->ship_name ?? $order->ship_number ?? null,
                    'subClass'      => $order->sub_class ?? $order->sub_class_fare ?? null,
                    'bookingDate'   => $order->booking_date_time ?? $order->created_at,
                    'bookingTime'   => $order->booking_time ?? null,
                    'origin'        => $order->origin_port ?? $order->origin,
                    'destination'   => $order->destination_port ?? $order->destination,
                    'departDate'    => $order->depart_date ?? $order->depart_date_time ?? null,
                    'departTime'    => $order->depart_time ?? null,
                    'paxAdult'      => $order->pax_adult ?? 0,
                    'paxChild'      => $order->pax_child ?? 0,
                    'paxInfant'     => $order->pax_infant ?? 0,
                    'status'        => $order->status,
                    'totalFare'     => (float) ($order->total_fare ?? 0),
                    'ticketPrice'   => (float) ($order->ticket_price ?? 0),
                    'paymentMethod' => $order->payment_method ?? 'SALDO',
                    'paymentUrl'    => $order->payment_url ?? null,
                    'timeLimit'     => $order->time_limit ?? null,
                ];
            });

            Log::info("Berhasil mengambil " . $formattedData->count() . " riwayat transaksi kapal.");

            return response()->json([
                'status' => 'SUCCESS',
                'data'   => $formattedData
            ], 200);

        } catch (\Exception $e) {
            Log::error("FATAL ERROR [Ship History]: " . $e->getMessage());
            return response()->json([
                'status'  => 'FAILED',
                'message' => 'Sistem Error saat memuat riwayat.'
            ], 500);
        }
    }
}