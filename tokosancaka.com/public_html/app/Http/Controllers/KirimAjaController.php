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

            // MAPPING STATUS MARKETPLACE & PERCETAKAN (DB B)
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

            // 2. LOOP DATA WEBHOOK
            foreach ($dataArray as $data) {
                if (!$data) continue;

                $orderId = $data['order_id'] ?? null;
                $awb     = $data['awb'] ?? null;

                if (!$orderId) continue;

                // LOGGING CHECK AWB
                if (!empty($awb)) {
                    Log::info("[WEBHOOK-KA] ЁЯФН DATA CHECK | Order: $orderId | AWB Ditemukan: $awb");
                } else {
                    Log::warning("[WEBHOOK-KA] тЪая╕П DATA CHECK | Order: $orderId | AWB TIDAK ADA di payload ini.");
                }

                // Ambil Waktu & Convert ke WIB
                $shippedAt  = $data['shipped_at'] ?? null;
                $finishedAt = $data['finished_at'] ?? null;
                $updateTime = now()->timezone('Asia/Jakarta');

                $foundInMainDB = false;

                // =========================================================================
                // BAGIAN 1: CEK DI SYSTEM A (DB UTAMA - TOKO SANCAKA)
                // =========================================================================

                // 1.A. Cek Model Order (Marketplace)
                $order = Order::where('invoice_number', $orderId)->lockForUpdate()->first();
                if ($order) {
                    $foundInMainDB = true;

                    // [FIX LOGIKA] FORCE UPDATE AWB
                    // Jika API kirim AWB, timpa shipping_reference (meskipun isinya Kode Booking)
                    if ($awb) {
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

                            // ---------------------------------------------------------------------
                        // 🔥 [BLOK 1.A] AUTO-CAIR DOKU (MARKETPLACE)
                        // ---------------------------------------------------------------------
                        try {
                            \Illuminate\Support\Facades\Log::info("[WEBHOOK-KA] 🔥 [BLOK 1.A] MEMULAI AUTO-CAIR DOKU...");

                            // 1. Ambil Data Toko
                            $store = null;
                            if (!empty($order->store_id)) {
                                $store = \App\Models\Store::find($order->store_id);
                            }

                            // 2. Cek Dompet DOKU
                            if ($store && !empty($store->doku_sac_id)) {
                                \Illuminate\Support\Facades\Log::info("[WEBHOOK-KA] 🏦 Toko: {$store->name} | SAC: {$store->doku_sac_id}");

                                // 3. Tentukan Nominal (Total Tagihan)
                                // Gunakan total_price atau subtotal (sesuaikan kolom DB Anda)
                                $nominalCair = (int) ($order->total_price ?? $order->subtotal ?? 0);

                                if ($nominalCair > 0) {
                                    $dokuService = app(\App\Services\DokuJokulService::class);
                                    $mainSacId = config('doku.main_sac_id');

                                    if ($mainSacId) {
                                        $transferResult = $dokuService->transferIntra(
                                            $mainSacId,           // Dari: Admin
                                            $store->doku_sac_id,  // Ke: Penjual
                                            $nominalCair
                                        );

                                        \Illuminate\Support\Facades\Log::info("[WEBHOOK-KA] 📡 Response DOKU:", $transferResult);

                                        if (($transferResult['success'] ?? false) === true) {
                                            \Illuminate\Support\Facades\Log::info("[WEBHOOK-KA] ✅ SUKSES CAIR! Rp ".number_format($nominalCair));
                                        } else {
                                            \Illuminate\Support\Facades\Log::error("[WEBHOOK-KA] ❌ Gagal Transfer: " . ($transferResult['message'] ?? 'Unknown Error'));
                                        }
                                    } else {
                                        \Illuminate\Support\Facades\Log::error("[WEBHOOK-KA] ❌ Main SAC ID Kosong.");
                                    }
                                }
                            } else {
                                \Illuminate\Support\Facades\Log::warning("[WEBHOOK-KA] ⚠️ Toko tidak punya SAC ID atau Store ID null.");
                            }
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error("[WEBHOOK-KA] ❌ Crash DOKU (1.A): " . $e->getMessage());
                        }
                        // ---------------------------------------------------------------------
                        }
                    }
                    $order->save();
                    Log::info("[WEBHOOK-KA] Updated Marketplace Order: $orderId");
                }

                // =========================================================================
            // 🔥🔥🔥 INI BAGIAN YANG ANDA GANTI / PASTE 🔥🔥🔥
            // =========================================================================

            // 1.B. Cek Pesanan Manual (SCK) - Direct DB Update
            $cekPesanan = DB::table('Pesanan')->where('nomor_invoice', $orderId)->first();
            if ($cekPesanan) {
                $foundInMainDB = true;
                $updateData = ['updated_at' => now()];

                // [FIX LOGIKA] FORCE UPDATE RESI
                if ($awb) {
                    $updateData['resi'] = $awb;
                }

                // ---------------------------------------------------------
                // 1. UPDATE STATUS (Hanya jika status berubah)
                // ---------------------------------------------------------
                if ($statusPesananIndo && $cekPesanan->status !== $statusPesananIndo) {
                    $updateData['status'] = $statusPesananIndo;
                    if (\Schema::hasColumn('Pesanan', 'status_pesanan')) {
                        $updateData['status_pesanan'] = $statusPesananIndo;
                    }

                    // Timestamp logic
                    if ($method === 'shipped_packages' && $shippedAt) $updateData['shipped_at'] = Carbon::parse($shippedAt)->timezone('Asia/Jakarta');
                    elseif ($method === 'finished_packages' && $finishedAt) $updateData['finished_at'] = Carbon::parse($finishedAt)->timezone('Asia/Jakarta');
                    elseif (isset($timestampMap[$method])) $updateData[$timestampMap[$method]] = $updateTime;

                    // Eksekusi Update Status
                    DB::table('Pesanan')->where('nomor_invoice', $orderId)->update($updateData);
                    Log::info("[WEBHOOK-KA] Updated Pesanan Manual: $orderId -> $statusPesananIndo");
                }

                // ---------------------------------------------------------
                // 2. SIMPAN KEUANGAN (DIPISAH DARI LOGIKA STATUS)
                // ---------------------------------------------------------
                // Jalankan INI jika webhook adalah 'finished_packages' atau status 'Selesai'.
                // KITA COPOT SYARAT "&& $cekPesanan->status !== 'Selesai'" AGAR TETAP JALAN.
                if ($statusPesananIndo === 'Selesai' || $method === 'finished_packages') {
                    try {
                        Log::info("[WEBHOOK-KA] 🚀 Memicu Simpan Keuangan untuk: $orderId");

                        $pesananModel = Pesanan::where('nomor_invoice', $orderId)->first();

                        if ($pesananModel) {
                            // Panggil fungsi di AdminController
                            // Pastikan method di AdminController namanya 'simpanKeuangan' (tanpa Ke)
                            \App\Http\Controllers\Admin\PesananController::simpanKeuangan($pesananModel);
                            Log::info("[WEBHOOK-KA] 💰 Keuangan Manual Berhasil Diproses.");
                        } else {
                            Log::error("[WEBHOOK-KA] Model Pesanan tidak ditemukan saat mau catat keuangan.");
                        }
                    } catch (\Throwable $th) {
                        Log::error("[WEBHOOK-KA] ❌ Gagal Catat Keuangan: " . $th->getMessage());
                    }

                }
            }
            // =========================================================================
            // 🔥🔥🔥 AKHIR BAGIAN YANG ANDA PASTE 🔥🔥🔥
            // =========================================================================

                // 1.C. Cek OrderMarketplace (Model Lain)
                $orderMarketplace = OrderMarketplace::where('invoice_number', $orderId)->first();
                if ($orderMarketplace) {
                    $foundInMainDB = true;

                    // [FIX LOGIKA] FORCE UPDATE RESI
                    // Timpa shipping_resi dengan AWB asli
                    if ($awb) {
                        $orderMarketplace->shipping_resi = $awb;
                    }

                    if ($statusGeneral && $orderMarketplace->status !== 'completed') $orderMarketplace->status = $statusGeneral;
                    $orderMarketplace->save();
                }

                // =========================================================================
                // BAGIAN 2: CEK DI SYSTEM B (PERCETAKAN) - JIKA TIDAK ADA DI A
                // =========================================================================

                if (!$foundInMainDB) {
                    try {
                        $percetakanDB = DB::connection('mysql_second');
                        $orderPercetakan = $percetakanDB->table('orders')
                                                        ->where('order_number', $orderId)
                                                        ->orWhere('shipping_ref', $orderId)
                                                        ->first();

                        if ($orderPercetakan) {
                            $updateDataB = ['updated_at' => now()];

                            // [FIX LOGIKA] FORCE UPDATE REF KE AWB
                            // Timpa shipping_ref yang isinya kode booking menjadi AWB asli
                            if ($awb) {
                                $updateDataB['shipping_ref'] = $awb;
                            }

                            if ($statusGeneral && $orderPercetakan->status !== 'completed') {
                                $updateDataB['status'] = $statusGeneral;
                            }

                            // Eksekusi Update ke DB Kedua
                            if (count($updateDataB) > 1) {
                                $percetakanDB->table('orders')
                                    ->where('id', $orderPercetakan->id)
                                    ->update($updateDataB);

                                Log::info("[WEBHOOK-KA] тЬЕ Updated DB PERCETAKAN: $orderId -> $statusGeneral");
                            }
                        } else {
                            Log::warning("[WEBHOOK-KA] тЪая╕П Order ID tidak ditemukan di Sancaka Utama maupun Percetakan: $orderId");
                        }

                    } catch (\Exception $e) {
                        Log::error("[WEBHOOK-KA] Error koneksi ke DB Percetakan: " . $e->getMessage());
                    }
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
