<?php

namespace App\Http\Controllers\Api\Mobile;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class TicketingController extends BaseController
{
    public function __construct()
    {
        // Cukup panggil parent! BaseController yang akan mengurus
        // UserID, BaseURL, dan Token secara aman tanpa gangguan Cache.
        parent::__construct();
    }

    /**
     * POST Airline/ScheduleAllAirline
     * Endpoint untuk pencarian jadwal semua maskapai
     */
    public function airlineSearch(Request $request)
    {
        // 1. Validasi Data Dasar
        $validator = Validator::make($request->all(), [
            'origin'      => 'required|string',
            'destination' => 'required|string',
            'departDate'  => 'required|date',
            'tripType'    => 'required|string|in:OneWay,RoundTrip',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'FAILED',
                'message' => 'Validasi gagal.',
                'errors'  => $validator->errors()
            ], 422);
        }

        // 2. Siapkan Data Payload
        $payload = $request->all();

        // Parameter wajib untuk loop Darmawisata
        $payload['cacheType'] = 2;
        $payload['isShowEachAirline'] = true;

        // 3. Eksekusi Request ke Darmawisata melalui BaseController
        $response = $this->forwardRequest('Airline/ScheduleAllAirline', $payload);

        // =========================================================
        // KODE KHUSUS UNTUK MENCETAK LAPORAN RAPI KE CS DARMAWISATA
        // =========================================================
        $csPayload = $payload;
        $csPayload['userID']      = $this->darmawisataUserId;

        // Ambil token langsung dari payload yang dikirim oleh React Native
        $csPayload['accessToken'] = $payload['accessToken'] ?? '';

        $endpointUrl = rtrim($this->darmawisataBaseUrl, '/') . '/Airline/ScheduleAllAirline';

        $logMessage = "\n\n================ BUKTI LAPORAN UNTUK CS DARMAWISATA ================\n";
        $logMessage .= "ENDPOINT :\nPOST " . $endpointUrl . "\n\n";
        $logMessage .= "--- REQUEST PAYLOAD (Kirim ini ke CS) ---\n";
        $logMessage .= json_encode($csPayload, JSON_PRETTY_PRINT) . "\n\n";
        $logMessage .= "--- RESPONSE DARI DARMAWISATA ---\n";
        $logMessage .= json_encode(json_decode($response->getContent()), JSON_PRETTY_PRINT) . "\n";
        $logMessage .= "====================================================================\n\n";

        Log::info($logMessage);
        // =========================================================

