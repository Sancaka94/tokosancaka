<?php 

namespace App\Http\Controllers\Api\Mobile;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ShipDluTicketingController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function shipDluRoute(Request $request)
    {
        Log::info("\n========== [SHIP DLU ROUTE - START] ==========");
        $payload = ['userID' => $this->darmawisataUserId, 'accessToken' => $request->accessToken];
        return $this->forwardRequest('ShipDlu/Route', $payload);
    }

    public function shipDluSchedule(Request $request)
    {
        Log::info("\n========== [SHIP DLU SCHEDULE - START] ==========");
        Log::info("Payload Request Mobile: ", $request->all());

        $validator = Validator::make($request->all(), [
            'ticketType'      => 'required|string',
            'paxClass'        => 'required|string',
            'vehicleType'     => 'nullable|string',
            'roomClass'       => 'nullable|string',
            'originPort'      => 'required|string',
            'destinationPort' => 'required|string',
            'departStartDate' => 'required|string',
            'departEndDate'   => 'required|string',
            'accessToken'     => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::warning("ShipDlu Schedule Validasi Gagal: ", $validator->errors()->toArray());
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        $payload = [
            'ticketType'      => $request->ticketType,
            'paxClass'        => $request->paxClass,
            'vehicleType'     => $request->vehicleType ?? "",
            'roomClass'       => $request->roomClass ?? "",
            'originPort'      => $request->originPort,
            'destinationPort' => $request->destinationPort,
            // Format Wajib ISO 8601 dengan Timezone (+07:00) menggunakan huruf 'P'
            'departStartDate' => date('Y-m-d\T00:00:00P', strtotime($request->departStartDate)),
            'departEndDate'   => date('Y-m-d\T23:59:59P', strtotime($request->departEndDate)),
            'userID'          => $this->darmawisataUserId,
            'accessToken'     => $request->accessToken
        ];

        Log::info("Payload to Darmawisata [ShipDlu/Schedule]: ", $payload);
        return $this->forwardRequest('ShipDlu/Schedule', $payload);
    }

    public function shipDluSelectSchedule(Request $request)
    {
        Log::info("\n========== [SHIP DLU SELECT SCHEDULE - START] ==========");

        $validator = Validator::make($request->all(), [
            'originPort'      => 'required|string',
            'destinationPort' => 'required|string',
            'shipNumber'      => 'required|string',
            'departDate'      => 'required|string',
            'fares'           => 'required|array',
            'shipID'          => 'required|string',
            'accessToken'     => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        $payload = [
            'originPort'      => $request->originPort,
            'destinationPort' => $request->destinationPort,
            'shipNumber'      => $request->shipNumber,
            'departDate'      => date('c', strtotime($request->departDate)), // ISO 8601
            'fares'           => $request->fares,
            'shipID'          => $request->shipID,
            'userID'          => $this->darmawisataUserId,
            'accessToken'     => $request->accessToken
        ];

        Log::info("Payload to Darmawisata [ShipDlu/SelectDLUSchedule]: ", $payload);
        return $this->forwardRequest('ShipDlu/SelectDLUSchedule', $payload);
    }

    public function shipDluPrice(Request $request)
    {
        Log::info("\n========== [SHIP DLU PRICE - START] ==========");

        $validator = Validator::make($request->all(), [
            'originPort'      => 'required|string',
            'destinationPort' => 'required|string',
            'shipNumber'      => 'required|string',
            'departDate'      => 'required|string',
            'fares'           => 'required|array',
            'shipID'          => 'required|string',
            'accessToken'     => 'required|string',
            'listPax'         => 'nullable|array',
            'listVehicle'     => 'nullable|array',
            'listRoom'        => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        $payload = [
            'originPort'      => $request->originPort,
            'destinationPort' => $request->destinationPort,
            'shipNumber'      => $request->shipNumber,
            'listPax'         => $request->listPax ?? [],
            'listVehicle'     => $request->listVehicle ?? [],
            'listRoom'        => $request->listRoom ?? [],
            'departDate'      => date('c', strtotime($request->departDate)), // ISO 8601
            'fares'           => $request->fares,
            'shipID'          => $request->shipID,
            'userID'          => $this->darmawisataUserId,
            'accessToken'     => $request->accessToken,
        ];

        Log::info("Payload to Darmawisata [ShipDlu/Price]: ", $payload);
        return $this->forwardRequest('ShipDlu/Price', $payload);
    }

    public function shipDluGetEticket(Request $request)
    {
        Log::info("\n========== [SHIP DLU GET ETICKET - START] ==========");

        $validator = Validator::make($request->all(), [
            'bookingNumber' => 'required|string',
            'accessToken'   => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        $payload = [
            'bookingNumber' => $request->bookingNumber,
            'userID'        => $this->darmawisataUserId,
            'accessToken'   => $request->accessToken,
        ];

        return $this->forwardRequest('ShipDlu/GetEticket', $payload);
    }

   public function shipDluIssued(Request $request)
    {
        Log::info("\n========== [SHIP DLU ISSUED - START] ==========");
        Log::info("Payload Request Mobile: ", $request->all());

        // 1. Validate incoming request
        $validator = Validator::make($request->all(), [
            'originPort'      => 'required|string',
            'destinationPort' => 'required|string',
            'shipNumber'      => 'required|string',
            'departDate'      => 'required|string|date', 
            'listPax'         => 'nullable|array',
            'listVehicle'     => 'nullable|array',
            'bookerData'      => 'required|array',
            'bookerData.name' => 'required|string',
            'bookerData.phone'=> 'required|string',
            'numCode'         => 'required|string',
            'listRoom'        => 'nullable|array',
            'shipID'          => 'required|string',
            'accessToken'     => 'required|string',
            'totalAmount'     => 'required|numeric',
            'fares'           => 'required|array',
            'isVehicle'       => 'required|boolean',
            'vehicleType'     => 'nullable|string',
        ]);

        if ($validator->fails()) {
            Log::warning("ShipDlu Issued Validasi Gagal: ", $validator->errors()->toArray());
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        $orderId = null;
        try {
            // STEP A: SIMPAN DRAFT LOKAL
            $user = $request->user();
            $totalAmountAgent = (float) $request->totalAmount;

            if (!$user || $user->saldo < $totalAmountAgent) {
                return response()->json([
                    'status' => 'FAILED', 
                    'message' => 'Saldo tidak cukup atau sesi tidak valid. Butuh: Rp ' . number_format($totalAmountAgent, 0, ',', '.')
                ], 400);
            }

            $orderId = DB::transaction(function () use ($request, $user) {
                return DB::table('ship_dlu_orders')->insertGetId([
                    'user_id'           => $user->id_pengguna ?? $user->id,
                    'dw_access_token'   => $request->accessToken,
                    'origin_port'       => $request->originPort,
                    'destination_port'  => $request->destinationPort,
                    'ship_number'       => $request->shipNumber,
                    'depart_date'       => date('Y-m-d H:i:s', strtotime($request->departDate)),
                    'booker_data'       => json_encode($request->bookerData),
                    'list_pax'          => json_encode($request->listPax ?? []),
                    'list_vehicle'      => json_encode($request->listVehicle ?? []),
                    'list_room'         => json_encode($request->listRoom ?? []),
                    'num_code'          => $request->numCode,
                    'ship_id'           => $request->shipID,
                    'status'            => 'PENDING_ISSUED', 
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
            });

            // =========================================================================
            // INJECT PRE-FLIGHT REQUEST DLU (SELECT SCHEDULE -> PRICE)
            // (Schedule dilewati karena sering error format jika diulang 1 hari)
            // =========================================================================
            
            // 1. Pre-flight SelectDLUSchedule
            $selectPayload = [
                'originPort'      => $request->originPort,
                'destinationPort' => $request->destinationPort,
                'shipNumber'      => $request->shipNumber,
                'departDate'      => date('c', strtotime($request->departDate)),
                'fares'           => $request->fares,
                'shipID'          => $request->shipID,
                'userID'          => $this->darmawisataUserId,
                'accessToken'     => $request->accessToken
            ];
            $selectRes = $this->forwardRequest('ShipDlu/SelectDLUSchedule', $selectPayload);
            $selectData = json_decode($selectRes->getContent(), true);

            // AUTO-CORRECTION: Darmawisata DLU (Memisahkan listRoom vs listPax)
            $finalListPax = $request->listPax ?? [];
            $finalListRoom = $request->listRoom ?? [];
            
            // Jika Darmawisata membalas butuh KAMAR (listRoom), kita pindahkan data penumpang ke dalam paxes kamar
            if (isset($selectData['listRoom']) && is_array($selectData['listRoom']) && count($selectData['listRoom']) > 0) {
                $finalListRoom = $selectData['listRoom'];
                $paxIndex = 0;
                foreach ($finalListRoom as &$room) {
                    if (isset($room['paxes']) && is_array($room['paxes'])) {
                        foreach ($room['paxes'] as &$paxSlot) {
                            if (isset($finalListPax[$paxIndex])) {
                                $paxSlot['name']   = $finalListPax[$paxIndex]['name'];
                                $paxSlot['id']     = $finalListPax[$paxIndex]['id'];
                                $paxSlot['gender'] = $finalListPax[$paxIndex]['gender'];
                                $paxSlot['dob']    = $finalListPax[$paxIndex]['dob'];
                                $paxSlot['city']   = $finalListPax[$paxIndex]['city'] ?? '-';
                                $paxSlot['note']   = $finalListPax[$paxIndex]['note'] ?? '-';
                                $paxIndex++;
                            } else {
                                // Jika kapasitas kamar sisa, isi dengan data kosong
                                $paxSlot['name']   = "Extra Pax";
                                $paxSlot['id']     = "0000000000000000";
                                $paxSlot['gender'] = "M";
                                $paxSlot['dob']    = "1990-01-01T00:00:00";
                                $paxSlot['city']   = "-";
                                $paxSlot['note']   = "-";
                            }
                        }
                    }
                }
                $finalListPax = []; // Wajib dikosongkan agar tidak "Unwanted Book"

            // Jika butuh listPax (Kelas Ekonomi biasa)
            } elseif (isset($selectData['listPax']) && is_array($selectData['listPax']) && count($selectData['listPax']) > 0) {
                $templatePax = $selectData['listPax'];
                foreach($templatePax as $idx => &$tpax) {
                    if (isset($finalListPax[$idx])) {
                        $tpax['name']   = $finalListPax[$idx]['name'];
                        $tpax['id']     = $finalListPax[$idx]['id'];
                        $tpax['gender'] = $finalListPax[$idx]['gender'];
                        $tpax['dob']    = $finalListPax[$idx]['dob'];
                    }
                }
                $finalListPax = $templatePax;
                $finalListRoom = [];
            }

            // 2. Pre-flight Price
            $pricePayload = [
                'originPort'      => $request->originPort,
                'destinationPort' => $request->destinationPort,
                'shipNumber'      => $request->shipNumber,
                'listPax'         => $finalListPax,
                'listVehicle'     => $request->listVehicle ?? [],
                'listRoom'        => $finalListRoom,
                'departDate'      => date('c', strtotime($request->departDate)),
                'fares'           => $request->fares,
                'shipID'          => $request->shipID,
                'userID'          => $this->darmawisataUserId,
                'accessToken'     => $request->accessToken
            ];
            $this->forwardRequest('ShipDlu/Price', $pricePayload);
            // =========================================================================

            // STEP B: RAKIT PAYLOAD FINAL DARMAWISATA (ISSUED)
            $dwPayload = [
                'originPort'      => $request->originPort,
                'destinationPort' => $request->destinationPort,
                'shipNumber'      => $request->shipNumber,
                'departDate'      => date('c', strtotime($request->departDate)), 
                'listPax'         => $finalListPax,
                'listVehicle'     => $request->listVehicle ?? [],
                'bookerData'      => $request->bookerData,
                'numCode'         => $request->numCode,
                'listRoom'        => $finalListRoom,
                'shipID'          => $request->shipID,
                'userID'          => $this->darmawisataUserId,
                'accessToken'     => $request->accessToken,
            ];

            Log::info("Payload to Darmawisata [ShipDlu/Issued]: ", $dwPayload);
            $response = $this->forwardRequest('ShipDlu/Issued', $dwPayload);
            $json = json_decode($response->getContent(), true);
            Log::info("Response Darmawisata [ShipDlu/Issued]: ", $json ?? ['error' => 'No JSON Response']);

            // STEP C: UPDATE DATABASE LOKAL
            $isSuccess = isset($json['status']) && $json['status'] === 'SUCCESS';
            $isProcessed = isset($json['respMessage']) && str_contains(strtolower($json['respMessage']), 'processed');

            if ($isSuccess || $isProcessed) {
                $totalPrice = (float) ($json['ticketPrice'] ?? $json['salesPrice'] ?? $totalAmountAgent);

                DB::transaction(function () use ($user, $totalPrice, $orderId, $json, $isProcessed) {
                    DB::table('Pengguna')->where('id_pengguna', $user->id_pengguna)->decrement('saldo', $totalPrice);
                    DB::table('Pengguna')->where('id_pengguna', 4)->decrement('balance_iak', $totalPrice);

                    $bookingDateTime = $json['bookingDateTime'] ?? $json['booking DateTime'] ?? null;
                    $issuedDateTimeLimit = $json['issuedDateTimeLimit'] ?? $json['issued DateTimeLimit'] ?? null;
                    $bookingNumber = $json['bookingNumber'] ?? $json['bokingNumber'] ?? null;
                    $finalStatus = $isProcessed ? 'PROCESSED' : 'ISSUED';

                    DB::table('ship_dlu_orders')->where('id', $orderId)->update([
                        'booking_number'      => $bookingNumber,
                        'sales_price'         => $json['salesPrice'] ?? $totalPrice,
                        'member_discount'     => $json['memberDiscount'] ?? 0,
                        'ship_markup'         => $json['shipMarkup'] ?? 0,
                        'ticket_price'        => $json['ticketPrice'] ?? $totalPrice,
                        'issued_time_limit'   => $issuedDateTimeLimit ? date('Y-m-d H:i:s', strtotime($issuedDateTimeLimit)) : null,
                        'booking_time'        => $bookingDateTime ? date('Y-m-d H:i:s', strtotime($bookingDateTime)) : null,
                        'status'              => $finalStatus,
                        'updated_at'          => now(),
                    ]);
                });

                $msg = $isProcessed ? 'Pembayaran berhasil! Tiket DLU sedang diproses oleh pusat.' : 'Tiket Kapal DLU Berhasil Dicetak (LUNAS)!';
                return response()->json([
                    'status'        => 'SUCCESS',
                    'bookingNumber' => $json['bookingNumber'] ?? $json['bokingNumber'] ?? 'PROSES',
                    'message'       => $msg,
                    'data'          => $json
                ]);

            } else {
                $message = $json['respMessage'] ?? 'Kapal DLU menolak penerbitan tiket.';

                if (str_contains(strtolower($message), "ticketed can't be issued") || str_contains(strtolower($message), 'already issued')) {
                    DB::table('ship_dlu_orders')->where('id', $orderId)->update(['status' => 'ISSUED', 'updated_at' => now()]);
                    return response()->json([
                        'status'  => 'SUCCESS',
                        'message' => 'Sinkronisasi berhasil! Tiket DLU ini sebelumnya sudah sukses diterbitkan.',
                        'data'    => $json
                    ]);
                }

                if ($orderId) {
                    DB::table('ship_dlu_orders')->where('id', $orderId)->update(['status' => 'FAILED', 'updated_at' => now()]);
                }

                return response()->json(['status' => 'FAILED', 'message' => 'Gagal Issued Kapal DLU: ' . $message]);
            }

        } catch (\Exception $e) {
            Log::error("FATAL ERROR [Ship DLU Issued]: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
            if ($orderId) {
                DB::table('ship_dlu_orders')->where('id', $orderId)->update(['status' => 'FAILED_SYSTEM_ERROR', 'updated_at' => now()]);
            }
            return response()->json(['status' => 'FAILED', 'message' => 'System Error: ' . $e->getMessage()], 500);
        }
    }

    public function shipDluClassTypes(Request $request)
    {
        $payload = ['userID' => $this->darmawisataUserId, 'accessToken' => $request->accessToken];
        return $this->forwardRequest('ShipDlu/ClassTypes', $payload);
    }

    public function shipDlueVehicleTypes(Request $request)
    {
        $payload = ['userID' => $this->darmawisataUserId, 'accessToken' => $request->accessToken];
        return $this->forwardRequest('ShipDlu/VehicleTypes', $payload);
    }

    public function shipDluTicketTypes(Request $request)
    {
        $payload = ['userID' => $this->darmawisataUserId, 'accessToken' => $request->accessToken];
        return $this->forwardRequest('ShipDlu/TicketTypes', $payload);
    }

    public function shipDluRoomClasses(Request $request)
    {
        $payload = ['userID' => $this->darmawisataUserId, 'accessToken' => $request->accessToken];
        return $this->forwardRequest('ShipDlu/RoomClasses', $payload);
    }

   public function shipDluHistory(Request $request)
    {
        Log::info("\n========== [SHIP DLU HISTORY - START] ==========");
        try {
            $user = $request->user();

            $orders = DB::table('ship_dlu_orders')
                ->where('user_id', $user->id_pengguna ?? $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            $formattedData = $orders->map(function ($order) {
                // Tentukan nama kapal fallback jika ship_name kosong
                $shipName = $order->ship_number;
                if ($shipName == '22917') $shipName = 'KM. Dharma Kencana 7';
                if ($shipName == '22955') $shipName = 'KM. Dharma Kartika 2';

                return [
                    'id'            => $order->id,
                    'bookingNumber' => $order->booking_number ?? $order->num_code ?? 'PROSES',
                    'numCode'       => $order->num_code ?? '',
                    'shipName'      => $shipName,
                    'departDate'    => $order->depart_date ?? null,
                    'status'        => $order->status,
                    'totalFare'     => (float) ($order->sales_price ?? $order->ticket_price ?? 0),
                    'paymentMethod' => 'SALDO',
                    'timeLimit'     => $order->issued_time_limit ?? null,
                    // Tarik data JSON dari database
                    'bookerData'    => json_decode($order->booker_data, true),
                    'listPax'       => json_decode($order->list_pax, true),
                    'listRoom'      => json_decode($order->list_room, true),
                    'listVehicle'   => json_decode($order->list_vehicle, true),
                    'origin'        => $order->origin_port,
                    'destination'   => $order->destination_port,
                ];
            });

            return response()->json(['status' => 'SUCCESS', 'data' => $formattedData], 200);

        } catch (\Exception $e) {
            Log::error("FATAL ERROR [Ship DLU History]: " . $e->getMessage());
            return response()->json(['status' => 'FAILED', 'message' => 'Sistem Error saat memuat riwayat.'], 500);
        }
    }

    public function destroyHistory(Request $request, $id)
    {
        try {
            $user = $request->user();
            $userId = $user->id_pengguna ?? $user->id;

            $deleted = DB::table('ship_dlu_orders')->where('id', $id)->where('user_id', $userId)->delete();
            if (!$deleted) return response()->json(['status' => 'FAILED', 'message' => 'Data tidak ditemukan.'], 404);

            return response()->json(['status' => 'SUCCESS', 'message' => 'Data tiket DLU berhasil dihapus.']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'FAILED', 'message' => 'Sistem Error.'], 500);
        }
    }

    public function bulkDestroyHistory(Request $request)
    {
        try {
            $user = $request->user();
            $userId = $user->id_pengguna ?? $user->id;
            
            if (empty($request->ids)) return response()->json(['status' => 'FAILED', 'message' => 'ID kosong.'], 422);

            $deleted = DB::table('ship_dlu_orders')->whereIn('id', $request->ids)->where('user_id', $userId)->delete();
            return response()->json(['status' => 'SUCCESS', 'message' => "{$deleted} data tiket DLU berhasil dihapus."]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'FAILED', 'message' => 'Sistem Error.'], 500);
        }
    }

    /**
     * =========================================================
     * MENERIMA WEBHOOK DARI DOKU (TIKET KAPAL DLU)
     * =========================================================
     */
    public function handleDokuCallback($data)
    {
        try {
            $invoiceNumber = $data['order']['invoice_number']; // Contoh: SHPDLU-12-ABC99
            
            // Ekstrak ID Order lokal (Angka di tengah)
            $parts = explode('-', $invoiceNumber);
            if (count($parts) < 2) {
                return response()->json(['status' => 'error', 'message' => 'Format invoice tidak valid']);
            }
            $transactionId = (int) $parts[1];

            // 1. Cari data pesanan di tabel Kapal DLU (Sesuaikan nama tabel jika berbeda)
            $order = DB::table('ship_dlu_orders')->where('id', $transactionId)->first();

            if (!$order) {
                Log::warning("DOKU Webhook Kapal DLU: Transaksi $transactionId tidak ditemukan.");
                return response()->json(['status' => 'error', 'message' => 'Transaction not found']);
            }

            // 2. Cegah double-update jika tiket sudah ISSUED atau dibayar
            if ($order->status !== 'HOLD') {
                Log::info("DOKU Webhook Kapal DLU: Transaksi $transactionId sudah diproses (Status: {$order->status}).");
                return response()->json(['status' => 'success', 'message' => 'Already processed']);
            }

            // 3. Update status menjadi PAID_PROCESSING (Lunas, siap di-Issued)
            DB::table('ship_dlu_orders')->where('id', $transactionId)->update([
                'status' => 'PAID_PROCESSING', 
                'updated_at' => now()
            ]);

            Log::info("DOKU Webhook Kapal DLU: Uang pesanan $transactionId sudah masuk Sancaka. Menunggu Issued.");
            
            // TODO: Jika ingin Auto-Issued, panggil fungsi API ShipDlu/Issued di sini.

            return response()->json(['status' => 'success', 'message' => 'Webhook Kapal DLU berhasil']);
            
        } catch (\Exception $e) {
            Log::error("Webhook Kapal DLU Error: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Sistem Error'], 500);
        }
    }

}