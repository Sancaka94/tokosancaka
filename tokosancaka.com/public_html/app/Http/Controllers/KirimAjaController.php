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
            ];

            $generalStatusMap = [
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

                $foundInMainDB = false;

                // =========================================================================
                // BAGIAN 1: CEK DI SYSTEM A (MARKETPLACE)
                // =========================================================================
                $order = Order::where('invoice_number', $orderId)->lockForUpdate()->first();

                if ($order) {
                    $foundInMainDB = true;
                    // Simpan status lama untuk validasi perubahan
                    $oldStatus = $order->status;

                    if ($awb) {
                        $order->shipping_reference = $awb;
                    }

                    if ($statusGeneral) {
                        // Hanya update jika status belum final atau status baru beda
                        if ($order->status !== 'completed' && $order->status !== 'canceled') {
                             $order->status = $statusGeneral;
                        }

                        // Timestamp logic (Sama)
                        if ($method === 'shipped_packages' && $shippedAt) $order->shipped_at = Carbon::parse($shippedAt)->timezone('Asia/Jakarta');
                        elseif ($method === 'finished_packages' && $finishedAt) $order->finished_at = Carbon::parse($finishedAt)->timezone('Asia/Jakarta');
                        elseif (isset($timestampMap[$method])) $order->{$timestampMap[$method]} = $updateTime;

                        // ---------------------------------------------------------------------
                        // 🔥 [FIX] PROTEKSI DOBEL PENCAIRAN (MARKETPLACE)
                        // ---------------------------------------------------------------------
                        // Syarat: Status BARU saja berubah jadi 'completed' DAN status lama BUKAN 'completed'
                        if ($statusGeneral === 'completed' && $oldStatus !== 'completed') {

                            // DOUBLE CHECK: Cek apakah saldo revenue sudah pernah masuk di tabel TopUp?
                            // Kita cek berdasarkan transaction_id unik (REV-NoInvoice)
                            $cekRevenueExists = TopUp::where('transaction_id', 'REV-' . $order->invoice_number)->exists();

                            if (!$cekRevenueExists) {
                                $this->processMarketplaceRevenue($order, $updateTime);

                                // --- PROSES DOKU (AUTO CAIR) ---
                                // Logika DOKU dipindah ke sini agar hanya jalan SEKALI saat status berubah
                                $this->processDokuDisbursement($order);
                            } else {
                                Log::warning("[WEBHOOK-KA] 🛑 Saldo Marketplace sudah pernah dicairkan utk $orderId. Skip.");
                            }
                        }
                    }
                    $order->save();
                    Log::info("[WEBHOOK-KA] Updated Marketplace Order: $orderId");
                }

                // =========================================================================
                // 🔥 BAGIAN 1.B: CEK PESANAN MANUAL (FIX DOBEL INPUT KEUANGAN)
                // =========================================================================

                // [PENTING] Tambahkan lockForUpdate agar tidak balapan data (Race Condition)
                // Kita gunakan query builder dengan lock
                $cekPesanan = DB::table('Pesanan')
                                ->where('nomor_invoice', $orderId)
                                ->lockForUpdate()
                                ->first();

                if ($cekPesanan) {
                    $foundInMainDB = true;
                    $updateData = ['updated_at' => now()];

                    // Simpan status lama sebelum update
                    $statusLamaPesanan = $cekPesanan->status;

                    if ($awb) {
                        $updateData['resi'] = $awb;
                    }

                    // UPDATE STATUS
                    if ($statusPesananIndo && $cekPesanan->status !== $statusPesananIndo) {
                        $updateData['status'] = $statusPesananIndo;
                        if (\Illuminate\Support\Facades\Schema::hasColumn('Pesanan', 'status_pesanan')) {
                            $updateData['status_pesanan'] = $statusPesananIndo;
                        }

                        if ($method === 'shipped_packages' && $shippedAt) $updateData['shipped_at'] = Carbon::parse($shippedAt)->timezone('Asia/Jakarta');
                        elseif ($method === 'finished_packages' && $finishedAt) $updateData['finished_at'] = Carbon::parse($finishedAt)->timezone('Asia/Jakarta');
                        elseif (isset($timestampMap[$method])) $updateData[$timestampMap[$method]] = $updateTime;

                        DB::table('Pesanan')->where('nomor_invoice', $orderId)->update($updateData);
                        Log::info("[WEBHOOK-KA] Updated Pesanan Manual: $orderId -> $statusPesananIndo");
                    }

                    // ---------------------------------------------------------
                    // 2. SIMPAN KEUANGAN (FIX DOBEL INPUT)
                    // ---------------------------------------------------------
                    // LOGIKA BARU: Hanya jalankan jika status BERUBAH dari 'Bukan Selesai' MENJADI 'Selesai'.
                    // Ini mencegah input ulang jika webhook 'finished' dikirim 2x.

                    $isJustFinished = ($statusPesananIndo === 'Selesai' && $statusLamaPesanan !== 'Selesai');

                    if ($isJustFinished) {
                        try {
                            Log::info("[WEBHOOK-KA] 🚀 Memicu Simpan Keuangan untuk: $orderId");

                            $pesananModel = Pesanan::where('nomor_invoice', $orderId)->first();

                            if ($pesananModel) {
                                // [OPSIONAL TAPI DISARANKAN]
                                // Sebaiknya di function simpanKeuangan ada cek DB lagi:
                                // if (LaporanKeuangan::where('invoice', $orderId)->exists()) return;

                                \App\Http\Controllers\Admin\PesananController::simpanKeuangan($pesananModel);
                                Log::info("[WEBHOOK-KA] 💰 Keuangan Manual Berhasil Diproses.");
                            }
                        } catch (\Throwable $th) {
                            Log::error("[WEBHOOK-KA] ❌ Gagal Catat Keuangan: " . $th->getMessage());
                        }
                    } else {
                        // Jika webhook 'finished' masuk lagi tapi status di DB sudah 'Selesai',
                        // kita abaikan keuangan agar tidak dobel.
                         if ($statusPesananIndo === 'Selesai' && $statusLamaPesanan === 'Selesai') {
                             Log::info("[WEBHOOK-KA] 🛡️ Keuangan di-skip karena pesanan $orderId sudah berstatus Selesai sebelumnya.");
                         }
                    }
                }

                // =========================================================================
                // BAGIAN 1.C & 2 (SISA KODE SAMA - TIDAK DIUBAH SIGNIFIKAN)
                // =========================================================================
                $orderMarketplace = OrderMarketplace::where('invoice_number', $orderId)->first();
                if ($orderMarketplace) {
                    $foundInMainDB = true;
                    if ($awb) $orderMarketplace->shipping_resi = $awb;
                    if ($statusGeneral && $orderMarketplace->status !== 'completed') {
                        $orderMarketplace->status = $statusGeneral;
                    }
                    $orderMarketplace->save();
                }

                if (!$foundInMainDB) {
                    // Logic Update DB Percetakan (tidak diubah, hanya dirapikan)
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
