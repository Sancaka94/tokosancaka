<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Pesanan;
use Illuminate\Support\Facades\Log;

class KirimAjaController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->all();
    
        Log::info('Handle request payload:', $payload);
        Log::debug('Raw request content: ' . $request->getContent());
    
        $method = $payload['method'] ?? null;
        $data   = $payload['data'][0] ?? null;
    
        if (!$method || !$data) {
            return response()->json(['error' => 'Invalid payload'], 400);
        }
    
        $orderId = $data['order_id'] ?? null;
        $awb     = $data['awb'] ?? null;
    
        if (!$orderId) {
            return response()->json(['error' => 'Order ID not found'], 400);
        }
    
        $order = Order::where('invoice_number', $orderId)->first();
        $pesanan = Pesanan::where('nomor_invoice', $orderId)->first();
    
        $pesananStatusMap = [
            'processed_packages'       => 'Menunggu Pickup',      
            'shipped_packages'         => 'Sedang Dikirim',     
            'canceled_packages'        => 'Dibatalkan',       
            'finished_packages'        => 'Selesai',              
            'returned_packages'        => 'Dalam Proses Retur', 
            'return_finished_packages' => 'Retur Selesai',        
        ];
    
        $orderStatusMap = [
            'processed_packages'       => 'processing',      
            'shipped_packages'         => 'shipment',     
            'canceled_packages'        => 'canceled',       
            'finished_packages'        => 'completed',              
            'returned_packages'        => 'returning', 
            'return_finished_packages' => 'returned',        
        ];
    
        $timestampMap = [
            'shipped_packages'         => 'shipped_at',
            'finished_packages'        => 'finished_at',
            'canceled_packages'        => 'rejected_at',
            'return_finished_packages' => 'returned_at', 
        ];
    
        $pesananStatus = $pesananStatusMap[$method] ?? null;
        $orderStatus   = $orderStatusMap[$method] ?? null;
    
        if ($order) {
            if ($awb) {
                $order->shipping_reference = $awb;
            }
            if ($orderStatus) {
                $order->status = $orderStatus;
    
                if (isset($timestampMap[$method])) {
                    $order->{$timestampMap[$method]} = now();
                }
            }
            $order->save();
        }
        
        if ($pesanan) {
            if ($awb) {
                $pesanan->resi = $awb;
            }
            if ($pesananStatus) {
                $pesanan->status = $pesananStatus;
                $pesanan->status_pesanan = $pesananStatus;
    
                if (isset($timestampMap[$method])) {
                    $pesanan->{$timestampMap[$method]} = now();
                }
            }
            $pesanan->save();
        }
    
        return response()->json(['success' => true]);
    }


    
    public function setCallback(Request $request, \App\Services\KiriminAjaService $kiriminAja)
    {
        $url = url('/api/webhook/kiriminaja');
    
        try {
            $response = $kiriminAja->setCallback($url);
    
            if (!empty($response) && isset($response['status']) && $response['status'] === true) {
                return response()->json([
                    'success' => true,
                    'message' => 'Callback URL berhasil diset di KiriminAja',
                    'data'    => $response
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $response['text'] ?? 'Gagal menyet callback URL',
                    'data'    => $response
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
