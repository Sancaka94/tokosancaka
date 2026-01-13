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
use Carbon\Carbon;

class KirimAjaController extends Controller
{
    /**
     * Handle Webhook dari KiriminAja (Real-time Update)
     */
    public function handle(Request $request)
    {
        // [LOG 1] Tangkap Payload Mentah
        // Ini yang paling penting untuk debugging jika data tidak masuk
        $payload = $request->all();
        Log::info('[WEBHOOK-KA] 🟢 Payload Diterima:', ['content' => $payload]);

        $method    = $payload['method'] ?? 'unknown';
        $dataArray = $payload['data'] ?? [];

        // Validasi Payload Kosong
        if (empty($dataArray)) {
            Log::warning('[WEBHOOK-KA] ⚠️ Data array kosong atau format salah.');
            return response()->json(['error' => 'Invalid payload, data[] is missing'], 400);
        }

        try {
            DB::beginTransaction();

            // -----------------------------------------------------------
            // 1. MAPPING STATUS & TIMESTAMP
            // -----------------------------------------------------------
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

            Log::info("[WEBHOOK-KA] Method: $method | Target Status Pesanan: " . ($pesananStatus ?? 'Tetap') . " | Target Order: " . ($orderStatus ?? 'Tetap'));

            // -----------------------------------------------------------
            // 2. LOOP DATA (BATCH PROCESSING)
            // -----------------------------------------------------------
            foreach ($dataArray as $index => $data) {
                if (!$data) continue;

                $orderId = $data['order_id'] ?? null;
                $awb     = $data['awb'] ?? null; // Ini RESI dari Webhook

                // [LOG 2] Cek Data Item
                Log::info("[WEBHOOK-KA] Processing Item #$index", ['order_id' => $orderId, 'awb_webhook' => $awb]);

                if (!$orderId) {
                    Log::warning('[WEBHOOK-KA] ⚠️ Order ID hilang di item ini.');
                    continue;
                }

                // Lock DB untuk mencegah race condition
                $order            = Order::where('invoice_number', $orderId)->lockForUpdate()->first();
                $pesanan          = Pesanan::where('nomor_invoice', $orderId)->lockForUpdate()->first();
                $orderMarketplace = OrderMarketplace::where('invoice_number', $orderId)->lockForUpdate()->first();

                // Ambil Timestamp & Convert ke WIB
                $shippedAt  = $data['shipped_at'] ?? null;
                $finishedAt = $data['finished_at'] ?? null;
                $updateTime = now()->timezone('Asia/Jakarta');

                // =========================================================================
                // A. UPDATE RESI / AWB (JIKA KOSONG)
                // =========================================================================
                if (!empty($awb)) {
                    // 1. Model Order
                    if ($order && empty($order->shipping_reference)) {
                        $order->shipping_reference = $awb;
                        $order->save();
                        Log::info("[WEBHOOK-KA] ✅ Resi Order Updated: $orderId -> $awb");
                    }
                    // 2. Model Pesanan
                    if ($pesanan && empty($pesanan->resi)) {
                        $pesanan->resi = $awb;
                        $pesanan->save();
                        Log::info("[WEBHOOK-KA] ✅ Resi Pesanan Updated: $orderId -> $awb");
                    }
                    // 3. Model OrderMarketplace
                    if ($orderMarketplace && empty($orderMarketplace->shipping_resi)) {
                        $orderMarketplace->shipping_resi = $awb;
                        $orderMarketplace->save();
                        Log::info("[WEBHOOK-KA] ✅ Resi OrderMarketplace Updated: $orderId -> $awb");
                    }
                }

                // =========================================================================
                // B. UPDATE STATUS & SALDO (MODEL ORDER)
                // =========================================================================
                if ($order && $orderStatus) {
                    $previousStatus = $order->status;

                    if ($previousStatus !== $orderStatus) {
                        $order->status = $orderStatus;

                        // Update Timestamp
                        if ($method === 'shipped_packages' && $shippedAt) {
                            $order->shipped_at = Carbon::parse($shippedAt)->timezone('Asia/Jakarta');
                        } elseif ($method === 'finished_packages' && $finishedAt) {
                            $order->finished_at = Carbon::parse($finishedAt)->timezone('Asia/Jakarta');
                        } elseif (isset($timestampMap[$method])) {
                            $order->{$timestampMap[$method]} = $updateTime;
                        }

                        Log::info("[WEBHOOK-KA] 🔄 Status Order Updated: $previousStatus -> $orderStatus");

                        // 🔥 LOGIKA SALDO (HANYA JIKA COMPLETED) 🔥
                        if ($orderStatus === 'completed' && $previousStatus !== 'completed') {
                            Log::info("[WEBHOOK-KA] 💰 Triggering Revenue Process for $orderId");
                            $this->processRevenue($order, $updateTime);
                        }

                        $order->save();
                    } else {
                        Log::info("[WEBHOOK-KA] Status Order Sama ($previousStatus), Skip Update.");
                    }
                }

                // =========================================================================
                // C. UPDATE STATUS (MODEL PESANAN) - MANUAL INPUT ADMIN
                // =========================================================================
                if ($pesanan && $pesananStatus) {
                    if ($pesanan->status !== 'Selesai' && $pesanan->status !== $pesananStatus) {

                        $oldPesananStatus = $pesanan->status;
                        $pesanan->status = $pesananStatus;

                        if (\Schema::hasColumn('pesanans', 'status_pesanan')) {
                            $pesanan->status_pesanan = $pesananStatus;
                        }

                        // Update Timestamp
                        if ($method === 'shipped_packages' && $shippedAt) {
                            $pesanan->shipped_at = Carbon::parse($shippedAt)->timezone('Asia/Jakarta');
                        } elseif ($method === 'finished_packages' && $finishedAt) {
                            $pesanan->finished_at = Carbon::parse($finishedAt)->timezone('Asia/Jakarta');
                        } elseif (isset($timestampMap[$method]) && \Schema::hasColumn('pesanans', $timestampMap[$method])) {
                            $pesanan->{$timestampMap[$method]} = $updateTime;
                        }

                        $pesanan->save();
                        Log::info("[WEBHOOK-KA] 🔄 Status Pesanan Updated: $oldPesananStatus -> $pesananStatus");

                        // 🔥 TRIGGER KEUANGAN OTOMATIS (DENGAN PENGAMAN) 🔥
                        if ($pesananStatus === 'Selesai') {
                             Log::info("[WEBHOOK-KA] 💰 Pesanan Selesai. Mengeksekusi pencatatan keuangan...");

                             try {
                                 // Kita coba panggil fungsinya
                                 // Pastikan function ini sudah PUBLIC STATIC di PesananController
                                 \App\Http\Controllers\Admin\PesananController::simpanKeuangan($pesanan);

                                 Log::info("[WEBHOOK-KA] 💰 Sukses mencatat keuangan.");
                             } catch (\Throwable $th) {
                                 // Jika EROR, Status Pesanan TETAP BERUBAH jadi Selesai.
                                 // Error dicatat di Log biar bisa kita perbaiki nanti.
                                 Log::error("[WEBHOOK-KA] ❌ GAGAL CATAT KEUANGAN: " . $th->getMessage());
                                 Log::error("[WEBHOOK-KA] ❌ Lokasi Error: " . $th->getFile() . " baris " . $th->getLine());
                             }
                        }

                    }
                }

                // =========================================================================
                // D. UPDATE STATUS (MODEL ORDER MARKETPLACE)
                // =========================================================================
                if ($orderMarketplace && $orderStatus) {
                    if ($orderMarketplace->status !== 'completed' && $orderMarketplace->status !== $orderStatus) {
                        $orderMarketplace->status = $orderStatus;
                        $orderMarketplace->save();
                        Log::info("[WEBHOOK-KA] 🔄 Status OrderMP Updated: $orderStatus");
                    }
                }

                // [LOG 3] Peringatan Jika Data Tidak Ditemukan
                if (!$order && !$pesanan && !$orderMarketplace) {
                    Log::warning("[WEBHOOK-KA] ❌ Order ID $orderId tidak ditemukan di tabel manapun!");
                }

            } // End Foreach

            DB::commit();
            Log::info("[WEBHOOK-KA] 🏁 Transaction Committed Successfully.");
            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[WEBHOOK-KA] 💥 CRITICAL ERROR:', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'payload' => $payload
            ]);
            return response()->json(['success' => false, 'message' => 'Internal Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Helper function untuk memproses Keuangan (Saldo Seller)
     */
    private function processRevenue($order, $updateTime)
    {
        try {
            $store = Store::find($order->store_id);
            if (!$store) {
                Log::error("[WEBHOOK-KA] Revenue Failed: Store ID {$order->store_id} not found.");
                return;
            }

            $seller = User::where('id_pengguna', $store->user_id)->first();
            if (!$seller) {
                Log::error("[WEBHOOK-KA] Revenue Failed: User ID {$store->user_id} not found.");
                return;
            }

            $revenue = $order->subtotal;

            // Tambah Saldo
            $seller->saldo += $revenue;
            $seller->save();

            // Catat Riwayat Transaksi
            TopUp::create([
                'customer_id'      => $seller->id_pengguna,
                'amount'           => $revenue,
                'status'           => 'success',
                'payment_method'   => 'marketplace_revenue',
                'transaction_id'   => 'REV-' . $order->invoice_number,
                'reference_id'     => $order->invoice_number,
                'created_at'       => $order->finished_at ?? $updateTime,
            ]);

            Log::info("[WEBHOOK-KA] 💵 Saldo Seller (+{$revenue}) berhasil ditambah untuk invoice {$order->invoice_number}.");

        } catch (\Exception $e) {
            Log::error("[WEBHOOK-KA] 💥 Revenue Error for {$order->invoice_number}: " . $e->getMessage());
            throw $e; // Lempar error agar transaksi utama di-rollback
        }
    }

    /**
     * Set Callback URL (Setup)
     */
    public function setCallback(Request $request, \App\Services\KiriminAjaService $kiriminAja)
    {
        $url = url('/api/webhook/kiriminaja');
        Log::info("[SETUP-KA] Setting Callback URL to: $url");

        try {
            $response = $kiriminAja->setCallback($url);
            Log::info("[SETUP-KA] Response:", ['response' => $response]);

            if (!empty($response) && isset($response['status']) && $response['status'] === true) {
                return response()->json([
                    'success' => true,
                    'message' => 'Callback URL berhasil diset',
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
            Log::error("[SETUP-KA] Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
