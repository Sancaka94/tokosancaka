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
     * Get Topup Product Type List [cite: 83, 84]
     */
    public function productTypeList(Request $request)
    {
        Log::info("\n========== [TOPUP PRODUCT TYPE - START] ==========");
        
        $validator = Validator::make($request->all(), [
            'accessToken' => 'required|string', // Access Token Session [cite: 102]
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        // userID Darmawisata biasanya di-inject dinamis di BaseController Anda, 
        // namun kita teruskan accessToken sesi ini.
        $payload = [
            'accessToken' => $request->accessToken 
        ];
        
        return $this->forwardRequest('TopUp/ProductType', $payload); 
    }

    /**
     * Get Topup Provider List [cite: 141, 142]
     */
    public function providerList(Request $request)
    {
        Log::info("\n========== [TOPUP PROVIDER - START] ==========");
        
        $validator = Validator::make($request->all(), [
            'productType' => 'required|string', [cite: 148]
            'accessToken' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        $payload = [
            'productType' => $request->productType,
            'accessToken' => $request->accessToken   
        ];

        return $this->forwardRequest('TopUp/Provider', $payload); 
    }

    /**
     * Get Topup Product List [cite: 254, 255]
     */
    public function productList(Request $request)
    {
        Log::info("\n========== [TOPUP PRODUCT LIST - START] ==========");
        
        $validator = Validator::make($request->all(), [
            'provider'    => 'required|string', [cite: 262]
            'accessToken' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        $payload = [
            'provider'    => $request->provider,
            'accessToken' => $request->accessToken   
        ];

        return $this->forwardRequest('TopUp/Product', $payload); 
    }

    /**
     * Order Topup (Payment) [cite: 1, 2]
     */
    public function topupOrder(Request $request)
    {
        Log::info("\n========== [TOPUP ORDER - START] ==========");

        $validator = Validator::make($request->all(), [
            'MSISDN'      => 'required|string', [cite: 9]
            'productCode' => 'required|string', [cite: 12]
            'sequence'    => 'required|integer', [cite: 14, 26]
            'customerID'  => 'nullable|string', [cite: 15, 27]
            'sellPrice'   => 'required|numeric', // Harga dari Frontend Sancaka
            'accessToken' => 'required|string', [cite: 17, 29]
        ]);

        if ($validator->fails()) {
            Log::warning("TopUp Order Validasi Gagal: ", $validator->errors()->toArray());
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $totalPrice = (float) $request->sellPrice;
        $userId = $user->id_pengguna ?? $user->id;

        // Cek Saldo
        if (!$user || $user->saldo < $totalPrice) {
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

            // 2. TEMBAK API DARMAWISATA [cite: 1, 21-32]
            $payload = [
                'MSISDN'      => $request->MSISDN,
                'productCode' => $request->productCode,
                'sequence'    => $request->sequence,
                'customerID'  => $request->customerID ?? "",
                'accessToken' => $request->accessToken   
            ];

            $response = $this->forwardRequest('TopUp/Order', $payload); 
            $json = json_decode($response->getContent(), true);

            // 3. HANDLE RESPONSE
            if (isset($json['status']) && strtoupper($json['status']) === 'SUCCESS') { [cite: 49, 65]
                DB::table('dw_ppob_dharma')->where('id', $orderId)->update([
                    'status'         => 'SUCCESS',
                    'transaction_id' => $json['transactionID'] ?? null, [cite: 48, 56]
                    'reference_id'   => $json['referenceID'] ?? null, [cite: 49, 58]
                    'resp_message'   => $json['respMessage'] ?? 'TopUp Berhasil', [cite: 49, 66]
                    'updated_at'     => now(),
                ]);

                return response()->json([
                    'status'  => 'SUCCESS',
                    'message' => 'TopUp Berhasil diproses!',
                    'data'    => $json
                ]);
            } else {
                // REFUND SALDO JIKA DITOLAK DARMAWISATA
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

            return response()->json(['status' => 'SUCCESS', 'data' => $formattedData], 200);

        } catch (\Exception $e) {
            Log::error("FATAL ERROR [TOPUP History]: " . $e->getMessage());
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
            'startDate'   => 'required|date', // Format: YYYY-MM-DD
            'endDate'     => 'required|date', // Format: YYYY-MM-DD
            'accessToken' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        // Format tanggal sesuai standar ISO 8601 yang diminta Darmawisata (Contoh: "2019-08-24T14:15:22Z")
        $payload = [
            'startDate'   => date('Y-m-d\TH:i:sP', strtotime($request->startDate)),
            'endDate'     => date('Y-m-d\TH:i:sP', strtotime($request->endDate)),
            'accessToken' => $request->accessToken
        ];

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
            'referenceID'      => 'required|string', // Billing reference ID
            'agentReferenceID' => 'nullable|string',
            'accessToken'      => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        $payload = [
            'referenceID'      => $request->referenceID,
            'agentReferenceID' => $request->agentReferenceID ?? "",
            'accessToken'      => $request->accessToken
        ];

        return $this->forwardRequest('TopUp/TransactionDetail', $payload);
    }
}