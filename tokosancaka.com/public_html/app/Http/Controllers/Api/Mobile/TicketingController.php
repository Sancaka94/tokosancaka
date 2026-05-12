<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\HTTP;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator; // <-- Tambahkan ini
use App\Models\AirDharmawisata;
use App\Models\AirBookingPassenger;
use App\Models\AirBookingFlight;
use App\Models\Api;


class TicketingController extends BaseController {

// Deklarasi property untuk menyimpan data dari database
    protected $darmawisataUserId;
    protected $darmawisataToken;
    protected $darmawisataBaseUrl;

    public function __construct()
{
    parent::__construct(); // Mengisi $this->darmawisataBaseUrl dari BaseController

    $mode = \App\Models\Api::getValue('DHARMAWISATA_MODE', 'global', 'development');
    $this->darmawisataUserId  = \App\Models\Api::getValue('DHARMAWISATA_USER_ID', $mode);
    $this->darmawisataToken   = \App\Models\Api::getValue('DHARMAWISATA_ACCESS_TOKEN', $mode);

    // Cek apakah data masuk
    // dd($this->darmawisataUserId, $this->darmawisataBaseUrl);
}

    /**
     * POST Airline/Search
     * Mendapatkan jadwal penerbangan berdasarkan kriteria pencarian
     */
    public function airlineSearch(Request $request)
    {
        // Validasi Data dari Aplikasi Mobile
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'origin'      => 'required|string',
            'destination' => 'required|string',
            'departDate'  => 'required|date',
            'returnDate'  => 'nullable|date', // Optional jika OneWay
            'tripType'    => 'required|string|in:OneWay,RoundTrip',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'FAILED',
                'message' => 'Validasi gagal, pastikan semua field mandatory terisi.',
                'errors'  => $validator->errors()
            ], 422);
        }

        // Ambil semua data request
        $payload = $request->all();

        // Inject Kredensial H2H dari Database (API Settings)
        $payload['userID'] = $this->darmawisataUserId;
        $payload['accessToken'] = $this->darmawisataToken;

        // Kirim Request ke Server Darmawisata
        return $this->forwardRequest('Airline/Search', $payload);
    }


