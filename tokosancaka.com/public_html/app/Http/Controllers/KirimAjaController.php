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
                'error_packages'           => 'Gagal Kirim Resi', // <--- TAMBAHAN BARU
            ];

            $generalStatusMap = [
                'processed_packages'       => 'processing',
                'shipped_packages'         => 'shipment',
                'canceled_packages'        => 'canceled',
                'finished_packages'        => 'completed',
                'returned_packages'        => 'returning',
                'return_finished_packages' => 'returned',
                'error_packages'           => 'error', // <--- TAMBAHAN BARU (untuk marketplace)
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
                    // 🔥 LOGIKA BARU: TANGKAP RESI RETUR
                    // =========================================================================
                    if (strpos($orderId, '-RTR-') !== false || strpos($orderId, 'RTR-') !== false) {
                        if ($awb) {
                            // Cari data retur yang resinya masih 'PROSES-PICKUP'
                            $returnOrder = \App\Models\ReturnOrder::where('new_resi', 'PROSES-PICKUP')->latest()->first();

                            if ($returnOrder) {
                                $oldResiFallback = $returnOrder->new_resi;

                                // 1. Update tabel return_orders
                                $returnOrder->update(['new_resi' => $awb]);

                                // 2. Update tabel complain_chats (Ganti teks PROSES-PICKUP jadi AWB Asli)
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

                        // Lanjut ke loop berikutnya, skip proses update order biasa
                        continue;
                    }

                $foundInMainDB = false;

                // =========================================================================
                // BAGIAN 1: CEK DI SYSTEM A (MARKETPLACE)
                // =========================================================================
                $order = Order::where('invoice_number', $orderId)->lockForUpdate()->first();

                if ($order) {
                    $foundInMainDB = true;
                    // Simpan status lama untuk validasi perubahan
                    $oldStatus = $order->status;

                    // Siapkan wadah untuk data yang akan di-update ke database
                    $updateOrderData = ['updated_at' => $updateTime];

                    if ($awb) {
                        $updateOrderData['shipping_reference'] = $awb;
                    }

                    if ($statusGeneral) {
                        // Perbaikan: Ganti 'shipment' jadi 'shipped' karena standar e-commerce biasanya pakai shipped
                        $finalStatus = ($statusGeneral === 'shipment') ? 'shipped' : $statusGeneral;

                        // Hanya update jika status belum final atau status baru beda
                        if ($order->status !== 'completed' && $order->status !== 'canceled') {
                             $updateOrderData['status'] = $finalStatus;
                        }

                        // Timestamp logic
                        if ($method === 'shipped_packages' && $shippedAt) $updateOrderData['shipped_at'] = Carbon::parse($shippedAt)->timezone('Asia/Jakarta');
                        elseif ($method === 'finished_packages' && $finishedAt) $updateOrderData['finished_at'] = Carbon::parse($finishedAt)->timezone('Asia/Jakarta');
                        elseif (isset($timestampMap[$method])) $updateOrderData[$timestampMap[$method]] = $updateTime;

                        // ---------------------------------------------------------------------
                        // 🔥 [FIX] TAHAN DANA DI ESCROW (JANGAN LANGSUNG CAIRKAN)
                        // ---------------------------------------------------------------------
                        // Syarat: Status BARU saja berubah jadi 'completed' DAN status lama BUKAN 'completed'
                        if ($finalStatus === 'completed' && $oldStatus !== 'completed') {

                            $cekEscrow = \App\Models\Escrow::where('order_id', $order->id)->exists();

                            if (!$cekEscrow) {
                                // Masukkan dana ke tabel Escrow dengan status "ditahan"
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
                            } else {
                                Log::info("[WEBHOOK-KA] 🛡️ Escrow untuk pesanan $orderId sudah ada. Skip.");
                            }
                        }
                    }

                    // 🔥 PERBAIKAN UTAMA: Eksekusi Update via Query Builder (DB::table)
                    // Ini memastikan data status langsung tertulis ke MySQL tanpa dihalangi Model
                    DB::table('orders')->where('id', $order->id)->update($updateOrderData);

                    Log::info("[WEBHOOK-KA] Updated Marketplace Order: $orderId ke status " . ($updateOrderData['status'] ?? $oldStatus));
                }

                    // =========================================================================
                    // 🔥 BAGIAN 1.B: CEK PESANAN MANUAL (FIX DOBEL INPUT KEUANGAN)
                    // =========================================================================

                    $cekPesanan = DB::table('Pesanan')
                                    ->where('nomor_invoice', $orderId)
                                    ->lockForUpdate()
                                    ->first();

                    if ($cekPesanan) {
                        $foundInMainDB = true;
                        $updateData = ['updated_at' => now()];
                        $statusLamaPesanan = $cekPesanan->status;

                        $perluUpdate = false; // Buat penanda apakah ada data yang berubah

                        // 1. CEK PERUBAHAN RESI/AWB
                        if ($awb && $cekPesanan->resi !== $awb) {
                            $updateData['resi'] = $awb;
                            $perluUpdate = true; // Tandai bahwa resi harus diupdate
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

                            $perluUpdate = true; // Tandai bahwa status harus diupdate
                        }

                        // 3. EKSEKUSI UPDATE KE DATABASE (Jika Resi atau Status berubah)
                        if ($perluUpdate) {
                            DB::table('Pesanan')->where('nomor_invoice', $orderId)->update($updateData);
                            Log::info("[WEBHOOK-KA] Updated Pesanan: $orderId | AWB: " . ($updateData['resi'] ?? $cekPesanan->resi) . " | Status: " . ($updateData['status'] ?? $cekPesanan->status));
                        }

                        // ---------------------------------------------------------
                        // 2. SIMPAN KEUANGAN (Sisa kode di bawahnya biarkan tetap sama)
                        // ---------------------------------------------------------

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
                    $updateMpData = ['updated_at' => $updateTime];

                    if ($awb) $updateMpData['shipping_resi'] = $awb;

                    if ($statusGeneral && $orderMarketplace->status !== 'completed') {
                        $finalStatus = ($statusGeneral === 'shipment') ? 'shipped' : $statusGeneral;
                        $updateMpData['status'] = $finalStatus;
                    }

                    // Gunakan nama tabel aslinya, sesuaikan jika namanya bukan 'order_marketplaces'
                    $tableName = $orderMarketplace->getTable();
                    DB::table($tableName)->where('id', $orderMarketplace->id)->update($updateMpData);
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
