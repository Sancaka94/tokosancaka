<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Str;

class DanaDigitalGoodsService
{
    protected $baseUrl;
    protected $aggregatorId;
    protected $merchantId;

    public function __construct()
    {
        // Set sesuai konfigurasi DANA Anda
        $this->baseUrl = config('services.dana.base_url', 'https://api.saas.dana.id');
        $this->aggregatorId = 'tokosancaka_aggregator'; // Ganti sesuai ID Anda
        $this->merchantId = config('services.dana.merchant_id');
    }

    /**
     * POST /dana/bizcenter/digitalgoods/product/notify-status.htm
     * Menginformasikan status produk ke DANA (AVAILABLE, UNAVAILABLE, DISCONTINUE)
     */
    public function notifyProductStatus(array $productStatusList)
    {
        Log::info('LOG LOG - Mengirim Notify Aggregator Product Status ke DANA', $productStatusList);

        // Validasi max 10 produk per hit
        if (count($productStatusList) > 10) {
            Log::warning('LOG LOG - Notify Status DANA melebihi batas (maks 10). Memotong array.');
            $productStatusList = array_slice($productStatusList, 0, 10);
        }

        $reqMsgId = (string) Str::uuid();
        $timestamp = Carbon::now('Asia/Jakarta')->toIso8601String();

        $payload = [
            "request" => [
                "head" => [
                    "version" => "1.0",
                    "function" => "dana.digital.goods.product.notifystatus",
                    "reqTime" => $timestamp,
                    "reqMsgId" => $reqMsgId
                ],
                "body" => [
                    "aggregatorId" => $this->aggregatorId,
                    "productStatusList" => $productStatusList
                ]
            ],
            // TODO: Generate & tambahkan signature nyata Anda
            "signature" => "YOUR_GENERATED_SIGNATURE" 
        ];

        try {
            $response = Http::post($this->baseUrl . '/dana/bizcenter/digitalgoods/product/notify-status.htm', $payload);
            Log::info('LOG LOG - Respon Notify Status DANA', $response->json());
            return $response->json();
        } catch (\Exception $e) {
            Log::error('LOG LOG - Gagal mengirim Notify Status DANA: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * POST /dana/bizcenter/digitalgoods/orderComplete.htm
     * Memberitahu DANA status akhir dari pesanan yang sebelumnya PENDING
     */
    public function orderCompleteCallback($orderData)
    {
        Log::info('LOG LOG - Mengirim Order Complete Callback ke DANA', ['orderId' => $orderData->order_id]);

        $reqMsgId = (string) Str::uuid();
        $timestamp = Carbon::now('Asia/Jakarta')->toIso8601String();

        $payload = [
            "request" => [
                "head" => [
                    "version" => "1.0",
                    "function" => "dana.digital.goods.order.complete",
                    "reqTime" => $timestamp,
                    "reqMsgId" => $reqMsgId
                ],
                "body" => [
                    "orders" => [
                        [
                            "partnerId" => $this->merchantId,
                            "aggregatorId" => $this->aggregatorId,
                            "requestId" => $orderData->request_id,
                            "orderId" => $orderData->order_id,
                            "createdTime" => Carbon::parse($orderData->created_at)->timezone('Asia/Jakarta')->toIso8601String(),
                            "modifiedTime" => Carbon::parse($orderData->updated_at)->timezone('Asia/Jakarta')->toIso8601String(),
                            "completedTime" => $timestamp,
                            "destinationInfo" => [
                                "primaryParam" => $orderData->primary_param
                            ],
                            "orderStatus" => [
                                "code" => $orderData->status_code, // e.g., "10" for SUCCESS, "30" for FAILED
                                "status" => $orderData->status_status,
                                "message" => $orderData->status_message
                            ],
                            "serialNumber" => $orderData->serial_number ?? "",
                            "token" => $orderData->token ?? "",
                            "product" => [
                                "productId" => $orderData->product_id,
                                "type" => "MOBILE_CREDIT", // Dapatkan ini dari relasi DB jika bisa
                                "provider" => "telkomsel", // Dapatkan ini dari relasi DB jika bisa
                                "price" => [
                                    "value" => $orderData->dana_price_value,
                                    "currency" => $orderData->dana_price_currency
                                ],
                                "availability" => true
                            ]
                        ]
                    ]
                ]
            ],
            // TODO: Generate & tambahkan signature nyata Anda
            "signature" => "YOUR_GENERATED_SIGNATURE" 
        ];

        try {
            $response = Http::post($this->baseUrl . '/dana/bizcenter/digitalgoods/orderComplete.htm', $payload);
            Log::info('LOG LOG - Respon Order Complete Callback DANA', $response->json());
            return $response->json();
        } catch (\Exception $e) {
            Log::error('LOG LOG - Gagal mengirim Order Complete Callback DANA: ' . $e->getMessage());
            return null;
        }
    }
}