/**
     * POST Airline/BaggageAndMeal
     * Gain access to baggage and meal addons
     */
    public function airlineBaggageAndMeal(Request $request)
    {
        // 1. Validasi Data dari Aplikasi Mobile (Sesuai dokumentasi Darmawisata 'Required')
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'airlineID'               => 'required|string',
            'origin'                  => 'required|string',
            'destination'             => 'required|string',
            'tripType'                => 'required|string|in:OneWay,RoundTrip',
            'departDate'              => 'required|date',
            'schDepart'               => 'required|string',

            // Kontak Penumpang
            'contactFirstName'        => 'required|string',
            'contactLastName'         => 'required|string',
            'contactTitle'            => 'required|string',
            'contactCountryCodePhone' => 'required|string',
            'contactAreaCodePhone'    => 'required|string',
            'contactRemainingPhoneNo' => 'required|string',
            'contactEmail'            => 'required|email',

            // Detail Penumpang
            'paxDetails'              => 'required|array',
            'paxDetails.*.IDNumber'   => 'required|string',
            'paxDetails.*.title'      => 'required|string',
            'paxDetails.*.firstName'  => 'required|string',
            'paxDetails.*.lastName'   => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'FAILED',
                'message' => 'Validasi gagal, pastikan semua field mandatory terisi.',
                'errors'  => $validator->errors()
            ], 422);
        }

        // 2. Ambil semua data request
        $payload = $request->all();

        // 3. Inject Kredensial H2H (Agar aman, biarkan backend yang menempelkan Token & User ID)
        // Pastikan Anda sudah menambahkan DARMAWISATA_USER_ID di file .env Anda
        // 3. Inject Kredensial H2H dari Database (API Settings)
        $payload['userID'] = $this->darmawisataUserId;
        $payload['accessToken'] = $this->darmawisataToken;

        // 4. Kirim Request ke Server Darmawisata menggunakan Helper yang sudah ada
        return $this->forwardRequest('Airline/BaggageAndMeal', $payload);
    }

    /**
     * POST Airline/Seat
     * Gain access to seat addons (Denah Kursi)
     */
    public function airlineSeat(Request $request)
    {
        // 1. Validasi Data dari Aplikasi Mobile (Sesuai dokumentasi 'Required')
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'airlineID'               => 'required|string',
            'origin'                  => 'required|string',
            'destination'             => 'required|string',
            'tripType'                => 'required|string|in:OneWay,RoundTrip',
            'departDate'              => 'required|date',
            'schDepart'               => 'required|string',

            // Kontak Penumpang
            'contactFirstName'        => 'required|string',
            'contactLastName'         => 'required|string',
            'contactTitle'            => 'required|string',
            'contactCountryCodePhone' => 'required|string',
            'contactAreaCodePhone'    => 'required|string',
            'contactRemainingPhoneNo' => 'required|string',
            'contactEmail'            => 'required|email',

            // Detail Penumpang
            'paxDetails'              => 'required|array',
            'paxDetails.*.IDNumber'   => 'required|string',
            'paxDetails.*.title'      => 'required|string',
            'paxDetails.*.firstName'  => 'required|string',
            'paxDetails.*.lastName'   => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'FAILED',
                'message' => 'Validasi gagal, pastikan semua field mandatory terisi.',
                'errors'  => $validator->errors()
            ], 422);
        }

        // 2. Ambil semua data request
        $payload = $request->all();

        // 3. Inject Kredensial H2H (Backend yang menempelkan Token & User ID)
        $payload['userID'] = $this->darmawisataUserId;
        $payload['accessToken'] = $this->darmawisataToken;

        // 4. Kirim Request ke Server Darmawisata
        return $this->forwardRequest('Airline/Seat', $payload);
    }

    /**
     * POST Airline/List
     * Get list of active airlines
     */
    public function airlineList(Request $request)
    {
        // 1. Ambil data request (kemungkinan kosong dari aplikasi mobile, dan itu tidak masalah)
        $payload = $request->all();

        // 2. Inject Kredensial H2H secara otomatis
        $payload['userID'] = $this->darmawisataUserId;
        $payload['accessToken'] = $this->darmawisataToken;

        // 3. Kirim Request ke Server Darmawisata
        return $this->forwardRequest('Airline/List', $payload);
    }

    /**
     * POST Airline/Route
     * Get airlines route of selected airline
     */
    public function airlineRoute(Request $request)
    {
        // 1. Validasi Data dari Aplikasi Mobile
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'airlineID' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'FAILED',
                'message' => 'Validasi gagal, pastikan airlineID terisi.',
                'errors'  => $validator->errors()
            ], 422);
        }

        // 2. Ambil data request
        $payload = $request->all();

        // 3. Inject Kredensial H2H secara otomatis
        $payload['userID'] = $this->darmawisataUserId;
        $payload['accessToken'] = $this->darmawisataToken;

        // 4. Kirim Request ke Server Darmawisata
        return $this->forwardRequest('Airline/Route', $payload);
    }

    /**
     * POST Airline/Nationality
     * Get nationality list (Daftar Kewarganegaraan/Negara)
     */
    public function airlineNationality(Request $request)
    {
        // 1. Ambil data request (kemungkinan kosong dari aplikasi mobile)
        $payload = $request->all();

        // 2. Inject Kredensial H2H secara otomatis
        $payload['userID'] = $this->darmawisataUserId;
        $payload['accessToken'] = $this->darmawisataToken;

        // 3. Kirim Request ke Server Darmawisata
        return $this->forwardRequest('Airline/Nationality', $payload);
    }

