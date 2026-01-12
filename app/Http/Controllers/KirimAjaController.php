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

        Log::info('Handle KiriminAja Webhook Payload Received', ['payload' => $payload]);

        $method = $payload['method'] ?? null;
        $dataArray = $payload['data'] ?? [];

        if (empty($dataArray)) {
            return response()->json(['error' => 'Invalid payload, data[] is missing'], 400);
        }

        try {
            DB::beginTransaction();

            // 1. Mapping Status
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
            $orderStatus    = $orderStatusMap[$method] ?? null;

            foreach ($dataArray as $data) {
                if (!$data) continue;

                $orderId = $data['order_id'] ?? null;
                $awb     = $data['awb'] ?? null; // Nomor Resi dari Webhook

                if (!$orderId) {
                    Log::warning('Webhook KiriminAja: Order ID missing', $data);
                    continue;
                }

                // Lock for update untuk mencegah race condition saat update saldo
                $order = Order::where('invoice_number', $orderId)->lockForUpdate()->first();
                $pesanan = Pesanan::where('nomor_invoice', $orderId)->lockForUpdate()->first();
                $orderMarketplace = OrderMarketplace::where('invoice_number', $orderId)->lockForUpdate()->first();

                // Ambil timestamp dari payload
                $shippedAt = $data['shipped_at'] ?? null;
                $finishedAt = $data['finished_at'] ?? null;
                $updateTime = now()->timezone('Asia/Jakarta'); // Default time

                // =========================================================================
                // A. UPDATE RESI / AWB (AGAR TIDAK NULL)
                // =========================================================================
                // Logika: Jika di webhook ada AWB, dan di DB masih kosong, isi segera.
                if (!empty($awb)) {
                    // 1. Untuk Model Order
                    if ($order && empty($order->shipping_reference)) {
                        $order->shipping_reference = $awb;
                        $order->save();
                        Log::info("Resi Order Updated: $orderId -> $awb");
                    }
                    // 2. Untuk Model Pesanan
                    if ($pesanan && empty($pesanan->resi)) {
                        $pesanan->resi = $awb;
                        $pesanan->save();
                        Log::info("Resi Pesanan Updated: $orderId -> $awb");
                    }
                    // 3. Untuk Model OrderMarketplace
                    if ($orderMarketplace && empty($orderMarketplace->shipping_resi)) {
                        $orderMarketplace->shipping_resi = $awb;
                        $orderMarketplace->save();
                        Log::info("Resi OrderMarketplace Updated: $orderId -> $awb");
                    }
                }

                // =========================================================================
                // B. UPDATE STATUS & SALDO (MODEL ORDER)
                // =========================================================================
                if ($order && $orderStatus) {
                    // Cek status sebelumnya agar saldo tidak masuk 2x
                    $previousStatus = $order->status;

                    // Update Status jika berbeda
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

                        // --- LOGIKA KEUANGAN (SALDO) ---
                        // Hanya jalankan jika status BARU saja berubah jadi 'completed'
                        // dan status SEBELUMNYA bukan 'completed'
                        if ($orderStatus === 'completed' && $previousStatus !== 'completed') {
                            $this->processRevenue($order, $updateTime);
                        }

                        $order->save();
                    }
                }

                // =========================================================================
                // C. UPDATE STATUS (MODEL PESANAN)
                // =========================================================================
                if ($pesanan && $pesananStatus) {
                    if ($pesanan->status !== 'Selesai' && $pesanan->status !== $pesananStatus) {
                        $pesanan->status = $pesananStatus;
                        // Sesuaikan kolom status lain jika ada (misal status_pesanan)
                        if (\Schema::hasColumn('pesanans', 'status_pesanan')) {
                            $pesanan->status_pesanan = $pesananStatus;
                        }

                        if ($method === 'shipped_packages' && $shippedAt) {
                            $pesanan->shipped_at = Carbon::parse($shippedAt)->timezone('Asia/Jakarta');
                        } elseif ($method === 'finished_packages' && $finishedAt) {
                            $pesanan->finished_at = Carbon::parse($finishedAt)->timezone('Asia/Jakarta');
                        } elseif (isset($timestampMap[$method]) && \Schema::hasColumn('pesanans', $timestampMap[$method])) {
                            $pesanan->{$timestampMap[$method]} = $updateTime;
                        }

                        $pesanan->save();
                    }
                }

                // =========================================================================
                // D. UPDATE STATUS (MODEL ORDER MARKETPLACE)
                // =========================================================================
                if ($orderMarketplace && $orderStatus) {
                    if ($orderMarketplace->status !== 'completed' && $orderMarketplace->status !== $orderStatus) {
                        $orderMarketplace->status = $orderStatus;
                        $orderMarketplace->save();
                    }
                }

                if (!$order && !$pesanan && !$orderMarketplace) {
                    Log::warning('Webhook: Order ID tidak ditemukan di semua tabel', ['order_id' => $orderId]);
                }

            } // End Foreach

            DB::commit();
            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal memproses Webhook KiriminAja', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'payload' => $payload
            ]);
            return response()->json(['success' => false, 'message' => 'Internal Error'], 500);
        }
    }

    /**
     * Helper function untuk memproses Keuangan (Saldo Seller)
     */
    private function processRevenue($order, $updateTime)
    {
        try {
            $store = Store::find($order->store_id);
            if (!$store) return;

            $seller = User::where('id_pengguna', $store->user_id)->first();
            if (!$seller) return;

            // Pastikan menggunakan nilai yang benar (subtotal / total_price - potongan admin jika ada)
            $revenue = $order->subtotal;

            // Tambah Saldo
            $seller->saldo += $revenue;
            $seller->save();

            // Catat Riwayat Transaksi (TopUp/Mutasi)
            TopUp::create([
                'customer_id'      => $seller->id_pengguna,
                'amount'           => $revenue,
                'status'           => 'success',
                'payment_method'   => 'marketplace_revenue',
                'transaction_id'   => 'REV-' . $order->invoice_number,
                'reference_id'     => $order->invoice_number,
                'created_at'       => $order->finished_at ?? $updateTime,
            ]);

            Log::info('Saldo Seller berhasil ditambah via Webhook.', [
                'invoice' => $order->invoice_number,
                'amount' => $revenue
            ]);

        } catch (\Exception $e) {
            Log::error('Webhook Revenue Error', ['invoice' => $order->invoice_number, 'msg' => $e->getMessage()]);
            throw $e; // Lempar error agar transaksi di-rollback
        }
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
