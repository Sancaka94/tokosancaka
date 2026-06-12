<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Api;

/**
 * Sancaka Express - Central Controller for Deliveree v10 API
 */
class DelivereeApiController extends Controller
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        // 1. Ambil mode saat ini (sandbox/production)
        $mode = Api::getValue('DELIVEREE_MODE', 'global', 'sandbox');

        // 2. Ambil base URL dinamis (fallback ke v10 sesuai dokumentasi)
        $defaultUrl = $mode === 'production' 
            ? 'https://api.deliveree.com/public_api/v10' 
            : 'https://api.sandbox.deliveree.com/public_api/v10';
        $this->baseUrl = Api::getValue('DELIVEREE_BASE_URL', $mode, $defaultUrl);

        // 3. Ambil API Key
        $this->apiKey = Api::getValue('DELIVEREE_API_KEY', $mode);
    }

    /**
     * Setup HTTP Client
     */
    private function client()
    {
        return Http::withHeaders([
            'Authorization' => $this->apiKey,
            'Accept-Language' => 'en', // Atau 'id'
            'Content-Type' => 'application/json',
        ])->baseUrl($this->baseUrl);
    }

    public function getVehicleTypes(Request $request)
    {
        try {
            $response = $this->client()->get('/vehicle_types', $request->all());
            return $this->handleResponse($response);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Get Vehicle Types');
        }
    }

    public function getExtraServices(Request $request, $vehicleTypeId)
    {
        try {
            $response = $this->client()->get("/vehicle_types/{$vehicleTypeId}/extra_services", $request->all());
            return $this->handleResponse($response);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Get Extra Services');
        }
    }

    public function getQuote(Request $request)
    {
        try {
            $response = $this->client()->post('/deliveries/get_quote', $request->all());
            return $this->handleResponse($response);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Get Delivery Quote');
        }
    }

    public function createDelivery(Request $request)
    {
        try {
            $response = $this->client()->post('/deliveries', $request->all());
            return $this->handleResponse($response);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Create Delivery');
        }
    }

    public function getDeliveriesList(Request $request)
    {
        try {
            $response = $this->client()->get('/deliveries', $request->all());
            return $this->handleResponse($response);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Get Deliveries List');
        }
    }

    public function getDeliveryDetails($id)
    {
        try {
            $response = $this->client()->get("/deliveries/{$id}");
            return $this->handleResponse($response);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Get Delivery Details');
        }
    }

    public function cancelDelivery($id)
    {
        try {
            $response = $this->client()->post("/deliveries/{$id}/cancel");
            return $this->handleResponse($response);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Cancel Delivery');
        }
    }

    public function getUserProfile()
    {
        try {
            $response = $this->client()->post('/customers/user_profile');
            return $this->handleResponse($response);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Get User Profile');
        }
    }

    /**
     * Centralized Response Handler
     */
    private function handleResponse($response)
    {
        $status = $response->status();
        $data = $response->json();

        if ($response->successful()) {
            return response()->json([
                'success' => true,
                'status'  => $status,
                'data'    => $data['data'] ?? $data,
                'pagination' => $data['pagination'] ?? null,
            ], $status);
        }

        // LOG LOG
        Log::error('Deliveree API Error', [
            'status' => $status,
            'body'   => $data
        ]);

        return response()->json([
            'success' => false,
            'status'  => $status,
            'message' => $data['message'] ?? 'Deliveree API request failed.',
            'error_code' => $data['code'] ?? null
        ], $status);
    }

    /**
     * Centralized Exception Handler
     */
    private function handleException(\Exception $e, $context)
    {
        // LOG LOG
        Log::error("Deliveree API Exception - {$context}", [
            'message' => $e->getMessage(),
            'trace'   => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Internal Server Error during Deliveree API call.',
            'error'   => $e->getMessage()
        ], 500);
    }
}