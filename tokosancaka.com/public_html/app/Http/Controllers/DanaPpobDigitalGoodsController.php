<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class DanaPpobDigitalGoodsController extends Controller
{
    /**
     * POST https://tokosancaka.com/destination/inquiry
     */
    public function destinationInquiry(Request $request)
    {
        Log::info('LOG LOG - Incoming Destination Inquiry', $request->all());

        $head = $request->input('request.head');
        $destinations = $request->input('request.body.destinationInfos', []);
        
        $inquiryResults = [];

        foreach ($destinations as $dest) {
            $inquiryResults[] = [
                "inquiryId" => uniqid('inq_'),
                "inquiryStatus" => [
                    "code" => "10",
                    "status" => "SUCCESS",
                    "message" => "Success"
                ],
                "destinationInfo" => [
                    "primaryParam" => $dest['primaryParam'],
                    "secondaryParam" => $dest['secondaryParam'] ?? ""
                ]
            ];
        }

        return $this->formatResponse('dana.digital.goods.destination.inquiry', $head['reqMsgId'], [
            "inquiryResults" => $inquiryResults
        ]);
    }

    /**
     * POST https://tokosancaka.com/order/create
     */
    public function createOrder(Request $request)
    {
        Log::info('LOG LOG - Incoming Create Order', $request->all());

        $head = $request->input('request.head');
        $body = $request->input('request.body');
        
        $requestId = $body['requestId'];
        
        // 1. IDEMPOTENCY CHECK
        $existingOrder = DB::table('orders_ppob_dana')
            ->leftJoin('products_dana_ppob', 'orders_ppob_dana.product_id', '=', 'products_dana_ppob.product_id')
            ->where('orders_ppob_dana.request_id', $requestId)
            ->first();
            
        if ($existingOrder) {
            Log::info('LOG LOG - Idempotent request detected for ID: ' . $requestId);
            return $this->formatOrderResponse($head, $existingOrder);
        }

        // 2. CREATE NEW ORDER
        $merchantOrderId = uniqid('ord_'); 
        
        try {
            DB::beginTransaction();

            DB::table('orders_ppob_dana')->insert([
                'order_id' => $merchantOrderId,
                'request_id' => $requestId,
                'product_id' => $body['productId'],
                'primary_param' => $body['destinationInfo']['primaryParam'],
                'dana_price_value' => $body['danaSellingPrice']['value'] ?? null,
                'dana_price_currency' => $body['danaSellingPrice']['currency'] ?? 'IDR',
                'status_code' => '20', 
                'status_status' => 'PENDING',
                'status_message' => 'Pending processing',
                'created_at' => Carbon::now('Asia/Jakarta'),
                'updated_at' => Carbon::now('Asia/Jakarta')
            ]);

            DB::commit();

            // Fetch newly created order with product details
            $order = DB::table('orders_ppob_dana')
                ->leftJoin('products_dana_ppob', 'orders_ppob_dana.product_id', '=', 'products_dana_ppob.product_id')
                ->where('orders_ppob_dana.order_id', $merchantOrderId)
                ->first();
                
            return $this->formatOrderResponse($head, $order);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('LOG LOG - Create Order Failed: ' . $e->getMessage());
            
            return response()->json(['error' => 'Internal Server Error'], 500); 
        }
    }

    /**
     * POST https://tokosancaka.com/order/detail
     */
    public function getOrderDetail(Request $request)
    {
        Log::info('LOG LOG - Incoming Get Order Detail', $request->all());

        $head = $request->input('request.head');
        $identifiers = $request->input('request.body.orderIdentifiers', []);
        
        $ordersResponse = [];

        foreach ($identifiers as $identifier) {
            $order = DB::table('orders_ppob_dana')
                ->leftJoin('products_dana_ppob', 'orders_ppob_dana.product_id', '=', 'products_dana_ppob.product_id')
                ->where('orders_ppob_dana.request_id', $identifier['requestId'])
                ->first();
            
            if ($order) {
                $ordersResponse[] = $this->buildOrderObject($order);
            } else {
                $ordersResponse[] = [
                    "requestId" => $identifier['requestId'],
                    "orderStatus" => [
                        "code" => "40",
                        "status" => "EMPTY",
                        "message" => "Order Not Found"
                    ]
                ];
            }
        }

        return $this->formatResponse('dana.digital.goods.order.query', $head['reqMsgId'], [
            "orders" => $ordersResponse
        ]);
    }

    /**
     * Helper: Format standard DANA Response Head & Body
     */
    private function formatResponse($function, $reqMsgId, $body)
    {
        $response = [
            "response" => [
                "head" => [
                    "version" => "2.0",
                    "function" => $function,
                    "respTime" => Carbon::now('Asia/Jakarta')->toIso8601String(),
                    "reqMsgId" => $reqMsgId
                ],
                "body" => $body,
                "signature" => "YOUR_GENERATED_SIGNATURE_HERE" 
            ]
        ];

        return response()->json($response);
    }

    /**
     * Helper: Format Order Response specifically for createOrder endpoint
     */
    private function formatOrderResponse($head, $order)
    {
        return $this->formatResponse('dana.digital.goods.order.create', $head['reqMsgId'], [
            "order" => $this->buildOrderObject($order)
        ]);
    }

    /**
     * Helper: Map DB row to DANA Order Object format
     */
    private function buildOrderObject($order)
    {
        return [
            "requestId" => $order->request_id,
            "orderId" => $order->order_id,
            "createdTime" => Carbon::parse($order->created_at)->timezone('Asia/Jakarta')->toIso8601String(),
            "modifiedTime" => Carbon::parse($order->updated_at)->timezone('Asia/Jakarta')->toIso8601String(),
            "destinationInfo" => [
                "primaryParam" => $order->primary_param,
                "secondaryParam" => $order->secondary_param ?? ""
            ],
            "orderStatus" => [
                "code" => $order->status_code,
                "status" => $order->status_status,
                "message" => $order->status_message
            ],
            "serialNumber" => $order->serial_number,
            "token" => $order->token,
            "product" => [
                "productId" => $order->product_id,
                // These fallbacks handle cases where the product might not exist in products_dana_ppob yet
                "type" => $order->product_type ?? "MOBILE_CREDIT", 
                "provider" => $order->provider ?? "telkomsel", 
                "price" => [
                    "value" => $order->dana_price_value,
                    "currency" => $order->dana_price_currency
                ],
                "availability" => (bool) ($order->is_available ?? true)
            ]
        ];
    }

    /**
     * POST https://tokosancaka.com/product
     * Used by DANA to query the product and sync the product availability status.
     */
    public function getProductList(Request $request)
    {
        Log::info('LOG LOG - Incoming Get List of Product', $request->all());

        $head = $request->input('request.head');
        $body = $request->input('request.body');

        // Parameter type wajib, provider opsional
        $type = $body['type'] ?? null;
        $provider = $body['provider'] ?? null;

        if (!$type) {
            return response()->json([
                "response" => [
                    "head" => [
                        "version" => "2.0",
                        "function" => "dana.digital.goods.product.query",
                        "respTime" => Carbon::now('Asia/Jakarta')->toIso8601String(),
                        "reqMsgId" => $head['reqMsgId'] ?? uniqid()
                    ],
                    "body" => ["error" => "Empty mandatory parameter: type"]
                ]
            ], 400); // 400 Bad Request
        }

        // Query ke database Anda
        $query = DB::table('products_dana_ppob')->where('product_type', $type);
        
        if (!empty($provider)) {
            $query->where('provider', $provider);
        }

        $productsDb = $query->get();
        $productsResponse = [];

        foreach ($productsDb as $prod) {
            $productsResponse[] = [
                "productId" => $prod->product_id,
                "type" => $prod->product_type,
                "provider" => $prod->provider,
                "price" => [
                    "value" => $prod->price_value,
                    "currency" => $prod->price_currency
                ],
                "availability" => (bool) $prod->is_available
            ];
        }

        return $this->formatResponse('dana.digital.goods.product.query', $head['reqMsgId'], [
            "products" => $productsResponse
        ]);
    }
}