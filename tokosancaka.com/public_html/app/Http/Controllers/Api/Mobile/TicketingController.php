<?php

namespace App\Http\Controllers\Api\Mobile;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

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
        $csPayload['accessToken'] = $this->darmawisataToken;

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
     * POST Airline/Price
     * Endpoint untuk mengecek harga detail dari maskapai berdasarkan jadwal yang dipilih
     */
    public function airlinePrice(Request $request)
    {
        // 1. Validasi Data Dasar (Mengacu pada dokumen API Darmawisata)
        $validator = Validator::make($request->all(), [
            'airlineID'   => 'required|string',
            'origin'      => 'required|string',
            'destination' => 'required|string',
            'tripType'    => 'required|string',
            'departDate'  => 'required|string',
            'schDeparts'  => 'required|array', // Wajib ada untuk pengecekan harga
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

        // ======================================================================
        // FIX PENTING: Cegah Laravel mengirim 'null' ke Darmawisata
        // Middleware Laravel mengubah "" jadi null, kita kembalikan lagi jadi ""
        // ======================================================================
        if (isset($payload['schDeparts']) && is_array($payload['schDeparts'])) {
            foreach ($payload['schDeparts'] as $key => $depart) {
                $payload['schDeparts'][$key]['flightClass'] = $depart['flightClass'] ?? "";
                $payload['schDeparts'][$key]['detailSchedule'] = $depart['detailSchedule'] ?? "";
                $payload['schDeparts'][$key]['garudaNumber'] = $depart['garudaNumber'] ?? "";
                $payload['schDeparts'][$key]['garudaAvailability'] = $depart['garudaAvailability'] ?? "";
            }
        }

        if (isset($payload['schReturns']) && is_array($payload['schReturns'])) {
            foreach ($payload['schReturns'] as $key => $return) {
                $payload['schReturns'][$key]['flightClass'] = $return['flightClass'] ?? "";
                $payload['schReturns'][$key]['detailSchedule'] = $return['detailSchedule'] ?? "";
                $payload['schReturns'][$key]['garudaNumber'] = $return['garudaNumber'] ?? "";
                $payload['schReturns'][$key]['garudaAvailability'] = $return['garudaAvailability'] ?? "";
            }
        }
        // ======================================================================

        // 3. Kirim Request ke Server Darmawisata
        $response = $this->forwardRequest('Airline/Price', $payload);

        // Opsional: Log response jika butuh untuk debug
        Log::info("LOG LOG: Response dari Darmawisata (Airline/Price): " . $response->getContent());

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
     * POST Airline/List
     * Get list of active airlines
     */
    public function airlineList(Request $request)
    {
        // 1. Ambil data request (kemungkinan kosong dari aplikasi mobile, dan itu tidak masalah)
        $payload = $request->all();



        // 3. Kirim Request ke Server Darmawisata
        return $this->forwardRequest('Airline/List', $payload);
    }



}
