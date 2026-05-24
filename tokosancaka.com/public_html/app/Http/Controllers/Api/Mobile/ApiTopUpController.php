<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Api;
use App\Models\User;
use App\Models\Pengguna;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;


use App\Services\DokuJokulService;

class ApiTopUpController extends Controller
{
    /**
     * ==========================================================
     * 1. API: AMBIL DAFTAR METODE PEMBAYARAN (SUDAH FIX 4 METODE)
     * ==========================================================
     * Catatan: Jika aplikasi Mobile (TopUpScreen) sudah menggunakan
     * hardcode 'CASH' dan 'GATEWAY', fungsi getMethods() ini
     * sebenarnya tidak lagi dipanggil oleh aplikasi mobile terbaru Anda.
     * Namun tetap dibiarkan jika sewaktu-waktu dibutuhkan.
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
                'code' => 'DOKU_JOKUL',
                'name' => 'DOKU Payment Gateway',
                'icon_url' => 'https://dashboard.doku.com/bo/assets/images/logodoku.png'
            ]
        ];

        // 3. METODE DANA DIRECT
        $danaMethods = [
            [
                'group' => 'E-Wallet',
                'code' => 'DANA',
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

    /**
     * ==========================================================
     * 2. API: REQUEST TOP UP (GENERATE INVOICE & URL LANGSUNG)
     * ==========================================================
     */
    public function requestTopUp(Request $request)
    {
        $request->validate([
            'amount'         => 'required|numeric|min:10000',
            'payment_method' => 'required|string',
        ]);

        $user = Auth::user() ?? auth('sanctum')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Silakan login kembali.'], 401);
        }

        DB::beginTransaction();

