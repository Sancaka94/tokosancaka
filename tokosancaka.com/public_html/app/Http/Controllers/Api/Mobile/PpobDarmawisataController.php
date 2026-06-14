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
    }

    /**
     * FUNGSI SMART MAPPING LOGO PPOB
     * Membaca nama/group lalu mencocokkan dengan file gambar di server Anda
     */
    private function getPpobLogo($groupName, $productName)
    {
        $group = strtoupper($groupName ?? '');
        $name  = strtoupper($productName ?? '');
        $base  = 'https://tokosancaka.com/public/storage/logo-ppob/';

        // Provider Seluler
        if (str_contains($group, 'TELKOMSEL') || str_contains($name, 'TELKOMSEL')) return $base . 'telkomsel.png';
        if (str_contains($group, 'INDOSAT') || str_contains($name, 'INDOSAT')) return $base . 'indosat.png';
        if (str_contains($group, 'XL') || str_contains($name, 'XL')) return $base . 'xl.png';
        if (str_contains($group, 'AXIS') || str_contains($name, 'AXIS')) return $base . 'axis.png';
        if (str_contains($group, 'TRI') || str_contains($group, 'THREE') || str_contains($name, 'THREE')) return $base . 'tri.png';
        if (str_contains($group, 'SMARTFREN') || str_contains($name, 'SMARTFREN')) return $base . 'smartfren.png';
        if (str_contains($group, 'BY.U') || str_contains($name, 'BY.U')) return $base . 'by.u.png';

        // PLN (Dipisah antara Pascabayar & Prabayar)
        if (str_contains($group, 'PLN') || str_contains($name, 'PLN')) {
            if (str_contains($group, 'PASCA') || str_contains($name, 'PASCA')) return $base . 'pln%20pascabayar.png';
            return $base . 'pln.png';
        }

        // E-Wallet & Utilitas Umum
        if (str_contains($group, 'BPJS') || str_contains($name, 'BPJS')) return $base . 'bpjs.png';
        if (str_contains($group, 'DANA') || str_contains($name, 'DANA')) return $base . 'dana.png';
        if (str_contains($group, 'OVO') || str_contains($name, 'OVO')) return $base . 'ovo.png';
        if (str_contains($group, 'GOPAY') || str_contains($group, 'GO PAY') || str_contains($name, 'GOPAY')) return $base . 'go%20pay.png';
        if (str_contains($group, 'SHOPEE') || str_contains($name, 'SHOPEE')) return $base . 'shopee%20pay.png';
        
        // Game Online & TV
        if (str_contains($group, 'FREE FIRE') || str_contains($name, 'FREE FIRE')) return $base . 'free%20fire.png';
        if (str_contains($group, 'MOBILE LEGEND') || str_contains($name, 'MOBILE LEGEND')) return $base . 'mobile%20legends.png';
        if (str_contains($group, 'K-VISION') || str_contains($group, 'GOL') || str_contains($name, 'K-VISION')) return $base . 'k-vision%20dan%20gol.png';
        if (str_contains($group, 'PGN') || str_contains($name, 'GAS')) return $base . 'pertamina%20gas.png';

        // Fallback jika tidak ada yang cocok (Anda bisa siapkan default.png di folder logo-ppob)
        return $base . 'default.png'; 
    }

    public function ppobProductGroup(Request $request)
    {
        Log::info("\n========== [PPOB PRODUCT GROUP - START] ==========");
        
        $validator = Validator::make($request->all(), [
            'accessToken' => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::warning("PPOB Product Group Validasi Gagal: ", $validator->errors()->toArray());
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        $payload = [
            'accessToken' => $request->accessToken
        ];
        
        return $this->forwardRequest('PPOB/ProductGroup', $payload); 
    }

    public function ppobProductList(Request $request)
    {
        Log::info("\n========== [PPOB PRODUCT LIST - START] ==========");
        
        $validator = Validator::make($request->all(), [
            'productGroup' => 'required|string',
            'accessToken'  => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        $payload = [
            'productGroup' => $request->productGroup,
            'accessToken'  => $request->accessToken   
        ];

        // Ambil data produk dari Darmawisata
        $response = $this->forwardRequest('PPOB/Product', $payload); 
        $json = json_decode($response->getContent(), true);

        // --- INI BAGIAN YANG DIUBAH (Auto Mapping Logo) ---
        if (isset($json['productList']) && is_array($json['productList'])) { 
            foreach ($json['productList'] as &$product) {
                // Terapkan Smart Mapping ke setiap produk
                $product['iconUrl'] = $this->getPpobLogo($product['group'] ?? '', $product['name'] ?? '');
            }
        }

        return response()->json($json);
    }

    public function ppobInquiry(Request $request)
    {
        Log::info("\n========== [PPOB INQUIRY - START] ==========");

        $validator = Validator::make($request->all(), [
            'productCode'    => 'required|string',
            'customerID'     => 'required|string',
            'customerMSISDN' => 'nullable|string',
            'accessToken'    => 'required|string',
        ]);

        if ($validator->fails()) {
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
                ->select(
                    't.id', 't.product_code', 't.customer_id', 
                    't.billing_reference_id', 't.sell_price', 
                    't.status', 't.created_at', 't.resp_message', 't.user_id'
                )
                ->orderBy('t.created_at', 'desc');

            if ($userId != 4) {
                $query->where('t.user_id', $userId);
            }

            $orders = $query->get();

            // --- INI BAGIAN YANG DIUBAH (Auto Mapping Logo di Riwayat) ---
            $formattedData = $orders->map(function ($order) {
                return [
                    'id'                 => $order->id,
                    'userId'             => $order->user_id, 
                    'productCode'        => $order->product_code,
                    'productName'        => $order->product_code, // Bisa di-query detailnya jika perlu
                    // Gunakan fungsi smart mapping untuk History juga
                    'iconUrl'            => $this->getPpobLogo($order->product_code, $order->product_code),
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