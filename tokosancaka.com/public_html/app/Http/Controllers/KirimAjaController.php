<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Pesanan;
use App\Models\Store;
use App\Models\User;
use App\Models\TopUp;
use App\Models\OrderMarketplace;
use App\Services\OrderRefundService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class KirimAjaController extends Controller
{
    public function handle(Request $request, OrderRefundService $refundService)
    {
        $payload = $request->all();
        $method = $payload['method'] ?? null;
        $dataArray = $payload['data'] ?? [];

        Log::info('[WEBHOOK-KA] Payload Received', ['method' => $method]);

        if (empty($dataArray)) {
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        try {
            DB::beginTransaction();

            // MAPPING STATUS (Sama seperti kode asli)
            $pesananStatusMap = [
                'processed_packages'       => 'Menunggu Pickup',
                'shipped_packages'         => 'Sedang Dikirim',
                'canceled_packages'        => 'Dibatalkan',
                'finished_packages'        => 'Selesai',
                'returned_packages'        => 'Dalam Proses Retur',
                'return_finished_packages' => 'Retur Selesai',
                'error_packages'           => 'Gagal Kirim Resi',
            ];

            $generalStatusMap = [
                'processed_packages'       => 'processing',
                'shipped_packages'         => 'shipment',
                'canceled_packages'        => 'canceled',
                'finished_packages'        => 'completed',
                'returned_packages'        => 'returning',
                'return_finished_packages' => 'returned',
                'error_packages'           => 'error',
            ];

            $timestampMap = [
                'shipped_packages'         => 'shipped_at',
                'finished_packages'        => 'finished_at',
                'canceled_packages'        => 'rejected_at',
                'return_finished_packages' => 'returned_at',
            ];

            $statusPesananIndo = $pesananStatusMap[$method] ?? null;
            $statusGeneral     = $generalStatusMap[$method] ?? null;

            $hasStatusPesananCol = Schema::hasColumn('Pesanan', 'status_pesanan');
            $pendingPushItems = [];

            foreach ($dataArray as $data) {
                if (!$data) continue;

                $orderId = $data['order_id'] ?? null;
                $awb     = $data['awb'] ?? null;

                if (!$orderId) continue;

                // LOGGING
                if (!empty($awb)) {
                    Log::info("[WEBHOOK-KA] 📍 DATA CHECK | Order: $orderId | AWB: $awb");
                } else {
                    Log::warning("[WEBHOOK-KA] ⚠️ DATA CHECK | Order: $orderId | AWB TIDAK ADA.");
                }

                $shippedAt  = $data['shipped_at'] ?? null;
                $finishedAt = $data['finished_at'] ?? null;
                $updateTime = now()->timezone('Asia/Jakarta');

                // =========================================================================
                // 🔥 LOGIKA TANGKAP RESI RETUR
                // =========================================================================
                if (strpos($orderId, '-RTR-') !== false || strpos($orderId, 'RTR-') !== false) {
                    if ($awb) {
                        $safeAwb = preg_replace('/[^a-zA-Z0-9-]/', '', $awb);

                        $returnOrder = \App\Models\ReturnOrder::where('new_resi', 'PROSES-PICKUP')->latest()->first();
                        if ($returnOrder) {
                            $oldResiFallback = $returnOrder->new_resi;
                            $returnOrder->update(['new_resi' => $safeAwb]);

                            if ($oldResiFallback === 'PROSES-PICKUP') {
                                DB::table('complain_chats')
                                    ->where('invoice_number', $returnOrder->invoice_number)
                                    ->where('message', 'LIKE', '%PROSES-PICKUP%')
                                    ->update([
                                        'message' => DB::raw("REPLACE(message, 'PROSES-PICKUP', '{$safeAwb}')")
                                    ]);
                            }
                            Log::info("[WEBHOOK-KA] ✅ Resi Retur Diperbarui | Invoice: {$returnOrder->invoice_number} | AWB Asli: {$safeAwb}");
                        }
                    }
                    continue;
                }

                $foundInMainDB = false;
                $buyerId = null;

                // =========================================================================
                // BAGIAN 1: CEK DI SYSTEM A (MARKETPLACE - TABEL ORDERS)
                // =========================================================================
                $order = Order::where('invoice_number', $orderId)->lockForUpdate()->first();

                if ($order) {
                    $foundInMainDB = true;
                    $buyerId = $order->user_id;
                    $oldStatus = $order->status;
                    $updateOrderData = ['updated_at' => $updateTime];

                    if ($awb) {
                        $updateOrderData['shipping_reference'] = $awb;
                    }

                    $finalStatus = $oldStatus;
                    if ($statusGeneral) {
                        $finalStatus = ($statusGeneral === 'shipment') ? 'shipped' : $statusGeneral;
                        if ($order->status !== 'completed' && $order->status !== 'canceled') {
                            $updateOrderData['status'] = $finalStatus;
                        }
                    }

                    // Timestamp logic
                    if ($method === 'shipped_packages' && $shippedAt) $updateOrderData['shipped_at'] = Carbon::parse($shippedAt)->timezone('Asia/Jakarta');
                    elseif ($method === 'finished_packages' && $finishedAt) $updateOrderData['finished_at'] = Carbon::parse($finishedAt)->timezone('Asia/Jakarta');
                    elseif (isset($timestampMap[$method])) $updateOrderData[$timestampMap[$method]] = $updateTime;

                    // --- TAHAN DANA DI ESCROW ---
                    if ($finalStatus === 'completed' && $oldStatus !== 'completed') {
                        $cekEscrow = \App\Models\Escrow::where('order_id', $order->id)->exists();
                        if (!$cekEscrow) {
                            \App\Models\Escrow::create([
                                'order_id'        => $order->id,
                                'invoice_number'  => $order->invoice_number,
                                'store_id'        => $order->store_id,
                                'user_id'         => $order->user_id,
                                'nominal_ditahan' => $order->total_amount ?? $order->subtotal,
                                'nominal_ongkir'  => $order->shipping_cost ?? 0,
                                'status_dana'     => 'ditahan',
                            ]);
                            Log::info("[WEBHOOK-KA] 🛡️ Dana pesanan $orderId berhasil DITAHAN di tabel Escrow.");
                        }
                    }

                    // =========================================================================
                    // 💸 PENGEMBALIAN DANA OTOMATIS (SEMUA METODE KECUALI CASH & COD)
                    // =========================================================================
                    if ($finalStatus === 'canceled') {
                        try {
                            \App\Models\Escrow::where('order_id', $order->id)
                                ->where('status_dana', 'ditahan')
                                ->update(['status_dana' => 'dibatalkan']);

                            $refundAmount = (float) ($order->total_amount ?? $order->total_price ?? $order->subtotal ?? 0);
                            $refundService->refundToUserSaldo(
                                $order->invoice_number,
                                $order->user_id,
                                $refundAmount,
                                $order->payment_method,
                                $order->payment_status ?? $oldStatus,
                                'WEBHOOK-KA'
                            );
                        } catch (\Exception $e) {
                            Log::error("[WEBHOOK-KA] ❌ Gagal Refund Saldo Marketplace: " . $e->getMessage());
                        }
                    }
                    // =========================================================================

                    DB::table('orders')->where('id', $order->id)->update($updateOrderData);
                    Log::info("[WEBHOOK-KA] Updated Marketplace Order: $orderId ke status " . ($updateOrderData['status'] ?? $oldStatus));
                }

                // =========================================================================
                // BAGIAN 1.B: CEK PESANAN MANUAL, WEB & MOBILE (TABEL PESANAN)
                // =========================================================================
                $cekPesanan = DB::table('Pesanan')
                                ->where('nomor_invoice', $orderId)
                                ->lockForUpdate()
                                ->first();

                if ($cekPesanan) {
                    $foundInMainDB = true;
                    $buyerId = $buyerId ?? ($cekPesanan->id_pengguna_pembeli ?? $cekPesanan->customer_id);
                    $updateData = ['updated_at' => now()];
                    $statusLamaPesanan = $cekPesanan->status;
                    $perluUpdate = false;

                    // 1. CEK PERUBAHAN RESI/AWB
                    if ($awb && $cekPesanan->resi !== $awb) {
                        $updateData['resi'] = $awb;
                        $perluUpdate = true;
                    }

                    // 2. CEK PERUBAHAN STATUS
                    if ($statusPesananIndo && $cekPesanan->status !== $statusPesananIndo) {
                        $updateData['status'] = $statusPesananIndo;
                        if ($hasStatusPesananCol) {
                            $updateData['status_pesanan'] = $statusPesananIndo;
                        }

                        if ($method === 'shipped_packages' && $shippedAt) $updateData['shipped_at'] = Carbon::parse($shippedAt)->timezone('Asia/Jakarta');
                        elseif ($method === 'finished_packages' && $finishedAt) $updateData['finished_at'] = Carbon::parse($finishedAt)->timezone('Asia/Jakarta');
                        elseif (isset($timestampMap[$method])) $updateData[$timestampMap[$method]] = $updateTime;

                        $perluUpdate = true;
                    }

                    // =========================================================================
                    // 💸 PENGEMBALIAN DANA OTOMATIS (SEMUA METODE KECUALI CASH & COD)
                    // =========================================================================
                    if ($statusPesananIndo === 'Dibatalkan') {
                        try {
                            $targetUserId = $cekPesanan->id_pengguna_pembeli ?? $cekPesanan->customer_id;

                            $komponenTagihan = (float) ($cekPesanan->total_harga_barang ?? $cekPesanan->item_price ?? 0)
                                             + (float) ($cekPesanan->shipping_cost ?? 0)
                                             + (float) ($cekPesanan->insurance_cost ?? 0);
                            $hargaTercatat = (float) ($cekPesanan->price ?? 0);

                            $refundAmount = ($hargaTercatat > 0 && $komponenTagihan > 0)
                                ? min($hargaTercatat, $komponenTagihan)
                                : max($hargaTercatat, $komponenTagihan);

                            $refundService->refundToUserSaldo(
                                $cekPesanan->nomor_invoice,
                                $targetUserId,
                                $refundAmount,
                                $cekPesanan->payment_method,
                                $statusLamaPesanan,
                                'WEBHOOK-KA'
                            );
                        } catch (\Exception $e) {
                            Log::error("[WEBHOOK-KA] ❌ Gagal Refund Saldo Pesanan: " . $e->getMessage());
                        }
                    }
                    // =========================================================================

                    // 3. EKSEKUSI UPDATE KE DATABASE
                    if ($perluUpdate) {
                        DB::table('Pesanan')->where('nomor_invoice', $orderId)->update($updateData);
                        Log::info("[WEBHOOK-KA] Updated Pesanan: $orderId | AWB: " . ($updateData['resi'] ?? $cekPesanan->resi) . " | Status: " . ($updateData['status'] ?? $cekPesanan->status));
                    }

                    // 4. SIMPAN KEUANGAN (FIX DOBEL INPUT)
                    $isJustFinished = ($statusPesananIndo === 'Selesai' && $statusLamaPesanan !== 'Selesai');
                    if ($isJustFinished) {
                        try {
                            Log::info("[WEBHOOK-KA] 🚀 Memicu Simpan Keuangan untuk: $orderId");
                            $pesananModel = Pesanan::where('nomor_invoice', $orderId)->first();
                            if ($pesananModel) {
                                \App\Http\Controllers\Admin\PesananController::simpanKeuangan($pesananModel);
                                Log::info("[WEBHOOK-KA] 💰 Keuangan Manual Berhasil Diproses.");
                            }
                        } catch (\Throwable $th) {
                            Log::error("[WEBHOOK-KA] ❌ Gagal Catat Keuangan: " . $th->getMessage());
                        }
                    } else {
                        if ($statusPesananIndo === 'Selesai' && $statusLamaPesanan === 'Selesai') {
                            Log::info("[WEBHOOK-KA] 🛡️ Keuangan di-skip karena pesanan $orderId sudah berstatus Selesai sebelumnya.");
                        }
                    }
                }

                // =========================================================================
                // BAGIAN 1.C: ORDER MARKETPLACE
                // =========================================================================
                $orderMarketplace = OrderMarketplace::where('invoice_number', $orderId)->lockForUpdate()->first();
                if ($orderMarketplace) {
                    $foundInMainDB = true;
                    $mpUserId = $orderMarketplace->user_id ?? $orderMarketplace->customer_id ?? null;
                    $buyerId = $buyerId ?? $mpUserId;
                    $oldMpStatus = $orderMarketplace->status;
                    $updateMpData = ['updated_at' => $updateTime];

                    if ($awb) $updateMpData['shipping_resi'] = $awb;

                    $finalMpStatus = $oldMpStatus;
                    if ($statusGeneral && $orderMarketplace->status !== 'completed') {
                        $finalMpStatus = ($statusGeneral === 'shipment') ? 'shipped' : $statusGeneral;
                        $updateMpData['status'] = $finalMpStatus;
                    }

                    // Refund otomatis jika OrderMarketplace dibatalkan
                    if ($finalMpStatus === 'canceled') {
                        try {
                            $mpRefundAmount = (float) ($orderMarketplace->total_amount ?? $orderMarketplace->total_price ?? $orderMarketplace->subtotal ?? $orderMarketplace->price ?? 0);
                            $refundService->refundToUserSaldo(
                                $orderMarketplace->invoice_number,
                                $mpUserId,
                                $mpRefundAmount,
                                $orderMarketplace->payment_method ?? null,
                                $orderMarketplace->payment_status ?? $oldMpStatus,
                                'WEBHOOK-KA'
                            );
                        } catch (\Exception $e) {
                            Log::error("[WEBHOOK-KA] ❌ Gagal Refund OrderMarketplace: " . $e->getMessage());
                        }
                    }

                    $tableName = $orderMarketplace->getTable();
                    DB::table($tableName)->where('id', $orderMarketplace->id)->update($updateMpData);
                }

                // =========================================================================
                // LOGIKA WEBHOOK RSUD SOEROTO NGAWI (MURNI SCK)
                // =========================================================================
                $rsudOrder = \App\Models\RsudOrderObat::where('kode_booking', $orderId)->first();

                if ($rsudOrder) {
                    $foundInMainDB = true;

                    if ($awb) {
                        $rsudOrder->resi = $awb;
                    }

                    if ($statusPesananIndo) {
                        $rsudOrder->status_racik = $statusPesananIndo;
                    }

                    $rsudOrder->save();
                    Log::info("[WEBHOOK-KA] ✅ Updated RSUD Order: $orderId | Status: " . ($rsudOrder->status_racik));
                }

                if (!$foundInMainDB) {
                    $this->updatePercetakanDB($orderId, $awb, $statusGeneral);
                }

                if ($buyerId) {
                    $pendingPushItems[] = [
                        'user_id'  => $buyerId,
                        'order_id' => $orderId,
                        'status'   => $pesananStatusMap[$method] ?? $method,
                    ];
                }
            } // End Foreach

            // =========================================================================
            // BATCH QUERY EXPO TOKEN & KIRIM NOTIFIKASI (ANTI N+1)
            // =========================================================================
            try {
                $pushMessages = [];
                $userIdsToFetch = collect($pendingPushItems)->pluck('user_id')->push(4)->unique()->filter()->values();

                $tokensByUser = DB::table('Pengguna')
                    ->whereIn('id_pengguna', $userIdsToFetch)
                    ->whereNotNull('expo_token')
                    ->where('expo_token', '!=', '')
                    ->pluck('expo_token', 'id_pengguna');

                foreach ($pendingPushItems as $item) {
                    $token = $tokensByUser[$item['user_id']] ?? null;
                    if ($token) {
                        $pushMessages[] = [
                            'to'    => $token,
                            'title' => 'Update Pesananmu! 📦',
                            'body'  => "Pesanan {$item['order_id']} statusnya sekarang: {$item['status']}",
                            'sound' => 'default',
                        ];
                    }
                }

                $adminToken = $tokensByUser[4] ?? null;
                if ($adminToken) {
                    $statusTextSummary = $pesananStatusMap[$method] ?? $method;
                    $jumlahResi = count($dataArray);

                    $pushMessages[] = [
                        'to'    => $adminToken,
                        'title' => 'Update Sancaka Express 📦',
                        'body'  => "Ada update status '$statusTextSummary' untuk $jumlahResi resi pengiriman.",
                        'sound' => 'default',
                    ];
                }

                if (!empty($pushMessages)) {
                    $chunks = array_chunk($pushMessages, 100);
                    foreach ($chunks as $chunk) {
                        \Illuminate\Support\Facades\Http::withHeaders([
                            'Accept'       => 'application/json',
                            'Content-Type' => 'application/json',
                        ])->post('https://exp.host/--/api/v2/push/send', $chunk);
                    }
                    Log::info("[WEBHOOK-KA] Berhasil mengirim " . count($pushMessages) . " notifikasi ke Customer & Admin.");
                }
            } catch (\Exception $notifError) {
                Log::error('[WEBHOOK-KA] Gagal kirim barisan notifikasi: ' . $notifError->getMessage());
            }

            DB::commit();
            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[WEBHOOK-KA] Critical Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Internal Error'], 500);
        }
    }

    // --- HELPER FUNCTION UNTUK DOKU (DIPISAH SUPAYA RAPI) ---
    private function processDokuDisbursement($order) {
        try {
            Log::info("[WEBHOOK-KA] 🔥 MEMULAI AUTO-CAIR DOKU...");
            $store = Store::find($order->store_id);

            if ($store && !empty($store->doku_sac_id)) {
                $nominalCair = (int) ($order->total_price ?? $order->subtotal ?? 0);

                if ($nominalCair > 0) {
                    $dokuService = app(\App\Services\DokuJokulService::class);
                    $mainSacId = config('doku.main_sac_id');

                    if ($mainSacId) {
                        $transferResult = $dokuService->transferIntra($mainSacId, $store->doku_sac_id, $nominalCair);
                        Log::info("[WEBHOOK-KA] 📡 Response DOKU:", $transferResult);

                        if (($transferResult['success'] ?? false) === true) {
                            Log::info("[WEBHOOK-KA] ✅ SUKSES CAIR! Rp ".number_format($nominalCair));
                        } else {
                            Log::error("[WEBHOOK-KA] ❌ Gagal Transfer: " . ($transferResult['message'] ?? 'Unknown Error'));
                        }
                    } else {
                        Log::error("[WEBHOOK-KA] ❌ Main SAC ID Kosong.");
                    }
                }
            } else {
                Log::warning("[WEBHOOK-KA] ⚠️ Toko tidak punya SAC ID.");
            }
        } catch (\Exception $e) {
            Log::error("[WEBHOOK-KA] ❌ Crash DOKU: " . $e->getMessage());
        }
    }

    private function processMarketplaceRevenue($order, $updateTime) {
        try {
            $store = Store::find($order->store_id);
            if ($store) {
                $seller = User::where('id_pengguna', $store->user_id)->lockForUpdate()->first(); // Tambah Lock
                if ($seller) {
                    $revenue = $order->subtotal;
                    $seller->saldo += $revenue;
                    $seller->save();

                    TopUp::create([
                        'customer_id'    => $seller->id_pengguna,
                        'amount'         => $revenue,
                        'status'         => 'success',
                        'payment_method' => 'marketplace_revenue',
                        'transaction_id' => 'REV-' . $order->invoice_number, // KUNCI IDEMPOTENCY
                        'reference_id'   => $order->invoice_number,
                        'created_at'     => $order->finished_at ?? $updateTime,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Marketplace Revenue Error: ' . $e->getMessage());
        }
    }

    // Helper untuk DB Percetakan (Code Splitting agar Handle tidak terlalu panjang)
    private function updatePercetakanDB($orderId, $awb, $statusGeneral) {
         try {
            $percetakanDB = DB::connection('mysql_second');
            $orderPercetakan = $percetakanDB->table('orders')
                                            ->where('order_number', $orderId)
                                            ->orWhere('shipping_ref', $orderId)
                                            ->first();

            if ($orderPercetakan) {
                $updateDataB = ['updated_at' => now()];
                if ($awb) $updateDataB['shipping_ref'] = $awb;
                if ($statusGeneral && $orderPercetakan->status !== 'completed') {
                    $updateDataB['status'] = $statusGeneral;
                }

                if (count($updateDataB) > 1) {
                    $percetakanDB->table('orders')->where('id', $orderPercetakan->id)->update($updateDataB);
                    Log::info("[WEBHOOK-KA] ✅ Updated DB PERCETAKAN");
                }
            }
        } catch (\Exception $e) {
            Log::error("[WEBHOOK-KA] Error koneksi ke DB Percetakan: " . $e->getMessage());
        }
    }

    public function setCallback(Request $request, \App\Services\KiriminAjaService $kiriminAja)
    {
        // Kode setCallback tetap sama
        $url = url('/api/webhook/kiriminaja');
        try {
            $response = $kiriminAja->setCallback($url);
            return response()->json(['success' => true, 'data' => $response]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