        try {
            $amount        = (int) $request->amount;
            $paymentMethod = strtoupper($request->payment_method);
            $userId        = $user->id_pengguna ?? $user->id;

            // 1. Generate Reference ID Unik (Format: TOPUP-XXXXXX)
            do {
                $invoiceNumber = 'TOPUP-' . strtoupper(Str::random(8));
            } while (Transaction::where('reference_id', $invoiceNumber)->exists());

            $paymentUrl = null;
            $isManual   = false;
            $status     = 'pending';

            // ====================================================================
            // 2. CEGAT METODE PEMBAYARAN & LANGSUNG HIT GATEWAY
            // ====================================================================

            // A. JIKA METODE CASH (Khusus Admin / ID 4)
            if ($paymentMethod === 'CASH') {
                if ($userId != 4) {
                    throw new \Exception("Akses Ditolak: Metode CASH hanya untuk Admin.");
                }
                $isManual = true;
            }

            // B. JIKA METODE TRANSFER_MANUAL
            elseif ($paymentMethod === 'TRANSFER_MANUAL') {
                 $isManual = true;
            }

            // C. JIKA METODE DOKU JOKUL (LANGSUNG BYPASS KE DOKU)
            elseif ($paymentMethod === 'DOKU_JOKUL' || $paymentMethod === 'DOKU') {
                $dokuService = new DokuJokulService();

                $customerData = [
                    'name'  => $user->nama_lengkap ?? $user->name ?? 'Pelanggan Sancaka',
                    'email' => $user->email ?? 'no-email@sancaka.com',
                    'phone' => $user->no_wa ?? $user->phone ?? '0000000000'
                ];

                $orderItemsPayload = [
                    ['sku' => 'TOPUP', 'name' => 'Top Up Saldo Sancaka', 'price' => $amount, 'quantity' => 1]
                ];

                $paymentUrl = $dokuService->createPayment(
                    $invoiceNumber,
                    $amount,
                    $customerData,
                    $orderItemsPayload,
                    []
                );

                if (!$paymentUrl) {
                    throw new \Exception('Gagal generate link pembayaran DOKU.');
                }
            }

            // D. JIKA METODE LAINNYA (DANA, TRIPAY, DLL)
            else {
                // Biarkan diarahkan ke web portal jika belum dipasang API langsung
                $akun       = $user->no_wa ?? $user->email ?? $userId;
                $paymentUrl = url('/pembayaran?akun=' . urlencode($akun));
            }

            // ====================================================================
            // 3. SIMPAN KE DATABASE
            // ====================================================================
            $transaction = Transaction::create([
                'user_id'        => $userId,
                'amount'         => $amount,
                'type'           => 'topup',
                'status'         => $status,
                'payment_method' => $paymentMethod,
                'description'    => 'Top up saldo via ' . $paymentMethod,
                'reference_id'   => $invoiceNumber,
                'payment_url'    => $paymentUrl,
            ]);

            DB::commit();

            Log::info("API MOBILE: Request Top Up berhasil dibuat: {$invoiceNumber} via {$paymentMethod} (User ID: {$userId})");

            // ====================================================================
            // 4. RETURN DATA KHUSUS (Jika manual vs gateway)
            // ====================================================================
            if ($paymentMethod === 'TRANSFER_MANUAL') {
                 return response()->json([
                    'success' => true,
                    'message' => 'Silakan lakukan transfer manual.',
                    'data' => [
                        'reference_id'   => $invoiceNumber,
                        'amount'         => $amount,
                        'is_manual'      => true,
                        'payment_url'    => null,
                        'bank_name'      => 'BCA',
                        'account_number' => '1234567890',
                        'account_name'   => 'CV. Sancaka Karya Hutama'
                    ]
                ]);
            }

            // RETURN DEFAULT
            return response()->json([
                'success' => true,
                'message' => 'Berhasil membuat tagihan Top Up.',
                'data' => [
                    'reference_id'   => $transaction->reference_id,
                    'amount'         => $transaction->amount,
                    'status'         => $transaction->status,
                    'payment_method' => $transaction->payment_method,
                    'payment_url'    => $transaction->payment_url,
                    'is_manual'      => $isManual
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('API TopUp Request Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses top up: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ==========================================================
     * 3. API: MENGAMBIL RIWAYAT TOP UP (FIX ADMIN & UNION)
     * ==========================================================
     */
    public function history(Request $request)
    {
        try {
            $user = Auth::user();
            $search = $request->query('search');

            // CEK ADMIN
            $isAdmin = ($user->id_pengguna == 4);

            // 1. QUERY DARI TRANSACTIONS
            $q1 = DB::table('transactions')
                ->select(
                    'id',
                    'reference_id',
                    'amount',
                    'status',
                    'description',
                    'payment_url',
                    'created_at',
                    DB::raw("'ISI_SALDO' as kategori_sumber"),
                    'user_id' // Tambahkan kolom user_id
                )
                ->where('type', 'topup');

            // Jika bukan admin, filter datanya
            if (!$isAdmin) {
                $q1->where('user_id', $user->id_pengguna);
            }

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
                    'payment_url',
                    'created_at',
                    DB::raw("'PENCAIRAN_ADMIN' as kategori_sumber"),
                    'customer_id as user_id' // Samakan alias menjadi user_id agar bisa di-UNION
                );

            // Jika bukan admin, filter datanya
            if (!$isAdmin) {
                $q2->where('customer_id', $user->id_pengguna);
            }

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
                $deskripsi = $trx->description ?? '';
                $metode = str_ireplace('Top up saldo via ', '', $deskripsi);

                return [
                    'id'             => $trx->id,
                    'user_id'        => $trx->user_id, // Sekarang Front-End bisa tahu ini transaksi siapa
                    'reference_id'   => $trx->reference_id,
                    'amount'         => (float)$trx->amount,
                    'status'         => strtolower($trx->status),
                    'payment_method' => strtoupper($metode ?: 'SISTEM'),
                    'payment_url'    => $trx->payment_url,
                    'kategori'       => $trx->kategori_sumber,
                    'created_at'     => date('d M Y, H:i', strtotime($trx->created_at)),
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
            'pin' => 'required|digits:6|confirmed'
        ], [
            'pin.required'  => 'PIN wajib diisi.',
            'pin.digits'    => 'PIN harus terdiri dari 6 angka.',
            'pin.confirmed' => 'Konfirmasi PIN tidak cocok.'
        ]);

        try {
            $user = Auth::user();

            if (!empty($user->pin)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah memiliki PIN. Silakan gunakan menu Edit PIN.'
                ], 400);
            }

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
            'old_pin.required'  => 'PIN lama wajib diisi.',
            'new_pin.required'  => 'PIN baru wajib diisi.',
            'new_pin.digits'    => 'PIN baru harus 6 angka.',
            'new_pin.confirmed' => 'Konfirmasi PIN baru tidak cocok.'
        ]);

        try {
            $user = Auth::user();

            if (!Hash::check($request->old_pin, $user->pin)) {
                return response()->json([
                    'success' => false,
                    'message' => 'PIN lama yang Anda masukkan salah.'
                ], 400);
            }

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
            'password' => 'required',
            'new_pin'  => 'required|digits:6|confirmed'
        ], [
            'password.required' => 'Password akun wajib diisi untuk verifikasi keamanan.',
            'new_pin.required'  => 'PIN baru wajib diisi.',
            'new_pin.digits'    => 'PIN baru harus 6 angka.',
            'new_pin.confirmed' => 'Konfirmasi PIN baru tidak cocok.'
        ]);

        try {
            $user = Auth::user();

            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Password akun yang Anda masukkan salah. Gagal mereset PIN.'
                ], 401);
            }

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

            if (empty($user->pin)) {
                return response()->json([
                    'success' => false,
                    'is_set'  => false,
                    'message' => 'Anda belum membuat PIN Keamanan.'
                ], 403);
            }

            if (!Hash::check($request->pin, $user->pin)) {
                return response()->json([
                    'success' => false,
                    'is_set'  => true,
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

    public function requestOtpResetPin(Request $request)
    {
        try {
            $user = Auth::user();
            $via = $request->input('via', 'wa');

            if ($via === 'email') {
                if (empty($user->email)) {
                    return response()->json(['success' => false, 'message' => 'Email belum terdaftar di akun Anda.'], 400);
                }
            } else {
                if (empty($user->no_wa)) {
                    return response()->json(['success' => false, 'message' => 'Nomor WhatsApp belum terdaftar di akun Anda.'], 400);
                }
            }

            $otpCode = strtoupper(Str::random(6));
            Cache::put('otp_reset_pin_' . $user->id_pengguna, $otpCode, now()->addMinutes(5));

            // --- JIKA VIA EMAIL ---
            if ($via === 'email') {
                // Template HTML Keren dengan Logo & Layout Copyable
                $htmlContent = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; background-color: #ffffff;'>
                    <div style='background-color: #ffffff; padding: 20px; text-align: center; border-bottom: 2px solid #dc2626;'>
                        <img src='https://tokosancaka.com/storage/uploads/sancaka.png' alt='Sancaka Express' style='max-width: 180px; height: auto;'>
                    </div>

                    <div style='padding: 30px; color: #334155;'>
                        <h2 style='color: #1e293b; margin-top: 0;'>Kode Verifikasi PIN</h2>
                        <p>Halo <strong>{$user->nama_lengkap}</strong>,</p>
                        <p>Gunakan kode di bawah ini untuk mereset PIN Keamanan akun Sancaka Express Anda:</p>

                        <div style='background-color: #f8fafc; padding: 25px; text-align: center; border-radius: 12px; border: 1px solid #e2e8f0; margin: 25px 0;'>
                            <div style='font-size: 36px; font-weight: 800; color: #dc2626; letter-spacing: 6px; margin-bottom: 20px;'>
                                {$otpCode}
                            </div>

                            <div style='display: inline-block; background-color: #dc2626; color: #ffffff; padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: bold;'>
                                <img src='https://cdn-icons-png.flaticon.com/512/1621/1621635.png' width='16' style='vertical-align: middle; margin-right: 8px;'>
                                SALIN KODE INI
                            </div>
                        </div>

                        <p style='font-size: 13px; color: #64748b;'>
                            *Tekan lama pada kode di atas untuk menyalin. Kode ini berlaku selama <strong>5 menit</strong>. Jika Anda tidak merasa melakukan permintaan ini, silakan abaikan email ini.
                        </p>

                        <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #e2e8f0; font-size: 12px; color: #94a3b8; text-align: center;'>
                            Toko Sancaka - Solusi Pengiriman Terpercaya
                        </div>
                    </div>
                </div>";

                Mail::send([], [], function ($message) use ($user, $htmlContent) {
                    $message->to($user->email)
                            ->subject('Kode OTP Reset PIN Keamanan - Sancaka Express')
                            ->html($htmlContent);
                });

                return response()->json(['success' => true, 'message' => 'OTP berhasil dikirim ke Email Anda.']);
            }

            // --- JIKA VIA WHATSAPP (FONNTE) ---
            else {
                $message = "Halo *{$user->nama_lengkap}*,\n\nBerikut kode OTP reset PIN Anda: *{$otpCode}*.\n\nKode ini berlaku 5 menit dan bersifat rahasia.";
                $nomorTujuan = $this->formatNomorWa($user->no_wa);

                $response = Http::asForm()->withHeaders([
                    'Authorization' => env('FONNTE_API_KEY') ?? 'cC3LrEd8VwDDRuE6urcj'
                ])->post('https://api.fonnte.com/send', [
                    'target' => $nomorTujuan,
                    'message' => $message,
                ]);

                if ($response->successful()) {
                    return response()->json(['success' => true, 'message' => 'OTP berhasil dikirim ke WhatsApp.']);
                }
                return response()->json(['success' => false, 'message' => 'Gagal kirim WA.'], 500);
            }

        } catch (\Exception $e) {
            Log::error('Request OTP Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal mengirim OTP.'], 500);
        }
    }

    /**
     * Helper untuk memformat nomor WhatsApp ke format internasional (62...)
     */
    private function formatNomorWa($nomor)
    {
        // 1. Hapus semua karakter non-angka
        $nomor = preg_replace('/[^0-9]/', '', $nomor);

        // 2. Jika diawali dengan '0', ganti menjadi '62'
        if (str_starts_with($nomor, '0')) {
            return '62' . substr($nomor, 1);
        }

        // 3. Jika sudah diawali '62', biarkan saja
        if (str_starts_with($nomor, '62')) {
            return $nomor;
        }

        // 4. Jika hanya nomor HP biasa (tanpa 0 di depan), tambahkan 62
        return '62' . $nomor;
    }

    /**
     * ==========================================================
     * 9. API: RESET PIN DENGAN OTP
     * ==========================================================
     */
    public function resetPinWithOtp(Request $request)
    {
        $request->validate([
            'otp'     => 'required|string|size:6',
            'new_pin' => 'required|digits:6|confirmed'
        ], [
            'otp.required'      => 'Kode OTP wajib diisi.',
            'otp.size'          => 'Kode OTP harus 6 karakter.',
            'new_pin.required'  => 'PIN baru wajib diisi.',
            'new_pin.digits'    => 'PIN baru harus 6 angka.',
            'new_pin.confirmed' => 'Konfirmasi PIN baru tidak cocok.'
        ]);

        try {
            $user = Auth::user();
            $cacheKey = 'otp_reset_pin_' . $user->id_pengguna;

            $savedOtp = Cache::get($cacheKey);

            if (!$savedOtp) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kode OTP sudah kedaluwarsa atau tidak valid. Silakan request ulang.'
                ], 400);
            }

            if (strtoupper($request->otp) !== $savedOtp) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kode OTP yang Anda masukkan salah.'
                ], 400);
            }

            $user->pin = Hash::make($request->new_pin);
            $user->save();

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
