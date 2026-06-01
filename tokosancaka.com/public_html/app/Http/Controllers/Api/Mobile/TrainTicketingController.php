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

    /**
     * POST Train/Booking
     * Flow: Simpan DRAFT -> Tembak API Booking -> Update ke HOLD jika sukses
     */
    public function trainBooking(Request $request)
    {
        // 1. Validasi Input dari Aplikasi Mobile
        $validator = Validator::make($request->all(), [
            'origin'            => 'required|string',
            'destination'       => 'required|string',
            'departDate'        => 'required|string',
            'trainID'           => 'required|string',
            'trainNumber'       => 'required|string',
            'trainName'         => 'nullable|string',
            'availabilityClass' => 'required|string',
            'subClass'          => 'required|string',
            'contactName'       => 'required|string',
            'contactPhone'      => 'required|string',
            'passengers'        => 'required|array'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        try {
            // 2. SIMPAN KE DATABASE LOKAL DENGAN STATUS "DRAFT"
            $orderId = DB::transaction(function () use ($request) {
                // Hitung Pax
                $paxAdult = 0; $paxChild = 0; $paxInfant = 0;
                foreach ($request->passengers as $pax) {
                    if ($pax['type'] == 0) $paxAdult++;
                    elseif ($pax['type'] == 1) $paxChild++;
                    elseif ($pax['type'] == 2) $paxInfant++;
                }

                // Insert Order (Induk)
                $id = DB::table('train_orders')->insertGetId([
                    'user_id'            => $request->user()->id_pengguna ?? null,
                    'dw_access_token'    => $request->accessToken, // Token fresh dari Darmawisata
                    'train_id'           => $request->trainID,
                    'train_number'       => $request->trainNumber,
                    'train_name'         => $request->trainName ?? '-',
                    'origin'             => $request->origin,
                    'destination'        => $request->destination,
                    'depart_date'        => date('Y-m-d H:i:s', strtotime($request->departDate)),
                    'availability_class' => $request->availabilityClass,
                    'sub_class'          => $request->subClass,
                    'contact_name'       => $request->contactName,
                    'contact_phone'      => $request->contactPhone,
                    'pax_adult'          => $paxAdult,
                    'pax_child'          => $paxChild,
                    'pax_infant'         => $paxInfant,
                    'status'             => 'DRAFT', // <-- STATUS AWAL DRAFT
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ]);

                // Insert Penumpang (Anak)
                foreach ($request->passengers as $pax) {
                    DB::table('train_passengers')->insert([
                        'train_order_id' => $id,
                        'name'           => $pax['name'],
                        'id_number'      => $pax['IDNumber'] ?? null,
                        'pax_type'       => $pax['type'],
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ]);
                }

                return $id;
            });

            Log::info("Train Order DRAFT berhasil dibuat. Local ID: " . $orderId);

            // 3. SIAPKAN PAYLOAD UNTUK DARMAWISATA (Sesuai Dokumentasi Train Booking)
            $dwPayload = [
                "origin"            => $request->origin,
                "destination"       => $request->destination,
                "departDate"        => date('Y-m-d\T00:00:00', strtotime($request->departDate)),
                "trainNumber"       => $request->trainNumber,
                "availabilityClass" => $request->availabilityClass,
                "subClass"          => $request->subClass,
                "contactName"       => $request->contactName,
                "contactPhone"      => $request->contactPhone,
                "paxAdult"          => DB::table('train_passengers')->where('train_order_id', $orderId)->where('pax_type', 0)->count(),
                "paxChild"          => DB::table('train_passengers')->where('train_order_id', $orderId)->where('pax_type', 1)->count(),
                "paxInfant"         => DB::table('train_passengers')->where('train_order_id', $orderId)->where('pax_type', 2)->count(),
                "passengers"        => $request->passengers, // Pastikan format array sesuai dokumen Darmawisata
                "trainID"           => $request->trainID,
                "userID"            => $this->darmawisataUserId,
                "accessToken"       => $request->accessToken
            ];

            // 4. TEMBAK API DARMAWISATA
            $response = $this->forwardRequest('Train/Booking', $dwPayload);
            $json = json_decode($response->getContent(), true);

            // 5. UPDATE DATABASE JIKA BOOKING SUKSES (Dapat PNR)
            if (isset($json['status']) && $json['status'] === 'SUCCESS') {
                DB::table('train_orders')->where('id', $orderId)->update([
                    'booking_code' => $json['bookingCode'],
                    'time_limit'   => date('Y-m-d H:i:s', strtotime($json['issuedTimeLimit'])),
                    'ticket_price' => $json['ticketPrice'] ?? 0,
                    'admin_fee'    => $json['adminFee'] ?? 0,
                    'total_fare'   => $json['salesPrice'] ?? 0,
                    'status'       => 'HOLD', // <-- UBAH JADI HOLD KARENA BERHASIL
                    'updated_at'   => now()
                ]);
                Log::info("Train Order HOLD. PNR: " . $json['bookingCode']);
            } else {
                // Jika API menolak, ubah status jadi FAILED
                DB::table('train_orders')->where('id', $orderId)->update([
                    'status' => 'FAILED',
                    'updated_at' => now()
                ]);
            }

            // Kembalikan response Darmawisata ke HP
            return $response;

        } catch (\Exception $e) {
            Log::error("Gagal Booking Kereta: " . $e->getMessage());
            return response()->json(['status' => 'FAILED', 'message' => 'System Error: ' . $e->getMessage()], 500);
        }
    }


    /**
     * POST Train/Issued
     * Flow: Tombol Issued ditekan -> Tembak API Issued -> Potong Saldo -> Update Lunas
     */
    public function trainIssued(Request $request)
    {
        // 1. Validasi: HP cukup mengirim order_id (ID Lokal)
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'FAILED', 'message' => 'Order ID tidak valid', 'errors' => $validator->errors()], 422);
        }

        try {
            // 2. Ambil data dari database lokal
            $order = DB::table('train_orders')->where('id', $request->order_id)->first();

            if (!$order) {
                return response()->json(['status' => 'FAILED', 'message' => 'Pesanan tidak ditemukan di database.']);
            }

            if ($order->status === 'ISSUED') {
                return response()->json(['status' => 'FAILED', 'message' => 'Tiket ini sudah lunas/Issued.']);
            }

            if (empty($order->booking_code)) {
                return response()->json(['status' => 'FAILED', 'message' => 'Kode Booking (PNR) kosong, tidak bisa mencetak tiket.']);
            }

            // 3. Rakit Payload untuk Issued Kereta
            $payloadIssued = [
                "bookingCode" => $order->booking_code,
                "bookingDate" => date('Y-m-d\TH:i:s', strtotime($order->created_at)), // Sesuai permintaan format Darmawisata
                "userID"      => $this->darmawisataUserId,
                "accessToken" => $order->dw_access_token
            ];

            // 4. Tembak API Darmawisata
            $response = $this->forwardRequest('Train/Issued', $payloadIssued);
            $json = json_decode($response->getContent(), true);

            // 5. EVALUASI DAN EKSEKUSI PEMOTONGAN SALDO
            if (isset($json['status']) && $json['status'] === 'SUCCESS') {
                
                $amount = (float) $order->total_fare;
                $user = $request->user();

                // Cek Saldo User (Failsafe)
                if ($user->saldo < $amount) {
                    return response()->json(['status' => 'FAILED', 'message' => 'Saldo tidak cukup untuk melakukan Issued tiket ini.']);
                }

                // Proses Potong Saldo User (Asumsi tabel 'Pengguna' dan kolom 'saldo')
                DB::table('Pengguna')->where('id_pengguna', $user->id_pengguna)->decrement('saldo', $amount);
                
                // Proses Potong Saldo Agen Darmawisata Induk (Opsional, sesuai logika bisnis Anda)
                DB::table('Pengguna')->where('id_pengguna', 4)->decrement('balance_iak', $amount);

                // Update Status Order Lokal menjadi ISSUED
                DB::table('train_orders')->where('id', $order->id)->update([
                    'status'     => 'ISSUED',
                    'updated_at' => now()
                ]);

                return response()->json([
                    'status'      => 'SUCCESS',
                    'bookingCode' => $order->booking_code,
                    'message'     => 'Tiket Kereta Berhasil Dicetak (LUNAS) dan Saldo Terpotong!',
                    'data'        => $json
                ]);

            } else {
                // Deteksi jika saldo H2H pusat (Sandbox/Production) habis
                $message = $json['respMessage'] ?? 'KAI menolak penerbitan tiket.';
                if (str_contains(strtolower($message), 'insufficient balance')) {
                    Log::error("Gagal Issued Kereta karena Saldo H2H Habis!");
                    return response()->json([
                        'status' => 'FAILED',
                        'message' => 'Tiket gagal diterbitkan: Saldo deposit pusat tidak cukup. Hubungi admin.'
                    ]);
                }

                return response()->json([
                    'status' => 'FAILED',
                    'message' => 'Gagal Issued dari KAI: ' . $message
                ]);
            }

        } catch (\Exception $e) {
            Log::error("Proses Issued Kereta Gagal: " . $e->getMessage());
            return response()->json(['status' => 'FAILED', 'message' => 'Sistem Error: ' . $e->getMessage()], 500);
        }
    }
}