<?php 

namespace App\Http\Controllers\Api\Mobile;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PpobDarmawisataController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        // Tidak ada lagi query kredensial manual,
        // Semua sudah di-handle elegan oleh BaseController!
    }

    public function ppobProductGroup(Request $request)
    {
        Log::info("\n========== [PPOB PRODUCT GROUP - START] ==========");
        
        // Tambahan: Validator untuk memastikan frontend wajib kirim accessToken
        $validator = Validator::make($request->all(), [
            'accessToken'  => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::warning("PPOB Product Group Validasi Gagal: ", $validator->errors()->toArray());
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }
        
        // Cukup ambil accessToken dari request Mobile (seperti ShipDLU)
        // userID otomatis di-inject di BaseController::forwardRequest
        $payload = [
            'accessToken' => $request->accessToken
        ];
        
        return $this->forwardRequest('PPOB/ProductGroup', $payload); 
    }

    public function ppobProductList(Request $request)
    {
        Log::info("\n========== [PPOB PRODUCT LIST - START] ==========");
        Log::info("Payload Request Mobile: ", $request->all());
        
        $validator = Validator::make($request->all(), [
            'productGroup' => 'required|string',
            'accessToken'  => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::warning("PPOB Product List Validasi Gagal: ", $validator->errors()->toArray());
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        $payload = [
            'productGroup' => $request->productGroup,
            'accessToken'  => $request->accessToken   
        ];

        // Ambil data produk dasar dari API Darmawisata
        $response = $this->forwardRequest('PPOB/Product', $payload); 
        $json = json_decode($response->getContent(), true);

        // Map gambar/ikon operator dari database dw_ppob_products
        if (isset($json['productList']) && is_array($json['productList'])) { 
            $productCodes = array_column($json['productList'], 'code');
            $localProducts = DB::table('dw_ppob_products')
                ->whereIn('product_code', $productCodes)
                ->pluck('icon_url', 'product_code');

            foreach ($json['productList'] as &$product) {
                $code = $product['code'];
                $product['iconUrl'] = $localProducts[$code] ?? 'https://tokosancaka.com/assets/images/ppob/default.png'; 
            }
        }

        return response()->json($json);
    }

    public function ppobInquiry(Request $request)
    {
        Log::info("\n========== [PPOB INQUIRY - START] ==========");
        Log::info("Payload Request Mobile: ", $request->all());

        $validator = Validator::make($request->all(), [
            'productCode'    => 'required|string',
            'customerID'     => 'required|string',
            'customerMSISDN' => 'nullable|string',
            'accessToken'    => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::warning("PPOB Inquiry Validasi Gagal: ", $validator->errors()->toArray());
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        $payload = [
            'productCode'    => $request->productCode,
            'customerID'     => $request->customerID,
            'customerMSISDN' => $request->customerMSISDN ?? "",
            'accessToken'    => $request->accessToken   
        ];

        return $this->forwardRequest('PPOB/Inquiry', $payload); 
    }

    public function ppobPayment(Request $request)
    {
        Log::info("\n========== [PPOB PAYMENT - START] ==========");
        Log::info("Payload Request Mobile: ", $request->all());

        $validator = Validator::make($request->all(), [
            'billingReferenceID' => 'required|string',
            'productCode'        => 'required|string',
            'customerID'         => 'required|string',
            'sellPrice'          => 'required|numeric',
            'accessToken'        => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $totalPrice = (float) $request->sellPrice;
        $userId = $user->id_pengguna ?? $user->id;

        if (!$user || $user->saldo < $totalPrice) {
            return response()->json([
                'status' => 'FAILED', 
                'message' => 'Saldo tidak cukup. Butuh: Rp ' . number_format($totalPrice, 0, ',', '.')
            ], 400);
        }

        $orderId = null;

        try {
            $orderId = DB::transaction(function () use ($request, $userId, $totalPrice) {
                // Potong saldo di awal
                DB::table('Pengguna')->where('id_pengguna', $userId)->decrement('saldo', $totalPrice);
                
                return DB::table('dw_ppob_transactions')->insertGetId([
                    'user_id'              => $userId,
                    'product_code'         => $request->productCode,
                    'customer_id'          => $request->customerID,
                    'billing_reference_id' => $request->billingReferenceID,
                    'sell_price'           => $totalPrice,
                    'status'               => 'PENDING_PAYMENT',
                    'created_at'           => now(),
                    'updated_at'           => now(),
                ]);
            });

            $payload = [
                'billingReferenceID' => $request->billingReferenceID,
                'accessToken'        => $request->accessToken   
            ];

            $response = $this->forwardRequest('PPOB/Payment', $payload); 
            $json = json_decode($response->getContent(), true);

            if (isset($json['status']) && $json['status'] === 'SUCCESS') {
                DB::table('dw_ppob_transactions')->where('id', $orderId)->update([
                    'status'       => 'SUCCESS',
                    'resp_message' => $json['respMessage'] ?? 'Transaksi Berhasil', 
                    'updated_at'   => now(),
                ]);

                return response()->json([
                    'status'  => 'SUCCESS',
                    'message' => 'Pembayaran PPOB Berhasil!',
                    'data'    => $json
                ]);
            } else {
                // Refund Saldo
                DB::table('Pengguna')->where('id_pengguna', $userId)->increment('saldo', $totalPrice);
                
                DB::table('dw_ppob_transactions')->where('id', $orderId)->update([
                    'status'       => 'FAILED',
                    'resp_message' => $json['respMessage'] ?? 'Gagal dari provider',
                    'updated_at'   => now(),
                ]);

                return response()->json(['status' => 'FAILED', 'message' => $json['respMessage'] ?? 'Transaksi Gagal']);
            }

        } catch (\Exception $e) {
            Log::error("FATAL ERROR [PPOB Payment]: " . $e->getMessage());
            if ($orderId) {
                DB::table('dw_ppob_transactions')->where('id', $orderId)->update(['status' => 'FAILED_SYSTEM_ERROR', 'updated_at' => now()]);
            }
            return response()->json(['status' => 'FAILED', 'message' => 'System Error: ' . $e->getMessage()], 500);
        }
    }

    public function ppobTransactionDetail(Request $request)
    {
        Log::info("\n========== [PPOB TRANSACTION DETAIL - START] ==========");
        $validator = Validator::make($request->all(), [
            'customerID'         => 'required|string',
            'billingReferenceID' => 'required|string',
            'accessToken'        => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }
        
        $payload = [
            'customerID'         => $request->customerID,          
            'billingReferenceID' => $request->billingReferenceID,  
            'accessToken'        => $request->accessToken          
        ];

        return $this->forwardRequest('PPOB/TransactionDetail', $payload); 
    }

    public function ppobHistory(Request $request)
    {
        Log::info("\n========== [PPOB HISTORY - START] ==========");
        try {
            $user = $request->user();
            $userId = $user->id_pengguna ?? $user->id;

            $query = DB::table('dw_ppob_transactions as t')
                ->leftJoin('dw_ppob_products as p', 't.product_code', '=', 'p.product_code')
                ->select(
                    't.id', 't.product_code', 'p.product_name', 'p.icon_url',
                    't.customer_id', 't.billing_reference_id', 't.sell_price', 
                    't.status', 't.created_at', 't.resp_message', 't.user_id'
                )
                ->orderBy('t.created_at', 'desc');

            if ($userId != 4) {
                $query->where('t.user_id', $userId);
            }

            $orders = $query->get();

            $formattedData = $orders->map(function ($order) {
                return [
                    'id'                 => $order->id,
                    'userId'             => $order->user_id, 
                    'productCode'        => $order->product_code,
                    'productName'        => $order->product_name ?? 'Produk PPOB',
                    'iconUrl'            => $order->icon_url ?? 'https://tokosancaka.com/assets/images/ppob/default.png',
                    'customerID'         => $order->customer_id,
                    'billingReferenceID' => $order->billing_reference_id,
                    'sellPrice'          => (float) $order->sell_price,
                    'status'             => $order->status,
                    'message'            => $order->resp_message,
                    'transactionDate'    => $order->created_at,
                ];
            });

            return response()->json(['status' => 'SUCCESS', 'data' => $formattedData], 200);

        } catch (\Exception $e) {
            Log::error("FATAL ERROR [PPOB History]: " . $e->getMessage());
            return response()->json(['status' => 'FAILED', 'message' => 'Sistem Error.'], 500);
        }
    }
}