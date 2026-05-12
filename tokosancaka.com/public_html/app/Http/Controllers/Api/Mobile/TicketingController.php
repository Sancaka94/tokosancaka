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
