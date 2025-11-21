<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Pesanan;
use App\Models\Store;  
use App\Models\User;  
use App\Models\TopUp; 
use App\Models\OrderMarketplace;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB; 
use Carbon\Carbon; // WAJIB: Import Carbon untuk manipulasi waktu

class KirimAjaController extends Controller
{
    // Anda bisa menambahkan Webhook Secret Key di sini jika ada otorisasi
    // private $webhookKey = env('KIRIMIN_AJA_WEBHOOK_SECRET');

    public function handle(Request $request)
    {
        $payload = $request->all();
        
        Log::info('Handle KiriminAja Webhook Payload Received');
        
        $method = $payload['method'] ?? null;
        $dataArray = $payload['data'] ?? [];

        if (empty($dataArray)) {
            return response()->json(['error' => 'Invalid payload, data[] is missing'], 400);
        }

        try {
            DB::beginTransaction();

            // Peta Status
            $pesananStatusMap = [
                'processed_packages'      => 'Menunggu Pickup',    
                'shipped_packages'        => 'Sedang Dikirim',    
                'canceled_packages'       => 'Dibatalkan',        
                'finished_packages'       => 'Selesai',           
                'returned_packages'       => 'Dalam Proses Retur',
                'return_finished_packages' => 'Retur Selesai',     
            ];
            $orderStatusMap = [ // Status untuk Order/OrderMarketplace
                'processed_packages'      => 'processing',    
                'shipped_packages'        => 'shipment',      
                'canceled_packages'       => 'canceled',        
                'finished_packages'       => 'completed',     
                'returned_packages'       => 'returning',
                'return_finished_packages' => 'returned',        
            ];
            $timestampMap = [ // Kolom timestamp di DB yang akan diisi
                'shipped_packages'        => 'shipped_at',
                'finished_packages'       => 'finished_at',
                'canceled_packages'       => 'rejected_at',
                'return_finished_packages' => 'returned_at', 
            ];
            
            $pesananStatus = $pesananStatusMap[$method] ?? null;
            $orderStatus    = $orderStatusMap[$method] ?? null;
            
            foreach ($dataArray as $data) {
                if (!$data) continue;

                $orderId = $data['order_id'] ?? null;
                $awb     = $data['awb'] ?? null;
                
                if (!$orderId) {
                    Log::warning('Webhook KiriminAja: Ditemukan data tanpa order_id', $data);
                    continue; 
                }
                
                // Cari model berdasarkan invoice number
                $order = Order::where('invoice_number', $orderId)->first();
                $pesanan = Pesanan::where('nomor_invoice', $orderId)->first();
                $orderMarketplace = OrderMarketplace::where('invoice_number', $orderId)->first(); 
                
                // Ambil timestamp spesifik dari payload
                $datePayload = $data['date'] ?? null;
                $shippedAt = $data['shipped_at'] ?? null;
                $finishedAt = $data['finished_at'] ?? null;
                
                // Tentukan waktu update (Convert dari UTC ke Asia/Jakarta/WIB)
                $updateTime = $datePayload ? Carbon::parse($datePayload)->timezone('Asia/Jakarta') : now();
                
                // =========================================================================
                // 3. Update Order (Model Marketplace/Utama)
                // =========================================================================
                if ($order) {
                    if ($awb && empty($order->shipping_reference)) { 
                        $order->shipping_reference = $awb;
                    }
                    
                    if ($orderStatus && $order->status !== 'completed') {
                        $order->status = $orderStatus;
                        
                        // Update kolom timestamp spesifik (Konversi UTC ke WIB)
                        if ($method === 'shipped_packages' && $shippedAt) {
                            $order->shipped_at = Carbon::parse($shippedAt)->timezone('Asia/Jakarta');
                        } elseif ($method === 'finished_packages' && $finishedAt) {
                            $order->finished_at = Carbon::parse($finishedAt)->timezone('Asia/Jakarta');
                        } elseif (isset($timestampMap[$method])) {
                            // Untuk canceled/returned
                            $order->{$timestampMap[$method]} = $updateTime; 
                        }

                        // LOGIKA PENAMBAHAN SALDO HANYA UNTUK STATUS COMPLETED
                        if ($orderStatus === 'completed') {
                            try {
                                $store = Store::find($order->store_id);
                                if ($store) {
                                    $seller = User::where('id_pengguna', $store->user_id)->first(); 
                                    if ($seller) {
                                        $revenue = $order->subtotal;
                                        
                                        $seller->saldo += $revenue;
                                        $seller->save();
                                        
                                        // BUAT CATATAN TRANSAKSI (TopUp/Revenue)
                                        TopUp::create([
                                            'customer_id'      => $seller->id_pengguna, 
                                            'amount'           => $revenue,             
                                            'status'           => 'success',
                                            'payment_method'   => 'marketplace_revenue', 
                                            'transaction_id'   => 'REV-' . $order->invoice_number,
                                            'reference_id'     => $order->invoice_number,
                                            'created_at'       => $order->finished_at ?? $updateTime, 
                                        ]);

                                        Log::info('Saldo Seller berhasil ditambah.', ['order_id' => $orderId, 'revenue' => $revenue]);
                                    }
                                }
                            } catch (\Exception $e) {
                                Log::error('Webhook: CRITICAL ERROR saat penambahan saldo.', ['order_id' => $orderId, 'error' => $e->getMessage()]);
                            }
                        }
                    }
                    $order->save();
                }
                
                // =========================================================================
                // 4. Update Pesanan (Model Pesanan Manual)
                // =========================================================================
                if ($pesanan) {
                    if ($awb && empty($pesanan->resi)) {
                        $pesanan->resi = $awb;
                    }
                    if ($pesananStatus && $pesanan->status !== 'Selesai') {
                        $pesanan->status = $pesananStatus;
                        $pesanan->status_pesanan = $pesananStatus;
                        
                        // Update timestamp (Konversi UTC ke WIB)
                        if ($method === 'shipped_packages' && $shippedAt) {
                            $pesanan->shipped_at = Carbon::parse($shippedAt)->timezone('Asia/Jakarta');
                        } elseif ($method === 'finished_packages' && $finishedAt) {
                            $pesanan->finished_at = Carbon::parse($finishedAt)->timezone('Asia/Jakarta');
                        } elseif (isset($timestampMap[$method])) {
                            $pesanan->{$timestampMap[$method]} = $updateTime; 
                        }
                    }
                    $pesanan->save();
                }
                
                // =========================================================================
                // 5. Update OrderMarketplace (Model Order Marketplace)
                // =========================================================================
                if ($orderMarketplace) {
                    if ($awb && empty($orderMarketplace->shipping_resi)) {
                        $orderMarketplace->shipping_resi = $awb;
                    }
                    if ($orderStatus && $orderMarketplace->status !== 'completed') {
                        $orderMarketplace->status = $orderStatus;
                        // Tambahkan update timestamp jika kolom ada di OrderMarketplace
                    }
                    $orderMarketplace->save();
                }
                
                if (!$order && !$pesanan && !$orderMarketplace) {
                    Log::warning('Webhook: Order ID tidak ditemukan di SEMUA tabel', ['order_id' => $orderId]);
                }

            } // Akhir loop foreach

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal memproses Webhook KiriminAja', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'payload' => $payload
            ]);
            return response()->json(['success' => false, 'message' => 'Internal Error'], 500);
        }
        
        return response()->json(['success' => true]);
    }


    // ... (Fungsi setCallback tetap sama) ...
    
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