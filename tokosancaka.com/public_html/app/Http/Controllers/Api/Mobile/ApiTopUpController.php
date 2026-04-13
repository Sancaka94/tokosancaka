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
use Illuminate\Support\Facades\Hash;

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
     * 3. API: MENGAMBIL RIWAYAT TOP UP (FIX BUG KOLOM UNION)
     * ==========================================================
     */
    public function history(Request $request)
    {
        try {
            $user = Auth::user();
            $search = $request->query('search');

            // 1. QUERY DARI TRANSACTIONS
            // (Tabel ini tidak punya payment_method, punyanya description)
            $q1 = DB::table('transactions')
                ->select(
                    'id',
                    'reference_id',
                    'amount',
                    'status',
                    'description', // Diambil dari description
                    'payment_url',
                    'created_at',
                    DB::raw("'ISI_SALDO' as kategori_sumber")
                )
                ->where('user_id', $user->id_pengguna)
                ->where('type', 'topup');

            if (!empty($search)) {
                $q1->where('reference_id', 'LIKE', "%{$search}%");
            }

            // 2. QUERY DARI TOP_UPS
            // (Tabel ini punya payment_method, kita samarkan jadi 'description' agar kembar dengan Q1)
            $q2 = DB::table('top_ups')
                ->select(
                    'id',
                    'transaction_id as reference_id',
                    'amount',
                    'status',
                    'payment_method as description', // KUNCI FIX: Alias disamakan
                    'payment_url',
                    'created_at',
                    DB::raw("'PENCAIRAN_ADMIN' as kategori_sumber")
                )
                ->where('customer_id', $user->id_pengguna);

            if (!empty($search)) {
                $q2->where('transaction_id', 'LIKE', "%{$search}%");
            }

            // 3. GABUNGKAN KEDUANYA (UNION)
            $unioned = $q1->unionAll($q2);

            // 4. BUNGKUS KE SUBQUERY AGAR BISA DI-PAGINATE
            $results = DB::table(DB::raw("({$unioned->toSql()}) as combined_table"))
                ->mergeBindings($unioned)
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            // 5. FORMAT DATA SEBELUM DIKIRIM KE HP
            $formattedData = collect($results->items())->map(function ($trx) {
                // Tarik nama metode dari 'description' yang sudah kita seragamkan tadi
                $deskripsi = $trx->description ?? '';
                // Bersihkan tulisan bawaan
                $metode = str_ireplace('Top up saldo via ', '', $deskripsi);

                return [
                    'id' => $trx->id,
                    'reference_id' => $trx->reference_id,
                    'amount' => (float)$trx->amount,
                    'status' => strtolower($trx->status),
                    'payment_method' => strtoupper($metode ?: 'SISTEM'),
                    'payment_url' => $trx->payment_url,
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
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ==========================================================
     * 4. API: REGISTER PIN (PEMBUATAN PIN PERTAMA KALI)
     * ==========================================================
     */
    public function registerPin(Request $request)
    {
        $request->validate([
            'pin' => 'required|digits:6|confirmed' // memastikan ada pin_confirmation
        ], [
            'pin.required' => 'PIN wajib diisi.',
            'pin.digits' => 'PIN harus terdiri dari 6 angka.',
            'pin.confirmed' => 'Konfirmasi PIN tidak cocok.'
        ]);

        try {
            $user = Auth::user();

            // Cek apakah user sudah punya PIN
            if (!empty($user->pin)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah memiliki PIN. Silakan gunakan menu Edit PIN.'
                ], 400);
            }

            // Simpan PIN dengan Hash
            $user->pin = Hash::make($request->pin);
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'PIN berhasil dibuat.'
            ]);

        } catch (\Exception $e) {
            Log::error('Register PIN Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat membuat PIN.'
            ], 500);
        }
    }

    /**
     * ==========================================================
     * 5. API: EDIT PIN (UBAH PIN SAAT INGAT PIN LAMA)
     * ==========================================================
     */
    public function editPin(Request $request)
    {
        $request->validate([
            'old_pin' => 'required|digits:6',
            'new_pin' => 'required|digits:6|confirmed'
        ], [
            'old_pin.required' => 'PIN lama wajib diisi.',
            'new_pin.required' => 'PIN baru wajib diisi.',
            'new_pin.digits' => 'PIN baru harus 6 angka.',
            'new_pin.confirmed' => 'Konfirmasi PIN baru tidak cocok.'
        ]);

        try {
            $user = Auth::user();

            // Cek apakah PIN lama cocok dengan yang di database
            if (!Hash::check($request->old_pin, $user->pin)) {
                return response()->json([
                    'success' => false,
                    'message' => 'PIN lama yang Anda masukkan salah.'
                ], 400);
            }

            // Simpan PIN baru
            $user->pin = Hash::make($request->new_pin);
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'PIN berhasil diperbarui.'
            ]);

        } catch (\Exception $e) {
            Log::error('Edit PIN Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengubah PIN.'
            ], 500);
        }
    }

    /**
     * ==========================================================
     * 6. API: RESET PIN (LUPA PIN - VERIFIKASI VIA PASSWORD)
     * ==========================================================
     */
    public function resetPin(Request $request)
    {
        $request->validate([
            'password' => 'required', // Meminta password akun untuk keamanan
            'new_pin'  => 'required|digits:6|confirmed'
        ], [
            'password.required' => 'Password akun wajib diisi untuk verifikasi keamanan.',
            'new_pin.required' => 'PIN baru wajib diisi.',
            'new_pin.digits' => 'PIN baru harus 6 angka.',
            'new_pin.confirmed' => 'Konfirmasi PIN baru tidak cocok.'
        ]);

        try {
            $user = Auth::user();

            // Verifikasi Password Akun
            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Password akun yang Anda masukkan salah. Gagal mereset PIN.'
                ], 401);
            }

            // Jika password benar, buat PIN baru
            $user->pin = Hash::make($request->new_pin);
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'PIN berhasil di-reset dan diperbarui.'
            ]);

        } catch (\Exception $e) {
            Log::error('Reset PIN Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mereset PIN.'
            ], 500);
        }
    }

    /**
     * ==========================================================
     * 7. API: VERIFIKASI PIN (UNTUK KEPERLUAN SEBELUM TRANSAKSI)
     * ==========================================================
     */
    public function verifyPin(Request $request)
    {
        $request->validate([
            'pin' => 'required|digits:6'
        ]);

        try {
            $user = Auth::user();

            // Jika user belum setting PIN sama sekali
            if (empty($user->pin)) {
                return response()->json([
                    'success' => false,
                    'is_set' => false,
                    'message' => 'Anda belum membuat PIN Keamanan.'
                ], 403);
            }

            // Cek kebenaran PIN
            if (!Hash::check($request->pin, $user->pin)) {
                return response()->json([
                    'success' => false,
                    'is_set' => true,
                    'message' => 'PIN Keamanan salah.'
                ], 401);
            }

            return response()->json([
                'success' => true,
                'message' => 'PIN valid.'
            ]);

        } catch (\Exception $e) {
            Log::error('Verify PIN Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan internal.'
            ], 500);
        }
    }

    /**
     * ==========================================================
     * 8. API: REQUEST OTP VIA WHATSAPP (FONNTE)
     * ==========================================================
     */
    public function requestOtpResetPin(Request $request)
    {
        try {
            $user = Auth::user();

            // Pastikan user memiliki nomor WA
            if (empty($user->no_wa)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nomor WhatsApp belum terdaftar di akun Anda.'
                ], 400);
            }

            // Generate OTP 6 Karakter (Huruf & Angka, Uppercase)
            $otpCode = strtoupper(Str::random(6));

            // Simpan OTP di Cache selama 5 menit dengan key unik per user
            Cache::put('otp_reset_pin_' . $user->id_pengguna, $otpCode, now()->addMinutes(5));

            // Format Pesan WhatsApp
            $message = "Halo *{$user->nama_lengkap}*,\n\n";
            $message .= "Berikut adalah kode OTP untuk mereset PIN Keamanan Anda:\n\n";
            $message .= "*{$otpCode}*\n\n";
            $message .= "Kode ini hanya berlaku selama 5 menit. JANGAN BERIKAN KODE INI KEPADA SIAPAPUN, termasuk pihak Sancaka.";

            // Kirim ke Fonnte
            $response = Http::withHeaders([
                'Authorization' => 'ynMyPswSKr14wdtXMJF7' // Token Fonnte Mas Amal
            ])->post('https://api.fonnte.com/send', [
                'target' => $user->no_wa,
                'message' => $message,
                'countryCode' => '62', // Default kode negara Indonesia
            ]);

            $fonnteResult = $response->json();

            if ($response->successful() && isset($fonnteResult['status']) && $fonnteResult['status'] == true) {
                return response()->json([
                    'success' => true,
                    'message' => 'Kode OTP berhasil dikirim ke WhatsApp Anda.'
                ]);
            } else {
                Log::error('Fonnte Send Error: ' . json_encode($fonnteResult));
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengirim OTP ke WhatsApp. Pastikan nomor aktif.'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Request OTP Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan internal saat mengirim OTP.'
            ], 500);
        }
    }

    /**
     * ==========================================================
     * 9. API: RESET PIN DENGAN OTP (MENGGANTIKAN RESET VIA PASSWORD)
     * ==========================================================
     */
    public function resetPinWithOtp(Request $request)
    {
        $request->validate([
            'otp'      => 'required|string|size:6',
            'new_pin'  => 'required|digits:6|confirmed'
        ], [
            'otp.required' => 'Kode OTP wajib diisi.',
            'otp.size' => 'Kode OTP harus 6 karakter.',
            'new_pin.required' => 'PIN baru wajib diisi.',
            'new_pin.digits' => 'PIN baru harus 6 angka.',
            'new_pin.confirmed' => 'Konfirmasi PIN baru tidak cocok.'
        ]);

        try {
            $user = Auth::user();
            $cacheKey = 'otp_reset_pin_' . $user->id_pengguna;

            // Ambil OTP dari Cache
            $savedOtp = Cache::get($cacheKey);

            // Cek apakah OTP ada/belum expired
            if (!$savedOtp) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kode OTP sudah kedaluwarsa atau tidak valid. Silakan request ulang.'
                ], 400);
            }

            // Cocokkan OTP (Case Insensitive agar aman kalau user ketik huruf kecil)
            if (strtoupper($request->otp) !== $savedOtp) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kode OTP yang Anda masukkan salah.'
                ], 400);
            }

            // Jika OTP Benar, Reset PIN
            $user->pin = Hash::make($request->new_pin);
            $user->save();

            // Hapus OTP dari cache agar tidak bisa dipakai 2 kali (Sekali Pakai / One Time)
            Cache::forget($cacheKey);

            return response()->json([
                'success' => true,
                'message' => 'PIN berhasil di-reset dan diperbarui.'
            ]);

        } catch (\Exception $e) {
            Log::error('Reset PIN OTP Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mereset PIN.'
            ], 500);
        }
    }
}
