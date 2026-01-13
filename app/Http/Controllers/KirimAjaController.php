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
    public function handle(Request $request)
    {
        $payload = $request->all();
        $method = $payload['method'] ?? null;
        $dataArray = $payload['data'] ?? [];

        Log::info('[WEBHOOK] Payload Received', ['method' => $method]);

        if (empty($dataArray)) {
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        try {
            DB::beginTransaction();

            // 1. MAPPING STATUS
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

            // 2. LOOP DATA
            foreach ($dataArray as $data) {
                if (!$data) continue;

                $orderId = $data['order_id'] ?? null;
                $awb     = $data['awb'] ?? null;

                if (!$orderId) {
                    Log::warning('Webhook: Missing Order ID');
                    continue;
                }

                // Ambil Waktu & Convert ke WIB
                $shippedAt  = $data['shipped_at'] ?? null;
                $finishedAt = $data['finished_at'] ?? null;
                $updateTime = now()->timezone('Asia/Jakarta');

                // =========================================================================
                // A. UPDATE ORDER MARKETPLACE (Logic Lama Mas)
                // =========================================================================
                $order = Order::where('invoice_number', $orderId)->lockForUpdate()->first();
                if ($order) {
                    if ($awb && empty($order->shipping_reference)) {
                        $order->shipping_reference = $awb;
                    }
                    if ($orderStatus && $order->status !== 'completed') {
                        $order->status = $orderStatus;
                        // Update Timestamp
                        if ($method === 'shipped_packages' && $shippedAt) $order->shipped_at = Carbon::parse($shippedAt)->timezone('Asia/Jakarta');
                        elseif ($method === 'finished_packages' && $finishedAt) $order->finished_at = Carbon::parse($finishedAt)->timezone('Asia/Jakarta');
                        elseif (isset($timestampMap[$method])) $order->{$timestampMap[$method]} = $updateTime;

                        if ($orderStatus === 'completed') {
                            $this->processMarketplaceRevenue($order, $updateTime);
                        }
                    }
                    $order->save();
                }

                // =========================================================================
                // B. UPDATE PESANAN MANUAL (SCK) - PAKE "DIRECT DB" BIAR PASTI MASUK
                // =========================================================================
                // Kita cek dulu apakah datanya ada
                $cekPesanan = DB::table('Pesanan')->where('nomor_invoice', $orderId)->first();

                if ($cekPesanan) {
                    // Siapkan Data Update
                    $updateData = ['updated_at' => now()];

                    // 1. Update Resi (Jika belum ada)
                    if ($awb && empty($cekPesanan->resi)) {
                        $updateData['resi'] = $awb;
                    }

                    // 2. Update Status (Jika berubah dan belum selesai)
                    if ($pesananStatus && $cekPesanan->status !== 'Selesai') {
                        $updateData['status'] = $pesananStatus;

                        // Update status_pesanan (jika kolom ada)
                        if (\Schema::hasColumn('Pesanan', 'status_pesanan')) {
                            $updateData['status_pesanan'] = $pesananStatus;
                        }

                        // Update Timestamp
                        if ($method === 'shipped_packages' && $shippedAt) $updateData['shipped_at'] = Carbon::parse($shippedAt)->timezone('Asia/Jakarta');
                        elseif ($method === 'finished_packages' && $finishedAt) $updateData['finished_at'] = Carbon::parse($finishedAt)->timezone('Asia/Jakarta');
                        elseif (isset($timestampMap[$method])) $updateData[$timestampMap[$method]] = $updateTime;
                    }

                    // 🔥 EKSEKUSI UPDATE LANGSUNG (BYPASS MODEL) 🔥
                    // Ini kunci kemenangannya: Kita paksa update via Query Builder
                    if (!empty($updateData)) {
                        DB::table('Pesanan')->where('nomor_invoice', $orderId)->update($updateData);
                        Log::info("[WEBHOOK] Sukses Update DB Pesanan: $orderId -> $pesananStatus");
                    }

                    // 3. TRIGGER KEUANGAN (Jika Status Baru = Selesai)
                    // Kita cek kondisi: Status WEBHOOK adalah Selesai, dan Status DATABASE sebelumnya BELUM Selesai
                    if ($pesananStatus === 'Selesai' && $cekPesanan->status !== 'Selesai') {
                        Log::info("[WEBHOOK] 💰 Pesanan Selesai. Eksekusi Keuangan...");
                        try {
                            // Ambil ulang data terbaru pakai Model agar kompatibel dengan function simpanKeuangan
                            $pesananModel = Pesanan::where('nomor_invoice', $orderId)->first();
                            if ($pesananModel) {
                                \App\Http\Controllers\Admin\PesananController::simpanKeuangan($pesananModel);
                                Log::info("[WEBHOOK] 💰 Keuangan Manual Berhasil Disimpan.");
                            }
                        } catch (\Throwable $th) {
                            Log::error("[WEBHOOK] Gagal Catat Keuangan: " . $th->getMessage());
                        }
                    }
                }

                // =========================================================================
                // C. UPDATE ORDER MARKETPLACE MODEL LAIN
                // =========================================================================
                $orderMarketplace = OrderMarketplace::where('invoice_number', $orderId)->first();
                if ($orderMarketplace) {
                    if ($awb && empty($orderMarketplace->shipping_resi)) {
                        $orderMarketplace->shipping_resi = $awb;
                    }
                    if ($orderStatus && $orderMarketplace->status !== 'completed') {
                        $orderMarketplace->status = $orderStatus;
                    }
                    $orderMarketplace->save();
                }

            } // End Foreach

            DB::commit();
            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[WEBHOOK] Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Internal Error'], 500);
        }
    }

    // Helper Private untuk Saldo Marketplace (Kode Lama Mas)
    private function processMarketplaceRevenue($order, $updateTime) {
        try {
            $store = Store::find($order->store_id);
            if ($store) {
                $seller = User::where('id_pengguna', $store->user_id)->first();
                if ($seller) {
                    $revenue = $order->subtotal;
                    $seller->saldo += $revenue;
                    $seller->save();
                    TopUp::create([
                        'customer_id'      => $seller->id_pengguna,
                        'amount'           => $revenue,
                        'status'           => 'success',
                        'payment_method'   => 'marketplace_revenue',
                        'transaction_id'   => 'REV-' . $order->invoice_number,
                        'reference_id'     => $order->invoice_number,
                        'created_at'       => $order->finished_at ?? $updateTime,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Marketplace Revenue Error: ' . $e->getMessage());
        }
    }

    // Fungsi Set Callback (Kode Lama Mas)
    public function setCallback(Request $request, \App\Services\KiriminAjaService $kiriminAja)
    {
        $url = url('/api/webhook/kiriminaja');
        try {
            $response = $kiriminAja->setCallback($url);
            return response()->json(['success' => true, 'data' => $response]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
