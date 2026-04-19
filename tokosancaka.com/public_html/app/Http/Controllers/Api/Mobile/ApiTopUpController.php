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
     * 2. API: REQUEST TOP UP (GENERATE INVOICE & URL)
     * Diperbarui: Hanya memproses 'CASH' dan 'GATEWAY'
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
            // 2. CEGAT METODE PEMBAYARAN
            // ====================================================================

            // A. JIKA METODE GATEWAY (Arahkan ke Web Portal)
            if ($paymentMethod === 'GATEWAY') {
                $akun = $user->no_wa ?? $user->email ?? $userId;
                $paymentUrl = url('/pembayaran?akun=' . urlencode($akun));
            }

            // B. JIKA METODE CASH (Khusus Admin / ID 4)
            elseif ($paymentMethod === 'CASH') {
                if ($userId != 4) {
                    throw new \Exception("Akses Ditolak: Metode CASH hanya untuk Admin.");
                }
                $isManual = true;
            }

            // C. JIKA METODE TRANSFER_MANUAL (Bawaan Kode Lama)
            elseif ($paymentMethod === 'TRANSFER_MANUAL') {
                 $isManual = true;
            }

            // D. FALLBACK (Metode lain paksa menjadi GATEWAY)
            else {
                $paymentMethod = 'GATEWAY';
                $akun          = $user->no_wa ?? $user->email ?? $userId;
                $paymentUrl    = url('/pembayaran?akun=' . urlencode($akun));
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
     * 3. API: MENGAMBIL RIWAYAT TOP UP (FIX BUG KOLOM UNION)
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
                $deskripsi = $trx->description ?? '';
                $metode = str_ireplace('Top up saldo via ', '', $deskripsi);

                return [
                    'id'             => $trx->id,
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

    /**
     * ==========================================================
     * 8. API: REQUEST OTP VIA WHATSAPP (FONNTE)
     * ==========================================================
     */
    public function requestOtpResetPin(Request $request)
    {
        try {
            $user = Auth::user();

            if (empty($user->no_wa)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nomor WhatsApp belum terdaftar di akun Anda.'
                ], 400);
            }

            $otpCode = strtoupper(Str::random(6));
            Cache::put('otp_reset_pin_' . $user->id_pengguna, $otpCode, now()->addMinutes(5));

            $message = "Halo *{$user->nama_lengkap}*,\n\n";
            $message .= "Berikut adalah kode OTP untuk mereset PIN Keamanan Anda:\n\n";
            $message .= "*{$otpCode}*\n\n";
            $message .= "Kode ini hanya berlaku selama 5 menit. JANGAN BERIKAN KODE INI KEPADA SIAPAPUN, termasuk pihak Sancaka.";

            $nomorTujuan = $user->no_wa;
            if (str_starts_with($nomorTujuan, '0')) {
                $nomorTujuan = substr($nomorTujuan, 1);
            } elseif (str_starts_with($nomorTujuan, '62')) {
                $nomorTujuan = substr($nomorTujuan, 2);
            } elseif (str_starts_with($nomorTujuan, '+62')) {
                $nomorTujuan = substr($nomorTujuan, 3);
            }

            $response = Http::withHeaders([
                'Authorization' => env('FONNTE_API_KEY') ?? env('FONNTE_KEY') ?? 'ynMyPswSKr14wdtXMJF7'
            ])->post('https://api.fonnte.com/send', [
                'target'      => $nomorTujuan,
                'message'     => $message,
                'countryCode' => '62',
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