        return $response;
    }

   /**
     * POST Airline/PriceAllAirline
     * Endpoint modern pasangan dari ScheduleAllAirline
     */
    public function airlinePriceAllAirline(Request $request)
    {
        // 1. Validasi parameter wajib sesuai dokumen Darmawisata
        $validator = Validator::make($request->all(), [
            'airlineID'              => 'required|string',
            'origin'                 => 'required|string',
            'destination'            => 'required|string',
            'tripType'               => 'required|string',
            'departDate'             => 'required|string',
            'journeyDepartReference' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'FAILED',
                'message' => 'Validasi gagal, data tidak lengkap.',
                'errors'  => $validator->errors()
            ], 422);
        }

        // 2. Siapkan Data Payload
        $payload = $request->all();

        // Kita pastikan parameter string kosong dikirim sebagai string "" (bukan null)
        // Karena middleware Laravel suka mengubah "" menjadi null
        $payload['airlineAccessCode']      = $payload['airlineAccessCode'] ?? "";
        $payload['journeyReturnReference'] = $payload['journeyReturnReference'] ?? "";

        // 3. Eksekusi Request ke Darmawisata
        $response = $this->forwardRequest('Airline/PriceAllAirline', $payload);

        // Cetak Log Response dari Darmawisata
        Log::info("\nLOG LOG: Response dari Darmawisata (Airline/PriceAllAirline):\n" . json_encode(json_decode($response->getContent()), JSON_PRETTY_PRINT));

        return $response;
    }

    /**
     * POST Airline/City
     * Get airline city list
     */
    public function airlineCity(Request $request)
    {
        // 1. Ambil data request (kosong dari sisi aplikasi mobile)
        $payload = $request->all();



        // 3. Kirim Request ke Server Darmawisata
        return $this->forwardRequest('Airline/City', $payload);
    }

    /**
     * POST Airline/Nationality
     * Get nationality list (Daftar Kewarganegaraan/Negara)
     */
    public function airlineNationality(Request $request)
    {
        // 1. Ambil data request (kemungkinan kosong dari aplikasi mobile)
        $payload = $request->all();



        // 3. Kirim Request ke Server Darmawisata
        return $this->forwardRequest('Airline/Nationality', $payload);
    }


    /**
     * POST Airline/Booking
     * Endpoint untuk proses booking tiket pesawat
     */
    public function airlineBooking(Request $request)
    {
        // 1. Validasi Parameter Dasar
        $validator = Validator::make($request->all(), [
            'airlineID'               => 'required|string',
            'origin'                  => 'required|string',
            'destination'             => 'required|string',
            'departDate'              => 'required|string',
            'contactFirstName'        => 'required|string',
            'contactCountryCodePhone' => 'required|string',
            'contactAreaCodePhone'    => 'required|string',
            'paxDetails'              => 'required|array',
            'schDeparts'              => 'required|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'FAILED',
                'message' => 'Validasi gagal, pastikan data penumpang dan kontak lengkap.',
                'errors'  => $validator->errors()
            ], 422);
        }

        $payload = json_decode($request->getContent(), true);

        // Pastikan parameter opsional aman
        $payload['returnDate'] = $payload['returnDate'] ?? "0001-01-01T00:00:00";
        $payload['schReturns'] = $payload['schReturns'] ?? [];
        $payload['insurance']  = $payload['insurance'] ?? false;

        // Hit ke Darmawisata
        $response = $this->forwardRequest('Airline/Booking', $payload);
        $json = json_decode($response->getContent(), true);

        if (isset($json['status']) && $json['status'] === 'SUCCESS') {
            try {
                // Menyusun Nama dan Telepon Kontak dari Request
                $contactName  = trim($request->contactFirstName . ' ' . ($request->contactLastName ?? ''));
                $contactPhone = $request->contactCountryCodePhone . $request->contactAreaCodePhone . ($request->contactRemainingPhoneNo ?? '');

                // Gunakan Model PesananTiket
                \App\Models\PesananTiket::create([
                    'user_id'       => $request->user()->id,
                    'booking_code'  => $json['bookingCode'],
                    'booking_date'  => $json['bookingDate'] ?? now(),
                    'time_limit'    => $json['timeLimit'] ?? null,
                    'airline_id'    => $json['airlineID'] ?? $request->airlineID,
                    'origin'        => $json['origin'] ?? $request->origin,
                    'destination'   => $json['destination'] ?? $request->destination,
                    'depart_date'   => $json['departDate'] ?? $request->departDate,
                    'trip_type'     => $json['tripType'] ?? $request->tripType,
                    'pax_adult'     => $json['paxAdult'] ?? $request->paxAdult ?? 1,
                    'pax_child'     => $json['paxChild'] ?? $request->paxChild ?? 0,
                    'pax_infant'    => $json['paxInfant'] ?? $request->paxInfant ?? 0,

                    'contact_name'  => $contactName,
                    'contact_phone' => $contactPhone,
                    'contact_email' => $request->contactEmail ?? '-',

                    'ticket_price'  => $json['ticketPrice'] ?? 0,
                    'status'        => 'HOLD',

                    // Fallback ke schDeparts dari request jika flightDeparts dari API kosong
                    'flight_detail' => !empty($json['flightDeparts']) ? $json['flightDeparts'] : $request->schDeparts,
                    'pax_detail'    => $request->paxDetails,
                ]);

                Log::info("LOG SUCCESS: Data booking {$json['bookingCode']} berhasil disimpan ke database lokal.");

            } catch (\Exception $e) {
                // Catat errornya secara detail jika masih gagal
                Log::error("LOG FATAL ERROR: Gagal simpan database lokal saat Booking! Pesan: " . $e->getMessage());
                Log::error("Trace: " . $e->getTraceAsString());

                return response()->json([
                    'status'  => 'FAILED',
                    'message' => 'DB ERROR: ' . $e->getMessage(),
                ], 500);
            }
        }

        return $response;
    }

    public function getLocalBookingDetail(Request $request)
{
    $request->validate([
        'bookingCode' => 'required|string'
    ]);

    $tiket = \App\Models\PesananTiket::where('user_id', $request->user()->id)
                ->where('booking_code', $request->bookingCode)
                ->first();

    if (!$tiket) {
        return response()->json([
            'status' => 'FAILED',
            'message' => 'Data tiket tidak ditemukan di sistem kami.'
        ], 404);
    }

    return response()->json([
        'status' => 'SUCCESS',
        'data'   => $tiket
    ]);
}

    /**
     * POST Airline/BaggageAndMeal
     * Mendapatkan daftar add-ons bagasi dan makanan
     */
    public function baggageAndMeal(Request $request)
    {
        // 1. Validasi Data sesuai dokumen (Required fields)
        $validator = Validator::make($request->all(), [
            'airlineID'               => 'required|string',
            'origin'                  => 'required|string',
            'destination'             => 'required|string',
            'tripType'                => 'required|string',
            'departDate'              => 'required|string',
            'schDepart'               => 'required|string',
            'contactFirstName'        => 'required|string',
            'contactLastName'         => 'required|string',
            'contactTitle'            => 'required|string',
            'contactCountryCodePhone' => 'required|string',
            'contactAreaCodePhone'    => 'required|string',
            'contactRemainingPhoneNo' => 'required|string',
            'contactEmail'            => 'required|string',
            'paxDetails'              => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'FAILED',
                'message' => 'Validasi gagal, parameter add-ons tidak lengkap.',
                'errors'  => $validator->errors()
            ], 422);
        }

        // 2. Siapkan Payload
        $payload = $request->all();

        // Pastikan parameter opsional dikirim sebagai string kosong jika null (standar Darmawisata)
        $payload['returnDate'] = $payload['returnDate'] ?? "";
        $payload['schReturn']  = $payload['schReturn'] ?? "";
        $payload['insurance']  = $payload['insurance'] ?? false;

        // Parameter segment dan fare basis jika ada (biasanya didapat dari hasil price/schedule)
        $payload['departureAirlineSegmentCode'] = $payload['departureAirlineSegmentCode'] ?? "";
        $payload['departureFareBasisCode']      = $payload['departureFareBasisCode'] ?? "";

        // 3. Eksekusi Request ke Darmawisata
        $response = $this->forwardRequest('Airline/BaggageAndMeal', $payload);

        // Cetak Log untuk keperluan Debugging
        Log::info("\nLOG LOG: Request BaggageAndMeal dijalankan.\n" .
                 "Response Status: " . $response->status());

        return $response;
    }

    /**
     * POST Airline/Seat
     * Mendapatkan denah kursi (Seat Map) dan harga kursi
     */
    public function airlineSeat(Request $request)
    {
        // 1. Validasi Parameter Sesuai Dokumentasi
        $validator = Validator::make($request->all(), [
            'airlineID'               => 'required|string',
            'origin'                  => 'required|string',
            'destination'             => 'required|string',
            'tripType'                => 'required|string',
            'departDate'              => 'required|string',
            'schDepart'               => 'required|string',
            'contactFirstName'        => 'required|string',
            'contactLastName'         => 'required|string',
            'contactTitle'            => 'required|string',
            'contactCountryCodePhone' => 'required|string',
            'contactAreaCodePhone'    => 'required|string',
            'contactRemainingPhoneNo' => 'required|string',
            'contactEmail'            => 'required|string',
            'paxDetails'              => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'FAILED',
                'message' => 'Validasi gagal, data seat request tidak lengkap.',
                'errors'  => $validator->errors()
            ], 422);
        }

        // 2. Siapkan Payload
        $payload = $request->all();

        // Mapping parameter opsional agar tidak null
        $payload['returnDate'] = $payload['returnDate'] ?? "";
        $payload['schReturn']  = $payload['schReturn'] ?? "";
        $payload['insurance']  = $payload['insurance'] ?? false;

        // Parameter segmentasi dari step Price/Schedule
        $payload['departureAirlineSegmentCode'] = $payload['departureAirlineSegmentCode'] ?? "";
        $payload['departureFareBasisCode']      = $payload['departureFareBasisCode'] ?? "";

        // 3. Eksekusi Request ke Server Darmawisata
        $response = $this->forwardRequest('Airline/Seat', $payload);

        // Debugging Log
        Log::info("\nLOG LOG: Request Seat Map dijalankan untuk Airline: " . $payload['airlineID']);

        return $response;
    }

    /**
     * POST Airline/Issued
     * Mengeksekusi pencetakan tiket (Issued) dan memotong saldo agen
     */
    public function airlineIssued(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'airlineID'   => 'required|string',
            'origin'      => 'required|string',
            'destination' => 'required|string',
            'tripType'    => 'required|string',
            'departDate'  => 'required|string',
            'bookingCode' => 'required|string',
            'bookingDate' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'FAILED',
                'message' => 'Validasi gagal, data issued tidak lengkap.',
                'errors'  => $validator->errors()
            ], 422);
        }

        $payload = $request->all();

        // Parameter opsional diisi string kosong jika tidak ada
        $payload['returnDate'] = $payload['returnDate'] ?? "0001-01-01T00:00:00";
        $payload['airlineAccessCode'] = $payload['airlineAccessCode'] ?? "";

        // Kirim ke Darmawisata
        $response = $this->forwardRequest('Airline/Issued', $payload);

        Log::info("\nLOG LOG: Request Airline/Issued dieksekusi untuk PNR: " . $payload['bookingCode']);
        Log::info("LOG LOG: Response Issued:\n" . json_encode(json_decode($response->getContent()), JSON_PRETTY_PRINT));

        return $response;
    }

    /**
     * POST Airline/BookingDetail
     * Endpoint untuk menarik detail history tiket (Status HOLD/ISSUED)
     */
    public function airlineBookingDetail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bookingCode' => 'required|string',
            'bookingDate' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'FAILED',
                'message' => 'Validasi gagal, Kode Booking dan Tanggal diperlukan.',
                'errors'  => $validator->errors()
            ], 422);
        }

        $payload = $request->all();

        // Parameter opsional
        $payload['referenceNo'] = $payload['referenceNo'] ?? "";

        $response = $this->forwardRequest('Airline/BookingDetail', $payload);

        Log::info("\nLOG LOG: Request Airline/BookingDetail dieksekusi untuk PNR: " . $payload['bookingCode']);

        return $response;
    }

    /**
     * POST Airline/List
     * Mendapatkan daftar maskapai yang aktif (Active Airlines)
     * * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function airlineList(Request $request)
    {
        // 1. Ambil data request
        // Meskipun dokumentasi meminta userID dan accessToken,
        // BaseController biasanya sudah menyuntikkannya di forwardRequest.
        $payload = $request->all();

        // 2. Eksekusi Request ke Darmawisata
        $response = $this->forwardRequest('Airline/List', $payload);

        // 3. Logging untuk memantau daftar maskapai yang masuk (Opsional namun disarankan)
        $jsonResponse = json_decode($response->getContent(), true);

        if (isset($jsonResponse['status']) && $jsonResponse['status'] === 'SUCCESS') {
            $count = count($jsonResponse['airlines'] ?? []);
            Log::info("LOG SUCCESS: Berhasil mengambil daftar maskapai. Jumlah: {$count} maskapai.");
        } else {
            Log::error("LOG FAILED: Gagal mengambil daftar maskapai. Pesan: " . ($jsonResponse['respMessage'] ?? 'Unknown Error'));
        }

        return $response;
    }

    /**
     * POST Airline/Route
     * Mendapatkan rute penerbangan spesifik untuk 1 maskapai yang dipilih
     */
    public function airlineRoute(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'airlineID' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'FAILED',
                'message' => 'Validasi gagal, airlineID wajib diisi.',
                'errors'  => $validator->errors()
            ], 422);
        }

        return $this->forwardRequest('Airline/Route', $request->all());
    }

    /**
     * POST Airline/LowFareRoute
     * Mendapatkan semua rute maskapai untuk pencarian jadwal harga termurah
     */
    public function airlineLowFareRoute(Request $request)
    {
        // Tidak ada parameter body wajib selain userID & accessToken
        // yang sudah di-handle oleh BaseController
        return $this->forwardRequest('Airline/LowFareRoute', $request->all());
    }

    /**
     * POST Airline/Schedule
     * Mendapatkan jadwal penerbangan spesifik untuk 1 maskapai
     */
    public function airlineScheduleSingle(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'airlineID'   => 'required|string',
            'tripType'    => 'required|string|in:OneWay,RoundTrip',
            'origin'      => 'required|string',
            'destination' => 'required|string',
            'departDate'  => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'FAILED',
                'message' => 'Validasi gagal, lengkapi data pencarian.',
                'errors'  => $validator->errors()
            ], 422);
        }

        $payload = $request->all();

        // Parameter opsional di-set default jika mobile tidak mengirimkan
        $payload['paxAdult']   = $payload['paxAdult'] ?? 1;
        $payload['paxChild']   = $payload['paxChild'] ?? 0;
        $payload['paxInfant']  = $payload['paxInfant'] ?? 0;
        $payload['returnDate'] = $payload['returnDate'] ?? "0001-01-01T00:00:00"; // Format standar jika OneWay

        return $this->forwardRequest('Airline/Schedule', $payload);
    }

    /**
     * POST Airline/LowFareSchedule
     * Mendapatkan jadwal dengan harga termurah (Promo/Low Fare)
     */
    public function airlineLowFareSchedule(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tripType'    => 'required|string|in:OneWay,RoundTrip',
            'origin'      => 'required|string',
            'destination' => 'required|string',
            'departDate'  => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'FAILED',
                'message' => 'Validasi gagal, lengkapi data origin dan destination.',
                'errors'  => $validator->errors()
            ], 422);
        }

        $payload = $request->all();

        // Sesuai dokumentasi, ada parameter wajib khusus untuk LowFare
        // cacheType: 0 (FullCache), 1 (FullLive), 2 (Mix)
        $payload['cacheType']         = $payload['cacheType'] ?? 2;
        // isShowEachAirline: wajib true jika ingin me-loop request (standar darmawisata)
        $payload['isShowEachAirline'] = $payload['isShowEachAirline'] ?? true;

        $payload['paxAdult']   = $payload['paxAdult'] ?? 1;
        $payload['paxChild']   = $payload['paxChild'] ?? 0;
        $payload['paxInfant']  = $payload['paxInfant'] ?? 0;

        return $this->forwardRequest('Airline/LowFareSchedule', $payload);
    }

    /**
     * POST Airline/BookingList
     * Menarik daftar riwayat pesanan (Booking List) berdasarkan tanggal
     */
    public function airlineBookingList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'startDate' => 'required|date',
            'endDate'   => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'FAILED',
                'message' => 'Validasi gagal, tanggal mulai dan akhir diperlukan.',
                'errors'  => $validator->errors()
            ], 422);
        }

        $payload = $request->all();

        // Dokumentasi menyebutkan filterByStatus (integer)
        // 0 biasanya untuk 'Semua Status' atau status default 'Booking'.
        $payload['filterByStatus'] = $payload['filterByStatus'] ?? 0;

        return $this->forwardRequest('Airline/BookingList', $payload);
    }

    /**
     * POST Airline/Price
     * Endpoint untuk cek harga 1 maskapai spesifik
     */
    public function airlinePriceSingle(Request $request)
    {
        // 1. Validasi parameter wajib sesuai dokumen Darmawisata
        $validator = Validator::make($request->all(), [
            'airlineID'              => 'required|string',
            'origin'                 => 'required|string',
            'destination'            => 'required|string',
            'tripType'               => 'required|string',
            'departDate'             => 'required|string',
            'journeyDepartReference' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'FAILED',
                'message' => 'Validasi gagal, data tidak lengkap.',
                'errors'  => $validator->errors()
            ], 422);
        }

        // 2. Siapkan Payload (Gunakan Trik Ninja anti-TrimStrings agar spasi aman!)
        $payload = json_decode($request->getContent(), true);

        // Pastikan string kosong tetap aman
        $payload['airlineAccessCode']      = $payload['airlineAccessCode'] ?? "";
        $payload['journeyReturnReference'] = $payload['journeyReturnReference'] ?? "";

        // 3. Eksekusi Request ke Darmawisata (Endpoint spesifik: Airline/Price)
        $response = $this->forwardRequest('Airline/Price', $payload);

        // Cetak Log Response dari Darmawisata
        Log::info("\nLOG LOG: Response dari Darmawisata (Airline/Price):\n" . json_encode(json_decode($response->getContent()), JSON_PRETTY_PRINT));

        return $response;
    }

    /**
     * POST Airline/SaveDB
     * Simpan data order ke database lokal sebagai draft beserta Session Token baru
     */
    public function saveToDatabase(Request $request)
    {
        try {
            // Gunakan Transaction agar jika salah satu tabel gagal, semua proses insert dibatalkan
            $orderId = DB::transaction(function () use ($request) {

                // 1. Simpan ke tabel flight_orders
                $orderId = DB::table('flight_orders')->insertGetId([
                    'user_id'            => $request->userID ?? $request->user()?->id_pengguna ?? 'GUEST',
                    'dw_access_token'    => $request->accessToken, // Token fresh dari App
                    'airline_id'         => $request->airlineID,
                    'flight_number'      => $request->flightNumber,
                    'origin'             => $request->origin,
                    'destination'        => $request->destination,
                    'trip_type'          => $request->tripType,
                    'depart_date'        => $request->departDate,
                    'flight_class'       => $request->flightClass,
                    'detail_schedule'    => $request->detailSchedule,
                    'base_fare'          => $request->baseFare,
                    'tax'                => $request->tax,
                    'total_fare'         => $request->totalFare,
                    'contact_title'      => $request->contact['title'],
                    'contact_first_name' => $request->contact['firstName'],
                    'contact_last_name'  => $request->contact['lastName'],
                    'contact_phone'      => $request->contact['phone'],
                    'contact_email'      => $request->contact['email'],
                    'status'             => 'DRAFT',
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ]);

                // 2. Simpan semua penumpang
                foreach ($request->passengers as $pax) {
                    $paxId = DB::table('flight_passengers')->insertGetId([
                        'order_id'   => $orderId,
                        'pax_type'   => $pax['type'],
                        'title'      => $pax['title'],
                        'first_name' => $pax['firstName'],
                        'last_name'  => $pax['lastName'],
                        'gender'     => $pax['gender'],
                        'birth_date' => $pax['birthDate'],
                        'doc_type'   => $pax['docType'],
                        'id_number'  => $pax['idNumber'],
                    ]);

                    // 3. Simpan kursi ATAU bagasi jika user memilihnya
                    if (!empty($pax['seat']) || !empty($pax['baggage'])) {
                        DB::table('flight_addons')->insert([
                            'order_id'       => $orderId,
                            'passenger_id'   => $paxId,
                            'seat_code'      => !empty($pax['seat']) ? $pax['seat'] : "",
                            'compartment'    => 'Y',
                            'baggage_string' => !empty($pax['baggage']) ? $pax['baggage'] : ""
                        ]);
                    }
                }

                return $orderId;
            });

            return response()->json([
                'status'   => 'SUCCESS',
                'order_id' => $orderId,
                'message'  => 'Data berhasil dicatat di database'
            ]);

        } catch (\Exception $e) {
            Log::error("Gagal Save DB Draft: " . $e->getMessage());
            return response()->json([
                'status'  => 'FAILED',
                'message' => 'Sistem gagal menyimpan data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST Airline/ProcessBooking
     * Membaca data dari DB lalu merakit Payload untuk dikirim ke Darmawisata
     */
    public function processBooking(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'FAILED', 'message' => 'Order ID tidak valid'], 422);
        }

        try {
            $orderId = $request->order_id;

            // 1. Ambil data Order
            $order = DB::table('flight_orders')->where('id', $orderId)->first();
            if (!$order) {
                throw new \Exception("Data order tidak ditemukan di database.");
            }

            // 2. Ambil data Penumpang & Kursi
            $passengers = DB::table('flight_passengers')->where('order_id', $orderId)->get();
            $paxDetails = [];
            $paxAdult = 0; $paxChild = 0; $paxInfant = 0;

            foreach ($passengers as $pax) {
                // Hitung jumlah pax
                if ($pax->pax_type == 0) $paxAdult++;
                elseif ($pax->pax_type == 1) $paxChild++;
                elseif ($pax->pax_type == 2) $paxInfant++;

                // Ambil data AddOns dari database
                $addonsDb = DB::table('flight_addons')->where('passenger_id', $pax->id)->first();
                $addOns = [];

                if ($addonsDb) {
                    $addOns[] = [
                        'aoOrigin'      => $order->origin,
                        'aoDestination' => $order->destination,
                        'seat'          => $addonsDb->seat_code ?? "",
                        'compartment'   => $addonsDb->compartment ?? "Y",
                        'baggageString' => $addonsDb->baggage_string ?? "",
                        'meals'         => []
                    ];
                }

                $paxDetails[] = [
                    'IDNumber'            => $pax->id_number,
                    'title'               => $pax->title,
                    'firstName'           => $pax->first_name,
                    'lastName'            => $pax->last_name,
                    'birthDate'           => date('Y-m-d\T00:00:00', strtotime($pax->birth_date)),
                    'gender'              => $pax->gender,
                    'nationality'         => "ID",
                    'birthCountry'        => "ID",
                    'DocType'             => $pax->doc_type,
                    'type'                => $pax->pax_type,
                    'parent'              => "",
                    'passportNumber'      => "",
                    'Email'               => "",
                    'batikMilesNo'        => "",
                    'garudaFrequentFlyer' => "",
                    // Jika addons tidak ada sama sekali, kirim null sesuai dokumen
                    'addOns'              => empty($addOns) ? null : $addOns
                ];
            }

            // Pecah kode area HP secara sederhana
            $phone = $order->contact_phone;
            $countryCode = "62";
            $areaCode = substr(str_replace('62', '', $phone), 0, 2); // Ambil 2 digit awal (misal 81)
            $remainingPhone = substr(str_replace('62', '', $phone), 2);

            // --- TAMBAHKAN BLOK KODE INI ---
            // Buka bungkusan JSON dari detail_schedule untuk mengakali TrimStrings Laravel
            $scheduleData = json_decode($order->detail_schedule, true);

            // Ambil data dari JSON (Jika bukan JSON/data lama, gunakan fallback)
            $dwDetailSchedule = is_array($scheduleData) ? $scheduleData['ref'] : $order->detail_schedule;
            $dwFlightNumber   = is_array($scheduleData) ? $scheduleData['fn'] : $order->flight_number;
            $dwDepartTime     = is_array($scheduleData) ? $scheduleData['depTime'] : "";
            $dwArrivalTime    = is_array($scheduleData) ? $scheduleData['arrTime'] : "";
            // -------------------------------

            // 3. Rakit Payload Final untuk Darmawisata
            $dwPayload = [
                'airlineID'               => $order->airline_id,
                'origin'                  => $order->origin,
                'destination'             => $order->destination,
                'tripType'                => $order->trip_type,
                'departDate'              => date('Y-m-d\T00:00:00', strtotime($order->depart_date)),
                'returnDate'              => "0001-01-01T00:00:00",
                'paxAdult'                => $paxAdult,
                'paxChild'                => $paxChild,
                'paxInfant'               => $paxInfant,
                'contactFirstName'        => $order->contact_first_name,
                'contactLastName'         => $order->contact_last_name,
                'contactTitle'            => $order->contact_title,
                'contactCountryCodePhone' => $countryCode,
                'contactAreaCodePhone'    => $areaCode,
                'contactRemainingPhoneNo' => $remainingPhone,
                'contactEmail'            => $order->contact_email,
                'paxDetails'              => $paxDetails,
                'insurance'               => false,
                'userID'                  => $this->darmawisataUserId,
                'accessToken'             => $order->dw_access_token, // GUNAKAN TOKEN BARU DARI DB
                // --- UBAH BAGIAN schDeparts MENJADI SEPERTI INI ---
                'schDeparts'              => [
                    [
                        'airlineCode'    => $order->airline_id,
                        'flightNumber'   => $dwFlightNumber, // <--- Menggunakan variabel aman
                        'schOrigin'      => $order->origin,
                        'schDestination' => $order->destination,
                        'detailSchedule' => $dwDetailSchedule, // <--- Menggunakan variabel aman
                        'schDepartTime'  => $dwDepartTime, // <--- Menggunakan variabel aman
                        'schArrivalTime' => $dwArrivalTime, // <--- Menggunakan variabel aman
                        'flightClass'    => $order->flight_class
                    ]
                ],
                'schReturns'              => []
            ];

            Log::info("LOG LOG: Memulai proses booking dari DB untuk Order ID: {$orderId}");

            // =========================================================================
            // 3.5. SYARAT WAJIB DARMAWISATA & AUTO-FILL BAGASI
            // =========================================================================
            $addonsPayload = [
                'airlineID'               => $dwPayload['airlineID'],
                'origin'                  => $dwPayload['origin'],
                'destination'             => $dwPayload['destination'],
                'tripType'                => $dwPayload['tripType'],
                'departDate'              => $dwPayload['departDate'],
                'returnDate'              => $dwPayload['returnDate'],
                'schDepart'               => $order->detail_schedule,
                'schReturn'               => "",
                'paxAdult'                => $dwPayload['paxAdult'],
                'paxChild'                => $dwPayload['paxChild'],
                'paxInfant'               => $dwPayload['paxInfant'],
                'contactFirstName'        => $dwPayload['contactFirstName'],
                'contactLastName'         => $dwPayload['contactLastName'],
                'contactTitle'            => $dwPayload['contactTitle'],
                'contactCountryCodePhone' => $dwPayload['contactCountryCodePhone'],
                'contactAreaCodePhone'    => $dwPayload['contactAreaCodePhone'],
                'contactRemainingPhoneNo' => $dwPayload['contactRemainingPhoneNo'],
                'contactEmail'            => $dwPayload['contactEmail'],
                'paxDetails'              => $dwPayload['paxDetails'],
                'departureAirlineSegmentCode' => "",
                'departureFareBasisCode'      => $order->flight_class,
                'userID'                  => $this->darmawisataUserId,
                'accessToken'             => $order->dw_access_token
            ];

            // Tembak AddOns untuk mendapatkan denah Bagasi
            $addonsRes = $this->forwardRequest('Airline/BaggageAndMeal', $addonsPayload);
            $addonsJson = json_decode($addonsRes->getContent(), true);

            // CEK APAKAH MASKAPAI MEWAJIBKAN BAGASI
            $isEnableNoBaggage = $addonsJson['isEnableNoBaggage'] ?? true;
            $defaultBaggage = "";

            if (!$isEnableNoBaggage && !empty($addonsJson['baggageAddOns'])) {
                // Cari string bagasi default (utamakan yang harganya 0 / gratis bawaan)
                foreach ($addonsJson['baggageAddOns'] as $routeBaggage) {
                    if (!empty($routeBaggage['infos'])) {
                        foreach ($routeBaggage['infos'] as $info) {
                            if (isset($info['baggageString'])) {
                                $defaultBaggage = $info['baggageString'];
                                if (($info['price'] ?? 1) == 0) {
                                    break 2; // Nemu yang gratis, langsung ambil dan stop cari
                                }
                            }
                        }
                    }
                }
            }

            // SUNTIKKAN STRING BAGASI KE SEMUA PENUMPANG DEWASA & ANAK
            if (!$isEnableNoBaggage && $defaultBaggage !== "") {
                foreach ($dwPayload['paxDetails'] as &$pax) {
                    if ($pax['type'] == 0 || $pax['type'] == 1) { // 0 = Adult, 1 = Child

                        // Jika penumpang belum punya addons sama sekali
                        if (empty($pax['addOns'])) {
                            $pax['addOns'] = [
                                [
                                    'aoOrigin'      => $order->origin,
                                    'aoDestination' => $order->destination,
                                    'seat'          => "",
                                    'compartment'   => "Y",
                                    'baggageString' => $defaultBaggage, // Suntik bagasi di sini
                                    'meals'         => []
                                ]
                            ];
                        } else {
                            // Jika penumpang sudah milih kursi (seperti di Order ID 4: Seat 6A)
                            // Kita cukup timpa baggageString-nya yang tadinya kosong
                            $pax['addOns'][0]['baggageString'] = $defaultBaggage;
                        }
                    }
                }
                unset($pax); // Hapus referensi memori array
                Log::info("LOG LOG: Maskapai mewajibkan bagasi. Sistem otomatis menyuntikkan bagasi: " . $defaultBaggage);
            }
            // =========================================================================

            // 4. Hit Darmawisata (Booking)
            $response = $this->forwardRequest('Airline/Booking', $dwPayload);
            $json = json_decode($response->getContent(), true);

            // 5. Update Status DB jika Berhasil
            if (isset($json['status']) && $json['status'] === 'SUCCESS') {
                DB::table('flight_orders')->where('id', $orderId)->update([
                    'status'       => 'BOOKED',
                    'booking_code' => $json['bookingCode'],
                    'updated_at'   => now()
                ]);

                // Opsional: Kamu bisa memanggil Model PesananTiket::create() di sini
                // jika kamu ingin mencatatnya juga di tabel sistem lama milikmu.
            } else {
                DB::table('flight_orders')->where('id', $orderId)->update([
                    'status'     => 'FAILED',
                    'updated_at' => now()
                ]);
            }

            return $response;

        } catch (\Exception $e) {
            Log::error("Proses Booking Gagal: " . $e->getMessage());
            return response()->json([
                'status'  => 'FAILED',
                'message' => 'Gagal memproses booking: ' . $e->getMessage()
            ], 500);
        }
    }

}
