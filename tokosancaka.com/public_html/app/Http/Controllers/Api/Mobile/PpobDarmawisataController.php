<?php 

namespace App\Http\Controllers\Api\Mobile;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PpobDarmawisataController extends BaseController
{
    private $apiUserId;
    private $apiAccessToken;

    public function __construct()
    {
        parent::__construct();
        $this->loadDynamicCredentials();
    }

    /**
     * Mengambil kredensial API secara dinamis dari database dw_api_credentials
     */
    private function loadDynamicCredentials()
    {
        $cred = DB::table('dw_api_credentials')
            ->where('provider', 'darmawisata')
            ->where('is_active', 1)
            ->first();

        if ($cred) {
            $this->apiUserId = $cred->user_id;
            $this->apiAccessToken = $cred->access_token;
        } else {
            Log::error("FATAL: Kredensial API Darmawisata tidak ditemukan di database.");
        }
    }

    public function ppobInquiry(Request $request)
    {
        Log::info("\n========== [PPOB INQUIRY - START] ==========");
        Log::info("Payload Request Mobile: ", $request->all());

        $validator = Validator::make($request->all(), [
            'productCode'    => 'required|string',
            'customerID'     => 'required|string',
            'customerMSISDN' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            Log::warning("PPOB Inquiry Validasi Gagal: ", $validator->errors()->toArray());
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        // Format Request sesuai dokumentasi Darmawisata
        $payload = [
            'productCode'    => $request->productCode,
            'customerID'     => $request->customerID,
            'customerMSISDN' => $request->customerMSISDN ?? "",
            'userID'         => $this->apiUserId,       
            'accessToken'    => $this->apiAccessToken   
        ];

        Log::info("Payload to Darmawisata [PPOB/Inquiry]: ", $payload);
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
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $totalPrice = (float) $request->sellPrice;

        if (!$user || $user->saldo < $totalPrice) {
            return response()->json([
                'status' => 'FAILED', 
                'message' => 'Saldo tidak cukup. Butuh: Rp ' . number_format($totalPrice, 0, ',', '.')
            ], 400);
        }

        $orderId = null;

        try {
            $orderId = DB::transaction(function () use ($request, $user, $totalPrice) {
                // Potong saldo di awal
                DB::table('Pengguna')->where('id_pengguna', $user->id_pengguna ?? $user->id)->decrement('saldo', $totalPrice);
                
                // Menyimpan ke tabel terpisah khusus Darmawisata
                return DB::table('dw_ppob_transactions')->insertGetId([
                    'user_id'              => $user->id_pengguna ?? $user->id,
                    'product_code'         => $request->productCode,
                    'customer_id'          => $request->customerID,
                    'billing_reference_id' => $request->billingReferenceID,
                    'sell_price'           => $totalPrice,
                    'status'               => 'PENDING_PAYMENT',
                    'created_at'           => now(),
                    'updated_at'           => now(),
                ]);
            });

            // Format Request PPOB/Payment Darmawisata
            $payload = [
                'billingReferenceID' => $request->billingReferenceID,
                'userID'             => $this->apiUserId,       
                'accessToken'        => $this->apiAccessToken   
            ];

            Log::info("Payload to Darmawisata [PPOB/Payment]: ", $payload);
            $response = $this->forwardRequest('PPOB/Payment', $payload); 
            $json = json_decode($response->getContent(), true);

            $isSuccess = isset($json['status']) && $json['status'] === 'SUCCESS';

            if ($isSuccess) {
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
                // Kembalikan saldo jika gagal
                DB::table('Pengguna')->where('id_pengguna', $user->id_pengguna ?? $user->id)->increment('saldo', $totalPrice);
                
                DB::table('dw_ppob_transactions')->where('id', $orderId)->update([
                    'status'       => 'FAILED',
                    'resp_message' => $json['respMessage'] ?? 'Gagal dari provider',
                    'updated_at'   => now(),
                ]);

                return response()->json(['status' => 'FAILED', 'message' => $json['respMessage'] ?? 'Transaksi Gagal']);
            }

        } catch (\Exception $e) {
            Log::error("FATAL ERROR [PPOB Payment]: " . $e->getMessage());
            return response()->json(['status' => 'FAILED', 'message' => 'System Error: ' . $e->getMessage()], 500);
        }
    }

    public function ppobProductGroup(Request $request)
    {
        $payload = [
            'userID'      => $this->apiUserId,       
            'accessToken' => $this->apiAccessToken   
        ];
        return $this->forwardRequest('PPOB/ProductGroup', $payload); 
    }

    public function ppobProductList(Request $request)
    {
        Log::info("\n========== [PPOB PRODUCT LIST - START] ==========");
        
        $validator = Validator::make($request->all(), [
            'productGroup' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        $payload = [
            'productGroup' => $request->productGroup,
            'userID'       => $this->apiUserId,       
            'accessToken'  => $this->apiAccessToken   
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
                $product['iconUrl'] = $localProducts[$code] ?? 'https://sancaka.com/assets/images/ppob/default.png'; // Ganti dengan path aset default Anda
            }
        }

        return response()->json($json);
    }

    public function ppobHistory(Request $request)
    {
        Log::info("\n========== [PPOB HISTORY - START] ==========");
        try {
            $user = $request->user();
            $userId = $user->id_pengguna ?? $user->id;

            // Mengambil dari tabel yang sudah dibedakan
            $query = DB::table('dw_ppob_transactions as t')
                ->leftJoin('dw_ppob_products as p', 't.product_code', '=', 'p.product_code')
                ->select(
                    't.id', 't.product_code', 'p.product_name', 'p.icon_url',
                    't.customer_id', 't.billing_reference_id', 't.sell_price', 
                    't.status', 't.created_at', 't.resp_message', 't.user_id'
                )
                ->orderBy('t.created_at', 'desc');

            // Filter Role: User ID 4 (Admin Utama) bisa lihat semua histori
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
                    'iconUrl'            => $order->icon_url ?? 'https://sancaka.com/assets/images/ppob/default.png',
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
            return response()->json(['status' => 'FAILED', 'message' => 'Sistem Error saat memuat riwayat.'], 500);
        }
    }
    
    public function ppobTransactionDetail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customerID'         => 'required|string',
            'billingReferenceID' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }
        
        $payload = [
            'customerID'         => $request->customerID,          
            'billingReferenceID' => $request->billingReferenceID,  
            'userID'             => $this->apiUserId,              
            'accessToken'        => $this->apiAccessToken          
        ];

        return $this->forwardRequest('PPOB/TransactionDetail', $payload); 
    }
}