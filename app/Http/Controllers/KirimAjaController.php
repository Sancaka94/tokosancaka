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

        Log::info('[WEBHOOK-KA] Payload Received', ['method' => $method]);

        if (empty($dataArray)) {
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        try {
            // Kita bungkus DB Utama dengan Transaksi
            // Catatan: DB Second tidak bisa ikut DB::beginTransaction() bawaan ini karena beda koneksi.
            // Tapi untuk update status sederhana, direct query sudah cukup aman.
            DB::beginTransaction();

            // 1. MAPPING STATUS UTAMA (DB A)
            $pesananStatusMap = [
                'processed_packages'       => 'Menunggu Pickup',
                'shipped_packages'         => 'Sedang Dikirim',
                'canceled_packages'        => 'Dibatalkan',
                'finished_packages'        => 'Selesai',
                'returned_packages'        => 'Dalam Proses Retur',
                'return_finished_packages' => 'Retur Selesai',
            ];

            // MAPPING STATUS MARKETPLACE & PERCETAKAN (DB B - Biasanya pakai bhs Inggris)
            $generalStatusMap = [
                'processed_packages'       => 'processing',
                'shipped_packages'         => 'shipment', // Atau 'shipping'
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

            $statusPesananIndo = $pesananStatusMap[$method] ?? null; // Bhs Indo
            $statusGeneral     = $generalStatusMap[$method] ?? null; // Bhs Inggris

            // 2. LOOP DATA WEBHOOK
            foreach ($dataArray as $data) {
                if (!$data) continue;

                $orderId = $data['order_id'] ?? null;
                $awb     = $data['awb'] ?? null;

                if (!$orderId) continue;

                // Ambil Waktu & Convert ke WIB
                $shippedAt  = $data['shipped_at'] ?? null;
                $finishedAt = $data['finished_at'] ?? null;
                $updateTime = now()->timezone('Asia/Jakarta');

                // Flag penanda apakah data ketemu di DB Utama
                $foundInMainDB = false;

                // =========================================================================
                // BAGIAN 1: CEK DI SYSTEM A (DB UTAMA - TOKO SANCAKA)
                // =========================================================================

                // 1.A. Cek Model Order (Marketplace)
                $order = Order::where('invoice_number', $orderId)->lockForUpdate()->first();
                if ($order) {
                    $foundInMainDB = true;
                    if ($awb && empty($order->shipping_reference)) {
                        $order->shipping_reference = $awb;
                    }
                    if ($statusGeneral && $order->status !== 'completed') {
                        $order->status = $statusGeneral;

                        // Timestamp Update
                        if ($method === 'shipped_packages' && $shippedAt) $order->shipped_at = Carbon::parse($shippedAt)->timezone('Asia/Jakarta');
                        elseif ($method === 'finished_packages' && $finishedAt) $order->finished_at = Carbon::parse($finishedAt)->timezone('Asia/Jakarta');
                        elseif (isset($timestampMap[$method])) $order->{$timestampMap[$method]} = $updateTime;

                        // Saldo Marketplace
                        if ($statusGeneral === 'completed') {
                            $this->processMarketplaceRevenue($order, $updateTime);
                        }
                    }
                    $order->save();
                    Log::info("[WEBHOOK-KA] Updated Marketplace Order: $orderId");
                }

                // 1.B. Cek Pesanan Manual (SCK) - Direct DB Update
                // Menggunakan DB::table untuk menghindari masalah Primary Key
                $cekPesanan = DB::table('Pesanan')->where('nomor_invoice', $orderId)->first();
                if ($cekPesanan) {
                    $foundInMainDB = true;
                    $updateData = ['updated_at' => now()];

                    if ($awb && empty($cekPesanan->resi)) $updateData['resi'] = $awb;

                    if ($statusPesananIndo && $cekPesanan->status !== 'Selesai') {
                        $updateData['status'] = $statusPesananIndo;
                        if (\Schema::hasColumn('Pesanan', 'status_pesanan')) {
                            $updateData['status_pesanan'] = $statusPesananIndo;
                        }

                        // Timestamp
                        if ($method === 'shipped_packages' && $shippedAt) $updateData['shipped_at'] = Carbon::parse($shippedAt)->timezone('Asia/Jakarta');
                        elseif ($method === 'finished_packages' && $finishedAt) $updateData['finished_at'] = Carbon::parse($finishedAt)->timezone('Asia/Jakarta');
                        elseif (isset($timestampMap[$method])) $updateData[$timestampMap[$method]] = $updateTime;

                        // Eksekusi Update
                        DB::table('Pesanan')->where('nomor_invoice', $orderId)->update($updateData);
                        Log::info("[WEBHOOK-KA] Updated Pesanan Manual: $orderId -> $statusPesananIndo");

                        // Keuangan Manual
                        if ($statusPesananIndo === 'Selesai' && $cekPesanan->status !== 'Selesai') {
                            try {
                                $pesananModel = Pesanan::where('nomor_invoice', $orderId)->first();
                                if ($pesananModel) {
                                    \App\Http\Controllers\Admin\PesananController::simpanKeuangan($pesananModel);
                                    Log::info("[WEBHOOK-KA] 💰 Keuangan Manual Disimpan.");
                                }
                            } catch (\Throwable $th) {
                                Log::error("[WEBHOOK-KA] Gagal Catat Keuangan: " . $th->getMessage());
                            }
                        }
                    }
                }

                // 1.C. Cek OrderMarketplace (Model Lain)
                $orderMarketplace = OrderMarketplace::where('invoice_number', $orderId)->first();
                if ($orderMarketplace) {
                    $foundInMainDB = true;
                    if ($awb && empty($orderMarketplace->shipping_resi)) $orderMarketplace->shipping_resi = $awb;
                    if ($statusGeneral && $orderMarketplace->status !== 'completed') $orderMarketplace->status = $statusGeneral;
                    $orderMarketplace->save();
                }

                // =========================================================================
                // BAGIAN 2: CEK DI SYSTEM B (PERCETAKAN) - JIKA TIDAK ADA DI A
                // =========================================================================

                if (!$foundInMainDB) {
                    // Gunakan koneksi kedua 'mysql_second'
                    try {
                        $percetakanDB = DB::connection('mysql_second');
                        $orderPercetakan = $percetakanDB->table('orders')
                                            ->where('order_number', $orderId)
                                            ->orWhere('shipping_ref', $orderId) // Jaga-jaga lookup by resi
                                            ->first();

                        if ($orderPercetakan) {
                            $updateDataB = ['updated_at' => now()];

                            // Update Resi di Percetakan
                            if ($awb && empty($orderPercetakan->shipping_ref)) {
                                $updateDataB['shipping_ref'] = $awb;
                            }

                            // Update Status
                            if ($statusGeneral && $orderPercetakan->status !== 'completed') {
                                $updateDataB['status'] = $statusGeneral;

                                // Mapping status spesifik percetakan jika perlu
                                // (Asumsi kolom status di percetakan pakai 'processing', 'shipment', 'completed')
                            }

                            // Eksekusi Update ke DB Kedua
                            if (count($updateDataB) > 1) { // Lebih dari 1 artinya ada data selain updated_at
                                $percetakanDB->table('orders')
                                    ->where('id', $orderPercetakan->id)
                                    ->update($updateDataB);

                                Log::info("[WEBHOOK-KA] ✅ Updated DB PERCETAKAN: $orderId -> $statusGeneral");
                            }
                        } else {
                            Log::warning("[WEBHOOK-KA] ⚠️ Order ID tidak ditemukan di Sancaka Utama maupun Percetakan: $orderId");
                        }

                    } catch (\Exception $e) {
                        Log::error("[WEBHOOK-KA] Error koneksi ke DB Percetakan: " . $e->getMessage());
                    }
                }

            } // End Foreach

            DB::commit(); // Commit transaksi DB Utama
            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[WEBHOOK-KA] Critical Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Internal Error'], 500);
        }
    }

    // Helper Private untuk Saldo Marketplace (Tidak Berubah)
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