/**
     * POST Airline/LowFareRoute
     * Get all airlines route for low fare schedule
     */
    public function airlineLowFareRoute(Request $request)
    {
        // 1. Ambil data request (kosong dari aplikasi mobile)
        $payload = $request->all();

        // 2. Inject Kredensial H2H secara otomatis
        $payload['userID'] = $this->darmawisataUserId;
        $payload['accessToken'] = $this->darmawisataToken;

        // 3. Kirim Request ke Server Darmawisata
        return $this->forwardRequest('Airline/LowFareRoute', $payload);
    }

    /**
     * POST Airline/City
     * Get airline city list
     */
    public function airlineCity(Request $request)
    {
        // 1. Ambil data request (kosong dari sisi aplikasi mobile)
        $payload = $request->all();

        // 2. Inject Kredensial H2H secara otomatis
        $payload['userID'] = $this->darmawisataUserId;
        $payload['accessToken'] = $this->darmawisataToken;

        // 3. Kirim Request ke Server Darmawisata
        return $this->forwardRequest('Airline/City', $payload);
    }

    /**
     * POST Airline/Booking
     * Gain access to booking airline API (Proses Pemesanan Tiket)
     */
    public function airlineBooking(Request $request)
    {
        // 1. Validasi Super Ketat untuk Proses Booking
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'airlineID'               => 'required|string',
            'origin'                  => 'required|string',
            'destination'             => 'required|string',
            'tripType'                => 'required|string|in:OneWay,RoundTrip',
            'departDate'              => 'required|date',

            // Validasi Jadwal
            'schDeparts'              => 'required|array|min:1',
            'schReturns'              => 'nullable|array', // Optional jika hanya OneWay

            // Validasi Kontak Pemesan
            'contactFirstName'        => 'required|string',
            'contactLastName'         => 'required|string',
            'contactTitle'            => 'required|string',
            'contactCountryCodePhone' => 'required|string',
            'contactAreaCodePhone'    => 'required|string',
            'contactRemainingPhoneNo' => 'required|string',
            'contactEmail'            => 'required|email',

            // Validasi Detail Penumpang
            'paxDetails'              => 'required|array|min:1',
            'paxDetails.*.IDNumber'   => 'required|string',
            'paxDetails.*.title'      => 'required|string',
            'paxDetails.*.firstName'  => 'required|string',
            'paxDetails.*.lastName'   => 'required|string',
            'paxDetails.*.birthDate'  => 'required|date',
            'paxDetails.*.nationality'=> 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'FAILED',
                'message' => 'Validasi pemesanan gagal. Periksa kembali kelengkapan data penumpang dan jadwal.',
                'errors'  => $validator->errors()
            ], 422);
        }

        // 2. Ambil data payload dari Mobile
        $payload = $request->all();

        // 3. Inject Kredensial H2H secara otomatis
        $payload['userID'] = $this->darmawisataUserId;
        $payload['accessToken'] = $this->darmawisataToken;

        // 4. Eksekusi Request Booking ke Darmawisata
        return $this->forwardRequest('Airline/Booking', $payload);
    }

    /**
     * POST Airline/BookingList
     * Get Booking List that booked by agent H2H API (Riwayat Pemesanan)
     */
    public function airlineBookingList(Request $request)
    {
        // 1. Validasi Data Pencarian
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'startDate'      => 'required|date',
            'endDate'        => 'required|date',
            'filterByStatus' => 'nullable|string', // Opsional, e.g., "Booking", "Issued", "Failed"
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'FAILED',
                'message' => 'Parameter tanggal wajib diisi.',
                'errors'  => $validator->errors()
            ], 422);
        }

        // 2. Siapkan Payload
        $payload = $request->all();

        // Default filterByStatus ke 0 (All) jika tidak dikirim dari mobile
        if (!isset($payload['filterByStatus'])) {
            $payload['filterByStatus'] = 0;
        }

        // 3. Inject Kredensial H2H
        $payload['userID'] = $this->darmawisataUserId;
        $payload['accessToken'] = $this->darmawisataToken;

        // 4. Kirim Request
        return $this->forwardRequest('Airline/BookingList', $payload);
    }

    /**
     * POST Airline/BookingDetail
     * Get detail of a specific booking (Detail Pesanan)
     */
    public function airlineBookingDetail(Request $request)
    {
        // 1. Validasi Data
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'bookingCode' => 'required|string',
            'bookingDate' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'FAILED',
                'message' => 'Kode booking dan tanggal booking wajib diisi.',
                'errors'  => $validator->errors()
            ], 422);
        }

        // 2. Siapkan Payload
        $payload = $request->all();

        // 3. Inject Kredensial H2H
        $payload['userID'] = $this->darmawisataUserId;
        $payload['accessToken'] = $this->darmawisataToken;

        // 4. Kirim Request
        return $this->forwardRequest('Airline/BookingDetail', $payload);
    }

