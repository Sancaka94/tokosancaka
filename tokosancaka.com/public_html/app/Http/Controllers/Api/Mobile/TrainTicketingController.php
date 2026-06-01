<?php

namespace App\Http\Controllers\Api\Mobile;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class TrainController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    // 1. STEP: SCHEDULE
    public function trainSchedule(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'origin'      => 'required|string',
            'destination' => 'required|string',
            'departDate'  => 'required|date',
            'trainID'     => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'FAILED', 'message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $payload = $request->all();
        $payload['paxAdult'] = $payload['paxAdult'] ?? 1;
        $payload['paxChild'] = $payload['paxChild'] ?? 0;
        $payload['paxInfant'] = $payload['paxInfant'] ?? 0;

        return $this->forwardRequest('Train/Schedule', $payload);
    }

    // 2. STEP: BOOKING (Mendapatkan bookingCode)
    public function trainBooking(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'origin'            => 'required|string',
            'destination'       => 'required|string',
            'departDate'        => 'required|string',
            'trainID'           => 'required|string',
            'trainNumber'       => 'required|string',
            'availabilityClass' => 'required|string',
            'subClass'          => 'required|string',
            'contactName'       => 'required|string',
            'contactPhone'      => 'required|string',
            'passengers'        => 'required|array'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        $response = $this->forwardRequest('Train/Booking', $request->all());
        
        // Disarankan: Simpan bookingCode yang didapat ke database lokal Anda dengan status HOLD
        return $response;
    }

    // 3A. STEP: SEAT MAP (Dipanggil jika user klik "Ubah Kursi")
    public function trainSeatMap(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'origin'      => 'required|string',
            'destination' => 'required|string',
            'departDate'  => 'required|string',
            'trainID'     => 'required|string',
            'trainNumber' => 'required|string',
            'subClass'    => 'required|string',
            'bookingCode' => 'required|string', // Didapat dari proses Booking
            'bookingDate' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        return $this->forwardRequest('Train/SeatMap', $request->all());
    }

    // 3B. STEP: TAKE SEAT (Dipanggil untuk mengunci kursi yang dipilih)
    public function trainTakeSeat(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bookingCode' => 'required|string',
            'bookingDate' => 'required|string',
            'trainID'     => 'required|string',
            'passengers'  => 'required|array' // Berisi mapping penumpang dengan kursi baru
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        return $this->forwardRequest('Train/TakeSeat', $request->all());
    }

    // 4. STEP: ISSUED (Bayar tiket)
    public function trainIssued(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bookingCode' => 'required|string',
            'bookingDate' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        // Logic pemotongan saldo lokal bisa disisipkan sebelum atau sesudah forwardRequest, 
        // mirip dengan alur AirlineIssued
        
        return $this->forwardRequest('Train/Issued', $request->all());
    }

    // 5. STEP: BOOKING DETAIL (Cek status akhir tiket)
    public function trainBookingDetail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bookingCode' => 'required|string',
            'bookingDate' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        return $this->forwardRequest('Train/BookingDetail', $request->all());
    }
}