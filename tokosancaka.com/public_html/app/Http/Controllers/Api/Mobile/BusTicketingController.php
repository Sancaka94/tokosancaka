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

        // PERBAIKAN: Gunakan Date Format standar H2H jika memungkinkan
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
        $response = $this->forwardRequest($endpoint, $payload);

        // ===============================================================
        // TAMBAHAN: CACHING JADWAL KE DATABASE YANG LEBIH AMAN
        // ===============================================================
        try {
            $json = json_decode($response->getContent(), true);
            if (isset($json['status']) && $json['status'] === 'SUCCESS' && !empty($json['schedules'])) {
                Log::info("Menyimpan " . count($json['schedules']) . " jadwal ke database...");
                
                foreach ($json['schedules'] as $sched) {
                    
                    // 1. FILTER NAMA BUS: Hapus kata " Busline" agar aman di SeatMap
                    $rawBusName = $sched['operatorName'] ?? $json['bus'] ?? 'All PO';
                    $cleanBusName = trim(str_replace(' Busline', '', $rawBusName));
                    
                    // 2. TENTUKAN WAKTU BERANGKAT
                    $departDate = isset($sched['departLocation'][0]['departTime']) 
                        ? date('Y-m-d H:i:s', strtotime($sched['departLocation'][0]['departTime'])) 
                        : date('Y-m-d H:i:s', strtotime($json['departDate']));
                        
                    $directCode = $sched['directCode'] ?? '';

                    if (empty($directCode)) continue; // Abaikan jika tidak ada directCode

                    // 3. LOGIKA INSERT / UPDATE MANUAL (Mencegah Error Timestamp)
                    $exists = DB::table('bus_schedule_caches')->where('direct_code', $directCode)->first();

                    if ($exists) {
                        DB::table('bus_schedule_caches')->where('direct_code', $directCode)->update([
                            'bus'                  => $cleanBusName,
                            'origin_terminal'      => $json['originTerminal'] ?? '',
                            'destination_terminal' => $json['destinationTerminal'] ?? '',
                            'depart_date'          => $departDate,
                            'location_id'          => $sched['locationID'] ?? '',
                            'depart_id'            => $sched['departLocation'][0]['departID'] ?? 0,
                            'arrival_id'           => $sched['arrivalLocation'][0]['arrivalID'] ?? 0,
                            'sub_class_fare'       => $sched['classes'][0]['classFare'] ?? 'EK',
                            'is_allow_choose_seat' => ($sched['isAllowChooseSeat'] ?? true) ? 1 : 0,
                            'updated_at'           => now(),
                        ]);
                    } else {
                        DB::table('bus_schedule_caches')->insert([
                            'direct_code'          => $directCode,
                            'bus'                  => $cleanBusName,
                            'origin_terminal'      => $json['originTerminal'] ?? '',
                            'destination_terminal' => $json['destinationTerminal'] ?? '',
                            'depart_date'          => $departDate,
                            'location_id'          => $sched['locationID'] ?? '',
                            'depart_id'            => $sched['departLocation'][0]['departID'] ?? 0,
                            'arrival_id'           => $sched['arrivalLocation'][0]['arrivalID'] ?? 0,
                            'sub_class_fare'       => $sched['classes'][0]['classFare'] ?? 'EK',
                            'created_at'           => now(),
                            'updated_at'           => now(),
                        ]);
                    }
                }
                Log::info("Caching Selesai.");
            }
        } catch (\Exception $e) {
            // Log ini akan mencetak error DB secara detail baris per baris
            Log::error("FATAL ERROR Caching jadwal bus: " . $e->getMessage() . " | Baris: " . $e->getLine());
        }

        return $response;
    }


    public function busSeatMap(Request $request)
    {
        Log::info("\n========== [BUS SEAT MAP - START] ==========");
        
        // Mobile cukup ngirim directCode saja, sisanya kita ambil dari Database Sancaka
        $validator = Validator::make($request->all(), [
            'directCode' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        // AMBIL DATA BERSIH DARI DATABASE LOKAL
        $schedule = DB::table('bus_schedule_caches')->where('direct_code', $request->directCode)->first();

        if (!$schedule) {
            return response()->json(['status' => 'FAILED', 'respMessage' => 'Sesi pencarian kadaluarsa. Silakan cari ulang jadwal bus.'], 404);
        }

        $payload = [
            // KEMBALIKAN KE "All PO" ATAU SESUAI PENCARIAN AWAL
            'bus'                 => $request->bus ?? 'All PO', 
            'originTerminal'      => $schedule->origin_terminal,
            'destinationTerminal' => $schedule->destination_terminal,
            'directCode'          => $schedule->direct_code,
            
            // PAKSA KE FORMAT T00:00:00 TANPA JAM ASLI
            'departDate'          => date('Y-m-d\T00:00:00', strtotime($schedule->depart_date)),
            
            'paxAdult'            => (int) ($request->paxAdult ?? 1),
            'paxChild'            => (int) ($request->paxChild ?? 0),
            'paxInfant'           => (int) ($request->paxInfant ?? 0),
            'subClassFare'        => $schedule->sub_class_fare,
            'userID'              => $this->darmawisataUserId,
            'accessToken'         => $request->accessToken
        ];

        Log::info("Payload to Darmawisata [Bus/SeatMap]: ", $payload);
        return $this->forwardRequest('Bus/SeatMap', $payload);
    }


   public function busBooking(Request $request)
{
    Log::info("\n========== [BUS BOOKING - START] ==========");

    $validator = Validator::make($request->all(), [
        'directCode' => 'required|string',
        'passengers' => 'required|array'
    ]);

    if ($validator->fails()) {
        return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
    }

    try {
        $schedule = DB::table('bus_schedule_caches')
            ->where('direct_code', $request->directCode)
            ->first();

        if (!$schedule) {
            return response()->json([
                'status'      => 'FAILED',
                'respMessage' => 'Sesi pemesanan habis atau jadwal tidak valid. Silakan kembali ke pencarian.'
            ], 404);
        }

        // ============================================================
        // HITUNG PAX DI LUAR CLOSURE agar bisa dipakai di mana saja
        // ============================================================
        $paxAdult  = 0;
        $paxChild  = 0;
        $paxInfant = 0;

        foreach ($request->passengers as $pax) {
            $type = strtolower($pax['type'] ?? 'adult');
            if ($type === 'adult')       $paxAdult++;
            elseif ($type === 'child')   $paxChild++;
            elseif ($type === 'infant')  $paxInfant++;
        }

        // ============================================================
        // STEP A: SIMPAN DATABASE STATUS DRAFT (satu transaction saja)
        // ============================================================
        $orderId = DB::transaction(function () use ($request, $schedule, $paxAdult, $paxChild, $paxInfant) {

            $id = DB::table('bus_orders')->insertGetId([
                'user_id'              => $request->user()->id_pengguna ?? null,
                'dw_access_token'      => $request->accessToken,
                'bus_name'             => $schedule->bus,
                'origin_terminal'      => $schedule->origin_terminal,
                'destination_terminal' => $schedule->destination_terminal,
                'direct_code'          => $schedule->direct_code,
                'location_id'          => $schedule->location_id,
                'depart_date'          => $schedule->depart_date,
                'sub_class_fare'       => $schedule->sub_class_fare,
                'pax_adult'            => $paxAdult,
                'pax_child'            => $paxChild,
                'pax_infant'           => $paxInfant,
                'status'               => 'DRAFT',
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);

            foreach ($request->passengers as $pax) {
                $paxTypeStr = strtolower($pax['type'] ?? 'adult');
                $paxTypeInt = ($paxTypeStr === 'child') ? 1 : (($paxTypeStr === 'infant') ? 2 : 0);

                DB::table('bus_passengers')->insert([
                    'bus_order_id' => $id,
                    'name'         => $pax['name']     ?? '-',
                    'id_number'    => $pax['IDNumber'] ?? null,
                    'pax_type'     => $paxTypeInt,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }

            return $id; // <-- wajib di-return agar $orderId terisi
        });

        // ============================================================
        // STEP B: FORMAT PASSENGERS UNTUK PAYLOAD API
        // ============================================================
        $formattedPassengers = [];
        foreach ($request->passengers as $index => $pax) {
            $paxTypeStr = strtolower($pax['type'] ?? 'adult');
            $paxTypeInt = ($paxTypeStr === 'child') ? 1 : (($paxTypeStr === 'infant') ? 2 : 0);

            $formattedPassengers[] = [
                'title'        => $pax['title']    ?? 'Mr',
                'firstName'    => $pax['name']     ?? 'Hamba Allah',
                'lastName'     => '.',
                'identity'     => $pax['IDNumber'] ?? '',
                'phone'        => $index === 0 ? ($request->contactPhone ?? '085745808809') : '08819435180',
                'identityType' => 'KTP',
                'address'      => $pax['address']  ?? 'Sesuai KTP',
                'email'        => $index === 0 ? ($request->contactEmail ?? 'tokosancaka@gmail.com') : 'salafy94@gmail.com',
                'birthDate'    => !empty($pax['birthDate'])
                                    ? date('Y-m-d\T00:00:00', strtotime($pax['birthDate']))
                                    : '1990-01-01T00:00:00',
                'parent'       => ($paxTypeInt === 2) ? 1 : 0,
                'paxType'      => $paxTypeInt,
            ];
        }

        // ============================================================
        // STEP C: BERSIHKAN choosedSeat
        // ============================================================
        $cleanSeats = [];
        if (is_array($request->choosedSeat)) {
            foreach ($request->choosedSeat as $seat) {
                if (!empty($seat) && trim($seat) !== '') {
                    $cleanSeats[] = (string) $seat;
                }
            }
        }

        // ============================================================
        // STEP D: BUILD PAYLOAD
        // ============================================================
        $payload = [
            'bus'                 => $request->bus ?? 'All PO',
            'originTerminal'      => $schedule->origin_terminal,
            'destinationTerminal' => $schedule->destination_terminal,
            'directCode'          => $schedule->direct_code,
            'subClassFare'        => $schedule->sub_class_fare,
            'locationID'          => (string) $schedule->location_id,
            'departDate'          => date('Y-m-d\T00:00:00', strtotime($schedule->depart_date)),
            'paxAdult'            => (int) $paxAdult,
            'paxChild'            => (int) $paxChild,
            'paxInfant'           => (int) $paxInfant,
            'passengers'          => $formattedPassengers,
            'departID'            => (int) $schedule->depart_id,
            'arrivalID'           => (int) $schedule->arrival_id,
            'userID'              => $this->darmawisataUserId,
            'accessToken'         => $request->accessToken,
        ];

        $isAllowChooseSeat = (bool) $schedule->is_allow_choose_seat;
        if ($isAllowChooseSeat && !empty($cleanSeats)) {
            $payload['choosedSeat'] = $cleanSeats;
        }

        Log::info("Payload to Darmawisata [Bus/Booking]: ", $payload);

        // ============================================================
        // STEP E: TEMBAK API
        // ============================================================
        $response = $this->forwardRequest('Bus/Booking', $payload);
        $json     = json_decode($response->getContent(), true);

        Log::info("Response Darmawisata [Bus/Booking]: ", $json ?? ['error' => 'No JSON Response']);

        // ============================================================
        // STEP F: UPDATE DATABASE LOKAL
        // ============================================================
        if (isset($json['status']) && $json['status'] === 'SUCCESS') {
            $issuedTimeLimit = isset($json['issuedTimeLimit'])
                ? date('Y-m-d H:i:s', strtotime($json['issuedTimeLimit']))
                : null;

            DB::table('bus_orders')->where('id', $orderId)->update([
                'booking_code'  => $json['bookingCode']   ?? '',
                'operator_name' => $json['operatorName']  ?? null,
                'depart_place'  => $json['departPlace']   ?? null,
                'depart_time'   => isset($json['departTime'])
                                    ? date('Y-m-d H:i:s', strtotime($json['departTime']))
                                    : null,
                'booking_time'  => isset($json['bookingTime'])
                                    ? date('Y-m-d H:i:s', strtotime($json['bookingTime']))
                                    : null,
                'time_limit'    => $issuedTimeLimit,
                'ticket_price'  => $json['ticketPrice']     ?? 0,
                'admin_fee'     => $json['memberDiscount']  ?? 0,
                'total_fare'    => $json['salesPrice']      ?? 0,
                'status'        => 'HOLD',
                'updated_at'    => now(),
            ]);

            return response()->json($json);
        } else {
            // Rollback order ke status FAILED agar tidak menggantung sebagai DRAFT
            DB::table('bus_orders')->where('id', $orderId)->update([
                'status'     => 'FAILED',
                'updated_at' => now(),
            ]);

            return response()->json([
                'status'  => 'FAILED',
                'message' => 'Gagal Booking: ' . ($json['respMessage'] ?? 'Unknown'),
            ]);
        }

    } catch (\Exception $e) {
        Log::error("FATAL ERROR [Bus Booking]: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
        return response()->json([
            'status'  => 'FAILED',
            'message' => 'System Error: ' . $e->getMessage(),
        ], 500);
    }
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