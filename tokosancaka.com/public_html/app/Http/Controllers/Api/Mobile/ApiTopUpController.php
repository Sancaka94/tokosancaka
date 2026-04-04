<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Api;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class ApiTopUpController extends Controller
{
    /**
     * ==========================================================
     * 1. API: AMBIL DAFTAR METODE PEMBAYARAN (SUDAH FIX 4 METODE)
     * ==========================================================
     */
    public function getMethods()
    {
        // 1. AMBIL METODE TRIPAY DARI API
        $mode = Api::getValue('TRIPAY_MODE', 'global', 'sandbox');

        $tripayChannels = Cache::remember('tripay_channels_' . $mode, 60 * 24, function () use ($mode) {
            $apiKey = ($mode === 'production') ? Api::getValue('TRIPAY_API_KEY', 'production') : Api::getValue('TRIPAY_API_KEY', 'sandbox');
            $baseUrl = ($mode === 'production') ? 'https://tripay.co.id/api/merchant/payment-channel' : 'https://tripay.co.id/api-sandbox/merchant/payment-channel';

            if (empty($apiKey)) return [];

            try {
                $response = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])->get($baseUrl);
                if ($response->successful()) {
                    return $response->json()['data'] ?? [];
                }
            } catch (\Exception $e) {
                Log::error('Tripay API Error: ' . $e->getMessage());
            }
            return [];
        });

        // 2. METODE DOKU JOKUL
        $dokuMethods = [
            [
                'group' => 'Payment Gateway',
                'code' => 'DOKU_JOKUL', // Kode ini akan dibaca oleh fungsi store() Mas Amal
                'name' => 'DOKU Payment Gateway',
                'icon_url' => 'https://dashboard.doku.com/bo/assets/images/logodoku.png'
            ]
        ];

        // 3. METODE DANA DIRECT
        $danaMethods = [
            [
                'group' => 'E-Wallet',
                'code' => 'DANA', // Kode ini akan dibaca oleh DANA Direct Mas Amal
                'name' => 'DANA (Direct)',
                'icon_url' => 'https://img.antaranews.com/cache/1200x800/2022/04/25/dana.jpg.webp'
            ]
        ];

        // 4. METODE TRANSFER MANUAL
        $manualMethods = [
            [
                'group' => 'Transfer Manual',
                'code' => 'TRANSFER_MANUAL',
                'name' => 'Transfer Bank Manual (BCA/Mandiri)',
                'icon_url' => 'https://tokosancaka.com/public/assets/doku.png'
            ]
        ];

        // GABUNGKAN KE-4 METODE KE DALAM JSON
        return response()->json([
            'success' => true,
            'data' => [
                'tripay' => collect($tripayChannels)->groupBy('group'),
                'doku'   => $dokuMethods,
                'dana'   => $danaMethods,
                'manual' => $manualMethods
            ]
        ]);
    }

    // PASTIKAN BARIS INI ADA DI PALING ATAS FILE (di bawah namespace)
