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
        Log::info("Memuat kredensial dinamis PPOB Darmawisata dari database...");
        $cred = DB::table('dw_api_credentials')
            ->where('provider', 'darmawisata')
            ->where('is_active', 1)
            ->first();

        if ($cred) {
            $this->apiUserId = $cred->user_id;
            $this->apiAccessToken = $cred->access_token;
            Log::info("Kredensial PPOB Darmawisata berhasil dimuat untuk User ID API: " . $this->apiUserId);
        } else {
            Log::error("FATAL: Kredensial API Darmawisata tidak ditemukan di database atau status tidak aktif.");
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
        
        $response = $this->forwardRequest('PPOB/Inquiry', $payload);
        Log::info("Response dari [PPOB/Inquiry] siap diteruskan ke client.");
        
        return $response; 
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
            Log::warning("PPOB Payment Validasi Gagal: ", $validator->errors()->toArray());
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $totalPrice = (float) $request->sellPrice;
        $userId = $user->id_pengguna ?? $user->id;

        Log::info("Memulai pengecekan saldo untuk User ID: {$userId}. Saldo saat ini: {$user->saldo}, Total Harga: {$totalPrice}");

        if (!$user || $user->saldo < $totalPrice) {
            Log::warning("Saldo tidak mencukupi untuk User ID: {$userId}. Transaksi ditolak.");
            return response()->json([
                'status' => 'FAILED', 
                'message' => 'Saldo tidak cukup. Butuh: Rp ' . number_format($totalPrice, 0, ',', '.')
            ], 400);
        }

        $orderId = null;

        try {
            Log::info("Memulai Database Transaction untuk pemotongan saldo dan insert order PPOB...");
            $orderId = DB::transaction(function () use ($request, $userId, $totalPrice) {
                // Potong saldo di awal
                DB::table('Pengguna')->where('id_pengguna', $userId)->decrement('saldo', $totalPrice);
                Log::info("Saldo User ID: {$userId} berhasil dipotong sebesar: {$totalPrice}");
                
                // Menyimpan ke tabel terpisah khusus Darmawisata
                $insertedId = DB::table('dw_ppob_transactions')->insertGetId([
                    'user_id'              => $userId,
                    'product_code'         => $request->productCode,
                    'customer_id'          => $request->customerID,
                    'billing_reference_id' => $request->billingReferenceID,
                    'sell_price'           => $totalPrice,
                    'status'               => 'PENDING_PAYMENT',
                    'created_at'           => now(),
                    'updated_at'           => now(),
                ]);
                Log::info("Data PPOB Transaction berhasil disimpan dengan ID: {$insertedId}");
                
                return $insertedId;
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

            Log::info("Response Asli dari Darmawisata [PPOB/Payment]: ", $json ?? ['error' => 'No JSON Response']);

            $isSuccess = isset($json['status']) && $json['status'] === 'SUCCESS';

            if ($isSuccess) {
                Log::info("Transaksi PPOB Darmawisata BERHASIL. Mengupdate status Order ID: {$orderId} ke SUCCESS.");
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
                Log::warning("Transaksi PPOB Darmawisata GAGAL dari provider. Memproses refund saldo User ID: {$userId} sebesar: {$totalPrice}");
                // Kembalikan saldo jika gagal
                DB::table('Pengguna')->where('id_pengguna', $userId)->increment('saldo', $totalPrice);
                Log::info("Refund saldo User ID: {$userId} BERHASIL.");
                
                DB::table('dw_ppob_transactions')->where('id', $orderId)->update([
                    'status'       => 'FAILED',
                    'resp_message' => $json['respMessage'] ?? 'Gagal dari provider',
                    'updated_at'   => now(),
                ]);
                Log::info("Status Order ID: {$orderId} diupdate ke FAILED.");

                return response()->json(['status' => 'FAILED', 'message' => $json['respMessage'] ?? 'Transaksi Gagal']);
            }

        } catch (\Exception $e) {
            Log::error("FATAL ERROR [PPOB Payment]: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
            
            if ($orderId) {
                DB::table('dw_ppob_transactions')->where('id', $orderId)->update([
                    'status' => 'FAILED_SYSTEM_ERROR', 
                    'updated_at' => now()
                ]);
                Log::error("Order ID: {$orderId} di-flag sebagai FAILED_SYSTEM_ERROR akibat exception.");
            }
            
            return response()->json(['status' => 'FAILED', 'message' => 'System Error: ' . $e->getMessage()], 500);
        }
    }

    public function ppobProductGroup(Request $request)
    {
        Log::info("\n========== [PPOB PRODUCT GROUP - START] ==========");
        $payload = [
            'userID'      => $this->apiUserId,       
            'accessToken' => $this->apiAccessToken   
        ];
        
        Log::info("Payload to Darmawisata [PPOB/ProductGroup]: ", $payload);
        return $this->forwardRequest('PPOB/ProductGroup', $payload); 
    }

    public function ppobProductList(Request $request)
    {
        Log::info("\n========== [PPOB PRODUCT LIST - START] ==========");
        Log::info("Payload Request Mobile: ", $request->all());
        
        $validator = Validator::make($request->all(), [
            'productGroup' => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::warning("PPOB Product List Validasi Gagal: ", $validator->errors()->toArray());
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        $payload = [
            'productGroup' => $request->productGroup,
            'userID'       => $this->apiUserId,       
            'accessToken'  => $this->apiAccessToken   
        ];

        Log::info("Payload to Darmawisata [PPOB/Product]: ", $payload);

        // Ambil data produk dasar dari API Darmawisata
        $response = $this->forwardRequest('PPOB/Product', $payload); 
        $json = json_decode($response->getContent(), true);

        // Map gambar/ikon operator dari database dw_ppob_products
        if (isset($json['productList']) && is_array($json['productList'])) { 
            Log::info("Berhasil mengambil " . count($json['productList']) . " produk dari Darmawisata. Memulai proses mapping logo lokal...");
            
            $productCodes = array_column($json['productList'], 'code');
            
            $localProducts = DB::table('dw_ppob_products')
                ->whereIn('product_code', $productCodes)
                ->pluck('icon_url', 'product_code');

            foreach ($json['productList'] as &$product) {
                $code = $product['code'];
                $product['iconUrl'] = $localProducts[$code] ?? 'https://sancaka.com/assets/images/ppob/default.png'; // Ganti dengan path aset default Anda
            }
            Log::info("Mapping logo lokal selesai.");
        } else {
            Log::warning("Response productList dari Darmawisata kosong atau format tidak sesuai.", $json ?? []);
        }

        return response()->json($json);
    }

    public function ppobHistory(Request $request)
    {
        Log::info("\n========== [PPOB HISTORY - START] ==========");
        try {
            $user = $request->user();
            $userId = $user->id_pengguna ?? $user->id;

            Log::info("Memuat riwayat transaksi PPOB untuk User ID: {$userId}");

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
            } else {
                Log::info("User ID: 4 (Admin) terdeteksi. Memuat seluruh riwayat transaksi (Bypass Filter).");
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

            Log::info("Berhasil memformat dan mengirim " . count($formattedData) . " baris riwayat PPOB ke client.");
            return response()->json(['status' => 'SUCCESS', 'data' => $formattedData], 200);

        } catch (\Exception $e) {
            Log::error("FATAL ERROR [PPOB History]: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
            return response()->json(['status' => 'FAILED', 'message' => 'Sistem Error saat memuat riwayat.'], 500);
        }
    }
    
    public function ppobTransactionDetail(Request $request)
    {
        Log::info("\n========== [PPOB TRANSACTION DETAIL - START] ==========");
        Log::info("Payload Request Mobile: ", $request->all());

        $validator = Validator::make($request->all(), [
            'customerID'         => 'required|string',
            'billingReferenceID' => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::warning("PPOB Transaction Detail Validasi Gagal: ", $validator->errors()->toArray());
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }
        
        $payload = [
            'customerID'         => $request->customerID,          
            'billingReferenceID' => $request->billingReferenceID,  
            'userID'             => $this->apiUserId,              
            'accessToken'        => $this->apiAccessToken          
        ];

        Log::info("Payload to Darmawisata [PPOB/TransactionDetail]: ", $payload);
        return $this->forwardRequest('PPOB/TransactionDetail', $payload); 
    }
}