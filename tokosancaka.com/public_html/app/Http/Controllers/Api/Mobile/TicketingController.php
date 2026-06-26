<?php

namespace App\Http\Controllers\Api\Mobile;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http; // Wajib di-import untuk API request
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
        $payload['cacheType'] = 1;
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

  public function airlinePriceAllAirline(Request $request)
    {
        // 1. Validasi parameter dasar saja
        $validator = Validator::make($request->all(), [
            'airlineID'              => 'required|string',
            'origin'                 => 'required|string',
            'destination'            => 'required|string',
            'tripType'               => 'required|string|in:OneWay,RoundTrip',
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

        // 2. Validasi Dinamis Khusus RoundTrip (Agar OneWay tidak ikut error)
        if ($request->tripType === 'RoundTrip' && empty($request->journeyReturnReference)) {
            return response()->json([
                'status'  => 'FAILED',
                'message' => 'Referensi penerbangan pulang wajib diisi untuk tiket RoundTrip.'
            ], 422);
        }

        // 3. Siapkan Data Payload
        $payload = $request->all();
        $payload['airlineAccessCode']      = $payload['airlineAccessCode'] ?? "";
        $payload['journeyReturnReference'] = $payload['journeyReturnReference'] ?? "";
        $payload['schReturns']             = $payload['schReturns'] ?? [];

        // 4. Eksekusi Request ke Darmawisata
        $response = $this->forwardRequest('Airline/PriceAllAirline', $payload);
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

        // --- MASUKKAN KODE INI DI SINI ---
        if (isset($payload['paxDetails']) && is_array($payload['paxDetails'])) {
            foreach ($payload['paxDetails'] as &$pax) {
                $pax['IDNumber']              = (string)($pax['IDNumber'] ?? "");
                $pax['passportNumber']        = $pax['passportNumber'] ?? "";
                $pax['passportIssuedCountry'] = $pax['passportIssuedCountry'] ?? "";
                $pax['passportIssuedDate']    = $pax['passportIssuedDate'] ?? "0001-01-01T00:00:00";
                $pax['passportExpiredDate']   = $pax['passportExpiredDate'] ?? "0001-01-01T00:00:00";
                $pax['Email']                 = $pax['Email'] ?? "";
                $pax['batikMilesNo']          = $pax['batikMilesNo'] ?? "";
                $pax['garudaFrequentFlyer']   = $pax['garudaFrequentFlyer'] ?? "";
                $pax['parent']                = (string)($pax['parent'] ?? "");
            }
            unset($pax);
        }
        // ---------------------------------

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
        // 1. Validasi Input: Kita HANYA butuh order_id dari React Native
        $validator = Validator::make($request->all(), [
            'order_id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'FAILED',
                'message' => 'Validasi gagal, order_id tidak dikirim oleh aplikasi.',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            // 2. Tarik SEMUA data dari Database (Lebih Praktis & Aman)
            $order = DB::table('flight_orders')->where('id', $request->order_id)->first();

            if (!$order) {
                return response()->json(['status' => 'FAILED', 'message' => 'Data pesanan tidak ditemukan di database.']);
            }

            if ($order->status === 'ISSUED') {
                return response()->json(['status' => 'FAILED', 'message' => 'Tiket ini sudah berstatus LUNAS / ISSUED.']);
            }

            $pnr = $order->booking_code;
            if (empty($pnr)) {
                 return response()->json(['status' => 'FAILED', 'message' => 'Kode Booking (PNR) kosong, tidak bisa mencetak tiket.']);
            }

            // 3. Kalkulasi Jumlah Penumpang Otomatis dari Database
            $paxAdult = DB::table('flight_passengers')->where('order_id', $order->id)->where('pax_type', 0)->count();
            $paxChild = DB::table('flight_passengers')->where('order_id', $order->id)->where('pax_type', 1)->count();
            $paxInfant = DB::table('flight_passengers')->where('order_id', $order->id)->where('pax_type', 2)->count();

            // Failsafe jika tabel penumpang tidak sinkron
            if ($paxAdult == 0) { $paxAdult = 1; }

         // 4. PERBAIKAN FORMAT TANGGAL (Wajib ISO 8601 pakai huruf 'T')
            $formattedDepartDate = str_replace(' ', 'T', $order->depart_date);

            // Format Tanggal Booking
            $formattedBookingDate = date('Y-m-d\TH:i:s', strtotime($order->created_at));

            // 5. Ambil User ID Darmawisata
            $env = \App\Models\Api::getValue('DARMAWISATA_MODE', 'global', 'development');
            $dwUserId = \App\Models\Api::getValue('DARMAWISATA_USERID', $env);

            // --- TAMBAHAN BARU: EKSTRAK TANGGAL PULANG DARI DATABASE ---
            $isRoundTrip = ($order->trip_type === 'RoundTrip');
            $returnDatePayload = "0001-01-01T00:00:00"; // Default untuk OneWay

            if ($isRoundTrip) {
                // Buka bungkusan JSON dari detail_schedule
                $scheduleData = json_decode($order->detail_schedule, true);
                $innerCheck = is_string($scheduleData) ? json_decode($scheduleData, true) : null;
                if (is_array($innerCheck) && isset($innerCheck['ref'])) {
                    $scheduleData = $innerCheck;
                }

                // 1. BACA DARI FORMAT JSON BARU (schReturns Array)
                if (is_array($scheduleData) && !empty($scheduleData['schReturns'])) {
                    $dwReturnDepartTime = $scheduleData['schReturns'][0]['schDepartTime'] ?? "";
                    if (!empty($dwReturnDepartTime)) {
                        $returnDatePayload = explode('T', $dwReturnDepartTime)[0] . "T00:00:00";
                    }
                }
                // 2. FALLBACK KE FORMAT LAMA (Untuk order lawas di database)
                else {
                    $dwReturnDepartTime = is_array($scheduleData) && isset($scheduleData['returnDepTime']) ? $scheduleData['returnDepTime'] : "";
                    if (!empty($dwReturnDepartTime)) {
                        $returnDatePayload = explode('T', $dwReturnDepartTime)[0] . "T00:00:00";
                    }
                }
            }
            // -------------------------------------------------------------

            // 6. RAKIT PAYLOAD MURNI (Sesuai Dokumentasi Resmi)
            $payloadIssued = [
                "airlineID"         => $order->airline_id,
                "origin"            => $order->origin,
                "destination"       => $order->destination,
                "tripType"          => $order->trip_type ?? "OneWay",
                "departDate"        => $formattedDepartDate,
                "returnDate"        => $returnDatePayload, // <--- KINI SUDAH DINAMIS
                "bookingCode"       => $pnr,
                "bookingDate"       => $formattedBookingDate,
                "airlineAccessCode" => "",
                "userID"            => $dwUserId,
                "accessToken"       => $order->dw_access_token
            ];

            Log::info("\nLOG LOG: Request Airline/Issued dieksekusi untuk PNR: " . $pnr);
            Log::info("LOG PAYLOAD ISSUED: " . json_encode($payloadIssued));

            // 7. Tembak API Darmawisata
            $response = $this->forwardRequest('Airline/Issued', $payloadIssued);
            $json = json_decode($response->getContent(), true);

            Log::info("LOG RESPONSE ISSUED:\n" . json_encode($json, JSON_PRETTY_PRINT));

            // 8. EVALUASI DAN EKSEKUSI PEMOTONGAN SALDO
            if (isset($json['status']) && $json['status'] === 'SUCCESS') {

                // --- PROSES POTONG SALDO ---
                $amount = (float) $order->total_fare;
                $user = $request->user();

                // Pastikan saldo cukup sebelum benar-benar memotong (Failsafe)
                if ($user->saldo < $amount && !in_array(strtoupper($order->payment_method), ['DANA', 'DOKU', 'TRIPAY'])) {
                     return response()->json(['status' => 'FAILED', 'message' => 'Tiket berhasil dicetak, tapi saldo Anda kurang dari tagihan. Segera lapor Admin.']);
                }

                // 1. Potong Saldo User (Hanya jika metodenya Saldo)
                if (!in_array(strtoupper($order->payment_method), ['DANA', 'DOKU', 'TRIPAY', 'CASH'])) {
                    DB::table('Pengguna')->where('id_pengguna', $user->id_pengguna)->decrement('saldo', $amount);
                }

                // 2. Potong Saldo Agen Darmawisata Utama (ID 4)
                DB::table('Pengguna')->where('id_pengguna', 4)->decrement('balance_iak', $amount);

                // 3. Update Status Order menjadi ISSUED
                DB::table('flight_orders')->where('id', $order->id)->update([
                    'status'     => 'ISSUED',
                    'updated_at' => now()
                ]);

                return response()->json([
                    'status' => 'SUCCESS',
                    'bookingCode' => $pnr,
                    'message' => 'Tiket Berhasil Dicetak (LUNAS) dan Saldo Terpotong!',
                    'data' => $json
                ]);

           } else {
                $message = $json['respMessage'] ?? 'Maskapai menolak penerbitan tiket.';

                // =================================================================
                // 🛡️ UX FIX: CEGAT STATUS 'PROCESSED' AGAR TIDAK MENJADI ERROR FATAL
                // =================================================================
                $isProcessed = (isset($json['bookingStatus']) && strtoupper($json['bookingStatus']) === 'PROCESSED')
                            || str_contains(strtoupper($message), 'PROCESSED');

                if ($isProcessed) {
                    Log::info("LOG LOG: PNR {$pnr} masih PROCESSED, menunggu antrean maskapai.");
                    return response()->json([
                        'status'  => 'PROCESSED', // <-- Kita buat status baru khusus untuk Frontend
                        'message' => 'Pesanan sedang dalam antrean maskapai. Silakan tunggu 10-30 detik lalu tekan tombol "Cetak Tiket" lagi.'
                    ]);
                }
                // =================================================================

                // TAMBAHKAN LOGIKA DETEKSI SALDO HABIS
                if (str_contains(strtolower($message), 'insufficient balance')) {
                    Log::error("LOG LOG: Gagal Issued karena Saldo H2H Habis!");
                    return response()->json([
                        'status'  => 'FAILED',
                        'message' => 'Tiket gagal diterbitkan: Saldo deposit pusat tidak cukup. Silakan hubungi admin.'
                    ]);
                }

                return response()->json([
                    'status'  => 'FAILED',
                    'message' => 'Gagal dari Darmawisata: ' . $message
                ]);
            }


        } catch (\Exception $e) {
            Log::error("Proses Issued Gagal (System Error): " . $e->getMessage());
            return response()->json(['status' => 'FAILED', 'message' => 'Sistem Error: ' . $e->getMessage()], 500);
        }
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
        $payload['referenceNo'] = $payload['referenceNo'] ?? "";

        // ================================================================
        // PERBAIKAN: SUNTIKKAN USER ID & ACCESS TOKEN DARI DATABASE LOKAL
        // ================================================================

        // Cari data pesanan berdasarkan PNR untuk mengambil token-nya
        $order = DB::table('flight_orders')->where('booking_code', $request->bookingCode)->first();

        if (!$order || empty($order->dw_access_token)) {
             return response()->json([
                 'status'  => 'FAILED',
                 'message' => 'Data order tidak ditemukan atau token Darmawisata kedaluwarsa.'
             ], 404);
        }

        // Tambahkan ke payload sebelum dilempar ke Darmawisata
        $payload['userID']      = $this->darmawisataUserId;
        $payload['accessToken'] = $order->dw_access_token;

        // ================================================================

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
        $payload['cacheType']         = $payload['cacheType'] ?? 1;
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
            $orderId = DB::transaction(function () use ($request) {

                // BUNGKUS SELURUH JADWAL TRANSIT KE DALAM JSON
                $detailScheduleJson = json_encode([
                    'ref'        => $request->detailSchedule,
                    'depTime'    => $request->departDate,
                    'schDeparts' => json_decode($request->schDepartsJson ?? '[]', true),
                    'schReturns' => json_decode($request->schReturnsJson ?? '[]', true)
                ]);

                // 1. CEK: Apakah ini UPDATE (Edit) atau INSERT baru?
                if (!empty($request->order_id)) {
                    $orderId = $request->order_id;

                    DB::table('flight_orders')->where('id', $orderId)->update([
                        'dw_access_token'    => $request->accessToken,
                        'base_fare'          => $request->baseFare,
                        'tax'                => $request->tax,
                        'total_fare'         => $request->totalFare,
                        'contact_title'      => $request->contact['title'],
                        'contact_first_name' => $request->contact['firstName'],
                        'contact_last_name'  => $request->contact['lastName'],
                        'contact_phone'      => $request->contact['phone'],
                        'contact_email'      => $request->contact['email'],
                        'detail_schedule'    => $detailScheduleJson, // Simpan jadwal transit JSON
                        'updated_at'         => now(),
                    ]);

                    // Bersihkan Data Penumpang Lama (Mencegah Duplikat)
                    $oldPaxIds = DB::table('flight_passengers')->where('order_id', $orderId)->pluck('id');
                    if ($oldPaxIds->isNotEmpty()) {
                        DB::table('flight_addons')->whereIn('passenger_id', $oldPaxIds)->delete();
                    }
                    DB::table('flight_passengers')->where('order_id', $orderId)->delete();

                } else {
                    $orderId = DB::table('flight_orders')->insertGetId([
                        'user_id'            => $request->userID ?? $request->user()?->id_pengguna ?? 'GUEST',
                        'dw_access_token'    => $request->accessToken,
                        'airline_id'         => $request->airlineID,
                        'flight_number'      => $request->flightNumber,
                        'origin'             => $request->origin,
                        'destination'        => $request->destination,
                        'trip_type'          => $request->tripType,
                        'depart_date'        => $request->departDate,
                        'flight_class'       => $request->flightClass,
                        'detail_schedule'    => $detailScheduleJson, // Simpan jadwal transit JSON
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
                }

                // 2. INSERT (atau RE-INSERT) Data Penumpang & Fasilitasnya
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
                        'id_number'  => $pax['idNumber'] ?? "",
                    ]);

                    if (!empty($pax['seat']) || !empty($pax['addOns'])) {
                        DB::table('flight_addons')->insert([
                            'order_id'       => $orderId,
                            'passenger_id'   => $paxId,
                            'seat_code'      => !empty($pax['seat']) ? $pax['seat'] : "",
                            'compartment'    => 'Y',
                            'baggage_string' => !empty($pax['addOns']) ? json_encode($pax['addOns']) : ""
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
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'order_id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'FAILED', 'message' => 'Order ID tidak valid'], 422);
        }

        try {
            $orderId = $request->order_id;
            $order = \Illuminate\Support\Facades\DB::table('flight_orders')->where('id', $orderId)->first();

            if (!$order) {
                throw new \Exception("Data order tidak ditemukan di database.");
            }

            $isRoundTrip = $order->trip_type === 'RoundTrip';
            $passengers = \Illuminate\Support\Facades\DB::table('flight_passengers')->where('order_id', $orderId)->get();

            $paxAdult = 0; $paxChild = 0; $paxInfant = 0;
            $adultsNIK = [];
            $addedPaxKeys = [];

            foreach ($passengers as $pax) {
                $uniqueKey = $pax->pax_type . '_' . strtoupper($pax->first_name) . '_' . strtoupper($pax->last_name);
                if (in_array($uniqueKey, $addedPaxKeys)) continue;
                $addedPaxKeys[] = $uniqueKey;

                if ($pax->pax_type == 0) { $adultsNIK[] = trim($pax->id_number); $paxAdult++; }
                elseif ($pax->pax_type == 1) { $paxChild++; }
                elseif ($pax->pax_type == 2) { $paxInfant++; }
            }

            $paxDetails = [];
            $addedPaxKeys = [];

            foreach ($passengers as $pax) {
                $uniqueKey = $pax->pax_type . '_' . strtoupper($pax->first_name) . '_' . strtoupper($pax->last_name);
                if (in_array($uniqueKey, $addedPaxKeys)) continue;
                $addedPaxKeys[] = $uniqueKey;

                $addonsDb = \Illuminate\Support\Facades\DB::table('flight_addons')->where('passenger_id', $pax->id)->first();
                $addOns = [];

                if ($addonsDb) {
                    $bagVal = trim($addonsDb->baggage_string ?? "");
                    $decodedAddons = json_decode($bagVal, true);

                    if (is_array($decodedAddons) && count($decodedAddons) > 0) {
                        foreach ($decodedAddons as $idx => $ao) {
                            $hasBaggage = !empty($ao['baggageString']);
                            $hasMeals   = !empty($ao['meals']) && count($ao['meals']) > 0;
                            $hasSeat    = ($idx == 0 && !empty($addonsDb->seat_code)) ? $addonsDb->seat_code : ($ao['seat'] ?? "");

                            if ($hasBaggage || $hasMeals || !empty($hasSeat)) {
                                $addOns[] = [
                                    'aoOrigin'      => $ao['aoOrigin'] ?? $order->origin,
                                    'aoDestination' => $ao['aoDestination'] ?? $order->destination,
                                    'seat'          => $hasSeat,
                                    'compartment'   => $ao['compartment'] ?? "Y",
                                    'baggageString' => $hasBaggage ? $ao['baggageString'] : null,
                                    'meals'         => $hasMeals ? $ao['meals'] : null
                                ];
                            }
                        }
                    } else {
                        $seatVal = trim($addonsDb->seat_code ?? "");
                        if (!empty($seatVal) || !empty($bagVal)) {
                            $addOns[] = [
                                'aoOrigin'      => $order->origin,
                                'aoDestination' => $order->destination,
                                'seat'          => $seatVal,
                                'compartment'   => $addonsDb->compartment ?? "Y",
                                'baggageString' => $bagVal,
                                'meals'         => null
                            ];
                        }
                    }
                }

                $parentRef = "";
                $idNumberToSend = trim($pax->id_number);
                $titleToSend = strtoupper($pax->title);

                if ($pax->pax_type == 1 || $pax->pax_type == 2) {
                    if ($pax->gender === 'Female' && in_array($titleToSend, ['MRS', 'MS', 'MR'])) {
                        $titleToSend = 'MISS';
                    } elseif ($pax->gender === 'Male' && in_array($titleToSend, ['MRS', 'MS', 'MR'])) {
                        $titleToSend = 'MSTR';
                    }
                }

                if (empty($idNumberToSend) || strlen($idNumberToSend) < 5) {
                    $idNumberToSend = date('dmy', strtotime($pax->birth_date)) . rand(1000000000, 9999999999);
                }

                if ($pax->pax_type == 2) {
                    $adultSequence = (count($adultsNIK) > 1) ? "2" : "1";
                    $parentRef = $adultSequence;
                }

                $paxDetails[] = [
                    'IDNumber'               => $idNumberToSend,
                    'title'                  => $titleToSend,
                    'firstName'              => strtoupper($pax->first_name),
                    'lastName'               => strtoupper($pax->last_name),
                    'birthDate'              => date('Y-m-d\T00:00:00', strtotime($pax->birth_date)),
                    'gender'                 => $pax->gender,
                    'nationality'            => "ID",
                    'birthCountry'           => "ID",
                    'DocType'                => "KTP",
                    'type'                   => $pax->pax_type,
                    'parent'                 => (string)$parentRef,
                    'passportNumber'         => "",
                    'passportIssuedCountry'  => "",
                    'passportIssuedDate'     => "0001-01-01T00:00:00",
                    'passportExpiredDate'    => "0001-01-01T00:00:00",
                    'Email'                  => "",
                    'batikMilesNo'           => "",
                    'garudaFrequentFlyer'    => "",
                    'addOns'                 => ($pax->pax_type == 2) ? [] : (empty($addOns) ? [] : $addOns)
                ];
            }

            $sortedPaxDetails = [];
            $infantsData = [];

            foreach ($paxDetails as $p) {
                if ($p['type'] == 2) { $infantsData[] = $p; }
            }

            $adultCounter = 1;
            foreach ($paxDetails as $p) {
                if ($p['type'] != 2) {
                    $sortedPaxDetails[] = $p;
                    if ($p['type'] == 0) {
                        foreach ($infantsData as $infant) {
                            if ($infant['parent'] === (string)$adultCounter) {
                                $sortedPaxDetails[] = $infant;
                            }
                        }
                        $adultCounter++;
                    }
                }
            }
            $paxDetails = $sortedPaxDetails;

            $phone = $order->contact_phone;
            $countryCode = "62";
            $areaCode = substr(str_replace('62', '', $phone), 0, 2);
            $remainingPhone = substr(str_replace('62', '', $phone), 2);

            $scheduleData = json_decode($order->detail_schedule, true);
            $innerCheck = is_string($scheduleData) ? json_decode($scheduleData, true) : null;
            if (is_array($innerCheck) && isset($innerCheck['ref'])) {
                $scheduleData = $innerCheck;
            }

            $dwDetailSchedule = is_array($scheduleData) ? $scheduleData['ref'] : $order->detail_schedule;

            $schDepartsArray = (is_array($scheduleData) && !empty($scheduleData['schDeparts']))
                               ? $scheduleData['schDeparts']
                               : [
                                   [
                                       'airlineCode'        => $order->airline_id,
                                       'flightNumber'       => $order->flight_number,
                                       'schOrigin'          => $order->origin,
                                       'schDestination'     => $order->destination,
                                       'detailSchedule'     => $dwDetailSchedule,
                                       'schDepartTime'      => date('Y-m-d\TH:i:s', strtotime($order->depart_date)),
                                       'schArrivalTime'     => date('Y-m-d\TH:i:s', strtotime($order->depart_date)),
                                       'flightClass'        => $order->flight_class,
                                       'garudaNumber'       => null,
                                       'garudaAvailability' => null
                                   ]
                               ];

            $schReturnsArray = (is_array($scheduleData) && !empty($scheduleData['schReturns']))
                               ? $scheduleData['schReturns']
                               : [];

            $returnDatePayload = "0001-01-01T00:00:00";
            if ($isRoundTrip && !empty($schReturnsArray)) {
                $dwReturnDepartTime = $schReturnsArray[0]['schDepartTime'] ?? "";
                if(!empty($dwReturnDepartTime)){
                    $returnDatePayload = explode('T', $dwReturnDepartTime)[0] . "T00:00:00";
                }
            }

            // 1. TAMBAHKAN INI PASTIKAN TEPAT SEBELUM $dwPayload
            foreach ($schDepartsArray as &$dep) {
                $dep['garudaNumber']       = $dep['garudaNumber'] ?? "";
                $dep['garudaAvailability'] = $dep['garudaAvailability'] ?? "";
            }
            unset($dep);

            foreach ($schReturnsArray as &$ret) {
                $ret['garudaNumber']       = $ret['garudaNumber'] ?? "";
                $ret['garudaAvailability'] = $ret['garudaAvailability'] ?? "";
            }
            unset($ret);

            $dwPayload = [
                'airlineID'               => $order->airline_id, // Tetap gunakan JT untuk Lion Group
                'origin'                  => $order->origin,
                'destination'             => $order->destination,
                'tripType'                => $order->trip_type,
                'departDate'              => date('Y-m-d\T00:00:00', strtotime($order->depart_date)),
                'returnDate'              => $returnDatePayload,
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
                'searchKey'               => "",
                'insurance'               => false,
                'promoCode'               => "",
                'userID'                  => $this->darmawisataUserId,
                'accessToken'             => $order->dw_access_token,
                'schDeparts'              => $schDepartsArray,
                'schReturns'              => $schReturnsArray
            ];

            $isAirAsia = in_array(strtoupper($dwPayload['airlineID']), ['QZ', 'XT']);

            // CEK METODE PEMBAYARAN DAN SALDO
            $paymentMethod = strtoupper($request->payment_method ?? 'SALDO');
            $isSaldo = in_array($paymentMethod, ['SALDO', 'POTONG SALDO', 'CASH']);
            $user = $request->user();

            // =========================================================================
            // 0. SAFETY CHECK KHUSUS AIRASIA (KARENA LANGSUNG ISSUED)
            // =========================================================================
            if ($isAirAsia && !$request->is_preview_only) {
                if (!$isSaldo) {
                    return response()->json([
                        'status' => 'FAILED',
                        'message' => 'Maskapai AirAsia tidak mendukung sistem HOLD/Bayar Nanti. Silakan ubah metode pembayaran menggunakan Saldo.'
                    ]);
                }
                if ($user->saldo < $order->total_fare) {
                    return response()->json([
                        'status' => 'FAILED',
                        'message' => 'Saldo Anda tidak mencukupi untuk melakukan Issued AirAsia secara instan.'
                    ]);
                }
            }

            // =========================================================================
            // 1. TAHAP PREVIEW TIKET
            // =========================================================================
            if ($request->is_preview_only) {
                \Illuminate\Support\Facades\Log::info("LOG LOG: Mode Preview diaktifkan. Mengecek Auto-Baggage...");

                $addonsPayload = $dwPayload;
                $addonsPayload['schDepart'] = $dwDetailSchedule;
                $addonsPayload['schReturn'] = is_array($scheduleData) && isset($scheduleData['returnRef']) ? $scheduleData['returnRef'] : "";
                $addonsPayload['departureAirlineSegmentCode'] = null;
                $addonsPayload['departureFareBasisCode'] = $order->flight_class;

                $addonsRes = $this->forwardRequest('Airline/BaggageAndMeal', $addonsPayload);
                $addonsJson = json_decode($addonsRes->getContent(), true);

                $isEnableNoBaggage = $addonsJson['isEnableNoBaggage'] ?? true;
                $defaultBaggage = "";
                if (!$isEnableNoBaggage && !empty($addonsJson['baggageAddOns'])) {
                    foreach ($addonsJson['baggageAddOns'] as $routeBaggage) {
                        if (!empty($routeBaggage['infos'])) {
                            foreach ($routeBaggage['infos'] as $info) {
                                if (isset($info['baggageString'])) {
                                    $defaultBaggage = $info['baggageString'];
                                    if (($info['price'] ?? 1) == 0) break 2;
                                }
                            }
                        }
                    }
                }

                if (!$isEnableNoBaggage && $defaultBaggage !== "") {
                    foreach ($dwPayload['paxDetails'] as &$pax) {
                        if ($pax['type'] == 0 || $pax['type'] == 1) {
                            if (empty($pax['addOns'])) {
                                $pax['addOns'] = [];
                                foreach ($dwPayload['schDeparts'] as $seg) {
                                    $pax['addOns'][] = [
                                        'aoOrigin'      => $seg['schOrigin'],
                                        'aoDestination' => $seg['schDestination'],
                                        'seat'          => "",
                                        'compartment'   => "Y",
                                        'baggageString' => $defaultBaggage,
                                        'meals'         => []
                                    ];
                                }
                            } else {
                                if (empty($pax['addOns'][0]['baggageString'])) {
                                    $pax['addOns'][0]['baggageString'] = $defaultBaggage;
                                }
                            }
                        }
                    }
                    unset($pax);
                }

                if ($isAirAsia) {
                    \Illuminate\Support\Facades\Log::info("LOG LOG: Mengeksekusi Airline/Preview...");
                    $previewRes = $this->forwardRequest('Airline/Preview', $dwPayload);
                    $previewJson = json_decode($previewRes->getContent(), true);

                    if (isset($previewJson['status']) && $previewJson['status'] === 'FAILED') {
                        \Illuminate\Support\Facades\DB::table('flight_orders')->where('id', $orderId)->update(['status' => 'FAILED', 'updated_at' => now()]);
                        return response()->json(['status' => 'FAILED', 'message' => 'Gagal Preview: ' . ($previewJson['respMessage'] ?? 'Maskapai menolak.')]);
                    }

                    if (isset($previewJson['ticketPrice']) && $previewJson['ticketPrice'] > 0) {
                        \Illuminate\Support\Facades\DB::table('flight_orders')->where('id', $orderId)->update(['total_fare' => $previewJson['ticketPrice'], 'updated_at' => now()]);
                    }

                    return response()->json([
                        'status'     => 'SUCCESS',
                        'message'    => 'Preview sukses, harga valid.',
                        'total_fare' => $previewJson['ticketPrice'] ?? $order->total_fare
                    ]);
                } else {
                    return response()->json(['status' => 'SUCCESS', 'total_fare' => $order->total_fare]);
                }
            }

            // =========================================================================
            // 2. TAHAP FINAL BOOKING
            // =========================================================================

            if ($isAirAsia) {
                \Illuminate\Support\Facades\Log::info("LOG LOG: Memulai Guzzle Session (cookies => true) untuk Final Booking AirAsia...");

                $env = \App\Models\Api::getValue('DARMAWISATA_MODE', 'global', 'development');
                $baseUrl = \App\Models\Api::getValue('DARMAWISATA_BASE_URL', $env);
                if (empty($baseUrl)) {
                    $baseUrl = ($env === 'production')
                        ? 'https://www.darmawisataindonesiah2h.co.id/h2h'
                        : 'https://uat-backup.darmawisataindonesiah2h.co.id:7080/h2h';
                }
                $baseUrl = rtrim($baseUrl, '/');

                $client = new \GuzzleHttp\Client([
                    'base_uri' => $baseUrl . '/',
                    'cookies'  => true,
                    'headers'  => [
                        'Content-Type' => 'application/json',
                        'Accept'       => 'application/json'
                    ],
                    'verify'   => false,
                    'http_errors' => false
                ]);

                try {
                    // 1. RE-PREVIEW
                    $previewUrl = $baseUrl . '/Airline/Preview';
                    \Illuminate\Support\Facades\Log::info("\n==================== [DARMAWISATA REQUEST] ====================");
                    \Illuminate\Support\Facades\Log::info("ENDPOINT : POST " . $previewUrl);
                    \Illuminate\Support\Facades\Log::info("PAYLOAD  : " . json_encode($dwPayload));

                    $previewResponse = $client->post('Airline/Preview', ['json' => $dwPayload]);
                    $previewStatus = $previewResponse->getStatusCode();
                    $previewBody = $previewResponse->getBody()->getContents();
                    $previewJson = json_decode($previewBody, true);

                    \Illuminate\Support\Facades\Log::info("STATUS   : " . $previewStatus);
                    \Illuminate\Support\Facades\Log::info("RESPONSE : " . $previewBody);
                    \Illuminate\Support\Facades\Log::info("===============================================================\n");

                    if (isset($previewJson['status']) && $previewJson['status'] === 'FAILED') {
                        \Illuminate\Support\Facades\DB::table('flight_orders')->where('id', $orderId)->update(['status' => 'FAILED', 'updated_at' => now()]);
                        return response()->json(['status' => 'FAILED', 'message' => 'Gagal mengunci sesi AirAsia: ' . ($previewJson['respMessage'] ?? 'Coba lagi.')]);
                    }

                    sleep(1);

                    // 2. BOOKING ISSUED KHUSUS AIRASIA
                    $bookingUrl = $baseUrl . '/Airline/BookingIssued'; // ENDPOINT KHUSUS SESUAI INSTRUKSI CS
                    \Illuminate\Support\Facades\Log::info("\n==================== [DARMAWISATA REQUEST] ====================");
                    \Illuminate\Support\Facades\Log::info("ENDPOINT : POST " . $bookingUrl);
                    \Illuminate\Support\Facades\Log::info("PAYLOAD  : " . json_encode($dwPayload));

                    $bookingResponse = $client->post('Airline/BookingIssued', ['json' => $dwPayload]);
                    $bookingStatus = $bookingResponse->getStatusCode();
                    $bookingBody = $bookingResponse->getBody()->getContents();
                    $json = json_decode($bookingBody, true);

                    \Illuminate\Support\Facades\Log::info("STATUS   : " . $bookingStatus);
                    \Illuminate\Support\Facades\Log::info("RESPONSE : " . $bookingBody);
                    \Illuminate\Support\Facades\Log::info("===============================================================\n");

                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Stateful Guzzle Error: " . $e->getMessage());
                    return response()->json(['status' => 'FAILED', 'message' => 'Koneksi H2H Darmawisata Terputus: ' . $e->getMessage()]);
                }

            } else {
                // Proses normal untuk maskapai selain AirAsia
                \Illuminate\Support\Facades\Log::info("LOG LOG: Mengeksekusi Final Booking Normal...");
                $response = $this->forwardRequest('Airline/Booking', $dwPayload);
                $json = json_decode($response->getContent(), true);
            }

            // =========================================================================
            // 3. PROSES RESPON BOOKING (Update DB & Generate Link Bayar)
            // =========================================================================
            if (isset($json['status']) && $json['status'] === 'SUCCESS') {
                $pnr = $json['bookingCode'] ?? $json['bookingCodeAirline'] ?? 'PENDING';
                $amount = (float) ($json['ticketPrice'] ?? $order->total_fare);

                // Khusus AirAsia status tiket akan langsung ISSUED
                $statusFinal = $isAirAsia ? 'ISSUED' : 'HOLD';

                \Illuminate\Support\Facades\DB::table('flight_orders')->where('id', $orderId)->update([
                    'status'         => $statusFinal,
                    'booking_code'   => $pnr,
                    'payment_method' => $paymentMethod,
                    'total_fare'     => $amount,
                    'updated_at'     => now()
                ]);

                // POTONG SALDO SECARA REAL-TIME JIKA AIRASIA (karena langsung ISSUED)
                if ($isAirAsia && $isSaldo) {
                    \Illuminate\Support\Facades\DB::table('Pengguna')
                        ->where('id_pengguna', $user->id_pengguna)
                        ->decrement('saldo', $amount);

                    // Opsional: Potong Saldo Agen Darmawisata Utama (ID 4) seperti di airlineIssued
                    \Illuminate\Support\Facades\DB::table('Pengguna')->where('id_pengguna', 4)->decrement('balance_iak', $amount);
                }

                if ($isSaldo) {
                    return response()->json([
                        'status' => 'SUCCESS',
                        'bookingCode' => $pnr,
                        'message' => $isAirAsia ? 'Tiket AirAsia Berhasil Di-Issued & Saldo Terpotong.' : 'Tiket berhasil di-HOLD.'
                    ]);
                }

                if ($paymentMethod === 'TRIPAY') {
                    $tripayMode = \App\Models\Api::getValue('TRIPAY_MODE', 'global', 'sandbox');
                    $apiKey = \App\Models\Api::getValue('TRIPAY_API_KEY', $tripayMode);
                    $privateKey = \App\Models\Api::getValue('TRIPAY_PRIVATE_KEY', $tripayMode);
                    $merchantCode = \App\Models\Api::getValue('TRIPAY_MERCHANT_CODE', $tripayMode);

                    $merchantRef = 'FLT-' . $orderId . '-' . $pnr;
                    $tripayUrl = $tripayMode === 'production' ? 'https://tripay.co.id/api/transaction/create' : 'https://tripay.co.id/api-sandbox/transaction/create';
                    $signature = hash_hmac('sha256', $merchantCode.$merchantRef.$amount, $privateKey);

                    $responseTripay = \Illuminate\Support\Facades\Http::withHeaders(['Authorization' => 'Bearer ' . trim($apiKey)])->post($tripayUrl, [
                        'method'         => 'BRIVA',
                        'merchant_ref'   => $merchantRef,
                        'amount'         => $amount,
                        'customer_name'  => $user->nama_lengkap ?? $order->contact_first_name,
                        'customer_email' => $user->email ?? $order->contact_email,
                        'customer_phone' => $user->no_hp ?? $order->contact_phone,
                        'order_items'    => [['sku' => 'TIKET', 'name' => 'Tiket PNR: '.$pnr, 'price' => $amount, 'quantity' => 1]],
                        'return_url'     => env('FRONTEND_URL', url('/')) . '/riwayattiket',
                        'signature'      => $signature
                    ]);

                    $resTripay = $responseTripay->json();
                    if ($responseTripay->successful() && isset($resTripay['success']) && $resTripay['success']) {
                        \Illuminate\Support\Facades\DB::table('flight_orders')->where('id', $orderId)->update(['payment_url' => $resTripay['data']['checkout_url']]);
                        return response()->json([
                            'status' => 'SUCCESS',
                            'bookingCode' => $pnr,
                            'payment_url' => $resTripay['data']['checkout_url']
                        ]);
                    }
                }

                if ($paymentMethod === 'DOKU') {
                    try {
                        $merchantRef = 'FLT-' . $orderId . '-' . $pnr;
                        $dokuService = new \App\Services\DokuJokulService();
                        $paymentUrl = $dokuService->createPayment($merchantRef, $amount);

                        \Illuminate\Support\Facades\DB::table('flight_orders')->where('id', $orderId)->update(['payment_url' => $paymentUrl]);
                        return response()->json([
                            'status' => 'SUCCESS',
                            'bookingCode' => $pnr,
                            'payment_url' => $paymentUrl
                        ]);
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('DOKU Error: ' . $e->getMessage());
                    }
                }

                return response()->json([
                    'status' => 'SUCCESS',
                    'bookingCode' => $pnr,
                    'message' => 'Tiket berhasil, link bayar gagal dibuat.'
                ]);

            } else {
                \Illuminate\Support\Facades\DB::table('flight_orders')->where('id', $orderId)->update([
                    'status' => 'FAILED', 'updated_at' => now()
                ]);
                return response()->json(['status' => 'FAILED', 'message' => $json['respMessage'] ?? 'Maskapai menolak.']);
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Proses Booking Gagal: " . $e->getMessage());
            return response()->json(['status' => 'FAILED', 'message' => 'Sistem Error: ' . $e->getMessage()], 500);
        }
    }

   /**
     * GET Airline/LocalOrders
     * Mengambil daftar keranjang / riwayat booking (Dengan Logika Akses Admin & User)
     */
    public function getLocalOrders(Request $request)
    {
        try {
            $user = $request->user();
            $userId = $user->id_pengguna ?? $user->id;
            $userRole = strtolower($user->role ?? 'pelanggan');

            // 1. Logika Query
            if ($userId == 4 || $userRole == 'admin') {
                $orders = \Illuminate\Support\Facades\DB::table('flight_orders')
                            ->orderBy('created_at', 'desc')
                            ->get();
            } else {
                $orders = \Illuminate\Support\Facades\DB::table('flight_orders')
                            ->where('user_id', $userId)
                            ->orderBy('created_at', 'desc')
                            ->get();
            }

            // 2. Looping data
            foreach ($orders as $order) {
                $passengers = \Illuminate\Support\Facades\DB::table('flight_passengers')
                                ->where('order_id', $order->id)
                                ->get();

               foreach ($passengers as $pax) {
                    $addon = \Illuminate\Support\Facades\DB::table('flight_addons')
                                ->where('passenger_id', $pax->id)
                                ->first();

                    $pax->seat = $addon && $addon->seat_code ? $addon->seat_code : '-';
                    $pax->baggage = 'Tidak ada ekstra bagasi';
                    $pax->meals = 'Tidak ada makanan tambahan'; // Tambahan info meals

                    if ($addon && !empty($addon->baggage_string)) {
                        // Coba decode JSON-nya
                        $decoded = json_decode($addon->baggage_string, true);

                        // Jika berhasil menjadi array (Artinya ini data JSON rute Transit / AddOns Baru)
                        if (is_array($decoded)) {
                            $bagList = [];
                            $mealList = [];

                            foreach ($decoded as $ao) {
                                // Ekstrak rutenya (Contoh: CGK-BTH)
                                $rute = ($ao['aoOrigin'] ?? '') . '-' . ($ao['aoDestination'] ?? '');

                                // Jika ada bagasi di rute ini
                                if (!empty($ao['baggageString'])) {
                                    $bagList[] = $ao['baggageString'] . " (" . $rute . ")";
                                }

                                // Jika ada makanan di rute ini
                                if (!empty($ao['meals']) && is_array($ao['meals'])) {
                                    foreach ($ao['meals'] as $mealCode) {
                                        $mealList[] = $mealCode . " (" . $rute . ")";
                                    }
                                }
                            }

                            if (count($bagList) > 0) {
                                $pax->baggage = implode(', ', $bagList);
                            }
                            if (count($mealList) > 0) {
                                $pax->meals = implode(', ', $mealList);
                            }

                        } else {
                            // FALLBACK: Jika isinya cuma teks biasa dari database lama
                            $pax->baggage = $addon->baggage_string;
                        }
                    }
                }

                $order->passengers = $passengers;

               // ==============================================================
                // 3. PARSING DATA JADWAL (TIKET PERGI DAN TIKET PULANG)
                // ==============================================================
                $schedule = json_decode($order->detail_schedule, true);

                // Antisipasi format JSON string yang ter-encode dua kali
                if (is_string($schedule)) {
                    $innerSchedule = json_decode($schedule, true);
                    if (is_array($innerSchedule)) {
                        $schedule = $innerSchedule;
                    }
                }

                // Set nilai default agar aplikasi tidak error jika data kosong
                $order->depTime = '--:--';
                $order->arrTime = '--:--';
                $order->returnDepTime = '--:--';
                $order->returnArrTime = '--:--';
                $order->return_date = null;
                $order->return_flight_number = '--';

                // Timpa dengan data asli jika tersedia di Database
                if (is_array($schedule)) {

                    // --- 3A. EKSTRAK RUTE PERGI (DEPART) ---
                    if (!empty($schedule['schDeparts']) && is_array($schedule['schDeparts'])) {
                        // Ambil penerbangan paling pertama
                        $firstSeg = $schedule['schDeparts'][0];
                        // Ambil penerbangan paling terakhir (Berguna jika Transit)
                        $lastSeg = end($schedule['schDeparts']);

                        if (!empty($firstSeg['schDepartTime'])) {
                            $order->depTime = date('H:i', strtotime($firstSeg['schDepartTime']));
                        }
                        if (!empty($lastSeg['schArrivalTime'])) {
                            $order->arrTime = date('H:i', strtotime($lastSeg['schArrivalTime']));
                        }
                    } else {
                        // Fallback sistem lama
                        if (!empty($schedule['depTime'])) $order->depTime = date('H:i', strtotime($schedule['depTime']));
                        if (!empty($schedule['arrTime'])) $order->arrTime = date('H:i', strtotime($schedule['arrTime']));
                    }

                    // --- 3B. EKSTRAK RUTE PULANG (RETURN) - KHUSUS ROUNDTRIP ---
                    if (!empty($schedule['schReturns']) && is_array($schedule['schReturns'])) {
                        $firstRetSeg = $schedule['schReturns'][0];
                        $lastRetSeg = end($schedule['schReturns']);

                        if (!empty($firstRetSeg['schDepartTime'])) {
                            $order->returnDepTime = date('H:i', strtotime($firstRetSeg['schDepartTime']));
                            $order->return_date = $firstRetSeg['schDepartTime']; // Kirim tanggal utuh ke React Native
                        }
                        if (!empty($lastRetSeg['schArrivalTime'])) {
                            $order->returnArrTime = date('H:i', strtotime($lastRetSeg['schArrivalTime']));
                        }
                        if (!empty($firstRetSeg['flightNumber'])) {
                            $order->return_flight_number = $firstRetSeg['flightNumber'];
                        }
                    } else {
                        // Fallback sistem lama
                        if (!empty($schedule['returnDepTime'])) {
                            $order->returnDepTime = date('H:i', strtotime($schedule['returnDepTime']));
                            $order->return_date = $schedule['returnDepTime'];
                        }
                        if (!empty($schedule['returnArrTime'])) $order->returnArrTime = date('H:i', strtotime($schedule['returnArrTime']));
                        if (!empty($schedule['returnFn'])) $order->return_flight_number = $schedule['returnFn'];
                    }
                }
            } // <-- Ini tutup kurung kurawal penutup foreach ($orders as $order)

            return response()->json([
                'status' => 'SUCCESS',
                'data'   => $orders
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'FAILED',
                'message' => 'Gagal mengambil data: ' . $e->getMessage()
            ], 500);
        }
    }

   /**
     * 1. FUNGSI PRIVATE: Mengambil Access Token dari Darmawisata (Dinamis dari DB)
     */
    private function sessionLogin()
    {
        // 1. Ambil Mode Global yang sedang aktif (development / production)
        $env = \App\Models\Api::getValue('DHARMAWISATA_MODE', 'global', 'development');

        // 2. Ambil Kredensial sesuai Mode dari Database
        $userId   = \App\Models\Api::getValue('DHARMAWISATA_USER_ID', $env);
        $password = \App\Models\Api::getValue('DHARMAWISATA_PASSWORD', $env);
        $baseUrl  = \App\Models\Api::getValue('DHARMAWISATA_BASE_URL', $env);

        // Failsafe: Jika base_url di database terdeteksi kosong, set default fallback
        if (empty($baseUrl)) {
            $baseUrl = ($env === 'production')
                ? 'https://www.darmawisataindonesiah2h.co.id/'
                : 'https://uat-backup.darmawisataindonesiah2h.co.id:7080/h2h/';
        }

        // Pastikan URL tidak double slash di akhir sebelum ditambah /Session/Login
        $url = rtrim($baseUrl, '/') . '/Session/Login';

        $token = date('Y-m-d\TH:i:s');

        // Rumus enkripsi Darmawisata: MD5(token + MD5(password))
        $md5Password  = md5($password);
        $securityCode = md5($token . $md5Password);

        $payload = [
            'token'        => $token,
            'securityCode' => $securityCode,
            'language'     => 1,
            'userID'       => $userId
        ];

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json'
            ])->post($url, $payload);

            $result = $response->json();

            // Kembalikan HANYA string token jika sukses
            if (isset($result['status']) && $result['status'] === 'SUCCESS') {
                // LOG LOG
                \Illuminate\Support\Facades\Log::info("LOG LOG: Session Darmawisata berhasil dibuat untuk mode: " . strtoupper($env));
                return $result['accessToken'];
            } else {
                \Illuminate\Support\Facades\Log::error("Darmawisata Login Gagal: " . json_encode($result));
                return null;
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error Login Darmawisata: " . $e->getMessage());
            return null;
        }
    }

    /**
     * 2. FUNGSI PUBLIC: Jembatan Penampung dari Form Login Web
     * URL Action: /auth/dharmawisata/login-proses
     */
    public function handleFormLogin(Request $request)
    {
        // Panggil fungsi internal di atas untuk mendapatkan token string
        $accessToken = $this->sessionLogin();

        if ($accessToken) {
            // Jika sukses, kembalikan teks token ke halaman form sesuai keinginanmu
            return response()->json([
                'status' => 'SUCCESS',
                'message' => 'Login Berhasil!',
                'accessToken' => $accessToken
            ]);
        }

        // Jika gagal
        return response()->json([
            'status' => 'FAILED',
            'message' => 'Authentication Failed. Cek logs Laravel untuk detail error dari Darmawisata.'
        ], 401);
    }

    /**
     * POST Airline/LocalOrders/Delete
     * Menghapus riwayat pesanan tiket lokal secara massal (Bulk Delete)
     */
    public function deleteLocalOrders(Request $request)
    {
        // 1. Validasi input: pastikan 'ids' adalah sebuah array
        $validator = Validator::make($request->all(), [
            'ids'   => 'required|array',
            'ids.*' => 'integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'FAILED',
                'message' => 'Validasi gagal, data ID tidak valid.',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            // Gunakan Transaction agar aman jika terjadi kegagalan parsial
            DB::transaction(function () use ($request) {
                // Hapus data addons dan passengers terlebih dahulu jika database-mu
                // belum menggunakan relasi ON DELETE CASCADE

                $passengers = DB::table('flight_passengers')->whereIn('order_id', $request->ids)->pluck('id');
                if ($passengers->isNotEmpty()) {
                    DB::table('flight_addons')->whereIn('passenger_id', $passengers)->delete();
                }
                DB::table('flight_passengers')->whereIn('order_id', $request->ids)->delete();

                // Terakhir, hapus data utama (Order)
                DB::table('flight_orders')->whereIn('id', $request->ids)->delete();
            });

            Log::info("LOG SUCCESS: Berhasil menghapus " . count($request->ids) . " data riwayat tiket.");

            return response()->json([
                'status'  => 'SUCCESS',
                'message' => 'Data riwayat berhasil dihapus secara permanen.'
            ]);

        } catch (\Exception $e) {
            Log::error("LOG FATAL ERROR: Gagal hapus data riwayat tiket! Pesan: " . $e->getMessage());

            return response()->json([
                'status'  => 'FAILED',
                'message' => 'Sistem gagal menghapus data: ' . $e->getMessage()
            ], 500);
        }
    }

   public function agentBalance(Request $request)
    {
        Log::info('--- Darmawisata Balance Request Started ---');
        Log::info('Incoming Request Payload from React Native:', $request->all());

        // 1. Validasi parameter wajib dari aplikasi mobile
        $request->validate([
            'userID'      => 'required|string',
            'accessToken' => 'required|string',
        ]);

        try {
            // Siapkan payload murni sesuai Schema API Darmawisata
            $payload = [
                'userID'      => $request->userID,
                'accessToken' => $request->accessToken
            ];

            // 2. Eksekusi Request menggunakan BaseController
            // Endpoint diubah menjadi "Agent/Balance" sesuai dokumen resmi
            $response = $this->forwardRequest('Agent/Balance', $payload);

            // 3. Buka bungkusan JSON dari respons Darmawisata
            $jsonResponse = json_decode($response->getContent(), true);

            // 4. Evaluasi Status dari server Darmawisata
            if (isset($jsonResponse['status']) && strtoupper($jsonResponse['status']) === 'SUCCESS') {
                Log::info('--- Darmawisata Balance Request Completed Successfully ---');

                // Format kembalian disesuaikan dengan ekspektasi Frontend React Native
                return response()->json([
                    'success' => true,
                    'data' => [
                        // Ambil nilai balance, fallback ke 0 jika kosong
                        'balance' => $jsonResponse['balance'] ?? 0
                    ]
                ], 200);

            } else {
                // Jika Darmawisata membalas dengan status FAILED (misal token expired/salah)
                $errorMessage = $jsonResponse['respMessage'] ?? 'Gagal memuat saldo dari server Darmawisata.';
                Log::warning('Darmawisata Balance Failed: ' . $errorMessage);

                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 400);
            }

        } catch (\Exception $e) {
            // Tangkap error jika server sedang down atau timeout
            Log::error('Darmawisata Balance System Exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat menghubungi server Darmawisata.'
            ], 500);
        }
    }
}
