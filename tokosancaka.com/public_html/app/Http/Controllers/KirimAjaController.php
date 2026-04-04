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
                        $returnOrder = \App\Models\ReturnOrder::where('new_resi', 'PROSES-PICKUP')->latest()->first();
                        if ($returnOrder) {
                            $oldResiFallback = $returnOrder->new_resi;
                            $returnOrder->update(['new_resi' => $awb]);

                            if ($oldResiFallback === 'PROSES-PICKUP') {
                                DB::table('complain_chats')
                                    ->where('invoice_number', $returnOrder->invoice_number)
                                    ->where('message', 'LIKE', '%PROSES-PICKUP%')
                                    ->update([
                                        'message' => DB::raw("REPLACE(message, 'PROSES-PICKUP', '{$awb}')")
                                    ]);
                            }
                            Log::info("[WEBHOOK-KA] ✅ Resi Retur Diperbarui | Invoice: {$returnOrder->invoice_number} | AWB Asli: {$awb}");
                        }
                    }
                    continue;
                }

                $foundInMainDB = false;

                // =========================================================================
                // BAGIAN 1: CEK DI SYSTEM A (MARKETPLACE)
                // =========================================================================
                $order = Order::where('invoice_number', $orderId)->lockForUpdate()->first();

                if ($order) {
                    $foundInMainDB = true;
                    $oldStatus = $order->status;
                    $updateOrderData = ['updated_at' => $updateTime];

                    if ($awb) {
                        $updateOrderData['shipping_reference'] = $awb;
                    }

                    $finalStatus = $oldStatus; // Default fallback
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
                    // 💸 [BARU] LOGIKA PENGEMBALIAN DANA (MARKETPLACE CANCEL)
                    // =========================================================================
                    if ($finalStatus === 'canceled' && $oldStatus !== 'canceled') {
                        try {
                            // 1. Batalkan Escrow (jika terlanjur ada)
                            \App\Models\Escrow::where('order_id', $order->id)->where('status_dana', 'ditahan')->update(['status_dana' => 'dibatalkan']);

                            // 2. Refund ke akun user jika menggunakan Saldo (Sesuaikan string 'Potong Saldo' / 'Saldo' dengan metodemu)
                            if (in_array(strtolower($order->payment_method), ['potong saldo', 'saldo', 'wallet'])) {
                                $buyer = User::where('id_pengguna', $order->user_id)->lockForUpdate()->first();
                                if ($buyer) {
                                    $refundAmount = $order->total_amount ?? $order->total_price ?? $order->subtotal ?? 0;
                                    $buyer->saldo += $refundAmount;
                                    $buyer->save();

                                    TopUp::create([
                                        'customer_id'    => $buyer->id_pengguna,
                                        'amount'         => $refundAmount,
                                        'status'         => 'success',
                                        'payment_method' => 'Refund Batal',
                                        'transaction_id' => 'RFND-' . $order->invoice_number,
                                        'reference_id'   => $order->invoice_number,
                                        'created_at'     => $updateTime,
                                    ]);
                                    Log::info("[WEBHOOK-KA] 💸 Refund Saldo Marketplace BERHASIL: $orderId sejumlah Rp $refundAmount");
                                }
                            }
                        } catch (\Exception $e) {
                            Log::error("[WEBHOOK-KA] ❌ Gagal Refund Saldo Marketplace: " . $e->getMessage());
                        }
                    }
                    // =========================================================================

                    DB::table('orders')->where('id', $order->id)->update($updateOrderData);
                    Log::info("[WEBHOOK-KA] Updated Marketplace Order: $orderId ke status " . ($updateOrderData['status'] ?? $oldStatus));
                }

                // =========================================================================
                // BAGIAN 1.B: CEK PESANAN MANUAL (FIX DOBEL INPUT KEUANGAN)
                // =========================================================================
                $cekPesanan = DB::table('Pesanan')
                                ->where('nomor_invoice', $orderId)
                                ->lockForUpdate()
                                ->first();

                if ($cekPesanan) {
                    $foundInMainDB = true;
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
                        if (\Illuminate\Support\Facades\Schema::hasColumn('Pesanan', 'status_pesanan')) {
                            $updateData['status_pesanan'] = $statusPesananIndo;
                        }

                        if ($method === 'shipped_packages' && $shippedAt) $updateData['shipped_at'] = Carbon::parse($shippedAt)->timezone('Asia/Jakarta');
                        elseif ($method === 'finished_packages' && $finishedAt) $updateData['finished_at'] = Carbon::parse($finishedAt)->timezone('Asia/Jakarta');
                        elseif (isset($timestampMap[$method])) $updateData[$timestampMap[$method]] = $updateTime;

                        $perluUpdate = true;
                    }

                    // =========================================================================
                    // 💸 [BARU] LOGIKA PENGEMBALIAN DANA (PESANAN MANUAL ADMIN)
                    // =========================================================================
                    $isJustCanceled = ($statusPesananIndo === 'Dibatalkan' && $statusLamaPesanan !== 'Dibatalkan');

                    if ($isJustCanceled && $cekPesanan->payment_method === 'Potong Saldo') {
                        try {
                            $customer = User::where('id_pengguna', $cekPesanan->customer_id)->lockForUpdate()->first();
                            if ($customer) {
                                $refundAmount = $cekPesanan->price; // Total yang dipotong di awal
                                $customer->saldo += $refundAmount;
                                $customer->save();

                                TopUp::create([
                                    'customer_id'    => $customer->id_pengguna,
                                    'amount'         => $refundAmount,
                                    'status'         => 'success',
                                    'payment_method' => 'Refund Batal',
                                    'transaction_id' => 'RFND-' . $cekPesanan->nomor_invoice,
                                    'reference_id'   => $cekPesanan->nomor_invoice,
                                    'created_at'     => $updateTime,
                                ]);
                                Log::info("[WEBHOOK-KA] 💸 Refund Saldo Admin BERHASIL: $orderId sejumlah Rp $refundAmount");
                            }
                        } catch (\Exception $e) {
                            Log::error("[WEBHOOK-KA] ❌ Gagal Refund Saldo Admin: " . $e->getMessage());
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
                // BAGIAN 1.C & 2 (ORDER MARKETPLACE & PERCETAKAN)
                // =========================================================================
                $orderMarketplace = OrderMarketplace::where('invoice_number', $orderId)->first();
                if ($orderMarketplace) {
                    $foundInMainDB = true;
                    $updateMpData = ['updated_at' => $updateTime];

                    if ($awb) $updateMpData['shipping_resi'] = $awb;

                    if ($statusGeneral && $orderMarketplace->status !== 'completed') {
                        $finalStatus = ($statusGeneral === 'shipment') ? 'shipped' : $statusGeneral;
                        $updateMpData['status'] = $finalStatus;
                    }

                    $tableName = $orderMarketplace->getTable();
                    DB::table($tableName)->where('id', $orderMarketplace->id)->update($updateMpData);
                }

                if (!$foundInMainDB) {
                    $this->updatePercetakanDB($orderId, $awb, $statusGeneral);
                }

            } // End Foreach

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
