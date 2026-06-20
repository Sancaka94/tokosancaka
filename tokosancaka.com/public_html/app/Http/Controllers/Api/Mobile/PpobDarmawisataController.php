<?php

namespace App\Http\Controllers\Api\Mobile;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PpobDarmawisataController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

  private function getPpobLogo($groupName, $productName)
    {
        $group = strtoupper($groupName ?? '');
        $name  = strtoupper($productName ?? '');
        $base  = 'https://tokosancaka.com/public/storage/logo-ppob/';
        $textToSearch = $group . ' ' . $name; // Gabungkan untuk sekali pencarian

        $mappings = [
            'HALO' => 'halo.png',
            'TELKOMSEL' => 'telkomsel.png',
            'INDOSAT' => 'indosat.png',
            'XL' => 'xl.png',
            'AXIS' => 'axis.png',
            'TRI' => 'tri.png',
            'THREE' => 'tri.png',
            'SMARTFREN' => 'smartfren.png',
            'BY.U' => 'by.u.png',
            'PLN PASCA' => 'pln%20pascabayar.png',
            'PASCA PLN' => 'pln%20pascabayar.png',
            'PLN' => 'pln.png',
            'BPJS' => 'bpjs.png',
            'DANA' => 'dana.png',
            'OVO' => 'ovo.png',
            'GOPAY' => 'go%20pay.png',
            'GO PAY' => 'go%20pay.png',
            'SHOPEE' => 'shopee%20pay.png',
            'FREE FIRE' => 'free%20fire.png',
            'MOBILE LEGEND' => 'mobile%20legends.png',
            'K-VISION' => 'k-vision%20dan%20gol.png',
            'GOL' => 'k-vision%20dan%20gol.png',
            'PGN' => 'pertamina%20gas.png',
            'GAS' => 'pertamina%20gas.png',
        ];

        foreach ($mappings as $keyword => $filename) {
            if (str_contains($textToSearch, $keyword)) {
                return $base . $filename;
            }
        }

        return $base . 'default.png';
    }

    public function ppobProductGroup(Request $request)
    {
        Log::info("\n========== [PPOB PRODUCT GROUP - START] ==========");

        $validator = Validator::make($request->all(), [
            'accessToken' => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::warning("PPOB Product Group Validasi Gagal: ", $validator->errors()->toArray());
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        $payload = [
            'accessToken' => $request->accessToken
        ];

        return $this->forwardRequest('PPOB/ProductGroup', $payload);
    }

    public function ppobProductList(Request $request)
    {
        Log::info("\n========== [PPOB PRODUCT LIST - START] ==========");

        $validator = Validator::make($request->all(), [
            'productGroup' => 'required|string',
            'accessToken'  => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        $payload = [
            'productGroup' => $request->productGroup,
            'accessToken'  => $request->accessToken
        ];

        // Ambil data produk dari Darmawisata
        $response = $this->forwardRequest('PPOB/Product', $payload);
        $json = json_decode($response->getContent(), true);

        // --- INI BAGIAN YANG DIUBAH (Auto Mapping Logo) ---
        if (isset($json['productList']) && is_array($json['productList'])) {
            foreach ($json['productList'] as &$product) {
                // Terapkan Smart Mapping ke setiap produk
                $product['iconUrl'] = $this->getPpobLogo($product['group'] ?? '', $product['name'] ?? '');
            }
        }

        return response()->json($json);
    }

    public function ppobInquiry(Request $request)
    {
        Log::info("\n========== [PPOB INQUIRY - START] ==========");

        $validator = Validator::make($request->all(), [
            'productCode'    => 'required|string',
            'customerID'     => 'required|string',
            'customerMSISDN' => 'nullable|string',
            'accessToken'    => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        $payload = [
            'productCode'    => $request->productCode,
            'customerID'     => $request->customerID,
            'customerMSISDN' => $request->customerMSISDN ?? "",
            'accessToken'    => $request->accessToken
        ];

        return $this->forwardRequest('PPOB/Inquiry', $payload);
    }

   public function ppobPayment(Request $request)
    {
        Log::info("\n========== [PPOB PAYMENT - START] ==========");

        $validator = Validator::make($request->all(), [
            'billingReferenceID' => 'required|string',
            'productCode'        => 'required|string',
            'customerID'         => 'required|string',
            'sellPrice'          => 'required|numeric',
            'accessToken'        => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $totalPrice = (float) $request->sellPrice;
        $userId = $user->id_pengguna ?? $user->id;

        if (!$user || $user->saldo < $totalPrice) {
            return response()->json([
                'status' => 'FAILED',
                'message' => 'Saldo tidak cukup. Butuh: Rp ' . number_format($totalPrice, 0, ',', '.')
            ], 400);
        }

        $orderId = null;

        try {
            // 1. Potong saldo & catat transaksi PENDING dulu
            $orderId = DB::transaction(function () use ($request, $userId, $totalPrice) {
                DB::table('Pengguna')->where('id_pengguna', $userId)->decrement('saldo', $totalPrice);

                return DB::table('dw_ppob_transactions')->insertGetId([
                    'user_id'              => $userId,
                    'product_code'         => $request->productCode,
                    'customer_id'          => $request->customerID,
                    'billing_reference_id' => $request->billingReferenceID,
                    'sell_price'           => $totalPrice,
                    'status'               => 'PENDING_PAYMENT',
                    'created_at'           => now(),
                    'updated_at'           => now(),
                ]);
            });

            // 2. Siapkan payload ke Darmawisata
            $payload = [
                'billingReferenceID' => $request->billingReferenceID,
                'accessToken'        => $request->accessToken
            ];

            // 3. Eksekusi API
            $response = $this->forwardRequest('PPOB/Payment', $payload);
            $json = json_decode($response->getContent(), true);

            // 4. Evaluasi Response
            if (isset($json['status']) && $json['status'] === 'SUCCESS') {
                DB::table('dw_ppob_transactions')->where('id', $orderId)->update([
                    'status'       => 'SUCCESS',
                    'resp_message' => $json['respMessage'] ?? 'Transaksi Berhasil',
                    'updated_at'   => now(),
                ]);

                return response()->json([
                    'status'  => 'SUCCESS',
                    'message' => 'Pembayaran PPOB Berhasil!',
                    'data'    => $json
                ]);
            } else {
                // JIKA FAILED TAPI PESANNYA PENDING (Masuk antrean provider)
                $respMsg = strtolower($json['respMessage'] ?? '');

                if (str_contains($respMsg, 'pending')) {
                    DB::table('dw_ppob_transactions')->where('id', $orderId)->update([
                        'status'       => 'PENDING_PROVIDER',
                        'resp_message' => 'Sedang diproses oleh provider (Pending Antrean).',
                        'updated_at'   => now(),
                    ]);

                    return response()->json([
                        'status'  => 'SUCCESS', // Berikan response success ke frontend biar masuk riwayat
                        'message' => 'Pembayaran sedang diproses oleh provider.',
                        'data'    => $json
                    ]);
                }

                // JIKA BENAR-BENAR GAGAL INSTAN (Bukan pending), BARU REFUND SALDO
                DB::transaction(function () use ($userId, $totalPrice, $orderId, $json) {
                    DB::table('Pengguna')->where('id_pengguna', $userId)->increment('saldo', $totalPrice);
                    DB::table('dw_ppob_transactions')->where('id', $orderId)->update([
                        'status'       => 'FAILED',
                        'resp_message' => $json['respMessage'] ?? 'Gagal dari provider',
                        'updated_at'   => now(),
                    ]);
                });

                return response()->json(['status' => 'FAILED', 'message' => $json['respMessage'] ?? 'Transaksi Gagal']);
            }

       } catch (\Exception $e) {
            Log::error("FATAL ERROR [PPOB Payment]: " . $e->getMessage());
            if ($orderId) {
                // REFUND SALDO KARENA SYSTEM ERROR DARI SANCAKA SENDIRI
                DB::table('Pengguna')->where('id_pengguna', $userId)->increment('saldo', $totalPrice);

                DB::table('dw_ppob_transactions')
                    ->where('id', $orderId)
                    ->update([
                        'status' => 'FAILED_SYSTEM_ERROR',
                        'resp_message' => 'System Error: Saldo telah dikembalikan.',
                        'updated_at' => now()
                    ]);
            }
            return response()->json(['status' => 'FAILED', 'message' => 'System Error: ' . $e->getMessage()], 500);
        }
    }

    public function ppobTransactionDetail(Request $request)
    {
        Log::info("\n========== [PPOB TRANSACTION DETAIL - START] ==========");
        $validator = Validator::make($request->all(), [
            'customerID'         => 'required|string',
            'billingReferenceID' => 'required|string',
            'accessToken'        => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        $payload = [
            'customerID'         => $request->customerID,
            'billingReferenceID' => $request->billingReferenceID,
            'accessToken'        => $request->accessToken
        ];

        return $this->forwardRequest('PPOB/TransactionDetail', $payload);
    }

    public function ppobHistory(Request $request)
    {
        Log::info("\n========== [PPOB HISTORY - START] ==========");
        try {
            $user = $request->user();
            $userId = $user->id_pengguna ?? $user->id;

            $query = DB::table('dw_ppob_transactions as t')
                ->select(
                    't.id', 't.product_code', 't.customer_id',
                    't.billing_reference_id', 't.sell_price',
                    't.status', 't.created_at', 't.resp_message', 't.user_id'
                )
                ->orderBy('t.created_at', 'desc');

            $role = strtolower($user->role ?? '');
            if ($role !== 'admin' && $userId != 4) {
                $query->where('t.user_id', $userId);
            }

            $orders = $query->get();

            // --- INI BAGIAN YANG DIUBAH (Auto Mapping Logo di Riwayat) ---
            $formattedData = $orders->map(function ($order) {
                return [
                    'id'                 => $order->id,
                    'userId'             => $order->user_id,
                    'productCode'        => $order->product_code,
                    'productName'        => $order->product_code, // Bisa di-query detailnya jika perlu
                    // Gunakan fungsi smart mapping untuk History juga
                    'iconUrl'            => $this->getPpobLogo($order->product_code, $order->product_code),
                    'customerID'         => $order->customer_id,
                    'billingReferenceID' => $order->billing_reference_id,
                    'sellPrice'          => (float) $order->sell_price,
                    'status'             => $order->status,
                    'message'            => $order->resp_message,
                    'transactionDate'    => $order->created_at,
                ];
            });

            return response()->json(['status' => 'SUCCESS', 'data' => $formattedData], 200);

        } catch (\Exception $e) {
            Log::error("FATAL ERROR [PPOB History]: " . $e->getMessage());
            return response()->json(['status' => 'FAILED', 'message' => 'Sistem Error.'], 500);
        }
    }

    /**
     * =========================================================
     * MENERIMA WEBHOOK DARI DOKU (PASCABAYAR DARMAWISATA)
     * =========================================================
     */
    public function handleDokuCallback($data)
    {
        try {
            $invoiceNumber = $data['order']['invoice_number']; // Contoh: PPOBD-123
            $transactionId = (int) str_replace('PPOBD-', '', $invoiceNumber); // Ambil ID aslinya

            // 1. Cari data pesanan di tabel Pascabayar (dw_ppob_transactions)
            $order = DB::table('dw_ppob_transactions')->where('id', $transactionId)->first();

            if (!$order) {
                Log::warning("DOKU Webhook PPOB Darma: Transaksi $transactionId tidak ditemukan.");
                return response()->json(['status' => 'error', 'message' => 'Transaction not found']);
            }

            // 2. Cegah double-update jika sudah lunas
            if ($order->status !== 'PENDING_PAYMENT') {
                Log::info("DOKU Webhook PPOB Darma: Transaksi $transactionId sudah diproses sebelumnya.");
                return response()->json(['status' => 'success', 'message' => 'Already processed']);
            }

            // 3. Update status menjadi SUCCESS / DIBAYAR
            DB::table('dw_ppob_transactions')->where('id', $transactionId)->update([
                'status' => 'PAID_PROCESSING', // Sancaka sudah terima uang, siap diteruskan ke Darmawisata
                'resp_message' => 'Pembayaran DOKU Sukses. Menunggu proses provider.',
                'updated_at' => now()
            ]);

            Log::info("DOKU Webhook PPOB Darma: Status pesanan $transactionId berhasil diupdate ke PAID_PROCESSING.");

            // Catatan: Di titik ini uang sudah masuk ke Sancaka.
            // Kamu bisa men-trigger cronjob atau hit langsung API Payment Darmawisata di sini.

            return response()->json(['status' => 'success', 'message' => 'Webhook PPOB Darmawisata berhasil']);

        } catch (\Exception $e) {
            Log::error("Webhook PPOB Darmawisata Error: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Sistem Error'], 500);
        }
    }

    /**
     * =========================================================
     * SINKRONISASI STATUS TRANSAKSI PENDING KE DARMAWISATA
     * =========================================================
     */
    public function syncPendingTransaction(Request $request)
    {
        Log::info("\n========== [PPOB SYNC TRANSACTION - START] ==========");

        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|integer', // ID transaksi lokal di tabel dw_ppob_transactions
            'accessToken'    => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        try {
            // 1. Ambil data transaksi lokal yang statusnya masih PENDING atau PROCESSING
            $order = DB::table('dw_ppob_transactions')
                ->where('id', $request->transaction_id)
                ->first();

            if (!$order) {
                return response()->json(['status' => 'FAILED', 'message' => 'Transaksi tidak ditemukan.']);
            }

            if (in_array($order->status, ['SUCCESS', 'FAILED'])) {
                return response()->json(['status' => 'SUCCESS', 'message' => 'Transaksi sudah berstatus final: ' . $order->status]);
            }

            // 2. Siapkan Payload untuk endpoint PPOB/TransactionDetail
            $payload = [
                'customerID'         => $order->customer_id,
                'billingReferenceID' => $order->billing_reference_id,
                'accessToken'        => $request->accessToken
            ];

            // 3. Tembak API Darmawisata
            $response = $this->forwardRequest('PPOB/TransactionDetail', $payload);
            $json = json_decode($response->getContent(), true);

            // 4. Evaluasi Response dari Darmawisata
            if (isset($json['status']) && $json['status'] === 'SUCCESS' && isset($json['detail'])) {

                $providerStatus = strtoupper($json['detail']['transactionStatus']); // Status asli dari provider[cite: 1]

                if ($providerStatus === 'SUCCESS') {
                    // TRANSAKSI BERHASIL
                    DB::table('dw_ppob_transactions')->where('id', $order->id)->update([
                        'status'       => 'SUCCESS',
                        'resp_message' => 'Transaksi berhasil diproses oleh provider.',
                        'updated_at'   => now(),
                    ]);

                    return response()->json(['status' => 'SUCCESS', 'message' => 'Status diperbarui menjadi SUCCESS.']);

                } elseif ($providerStatus === 'FAILED') {
                    // TRANSAKSI GAGAL - KEMBALIKAN SALDO USER
                    DB::transaction(function () use ($order) {
                        DB::table('Pengguna')->where('id_pengguna', $order->user_id)->increment('saldo', $order->sell_price);

                        DB::table('dw_ppob_transactions')->where('id', $order->id)->update([
                            'status'       => 'FAILED',
                            'resp_message' => 'Transaksi gagal di sisi provider. Saldo dikembalikan.',
                            'updated_at'   => now(),
                        ]);
                    });

                    return response()->json(['status' => 'SUCCESS', 'message' => 'Transaksi gagal, saldo berhasil dikembalikan.']);

                } else {
                    // MASIH PENDING
                    return response()->json(['status' => 'PENDING', 'message' => 'Transaksi masih diproses oleh provider.']);
                }

            } else {
                return response()->json(['status' => 'FAILED', 'message' => $json['respMessage'] ?? 'Gagal mengecek status ke provider.']);
            }

        } catch (\Exception $e) {
            Log::error("FATAL ERROR [PPOB Sync]: " . $e->getMessage());
            return response()->json(['status' => 'FAILED', 'message' => 'System Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * =========================================================
     * HAPUS RIWAYAT TRANSAKSI SECARA MASSAL (KHUSUS ADMIN)
     * =========================================================
     */
    public function bulkDestroyHistory(Request $request)
    {
        $user = $request->user();

        // Proteksi: Hanya user ID 4 (atau role admin) yang boleh eksekusi
        $userId = $user->id_pengguna ?? $user->id;
        if ($userId != 4) {
            return response()->json(['status' => 'FAILED', 'message' => 'Anda tidak memiliki akses.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'ids'   => 'required|array',
            'ids.*' => 'integer'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        try {
            DB::table('dw_ppob_transactions')->whereIn('id', $request->ids)->delete();
            return response()->json(['status' => 'SUCCESS', 'message' => 'Riwayat berhasil dihapus']);
        } catch (\Exception $e) {
            Log::error("Bulk Delete PPOB Error: " . $e->getMessage());
            return response()->json(['status' => 'FAILED', 'message' => 'Gagal menghapus data di database.'], 500);
        }
    }

    /**
     * =========================================================
     * PPOB OPEN PAYMENT (Untuk Prudential, Asuransi, dll)
     * =========================================================
     */
    public function ppobOpenPayment(Request $request)
    {
        Log::info("\n========== [PPOB OPEN PAYMENT - START] ==========");

        // Validasi membutuhkan input sellPrice (Nominal yang diketik user)
        $validator = Validator::make($request->all(), [
            'productCode'    => 'required|string',
            'customerID'     => 'required|string',
            'customerMSISDN' => 'nullable|string',
            'sellPrice'      => 'required|numeric', // Nominal dari input user WAJIB ada
            'accessToken'    => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'FAILED', 'errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $totalPrice = (float) $request->sellPrice;
        $userId = $user->id_pengguna ?? $user->id;

        if (!$user || $user->saldo < $totalPrice) {
            return response()->json([
                'status' => 'FAILED',
                'message' => 'Saldo tidak cukup. Butuh: Rp ' . number_format($totalPrice, 0, ',', '.')
            ], 400);
        }

        $orderId = null;
        // Karena kita men-skip Inquiry, kita tidak punya billingReferenceID dari Darmawisata.
        // Solusinya: Kita generate Reference ID lokal milik kita sendiri.
        $billingRef = 'OP-' . time() . '-' . rand(100, 999);

        try {
            // 1. Potong saldo & catat transaksi PENDING dulu
            $orderId = DB::transaction(function () use ($request, $userId, $totalPrice, $billingRef) {
                DB::table('Pengguna')->where('id_pengguna', $userId)->decrement('saldo', $totalPrice);

                return DB::table('dw_ppob_transactions')->insertGetId([
                    'user_id'              => $userId,
                    'product_code'         => $request->productCode,
                    'customer_id'          => $request->customerID,
                    'billing_reference_id' => $billingRef,
                    'sell_price'           => $totalPrice,
                    'status'               => 'PENDING_PAYMENT',
                    'created_at'           => now(),
                    'updated_at'           => now(),
                ]);
            });

            // 2. Siapkan Payload Khusus Darmawisata Open Payment
            $payload = [
                'productCode'        => $request->productCode,
                'customerID'         => $request->customerID,
                'customerMSISDN'     => $request->customerMSISDN ?? "",
                'agentPayment'       => $totalPrice, // PERBEDAAN UTAMA: Parameter nominal pembayaran
                'billingReferenceID' => $billingRef, // Masukkan Reference ID yang kita generate
                'accessToken'        => $request->accessToken
            ];

            // 3. Eksekusi API (PERBEDAAN UTAMA: Endpoint Darmawisata adalah PPOB/OpenPayment)
            $response = $this->forwardRequest('PPOB/OpenPayment', $payload);
            $json = json_decode($response->getContent(), true);

            // 4. Evaluasi Response (Identik dengan ppobPayment standar)
            if (isset($json['status']) && $json['status'] === 'SUCCESS') {
                DB::table('dw_ppob_transactions')->where('id', $orderId)->update([
                    'status'       => 'SUCCESS',
                    'resp_message' => $json['respMessage'] ?? 'Transaksi Berhasil',
                    'updated_at'   => now(),
                ]);

                return response()->json([
                    'status'  => 'SUCCESS',
                    'message' => 'Pembayaran PPOB Open Payment Berhasil!',
                    'data'    => $json
                ]);
            } else {
                $respMsg = strtolower($json['respMessage'] ?? '');

                if (str_contains($respMsg, 'pending')) {
                    DB::table('dw_ppob_transactions')->where('id', $orderId)->update([
                        'status'       => 'PENDING_PROVIDER',
                        'resp_message' => 'Sedang diproses oleh provider (Pending Antrean).',
                        'updated_at'   => now(),
                    ]);

                    return response()->json([
                        'status'  => 'SUCCESS',
                        'message' => 'Pembayaran sedang diproses oleh provider.',
                        'data'    => $json
                    ]);
                }

                // Gagal instan, Refund Saldo
                DB::transaction(function () use ($userId, $totalPrice, $orderId, $json) {
                    DB::table('Pengguna')->where('id_pengguna', $userId)->increment('saldo', $totalPrice);
                    DB::table('dw_ppob_transactions')->where('id', $orderId)->update([
                        'status'       => 'FAILED',
                        'resp_message' => $json['respMessage'] ?? 'Gagal dari provider',
                        'updated_at'   => now(),
                    ]);
                });

                return response()->json(['status' => 'FAILED', 'message' => $json['respMessage'] ?? 'Transaksi Gagal']);
            }

       } catch (\Exception $e) {
            Log::error("FATAL ERROR [PPOB Open Payment]: " . $e->getMessage());
            if ($orderId) {
                // Refund saldo untuk system error
                DB::table('Pengguna')->where('id_pengguna', $userId)->increment('saldo', $totalPrice);

                DB::table('dw_ppob_transactions')
                    ->where('id', $orderId)
                    ->update([
                        'status' => 'FAILED_SYSTEM_ERROR',
                        'resp_message' => 'System Error: Saldo telah dikembalikan.',
                        'updated_at' => now()
                    ]);
            }
            return response()->json(['status' => 'FAILED', 'message' => 'System Error: ' . $e->getMessage()], 500);
        }
    }

}
