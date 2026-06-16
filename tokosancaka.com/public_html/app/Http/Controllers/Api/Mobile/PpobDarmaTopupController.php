<?php 

namespace App\Http\Controllers\Api\Mobile;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PpobDarmaTopupController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get Topup Product Type List
     */
    public function productTypeList(Request $request)
    {
        Log::info("\n========== [TOPUP PRODUCT TYPE - START] ==========");
        
        $validator = Validator::make($request->all(), [
            'accessToken' => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::warning("TopUp Product Type Validasi Gagal: ", $validator->errors()->toArray());
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        $payload = [
            'accessToken' => $request->accessToken 
        ];
        
        Log::info("Payload TopUp Product Type: ", $payload);
        return $this->forwardRequest('TopUp/ProductType', $payload); 
    }

    /**
     * Get Topup Provider List
     */
    public function providerList(Request $request)
    {
        Log::info("\n========== [TOPUP PROVIDER - START] ==========");
        
        $validator = Validator::make($request->all(), [
            'productType' => 'required|string',
            'accessToken' => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::warning("TopUp Provider Validasi Gagal: ", $validator->errors()->toArray());
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        $payload = [
            'productType' => $request->productType,
            'accessToken' => $request->accessToken   
        ];

        Log::info("Payload TopUp Provider: ", $payload);
        return $this->forwardRequest('TopUp/Provider', $payload); 
    }

    /**
     * Get Topup Product List
     */
    public function productList(Request $request)
    {
        Log::info("\n========== [TOPUP PRODUCT LIST - START] ==========");
        
        $validator = Validator::make($request->all(), [
            'provider'    => 'required|string',
            'accessToken' => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::warning("TopUp Product List Validasi Gagal: ", $validator->errors()->toArray());
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        $payload = [
            'provider'    => $request->provider,
            'accessToken' => $request->accessToken   
        ];

        Log::info("Payload TopUp Product List: ", $payload);
        return $this->forwardRequest('TopUp/Product', $payload); 
    }

    /**
     * Order Topup (Payment)
     */
    public function topupOrder(Request $request)
    {
        Log::info("\n========== [TOPUP ORDER - START] ==========");

        $validator = Validator::make($request->all(), [
            'MSISDN'      => 'required|string',
            'productCode' => 'required|string',
            'sequence'    => 'required|integer',
            'customerID'  => 'nullable|string',
            'sellPrice'   => 'required|numeric', 
            'accessToken' => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::warning("TopUp Order Validasi Gagal: ", $validator->errors()->toArray());
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $totalPrice = (float) $request->sellPrice;
        $userId = $user->id_pengguna ?? $user->id;

        Log::info("User ID Requesting TopUp: " . $userId . " | Nominal: Rp " . $totalPrice);

        // Cek Saldo
        if (!$user || $user->saldo < $totalPrice) {
            Log::warning("Saldo Sancaka tidak cukup untuk User ID: " . $userId);
            return response()->json([
                'status' => 'FAILED', 
                'message' => 'Saldo Sancaka tidak cukup. Butuh: Rp ' . number_format($totalPrice, 0, ',', '.')
            ], 400);
        }

        $orderId = null;

        try {
            // 1. DEDUCT SALDO & BUAT DRAFT TRANSAKSI LOKAL
            $orderId = DB::transaction(function () use ($request, $userId, $totalPrice) {
                DB::table('Pengguna')->where('id_pengguna', $userId)->decrement('saldo', $totalPrice);
                
                return DB::table('dw_ppob_dharma')->insertGetId([
                    'user_id'      => $userId,
                    'product_code' => $request->productCode,
                    'msisdn'       => $request->MSISDN,
                    'customer_id'  => $request->customerID,
                    'sequence'     => $request->sequence,
                    'sell_price'   => $totalPrice,
                    'status'       => 'PENDING_PAYMENT',
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            });

            Log::info("Draft Transaksi Sancaka Berhasil Dibuat. Order ID: " . $orderId);

            // 2. TEMBAK API DARMAWISATA
            $payload = [
                'MSISDN'      => $request->MSISDN,
                'productCode' => $request->productCode,
                'sequence'    => $request->sequence,
                'customerID'  => $request->customerID ?? "",
                'accessToken' => $request->accessToken   
            ];

            Log::info("Meneruskan Request Order ke Darmawisata: ", $payload);
            $response = $this->forwardRequest('TopUp/Order', $payload); 
            $json = json_decode($response->getContent(), true);

            Log::info("Response Order Darmawisata: ", $json ?? []);

            // 3. HANDLE RESPONSE
            if (isset($json['status']) && strtoupper($json['status']) === 'SUCCESS') {
                DB::table('dw_ppob_dharma')->where('id', $orderId)->update([
                    'status'         => 'SUCCESS',
                    'transaction_id' => $json['transactionID'] ?? null,
                    'reference_id'   => $json['referenceID'] ?? null,
                    'resp_message'   => $json['respMessage'] ?? 'TopUp Berhasil',
                    'updated_at'     => now(),
                ]);

                Log::info("TopUp SUKSES. Selesai memproses Order ID: " . $orderId);
                return response()->json([
                    'status'  => 'SUCCESS',
                    'message' => 'TopUp Berhasil diproses!',
                    'data'    => $json
                ]);
            } else {
                // REFUND SALDO JIKA DITOLAK DARMAWISATA
                Log::warning("TopUp DITOLAK Darmawisata. Mengembalikan saldo User ID: " . $userId);
                DB::table('Pengguna')->where('id_pengguna', $userId)->increment('saldo', $totalPrice);
                
                DB::table('dw_ppob_dharma')->where('id', $orderId)->update([
                    'status'       => 'FAILED',
                    'resp_message' => $json['respMessage'] ?? 'Gagal dari provider',
                    'updated_at'   => now(),
                ]);

                return response()->json(['status' => 'FAILED', 'message' => $json['respMessage'] ?? 'Transaksi TopUp Gagal']);
            }

        } catch (\Exception $e) {
            Log::error("FATAL ERROR [TOPUP Order]: " . $e->getMessage());
            
            // CRITICAL: REFUND SALDO JIKA TERJADI SYSTEM ERROR/TIMEOUT
            if ($orderId) {
                Log::info("Melakukan Auto-Refund karena System Error pada Order ID: " . $orderId);
                DB::table('Pengguna')->where('id_pengguna', $userId)->increment('saldo', $totalPrice);
                DB::table('dw_ppob_dharma')->where('id', $orderId)->update([
                    'status' => 'FAILED_SYSTEM_ERROR', 
                    'resp_message' => 'System Error: Saldo telah dikembalikan otomatis.',
                    'updated_at' => now()
                ]);
            }
            return response()->json(['status' => 'FAILED', 'message' => 'System Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * =========================================================
     * HISTORY LOKAL SANCAKA (Untuk ditampilkan di App Mobile)
     * =========================================================
     */
    public function topupHistory(Request $request)
    {
        Log::info("\n========== [TOPUP HISTORY (LOCAL) - START] ==========");
        
        try {
            $user = $request->user();
            $userId = $user->id_pengguna ?? $user->id;

            $query = DB::table('dw_ppob_dharma as t')
                ->select(
                    't.id', 't.product_code', 't.msisdn', 't.customer_id', 
                    't.reference_id', 't.transaction_id', 't.sell_price', 
                    't.status', 't.created_at', 't.resp_message', 't.user_id'
                )
                ->orderBy('t.created_at', 'desc');

            // Cek role admin dengan aman (menghindari hardcode ID)
            $role = strtolower($user->role ?? '');
            if ($role !== 'admin') {
                $query->where('t.user_id', $userId);
            }

            $orders = $query->get();

            $formattedData = $orders->map(function ($order) {
                return [
                    'id'                 => $order->id,
                    'userId'             => $order->user_id, 
                    'productCode'        => $order->product_code,
                    'msisdn'             => $order->msisdn,
                    'customerID'         => $order->customer_id,
                    'transactionID'      => $order->transaction_id,
                    'referenceID'        => $order->reference_id,
                    'sellPrice'          => (float) $order->sell_price,
                    'status'             => $order->status,
                    'message'            => $order->resp_message,
                    'transactionDate'    => $order->created_at,
                ];
            });

            Log::info("Berhasil memuat " . count($formattedData) . " data riwayat lokal.");
            return response()->json(['status' => 'SUCCESS', 'data' => $formattedData], 200);

        } catch (\Exception $e) {
            Log::error("FATAL ERROR [TOPUP History Local]: " . $e->getMessage());
            return response()->json(['status' => 'FAILED', 'message' => 'Sistem Error.'], 500);
        }
    }

    /**
     * =========================================================
     * GET TOPUP HISTORY TRANSACTION LIST (Dari Darmawisata API)
     * =========================================================
     */
    public function transactionList(Request $request)
    {
        Log::info("\n========== [TOPUP TRANSACTION LIST (API) - START] ==========");
        
        $validator = Validator::make($request->all(), [
            'startDate'   => 'required|date', 
            'endDate'     => 'required|date', 
            'accessToken' => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::warning("TopUp Transaction List API Validasi Gagal: ", $validator->errors()->toArray());
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        // Format tanggal sesuai standar ISO 8601 yang diminta Darmawisata
        $payload = [
            'startDate'   => date('Y-m-d\TH:i:sP', strtotime($request->startDate)),
            'endDate'     => date('Y-m-d\TH:i:sP', strtotime($request->endDate)),
            'accessToken' => $request->accessToken
        ];

        Log::info("Payload Transaction List API: ", $payload);
        return $this->forwardRequest('TopUp/TransactionList', $payload);
    }

    /**
     * =========================================================
     * GET TOPUP HISTORY TRANSACTION DETAIL (Dari Darmawisata API)
     * =========================================================
     */
    public function transactionDetail(Request $request)
    {
        Log::info("\n========== [TOPUP TRANSACTION DETAIL (API) - START] ==========");
        
        $validator = Validator::make($request->all(), [
            'referenceID'      => 'required|string', 
            'agentReferenceID' => 'nullable|string',
            'accessToken'      => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::warning("TopUp Transaction Detail API Validasi Gagal: ", $validator->errors()->toArray());
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        $payload = [
            'referenceID'      => $request->referenceID,
            'agentReferenceID' => $request->agentReferenceID ?? "",
            'accessToken'      => $request->accessToken
        ];

        Log::info("Payload Transaction Detail API: ", $payload);
        return $this->forwardRequest('TopUp/TransactionDetail', $payload);
    }
}