<?php

namespace App\Http\Controllers\Api\Mobile;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class TrainTicketingController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    // ========================================================================
    // 1. DATA MASTER & SCHEDULE
    // ========================================================================

    public function trainList(Request $request)
    {
        Log::info("\n========== [TRAIN LIST - START] ==========");
        $payload = ['userID' => $this->darmawisataUserId, 'accessToken' => $request->accessToken];
        return $this->forwardRequest('Train/List', $payload);
    }

    public function trainRoute(Request $request)
    {
        Log::info("\n========== [TRAIN ROUTE - START] ==========");
        $validator = Validator::make($request->all(), ['trainID' => 'required|string']);
        if ($validator->fails()) return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);

        $payload = [
            'trainID' => $request->trainID,
            'userID' => $this->darmawisataUserId,
            'accessToken' => $request->accessToken
        ];
        return $this->forwardRequest('Train/Route', $payload);
    }

    public function trainSchedule(Request $request)
    {
        Log::info("\n========== [TRAIN SCHEDULE - START] ==========");
        Log::info("Payload Request Mobile: ", $request->all());

        $validator = Validator::make($request->all(), [
            'trainID'     => 'required|string',
            'departDate'  => 'required|string',
            'origin'      => 'required|string',
            'destination' => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::warning("Train Schedule Validasi Gagal: ", $validator->errors()->toArray());
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        $payload = [
            'trainID'     => $request->trainID,
            'paxAdult'    => $request->paxAdult ?? 1,
            'paxChild'    => $request->paxChild ?? 0,
            'paxInfant'   => $request->paxInfant ?? 0,
            'departDate'  => date('Y-m-d\T00:00:00', strtotime($request->departDate)),
            'origin'      => $request->origin,
            'destination' => $request->destination,
            'userID'      => $this->darmawisataUserId,
            'accessToken' => $request->accessToken
        ];

        Log::info("Payload to Darmawisata [Train/Schedule]: ", $payload);
        $response = $this->forwardRequest('Train/Schedule', $payload);

        // Log opsional (bisa di-comment jika response terlalu panjang)
        // Log::info("Response Darmawisata [Train/Schedule]: " . $response->getContent());

        return $response;
    }

    // ========================================================================
    // 2. BOOKING FLOW (DRAFT -> HIT API -> HOLD)
    // ========================================================================

    public function trainBooking(Request $request)
    {
        Log::info("\n========== [TRAIN BOOKING - START] ==========");
        Log::info("Payload Request Mobile: ", $request->all());

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
            Log::warning("Train Booking Validasi Gagal: ", $validator->errors()->toArray());
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        try {
            // STEP A: SIMPAN DATABASE STATUS DRAFT
            Log::info("Proses simpan DRAFT ke database lokal...");
            $orderId = DB::transaction(function () use ($request) {
                $paxAdult = 0; $paxChild = 0; $paxInfant = 0;
                foreach ($request->passengers as $pax) {
                    if ($pax['type'] == 0 || strtolower($pax['type']) == 'adult') $paxAdult++;
                    elseif ($pax['type'] == 1 || strtolower($pax['type']) == 'child') $paxChild++;
                    elseif ($pax['type'] == 2 || strtolower($pax['type']) == 'infant') $paxInfant++;
                }

                $id = DB::table('train_orders')->insertGetId([
                    'user_id'            => $request->user()->id_pengguna ?? null,
                    'dw_access_token'    => $request->accessToken,
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
                    'status'             => 'DRAFT',
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ]);

                foreach ($request->passengers as $pax) {
                    DB::table('train_passengers')->insert([
                        'train_order_id' => $id,
                        'name'           => $pax['name'],
                        'id_number'      => $pax['IDNumber'] ?? null,
                        'pax_type'       => (is_numeric($pax['type']) ? $pax['type'] : ($pax['type'] == 'Adult' ? 0 : 1)),
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ]);
                }
                return $id;
            });

            Log::info("Train Order DRAFT berhasil dibuat. Local ID: " . $orderId);

            // STEP B: RAKIT PAYLOAD DARMAWISATA
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
                "passengers"        => $request->passengers,
                "trainID"           => $request->trainID,
                "userID"            => $this->darmawisataUserId,
                "accessToken"       => $request->accessToken
            ];

            Log::info("Payload to Darmawisata [Train/Booking]: ", $dwPayload);

            // STEP C: TEMBAK API
            $response = $this->forwardRequest('Train/Booking', $dwPayload);
            $json = json_decode($response->getContent(), true);

            Log::info("Response Darmawisata [Train/Booking]: ", $json ?? ['error' => 'No JSON Response']);

            // STEP D: UPDATE DATABASE LOKAL
            if (isset($json['status']) && $json['status'] === 'SUCCESS') {
                DB::table('train_orders')->where('id', $orderId)->update([
                    'booking_code' => $json['bookingCode'],
                    'time_limit'   => date('Y-m-d H:i:s', strtotime($json['issuedTimeLimit'])),
                    'ticket_price' => $json['ticketPrice'] ?? 0,
                    'admin_fee'    => $json['adminFee'] ?? 0,
                    'total_fare'   => $json['salesPrice'] ?? 0,
                    'status'       => 'HOLD',
                    'updated_at'   => now()
                ]);
                Log::info("Train Order UPDATE ke HOLD sukses. PNR: " . $json['bookingCode']);
            } else {
                $message = $json['respMessage'] ?? 'KAI menolak penerbitan tiket.';

                // [TAMBAHAN BARU] Tangkap jika KAI membatalkan tiket karena Expired / Time Limit Habis
                if (isset($json['bookingStatus']) && strtolower($json['bookingStatus']) === 'canceled') {
                    // Update status lokal menjadi CANCELLED agar di aplikasi berubah jadi Batal
                    DB::table('train_orders')->where('id', $order->id)->update([
                        'status' => 'CANCELLED',
                        'updated_at' => now()
                    ]);

                    Log::warning("Train Issued Gagal: Time limit habis untuk PNR {$order->booking_code}. Status diubah ke CANCELLED.");
                    return response()->json([
                        'status' => 'FAILED',
                        'message' => 'Batas waktu pembayaran habis. Tiket telah dibatalkan otomatis oleh sistem KAI. Silakan pesan ulang.'
                    ]);
                }

                // Tangkap jika saldo agen H2H pusat habis
                if (str_contains(strtolower($message), 'insufficient balance')) {
                    Log::error("FATAL: Gagal Issued Kereta karena Saldo H2H Pusat Darmawisata Habis!");
                    return response()->json([
                        'status' => 'FAILED',
                        'message' => 'Tiket gagal diterbitkan: Saldo deposit pusat tidak cukup. Hubungi admin.'
                    ]);
                }

                return response()->json(['status' => 'FAILED', 'message' => 'Gagal Issued: ' . $message]);
            }

        } catch (\Exception $e) {
            Log::error("FATAL ERROR [Train Booking]: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
            return response()->json(['status' => 'FAILED', 'message' => 'System Error: ' . $e->getMessage()], 500);
        }
    }

    // ========================================================================
    // 3. SEAT MAP & TAKE SEAT
    // ========================================================================

    public function trainSeatMap(Request $request)
    {
        Log::info("\n========== [TRAIN SEAT MAP - START] ==========");
        Log::info("Payload Request Mobile: ", $request->all());

        $payload = $request->all();
        $payload['userID'] = $this->darmawisataUserId;
        $payload['departDate'] = date('Y-m-d\TH:i:s', strtotime($request->departDate));

        // Darmawisata wajib meminta format tanggal booking sama persis
        if(isset($payload['bookingDate'])) {
            $payload['bookingDate'] = date('Y-m-d\TH:i:s', strtotime($request->bookingDate));
        }

        Log::info("Payload to Darmawisata [Train/SeatMap]: ", $payload);
        return $this->forwardRequest('Train/SeatMap', $payload);
    }

    public function trainTakeSeat(Request $request)
    {
        Log::info("\n========== [TRAIN TAKE SEAT - START] ==========");
        Log::info("Payload Request Mobile: ", $request->all());

        $validator = Validator::make($request->all(), [
            'bookingCode' => 'required|string',
            'bookingDate' => 'required|string',
            'trainID'     => 'required|string',
            'passengers'  => 'required|array'
        ]);

        if ($validator->fails()) return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);

        $payload = [
            'bookingCode' => $request->bookingCode,
            'bookingDate' => date('Y-m-d\TH:i:s', strtotime($request->bookingDate)),
            'trainID'     => $request->trainID,
            'passengers'  => $request->passengers,
            'userID'      => $this->darmawisataUserId,
            'accessToken' => $request->accessToken
        ];

        Log::info("Payload to Darmawisata [Train/TakeSeat]: ", $payload);
        $response = $this->forwardRequest('Train/TakeSeat', $payload);

        // Jika sukses, opsional update tabel train_passengers dengan kursi baru
        $json = json_decode($response->getContent(), true);
        if (isset($json['status']) && $json['status'] === 'SUCCESS') {
            Log::info("Ubah kursi sukses untuk PNR: " . $request->bookingCode);
        }

        return $response;
    }

    // ========================================================================
    // 4. ISSUED FLOW
    // ========================================================================

    public function trainIssued(Request $request)
{
    Log::info("\n========== [TRAIN ISSUED - START] ==========");
    $validator = Validator::make($request->all(), ['order_id' => 'required|integer']);

    if ($validator->fails()) {
        return response()->json(['status' => 'FAILED', 'message' => 'Order ID tidak valid'], 422);
    }

    try {
        $order = DB::table('train_orders')->where('id', $request->order_id)->first();

        if (!$order) {
            return response()->json(['status' => 'FAILED', 'message' => 'Pesanan tidak ditemukan.']);
        }

        if ($order->status === 'ISSUED') {
            return response()->json(['status' => 'FAILED', 'message' => 'Tiket sudah Issued.']);
        }

       // ==========================================
        // VALIDASI PNR KOSONG (Sangat Penting)
        // ==========================================
        if (empty($order->booking_code)) {
            Log::error('LOG LOG: [TRAIN ISSUED] Gagal: booking_code kosong di database untuk order ID: ' . $order->id);
            return response()->json([
                'status' => 'FAILED',
                'message' => 'Kode Booking (PNR) tidak ditemukan. Pesanan ini kemungkinan gagal saat proses Booking.'
            ], 400);
        }

        // Pastikan format ISO 8601 (T) dan zona waktu mengikuti standar Darmawisata
        $payloadIssued = [
            "bookingCode" => $order->booking_code,
            "bookingDate" => date('c', strtotime($order->created_at)), // Mengubah format menjadi standar ISO dengan Timezone
            "userID"      => $this->darmawisataUserId,
            "accessToken" => $order->dw_access_token
        ];

        Log::info("LOG LOG: [TRAIN ISSUED] Payload to Darmawisata: ", $payloadIssued);
        $response = $this->forwardRequest('Train/Issued', $payloadIssued);

            $json = json_decode($response->getContent(), true);

            Log::info("Response Darmawisata [Train/Issued]: ", $json ?? ['error' => 'No JSON']);

            if (isset($json['status']) && $json['status'] === 'SUCCESS') {
                $amount = (float) $order->total_fare;
                $user = $request->user();

                if ($user->saldo < $amount) {
                    Log::error("Gagal Issued Kereta karena saldo User Lokal tidak cukup. Butuh: $amount, Saldo: {$user->saldo}");
                    return response()->json(['status' => 'FAILED', 'message' => 'Saldo tidak cukup untuk Issued.']);
                }

                // Potong Saldo User & Agen
                DB::table('Pengguna')->where('id_pengguna', $user->id_pengguna)->decrement('saldo', $amount);
                DB::table('Pengguna')->where('id_pengguna', 4)->decrement('balance_iak', $amount);

                DB::table('train_orders')->where('id', $order->id)->update([
                    'status'     => 'ISSUED',
                    'updated_at' => now()
                ]);

                Log::info("Train Order ISSUED SUKSES. PNR: " . $order->booking_code . " | Saldo terpotong: " . $amount);

                return response()->json([
                    'status'      => 'SUCCESS',
                    'bookingCode' => $order->booking_code,
                    'message'     => 'Tiket Kereta Berhasil Dicetak (LUNAS) dan Saldo Terpotong!',
                    'data'        => $json
                ]);

            } else {
                $message = $json['respMessage'] ?? 'KAI menolak penerbitan tiket.';
                if (str_contains(strtolower($message), 'insufficient balance')) {
                    Log::error("FATAL: Gagal Issued Kereta karena Saldo H2H Pusat Darmawisata Habis!");
                    return response()->json([
                        'status' => 'FAILED',
                        'message' => 'Tiket gagal diterbitkan: Saldo deposit pusat tidak cukup. Hubungi admin.'
                    ]);
                }
                return response()->json(['status' => 'FAILED', 'message' => 'Gagal Issued: ' . $message]);
            }

        } catch (\Exception $e) {
            Log::error("FATAL ERROR [Train Issued]: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
            return response()->json(['status' => 'FAILED', 'message' => 'Sistem Error: ' . $e->getMessage()], 500);
        }
    }

    // ========================================================================
    // 5. MANAJEMEN & CANCEL
    // ========================================================================

    public function trainBookingDetail(Request $request)
    {
        Log::info("\n========== [TRAIN BOOKING DETAIL - START] ==========");
        $payload = [
            'bookingCode' => $request->bookingCode,
            'bookingDate' => date('Y-m-d\TH:i:s', strtotime($request->bookingDate)),
            'userID'      => $this->darmawisataUserId,
            'accessToken' => $request->accessToken
        ];

        Log::info("Payload to Darmawisata [Train/BookingDetail]: ", $payload);
        return $this->forwardRequest('Train/BookingDetail', $payload);
    }

    public function trainBookingList(Request $request)
    {
        Log::info("\n========== [TRAIN BOOKING LIST - START] ==========");
        $payload = [
            'filterBy'    => $request->filterBy ?? 0,
            'startDate'   => date('Y-m-d\TH:i:s', strtotime($request->startDate)),
            'endDate'     => date('Y-m-d\TH:i:s', strtotime($request->endDate)),
            'userID'      => $this->darmawisataUserId,
            'accessToken' => $request->accessToken
        ];

        Log::info("Payload to Darmawisata [Train/BookingList]: ", $payload);
        return $this->forwardRequest('Train/BookingList', $payload);
    }

    public function trainCancel(Request $request)
    {
        Log::info("\n========== [TRAIN CANCEL - START] ==========");
        $payload = [
            'bookingCode' => $request->bookingCode,
            'bookingDate' => date('Y-m-d\TH:i:s', strtotime($request->bookingDate)),
            'userID'      => $this->darmawisataUserId,
            'accessToken' => $request->accessToken
        ];

        Log::info("Payload to Darmawisata [Train/Cancel]: ", $payload);
        $response = $this->forwardRequest('Train/Cancel', $payload);

        $json = json_decode($response->getContent(), true);
        if (isset($json['status']) && $json['status'] === 'SUCCESS') {
            DB::table('train_orders')->where('booking_code', $request->bookingCode)->update(['status' => 'CANCELLED', 'updated_at' => now()]);
            Log::info("Order PNR {$request->bookingCode} berhasil di-CANCEL.");
        }

        return $response;
    }

    // ========================================================================
    // 6. HISTORY (PENGAMBILAN DATA LOKAL UNTUK FRONTEND)
    // ========================================================================

    public function trainHistory(Request $request)
    {
        Log::info("\n========== [TRAIN HISTORY - START] ==========");

        try {
            $user = $request->user();

            // Ambil data riwayat dari database lokal berdasarkan user yang login
            $orders = DB::table('train_orders')
                ->where('user_id', $user->id_pengguna ?? $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            // Format data agar sesuai persis dengan interface BookingItem di React Native
            $formattedData = $orders->map(function ($order) {
                return [
                    'id'            => $order->id,
                    'bookingCode'   => $order->booking_code ?? 'PROSES',
                    'trainName'     => $order->train_name,
                    // Tambahkan 3 baris ini untuk kebutuhan Seat Map
                    'trainNumber'   => $order->train_number,
                    'subClass'      => $order->sub_class,
                    'bookingDate'   => $order->created_at, // Darmawisata butuh format tanggal order

                    'origin'        => $order->origin,
                    'destination'   => $order->destination,
                    'departDate'    => $order->depart_date,
                    'status'        => $order->status, // HOLD, ISSUED, CANCELLED, FAILED
                    'totalFare'     => (float) $order->total_fare,
                    'paymentMethod' => $order->payment_method ?? 'SALDO',
                    'paymentUrl'    => $order->payment_url,
                    'timeLimit'     => $order->time_limit,
                ];
            });

            Log::info("Berhasil mengambil " . $formattedData->count() . " riwayat transaksi kereta.");

            return response()->json([
                'status' => 'SUCCESS',
                'data'   => $formattedData
            ], 200);

        } catch (\Exception $e) {
            Log::error("FATAL ERROR [Train History]: " . $e->getMessage());
            return response()->json([
                'status'  => 'FAILED',
                'message' => 'Sistem Error saat memuat riwayat.'
            ], 500);
        }
    }
}