// use App\Services\DokuJokulService;

    /**
     * ==========================================================
     * 2. API: REQUEST TOP UP (GENERATE INVOICE & URL)
     * ==========================================================
     */
    public function requestTopUp(Request $request, \App\Services\DokuJokulService $dokuJokulService)
    {
        $request->validate([
            'amount'         => 'required|numeric|min:10000',
            'payment_method' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            $user = Auth::user();
            $amount = (int) $request->amount;
            $invoiceNumber = 'TOPUP-' . strtoupper(Str::random(10));

            // Buat Transaksi di DB
            $transaction = Transaction::create([
                'user_id'            => $user->id_pengguna,
                'amount'             => $amount,
                'type'               => 'topup',
                'status'             => 'pending',
                'payment_method'     => $request->payment_method,
                'description'        => 'Top up saldo via ' . $request->payment_method,
                'reference_id'       => $invoiceNumber,
            ]);

            // ===========================================
            // LOGIKA 1: TRANSFER MANUAL
            // ===========================================
            if ($request->payment_method === 'TRANSFER_MANUAL') {
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Silakan lakukan transfer manual.',
                    'data' => [
                        'reference_id' => $invoiceNumber,
                        'amount' => $amount,
                        'is_manual' => true,
                        'bank_name' => 'BCA',
                        'account_number' => '1234567890',
                        'account_name' => 'CV. Sancaka Karya Hutama'
                    ]
                ]);
            }

            // ===========================================
            // LOGIKA 2: DOKU JOKUL
            // ===========================================
            elseif ($request->payment_method === 'DOKU_JOKUL') {
                Log::info('Memulai Top Up DOKU (Jokul) Mobile: ' . $invoiceNumber);

                $customerData = [
                    'name'  => $user->nama_lengkap,
                    'email' => $user->email ?? 'no-email@sancaka.com',
                    'phone' => $user->no_wa ?? '080000000000'
                ];
                $lineItems = [
                    ['name' => 'Top Up Saldo', 'price' => $amount, 'quantity' => 1]
                ];

                // URL ini cuma fallback, di mobile akan di-handle Expo Browser
                $successRedirectUrl = config('app.url');

                $paymentUrl = $dokuJokulService->createPayment(
                    $invoiceNumber,
                    $amount,
                    $customerData,
                    $lineItems,
                    [],
                    $successRedirectUrl
                );

                if (empty($paymentUrl)) {
                    throw new \Exception('Gagal membuat transaksi DOKU.');
                }

                $transaction->payment_url = $paymentUrl;
                $transaction->save();
                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Transaksi DOKU berhasil dibuat.',
                    'data' => [
                        'reference_id' => $invoiceNumber,
                        'amount' => $amount,
                        'payment_url' => $paymentUrl, // <--- Expo Browser akan baca ini
                        'is_manual' => false
                    ]
                ]);
            }

            // ===========================================
            // LOGIKA 3: TRIPAY (Default)
            // ===========================================
            else {
                $apiKey       = config('tripay.api_key');
                $privateKey   = config('tripay.private_key');
                $merchantCode = config('tripay.merchant_code');
                $mode         = Api::getValue('TRIPAY_MODE', 'global', 'sandbox');

                $payload = [
                    'method'         => $request->payment_method,
                    'merchant_ref'   => $invoiceNumber,
                    'amount'         => $amount,
                    'customer_name'  => $user->nama_lengkap,
                    'customer_email' => $user->email ?? 'no-email@sancaka.com',
                    'customer_phone' => $user->no_wa ?? '080000000000',
                    'order_items'    => [
                        ['sku' => 'TOPUP', 'name' => 'Top Up Saldo', 'price' => $amount, 'quantity' => 1],
                    ],
                    'expired_time'   => time() + (1 * 60 * 60),
                    'signature'      => hash_hmac('sha256', $merchantCode.$invoiceNumber.$amount, $privateKey),
                ];

                $baseUrl = $mode === 'production'
                    ? 'https://tripay.co.id/api/transaction/create'
                    : 'https://tripay.co.id/api-sandbox/transaction/create';

                $response = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])->post($baseUrl, $payload);

                if ($response->successful() && isset($response->json()['success']) && $response->json()['success'] === true) {
                    $tripayData = $response->json()['data'];
                    $paymentUrl = $tripayData['checkout_url'] ?? null;

                    $transaction->payment_url = $paymentUrl;
                    $transaction->save();
                    DB::commit();

                    return response()->json([
                        'success' => true,
                        'message' => 'Transaksi Tripay berhasil dibuat.',
                        'data' => [
                            'reference_id' => $invoiceNumber,
                            'amount' => $amount,
                            'payment_url' => $paymentUrl,
                            'is_manual' => false
                        ]
                    ]);
                } else {
                    throw new \Exception('Gagal dari server Tripay: ' . ($response->json()['message'] ?? 'Unknown Error'));
                }
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('API TopUp Request Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

  /**
     * ==========================================================
     * 3. API: MENGAMBIL RIWAYAT TOP UP (FIX BUG UNION PAGINATE)
     * ==========================================================
     */
    public function history(Request $request)
    {
        try {
            $user = Auth::user();
            $search = $request->query('search');

            // 1. QUERY DARI TRANSACTIONS
            $q1 = DB::table('transactions')
                ->select(
                    'id',
                    'reference_id',
                    'amount',
                    'status',
                    'description',
                    'payment_method',
                    'created_at',
                    DB::raw("'ISI_SALDO' as kategori_sumber")
                )
                ->where('user_id', $user->id_pengguna)
                ->where('type', 'topup');

            if (!empty($search)) {
                $q1->where('reference_id', 'LIKE', "%{$search}%");
            }

            // 2. QUERY DARI TOP_UPS
            $q2 = DB::table('top_ups')
                ->select(
                    'id',
                    'transaction_id as reference_id',
                    'amount',
                    'status',
                    'payment_method as description',
                    'payment_method',
                    'created_at',
                    DB::raw("'PENCAIRAN_ADMIN' as kategori_sumber")
                )
                ->where('customer_id', $user->id_pengguna);

            if (!empty($search)) {
                $q2->where('transaction_id', 'LIKE', "%{$search}%");
            }

            // 3. GABUNGKAN KEDUANYA (UNION)
            $unioned = $q1->unionAll($q2);

            // =======================================================
            // KUNCI FIX BUG: Bungkus Query Union ke dalam Subquery!
            // =======================================================
            $results = DB::table(DB::raw("({$unioned->toSql()}) as combined_table"))
                ->mergeBindings($unioned) // Wajib membawa binding parameternya
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            // 4. FORMAT DATA
            $formattedData = collect($results->items())->map(function ($trx) {
                // Antisipasi jika deskripsi null
                $deskripsi = $trx->description ?? '';
                $metode = str_ireplace('Top up saldo via ', '', $deskripsi);

                return [
                    'id' => $trx->id,
                    'reference_id' => $trx->reference_id,
                    'amount' => (float)$trx->amount,
                    'status' => strtolower($trx->status),
                    'payment_method' => strtoupper($metode ?: 'SISTEM'),
                    'kategori' => $trx->kategori_sumber,
                    'created_at' => date('d M Y, H:i', strtotime($trx->created_at)),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedData,
            ]);

        } catch (\Exception $e) {
            Log::error('History Error: ' . $e->getMessage());
            // Berikan respon error berbentuk JSON, agar HP tidak crash
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
