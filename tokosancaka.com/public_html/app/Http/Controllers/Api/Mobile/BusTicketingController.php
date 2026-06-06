<?php

namespace App\Http\Controllers\Api\Mobile;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class BusTicketingController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function busList(Request $request)
    {
        Log::info("\n========== [BUS LIST - START] ==========");
        $payload = ['userID' => $this->darmawisataUserId, 'accessToken' => $request->accessToken];
        return $this->forwardRequest('Bus/List', $payload);
    }

    public function busRoute(Request $request)
    {
        Log::info("\n========== [BUS ROUTE - START] ==========");
        $validator = Validator::make($request->all(), ['bus' => 'required|string']);
        if ($validator->fails()) return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);

        $payload = [
            'bus' => $request->bus,
            'userID' => $this->darmawisataUserId,
            'accessToken' => $request->accessToken
        ];
        return $this->forwardRequest('Bus/Route', $payload);
    }

   public function busSchedule(Request $request)
    {
        Log::info("\n========== [BUS SCHEDULE - START] ==========");
        Log::info("Payload Request Mobile: ", $request->all());

        // SUDAH DIPERBAIKI MENJADI NULLABLE
        $validator = Validator::make($request->all(), [
            'bus'                 => 'nullable|string', 
            'originTerminal'      => 'required|string',
            'destinationTerminal' => 'required|string',
            'directCode'          => 'nullable|string', 
            'departDate'          => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::warning("Bus Schedule Validasi Gagal: ", $validator->errors()->toArray());
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        // SUDAH DITAMBAHKAN FALLBACK ?? '' 
        $payload = [
            'bus'                 => $request->bus ?? '', 
            'originTerminal'      => $request->originTerminal,
            'destinationTerminal' => $request->destinationTerminal,
            'directCode'          => $request->directCode ?? '', 
            'departDate'          => date('Y-m-d\T00:00:00', strtotime($request->departDate)),
            'paxAdult'            => (int) ($request->paxAdult ?? 1),
            'paxChild'            => (int) ($request->paxChild ?? 0),
            'paxInfant'           => (int) ($request->paxInfant ?? 0),
            'subClassFare'        => $request->subClassFare ?? '',
            'userID'              => $this->darmawisataUserId,
            'accessToken'         => $request->accessToken
        ];

        Log::info("Payload to Darmawisata [Bus/Schedule]: ", $payload);
        
        $endpoint = 'Bus/Schedule'; 
        return $this->forwardRequest($endpoint, $payload);
    }

    public function busBooking(Request $request)
    {
        Log::info("\n========== [BUS BOOKING - START] ==========");
        Log::info("Payload Request Mobile: ", $request->all());

        $validator = Validator::make($request->all(), [
            'bus'                 => 'required|string',
            'originTerminal'      => 'required|string',
            'destinationTerminal' => 'required|string',
            'directCode'          => 'required|string',
            'locationID'          => 'required|string',
            'departDate'          => 'required|string',
            'passengers'          => 'required|array'
        ]);

        if ($validator->fails()) {
            Log::warning("Bus Booking Validasi Gagal: ", $validator->errors()->toArray());
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        try {
            // STEP A: SIMPAN DATABASE STATUS DRAFT
            Log::info("Proses simpan DRAFT ke database lokal...");
            $orderId = DB::transaction(function () use ($request) {
                $paxAdult = 0; $paxChild = 0; $paxInfant = 0;
                foreach ($request->passengers as $pax) {
                    $type = $pax['type'] ?? 'Adult';
                    if ($type == 0 || strtolower($type) == 'adult') $paxAdult++;
                    elseif ($type == 1 || strtolower($type) == 'child') $paxChild++;
                    elseif ($type == 2 || strtolower($type) == 'infant') $paxInfant++;
                }

                $id = DB::table('bus_orders')->insertGetId([
                    'user_id'              => $request->user()->id_pengguna ?? null,
                    'dw_access_token'      => $request->accessToken,
                    'bus_name'             => $request->bus,
                    'origin_terminal'      => $request->originTerminal,
                    'destination_terminal' => $request->destinationTerminal,
                    'direct_code'          => $request->directCode,
                    'location_id'          => $request->locationID,
                    'depart_date'          => date('Y-m-d H:i:s', strtotime($request->departDate)),
                    'sub_class_fare'       => $request->subClassFare ?? '',
                    'pax_adult'            => $paxAdult,
                    'pax_child'            => $paxChild,
                    'pax_infant'           => $paxInfant,
                    'status'               => 'DRAFT',
                    'created_at'           => now(),
                    'updated_at'           => now(),
                ]);

                foreach ($request->passengers as $pax) {
                    DB::table('bus_passengers')->insert([
                        'bus_order_id' => $id,
                        'name'         => $pax['name'] ?? '-',
                        'id_number'    => $pax['IDNumber'] ?? null,
                        'pax_type'     => (is_numeric($pax['type'] ?? 0) ? ($pax['type'] ?? 0) : (($pax['type'] ?? 'Adult') == 'Adult' ? 0 : 1)),
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ]);
                }
                return $id;
            });

            Log::info("Bus Order DRAFT berhasil dibuat. Local ID: " . $orderId);

         // STEP B: RAKIT PAYLOAD DARMAWISATA
            $formattedPassengers = [];
            foreach ($request->passengers as $index => $pax) {
                $nameParts = explode(' ', $pax['name'] ?? 'Hamba Allah');
                $firstName = $nameParts[0];
                $lastName = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : $firstName;

                $paxTypeStr = strtolower($pax['type'] ?? 'adult');
                $paxTypeInt = ($paxTypeStr == 'child') ? 1 : (($paxTypeStr == 'infant') ? 2 : 0);

                $formattedPassengers[] = [
                    'title'        => $pax['title'] ?? 'Mr',
                    'firstName'    => $firstName,
                    'lastName'     => $lastName,
                    'identity'     => $pax['IDNumber'] ?? '',
                    'phone'        => $index === 0 ? ($request->contactPhone ?? '080000000000') : '080000000000',
                    'identityType' => 'KTP',
                    'address'      => 'Sesuai KTP', // <-- PERBAIKAN: Jangan pakai "-"
                    'email'        => $index === 0 ? ($request->contactEmail ?? 'noemail@domain.com') : 'noemail@domain.com',
                    'birthDate'    => (!empty($pax['birthDate']) ? date('Y-m-d', strtotime($pax['birthDate'])) : '1990-01-01') . 'T00:00:00',
                    'parent'       => 1,
                    'paxType'      => $paxTypeInt
                ];
            }

            // PERBAIKAN: Bersihkan array choosedSeat dari tipe data "null"
            $cleanSeats = [];
            if (is_array($request->choosedSeat)) {
                foreach ($request->choosedSeat as $seat) {
                    if (!empty($seat)) {
                        $cleanSeats[] = (string) $seat;
                    }
                }
            }
            $totalPaxCount = count($formattedPassengers);
            
            if (is_array($request->choosedSeat)) {
                foreach ($request->choosedSeat as $seat) {
                    // Jika null/kosong, jadikan string kosong
                    $cleanSeats[] = $seat ? (string) $seat : "";
                }
            }
            // Jika array benar-benar kosong, isi string kosong sejumlah penumpang
            if (empty($cleanSeats)) {
                $cleanSeats = array_fill(0, $totalPaxCount, "");
            }

          $payload = [
    'bus'                 => $request->bus,
    'originTerminal'      => $request->originTerminal,
    'destinationTerminal' => $request->destinationTerminal,
    'choosedSeat'         => $cleanSeats, 
    'directCode'          => $request->directCode,
    'subClassFare'        => !empty($request->subClassFare) ? $request->subClassFare : 'EK', // <-- CEGAH NULL
    'locationID'          => (string) $request->locationID,
    'departDate'          => date('Y-m-d\TH:i:s', strtotime($request->departDate)),
    'paxAdult'            => DB::table('bus_passengers')->where('bus_order_id', $orderId)->where('pax_type', 0)->count(),
    'paxChild'            => DB::table('bus_passengers')->where('bus_order_id', $orderId)->where('pax_type', 1)->count(),
    'paxInfant'           => DB::table('bus_passengers')->where('bus_order_id', $orderId)->where('pax_type', 2)->count(),
    'passengers'          => array_map(function($pax) {
                                // Koreksi relasi parent agar tidak hardcode 1 untuk orang dewasa
                                $pax['parent'] = ($pax['paxType'] == 2) ? 1 : 0;
                                return $pax;
                             }, $formattedPassengers),
    'departID'            => (int) ($request->departID ?? 0),
    'arrivalID'           => (int) ($request->arrivalID ?? 0),
    'userID'              => $this->darmawisataUserId,
    'accessToken'         => $request->accessToken
];

            Log::info("Payload to Darmawisata [Bus/Booking] FIXED: ", $payload);

            // STEP C: TEMBAK API
            $response = $this->forwardRequest('Bus/Booking', $payload);
            $json = json_decode($response->getContent(), true);

            Log::info("Response Darmawisata [Bus/Booking]: ", $json ?? ['error' => 'No JSON Response']);

            // STEP D: UPDATE DATABASE LOKAL
            if (isset($json['status']) && $json['status'] === 'SUCCESS') {
                // Response Bus memiliki field berbeda dengan Kereta:
                // - operatorName  : nama detail operator bus (PO)
                // - bookingTime   : waktu saat booking dilakukan
                // - departPlace   : tempat keberangkatan (berbeda dari origin terminal)
                // - departTime    : waktu keberangkatan detail (berbeda dari departDate)
                // - issuedTimeLimit : batas waktu issued (sama seperti kereta, ini kunci auto-cancel)
                $issuedTimeLimit = isset($json['issuedTimeLimit'])
                    ? date('Y-m-d H:i:s', strtotime($json['issuedTimeLimit']))
                    : null;

                DB::table('bus_orders')->where('id', $orderId)->update([
                    'booking_code'  => $json['bookingCode'] ?? '',
                    'operator_name' => $json['operatorName'] ?? null,
                    'depart_place'  => $json['departPlace'] ?? null,
                    'depart_time'   => isset($json['departTime']) ? date('Y-m-d H:i:s', strtotime($json['departTime'])) : null,
                    'booking_time'  => isset($json['bookingTime']) ? date('Y-m-d H:i:s', strtotime($json['bookingTime'])) : null,
                    'time_limit'    => $issuedTimeLimit,
                    'ticket_price'  => $json['ticketPrice'] ?? 0,
                    'admin_fee'     => $json['memberDiscount'] ?? 0,
                    'total_fare'    => $json['salesPrice'] ?? 0,
                    'status'        => 'HOLD',
                    'updated_at'    => now()
                ]);

                // Log time_limit secara eksplisit agar auto-cancel / cron job bisa memantau
                Log::info("Bus Order UPDATE ke HOLD sukses. PNR: " . ($json['bookingCode'] ?? '-') . " | Time Limit: " . ($issuedTimeLimit ?? 'TIDAK ADA (PO langsung issued)'));
                return response()->json($json);
            } else {
                $message = $json['respMessage'] ?? 'Operator menolak penerbitan tiket bus.';
                
                if (isset($json['bookingStatus']) && strtolower($json['bookingStatus']) === 'canceled') {
                    DB::table('bus_orders')->where('id', $orderId)->update([
                        'status' => 'CANCELLED',
                        'updated_at' => now()
                    ]);
                    Log::warning("Bus Issued Gagal: Time limit habis atau tiket batal. Status diubah ke CANCELLED.");
                    return response()->json([
                        'status' => 'FAILED',
                        'message' => 'Tiket telah dibatalkan otomatis oleh sistem. Silakan pesan ulang.'
                    ]);
                }

                if (str_contains(strtolower($message), 'insufficient balance')) {
                    Log::error("FATAL: Gagal Issued Bus karena Saldo H2H Pusat Darmawisata Habis!");
                    return response()->json([
                        'status' => 'FAILED',
                        'message' => 'Tiket gagal diterbitkan: Saldo deposit pusat tidak cukup. Hubungi admin.'
                    ]);
                }

                return response()->json(['status' => 'FAILED', 'message' => 'Gagal Issued: ' . $message]);
            }

        } catch (\Exception $e) {
            Log::error("FATAL ERROR [Bus Booking]: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
            return response()->json(['status' => 'FAILED', 'message' => 'System Error: ' . $e->getMessage()], 500);
        }
    }

    public function busSeatMap(Request $request)
    {
        Log::info("\n========== [BUS SEAT MAP - START] ==========");
        
        $validator = Validator::make($request->all(), [
            'originTerminal'      => 'required|string',
            'destinationTerminal' => 'required|string',
            'departDate'          => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        $payload = [
            'bus'                 => $request->bus ?? '',
            'originTerminal'      => $request->originTerminal,
            'destinationTerminal' => $request->destinationTerminal,
            'directCode'          => $request->directCode ?? '',
            // PERBAIKAN: Ambil jam secara utuh dari mobile
            'departDate'          => date('Y-m-d\TH:i:s', strtotime($request->departDate)),
            'paxAdult'            => (int) ($request->paxAdult ?? 1),
            'paxChild'            => (int) ($request->paxChild ?? 0),
            'paxInfant'           => (int) ($request->paxInfant ?? 0),
            'subClassFare'        => $request->subClassFare ?? 'EK',
            'userID'              => $this->darmawisataUserId,
            'accessToken'         => $request->accessToken
        ];


        Log::info("Payload to Darmawisata [Bus/SeatMap]: ", $payload);
        return $this->forwardRequest('Bus/SeatMap', $payload);
    }


    
    public function busBookingList(Request $request)
    {
        Log::info("\n========== [BUS BOOKING LIST - START] ==========");
        $payload = [
            'filterBy'    => $request->filterBy ?? '',
            'startDate'   => date('Y-m-d\TH:i:s', strtotime($request->startDate)),
            'endDate'     => date('Y-m-d\TH:i:s', strtotime($request->endDate)),
            'userID'      => $this->darmawisataUserId,
            'accessToken' => $request->accessToken
        ];

        Log::info("Payload to Darmawisata [Bus/BookingList]: ", $payload);
        return $this->forwardRequest('Bus/BookingList', $payload);
    }

    public function busBookingDetail(Request $request)
    {
        Log::info("\n========== [BUS BOOKING DETAIL - START] ==========");
        
        $validator = Validator::make($request->all(), [
            'bookingCode' => 'required|string',
            'bookingDate' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        $payload = [
            'bookingCode' => $request->bookingCode,
            'bookingDate' => date('Y-m-d\TH:i:s', strtotime($request->bookingDate)),
            'userID'      => $this->darmawisataUserId,
            'accessToken' => $request->accessToken
        ];

        Log::info("Payload to Darmawisata [Bus/BookingDetail]: ", $payload);

        // 1. Tembak API Darmawisata
        $response = $this->forwardRequest('Bus/BookingDetail', $payload);
        $json = json_decode($response->getContent(), true);

        // 2. LOGIKA BACKUP & FALLBACK
        if (isset($json['status']) && $json['status'] === 'SUCCESS') {
            // A. Jika API Darmawisata SUKSES, simpan/update payload utuh ke Database
            DB::table('bus_ticket_booking')->updateOrInsert(
                ['booking_code' => $request->bookingCode],
                [
                    'payload'    => json_encode($json),
                    'updated_at' => now()
                ]
            );
            Log::info("Data detail tiket bus PNR {$request->bookingCode} berhasil disimpan ke DB Backup.");

            return $response; // Kembalikan data aslinya

        } else {
            // B. Jika API Darmawisata GAGAL (Token expired, operator timeout, dll)
            Log::warning("Darmawisata API Gagal mengambil detail tiket bus. Alasan: " . ($json['respMessage'] ?? 'Unknown'));

            // Cek apakah kita punya backup data ini di database
            $backup = DB::table('bus_ticket_booking')->where('booking_code', $request->bookingCode)->first();

            if ($backup && $backup->payload) {
                Log::info("MENGGUNAKAN DATA BACKUP DARI DATABASE untuk PNR: {$request->bookingCode}");

                // Decode teks JSON dari database menjadi array PHP
                $backupData = json_decode($backup->payload, true);

                // Kembalikan response seolah-olah sukses
                return response()->json($backupData, 200);
            }

            // Jika tidak ada backup sama sekali, kembalikan pesan error aslinya
            Log::error("Backup tidak ditemukan untuk PNR: {$request->bookingCode}");
            return $response;
        }
    }

    public function busTerminal(Request $request)
    {
        Log::info("\n========== [BUS TERMINAL - START] ==========");
        $payload = [
            'userID'      => $this->darmawisataUserId,
            'accessToken' => $request->accessToken
        ];

        Log::info("Payload to Darmawisata [Bus/Terminal]: ", $payload);
        return $this->forwardRequest('Bus/Terminal', $payload);
    }

    public function busTerminalSearch(Request $request)
    {
        Log::info("\n========== [BUS TERMINAL SEARCH - START] ==========");
        $payload = [
            'terminalName' => $request->terminalName ?? '',
            'userID'       => $this->darmawisataUserId,
            'accessToken'  => $request->accessToken
        ];

        Log::info("Payload to Darmawisata [Bus/TerminalSearch]: ", $payload);
        return $this->forwardRequest('Bus/TerminalSearch', $payload);
    }

 public function busIssued(Request $request)
    {
        Log::info("\n========== [BUS ISSUED - START] ==========");
        Log::info("Payload Request Mobile: ", $request->all());

        // 1. Mobile cukup kirim bookingCode saja (Sangat ringkas & aman)
        $validator = Validator::make($request->all(), [
            'bookingCode' => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::warning("Bus Issued Validasi Gagal: ", $validator->errors()->toArray());
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        try {
            // 2. Ambil seluruh data dari Database berdasarkan bookingCode
            $order = DB::table('bus_orders')->where('booking_code', $request->bookingCode)->first();

            if (!$order) {
                return response()->json(['status' => 'FAILED', 'message' => 'Data Booking tidak ditemukan di database lokal.'], 404);
            }

            // 3. Format booking_time dari DB menjadi standar ISO 8601 Darmawisata (Contoh: 2026-06-06T11:48:59)
            // Jangan pakai T00:00:00, harus waktu real saat booking terjadi
            $bookingDateFormatted = date('Y-m-d\TH:i:s', strtotime($order->booking_time));

            // 4. Susun Payload persis seperti dokumentasi Darmawisata
            $payload = [
                'bookingCode' => $order->booking_code,
                'bookingDate' => $bookingDateFormatted,
                'userID'      => $this->darmawisataUserId,
                'accessToken' => $order->dw_access_token 
            ];

            Log::info("Payload to Darmawisata [Bus/Issued]: ", $payload);

            // 5. Tembak endpoint Bus/Issued
            $response = $this->forwardRequest('Bus/Issued', $payload);
            $json = json_decode($response->getContent(), true);

            Log::info("Response Darmawisata [Bus/Issued]: ", $json ?? ['error' => 'No JSON Response']);

            // 6. Evaluasi Hasil & Update DB
            if (isset($json['status']) && $json['status'] === 'SUCCESS') {
                
                // Jika sukses, ubah status di DB jadi ISSUED
                DB::table('bus_orders')->where('booking_code', $request->bookingCode)->update([
                    'status'     => 'ISSUED',
                    'updated_at' => now()
                ]);
                
                Log::info("Bus Order PNR {$request->bookingCode} BERHASIL DI-ISSUED.");
                
                // Lempar response sukses ke React Native (reffNumber penting untuk tiket)
                return response()->json([
                    'status' => 'SUCCESS',
                    'reffNumber' => $json['reffNumber'] ?? $request->bookingCode,
                    'message' => 'Tiket berhasil diterbitkan'
                ]);

            } else {
                Log::warning("Gagal Issued Bus PNR {$request->bookingCode}. Alasan: " . ($json['respMessage'] ?? 'Unknown'));
                return response()->json([
                    'status' => 'FAILED',
                    'respMessage' => $json['respMessage'] ?? 'Operator menolak penerbitan tiket.'
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error("FATAL ERROR [Bus Issued]: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json(['status' => 'FAILED', 'message' => 'System Error: ' . $e->getMessage()], 500);
        }
    }

    public function busHistory(Request $request)
    {
        Log::info("\n========== [BUS HISTORY - START] ==========");

        try {
            $user = $request->user();

            // Ambil data riwayat dari database lokal berdasarkan user yang login
            $orders = DB::table('bus_orders')
                ->where('user_id', $user->id_pengguna ?? $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            // Format data agar sesuai persis dengan interface BookingItem di React Native
            $formattedData = $orders->map(function ($order) {
                return [
                    'id'            => $order->id,
                    'bookingCode'   => $order->booking_code ?? 'PROSES',
                    'busName'       => $order->bus_name,
                    'operatorName'  => $order->operator_name ?? null,  // Nama PO Bus (dari response Booking)
                    'subClassFare'  => $order->sub_class_fare,
                    'bookingDate'   => $order->created_at,             // Darmawisata butuh format tanggal order
                    'bookingTime'   => $order->booking_time ?? null,   // Waktu booking (dari response Booking)
                    'origin'        => $order->origin_terminal,
                    'destination'   => $order->destination_terminal,
                    'departDate'    => $order->depart_date,
                    'departPlace'   => $order->depart_place ?? null,   // Tempat berangkat detail (dari response Booking)
                    'departTime'    => $order->depart_time ?? null,    // Waktu berangkat detail (dari response Booking)
                    'paxAdult'      => $order->pax_adult ?? 0,
                    'paxChild'      => $order->pax_child ?? 0,
                    'paxInfant'     => $order->pax_infant ?? 0,
                    'status'        => $order->status,                 // DRAFT, HOLD, ISSUED, CANCELLED
                    'totalFare'     => (float) $order->total_fare,
                    'ticketPrice'   => (float) $order->ticket_price,
                    'paymentMethod' => $order->payment_method ?? 'SALDO',
                    'paymentUrl'    => $order->payment_url ?? null,
                    'timeLimit'     => $order->time_limit,             // issuedTimeLimit dari Darmawisata (kunci auto-cancel)
                ];
            });

            Log::info("Berhasil mengambil " . $formattedData->count() . " riwayat transaksi bus.");

            return response()->json([
                'status' => 'SUCCESS',
                'data'   => $formattedData
            ], 200);

        } catch (\Exception $e) {
            Log::error("FATAL ERROR [Bus History]: " . $e->getMessage());
            return response()->json([
                'status'  => 'FAILED',
                'message' => 'Sistem Error saat memuat riwayat.'
            ], 500);
        }
    }

}