/**
     * POST Airline/Schedule
     * Mendapatkan jadwal penerbangan maskapai
     */
    public function airlineSchedule(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'airlineID'   => 'required|string',
            'tripType'    => 'required|string|in:OneWay,RoundTrip',
            'origin'      => 'required|string',
            'destination' => 'required|string',
            'departDate'  => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'FAILED',
                'message' => 'Parameter pencarian jadwal tidak lengkap.',
                'errors'  => $validator->errors()
            ], 422);
        }

        $payload = $request->all();
        $payload['userID'] = $this->darmawisataUserId;
        $payload['accessToken'] = $this->darmawisataToken;

        return $this->forwardRequest('Airline/Schedule', $payload);
    }

    /**
     * POST Airline/Price
     * Mendapatkan harga spesifik dari jadwal yang dipilih (per maskapai)
     */
    public function airlinePrice(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'airlineID'   => 'required|string',
            'origin'      => 'required|string',
            'destination' => 'required|string',
            'tripType'    => 'required|string|in:OneWay,RoundTrip',
            'departDate'  => 'required|date',
            'schDeparts'  => 'required|array|min:1', // Diambil dari response Schedule
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'FAILED',
                'message' => 'Validasi gagal. Pastikan jadwal penerbangan (schDeparts) telah dipilih.',
                'errors'  => $validator->errors()
            ], 422);
        }

        $payload = $request->all();
        $payload['userID'] = $this->darmawisataUserId;
        $payload['accessToken'] = $this->darmawisataToken;

        return $this->forwardRequest('Airline/Price', $payload);
    }

    /**
     * POST Airline/PriceAllAirline
     * Mendapatkan harga dari jadwal menggunakan Journey Reference
     */
    public function airlinePriceAllAirline(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'airlineID'              => 'required|string',
            'origin'                 => 'required|string',
            'destination'            => 'required|string',
            'tripType'               => 'required|string|in:OneWay,RoundTrip',
            'departDate'             => 'required|date',
            'journeyDepartReference' => 'required|string', // Reference unik dari jadwal
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'FAILED',
                'message' => 'Validasi gagal. journeyDepartReference wajib disertakan.',
                'errors'  => $validator->errors()
            ], 422);
        }

        $payload = $request->all();
        $payload['userID'] = $this->darmawisataUserId;
        $payload['accessToken'] = $this->darmawisataToken;

        return $this->forwardRequest('Airline/PriceAllAirline', $payload);
    }

    /**
     * POST Airline/Issued
     * Eksekusi penerbitan tiket (memotong saldo agen secara permanen)
     */
    public function airlineIssued(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'airlineID'   => 'required|string',
            'origin'      => 'required|string',
            'destination' => 'required|string',
            'tripType'    => 'required|string|in:OneWay,RoundTrip',
            'departDate'  => 'required|date',
            'bookingCode' => 'required|string', // PNR yang didapat saat Booking
            'bookingDate' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'FAILED',
                'message' => 'Validasi gagal. Data tiket tidak lengkap.',
                'errors'  => $validator->errors()
            ], 422);
        }

        // PERHATIAN: Di sistem *real*, Anda harus mengecek saldo user/dompet internal (Sancaka)
        // milik pelanggan Anda terlebih dahulu sebelum memanggil API Darmawisata ini.
        // Pastikan saldo mereka cukup untuk membayar tiket ini!

        $payload = $request->all();
        $payload['userID'] = $this->darmawisataUserId;
        $payload['accessToken'] = $this->darmawisataToken;

        return $this->forwardRequest('Airline/Issued', $payload);
    }

    /**
     * POST Airline/LowFareSchedule
     * Mendapatkan jadwal penerbangan dengan tarif termurah
     */
    public function airlineLowFareSchedule(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'tripType'          => 'required|string|in:OneWay,RoundTrip',
            'origin'            => 'required|string',
            'destination'       => 'required|string',
            'departDate'        => 'required|date',
            'cacheType'         => 'required', // Bisa bernilai 0 (FullCache), 1 (FullLive), atau 2 (Mix)
            'isShowEachAirline' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'FAILED',
                'message' => 'Parameter Low Fare Schedule tidak lengkap.',
                'errors'  => $validator->errors()
            ], 422);
        }

        $payload = $request->all();
        $payload['userID'] = $this->darmawisataUserId;
        $payload['accessToken'] = $this->darmawisataToken;

        return $this->forwardRequest('Airline/LowFareSchedule', $payload);
    }

    /**
     * POST Airline/ScheduleAllAirline
     * Mendapatkan jadwal penerbangan dari semua maskapai sekaligus
     */
    public function airlineScheduleAllAirline(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'tripType'          => 'required|string|in:OneWay,RoundTrip',
            'origin'            => 'required|string',
            'destination'       => 'required|string',
            'departDate'        => 'required|date',
            'cacheType'         => 'required',
            'isShowEachAirline' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'FAILED',
                'message' => 'Parameter pencarian jadwal semua maskapai tidak lengkap.',
                'errors'  => $validator->errors()
            ], 422);
        }

        $payload = $request->all();
        $payload['userID'] = $this->darmawisataUserId;
        $payload['accessToken'] = $this->darmawisataToken;

        return $this->forwardRequest('Airline/ScheduleAllAirline', $payload);
    }

    /**
     * POST Airline/timer_Elapsed
     * Endpoint untuk trigger internal / keep-alive ping
     */
    public function airlineTimerElapsed(Request $request)
    {
        // Endpoint ini tidak memerlukan parameter input maupun kredensial user
        return $this->forwardRequest('Airline/timer_Elapsed', []);
    }

    /**
     * POST Session/Login
     * Melakukan autentikasi ke server Darmawisata untuk mendapatkan Access Token
     */
    public function sessionLogin(Request $request)
    {
        // 1. Validasi Input
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'token'        => 'required|string', // Timestamp
            'securityCode' => 'required|string', // Password mentah
            'language'     => 'nullable',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status'  => 'FAILED',
                    'message' => 'Validasi gagal',
                    'errors'  => $validator->errors()
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        $pass = "Darmaj4y4";
        $user = $this->darmawisataUserId; // PWB6RGHXRC
        $hash = md5($user . $pass); // Gabungan tanpa spasi

        $payload = [
            'token'        => $request->token,
            'securityCode' => $hash,
            'userID'       => $user,
            'language'     => $request->language ?? "ID",
            'accessToken'  => ""
        ];

        // 3. Kirim Request ke Server Darmawisata via BaseController
        $response = $this->forwardRequest('Session/Login', $payload);
        $data = json_decode($response->getContent(), true);

        // 4. Cek Jika Request dipanggil dari Halaman Web (Blade)
        if (!$request->expectsJson()) {
            if (isset($data['status']) && $data['status'] === 'SUCCESS') {
                // Simpan token ke session jika perlu, atau cukup tampilkan pesan sukses
                return back()->with('success', "
                    <strong>Login Berhasil!</strong><br>
                    Token: <code class='text-break'>{$data['accessToken']}</code><br>
                    Waktu Server: {$data['respTime']}
                ");
            } else {
                $errorMessage = $data['respMessage'] ?? ($data['message'] ?? 'Authentication failed');
                return back()->with('error', 'Login Gagal: ' . $errorMessage)->withInput();
            }
        }

        // 5. Jika dipanggil dari Mobile, kembalikan JSON asli
        return $response;
    }
}
