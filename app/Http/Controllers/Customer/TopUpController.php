<?php

namespace App\Http\Controllers\Customer;

use App\Events\AdminNotificationEvent;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Services\DokuJokulService;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage; // Ditambahkan untuk 'uploadProof'
use App\Events\SaldoUpdated; // <-- DITAMBAHKAN
use Illuminate\Support\Facades\Notification; // <-- DITAMBAHKAN
use App\Notifications\NotifikasiUmum; // <-- DITAMBAHKAN

class TopUpController extends Controller
{
    /**
     * Menampilkan riwayat transaksi top up.
     */
    public function index()
    {
        $user = Auth::user();
        
        // Mengambil dari relasi transactions (pastikan relasi ada di Model User)
        $transactions = $user->transactions()
                            ->where('type', 'topup')
                            ->latest()
                            ->paginate(15);

        return view('customer.topup.index', compact('transactions'));
    }

    /**
     * Menampilkan halaman form top up.
     */
    public function create()
    {
        return view('customer.topup.create');
    }

    /**
     * =========================================================================
     * FUNGSI STORE (Alur Baru: Upload Bukti di Halaman Show)
     * =========================================================================
     */
    public function store(Request $request, DokuJokulService $dokuJokulService)
    {
        $validated = $request->validate([
            'amount'            => 'required|numeric|min:10000',
            'payment_method'    => 'required|string|max:255',
            
            // ==========================================================
            // === PERUBAHAN: Validasi proof_of_payment DIHAPUS dari sini ===
            // ==========================================================
        ]);

        // ==========================================================
        // === PERBAIKAN SYNTAX ERROR (Tanda '{' dihapus) ===
        // ==========================================================
        DB::beginTransaction(); 
        
        try {
            $user = Auth::user();
            $amount = (int) $validated['amount'];
            $invoiceNumber = 'TOPUP-' . strtoupper(Str::random(10));

            // ==========================================================
            // Logika untuk TRANSFER MANUAL (Alur Baru)
            // ==========================================================
            if ($validated['payment_method'] === 'TRANSFER_MANUAL') {
                
                Log::info('Memulai Top Up Manual untuk ' . $invoiceNumber);

                // === Logika upload file DIHAPUS dari SINI ===

                $transaction = Transaction::create([
                    'user_id'            => $user->id_pengguna, // Sesuaikan dengan primary key User Anda
                    'amount'             => $amount,
                    'type'               => 'topup',
                    'status'             => 'pending',
                    'payment_method'     => $validated['payment_method'],
                    'description'        => 'Top up saldo via Transfer Manual',
                    'reference_id'       => $invoiceNumber,
                    'payment_proof_path' => null, // Path bukti transfer dikosongkan dulu
                    'payment_url'        => null,
                ]);

                // === Notifikasi admin dipindah ke uploadProof ===

                DB::commit();

                // Langsung redirect ke halaman 'show' agar customer bisa lihat No. Rekening
                return redirect()->route('customer.topup.show', ['topup' => $transaction->reference_id])
                                 ->with('success', 'Silakan lakukan transfer dan upload bukti pembayaran Anda di halaman ini.');
            
            } 
            
            // ==========================================================
            // === BAGIAN YANG HILANG: Logika DOKU & TRIPAY ===
            // ==========================================================
            else {
                
                // Inisialisasi $redirectUrl (Perbaikan dari error lama)
                $redirectUrl = null;

                $transaction = Transaction::create([
                    'user_id'        => $user->id_pengguna,
                    'amount'         => $amount,
                    'type'           => 'topup',
                    'status'         => 'pending',
                    'payment_method' => $validated['payment_method'],
                    'description'    => 'Top up saldo via ' . $validated['payment_method'],
                    'reference_id'   => $invoiceNumber,
                ]);

                // Notifikasi Admin (Opsional untuk PG)
                $message = $user->nama_lengkap . ' meminta top up sebesar Rp ' . number_format($amount);
                $url = route('admin.saldo.requests.index'); 
                event(new AdminNotificationEvent('Permintaan Top Up Baru!', $message, $url));

                
                $paymentUrl = null;
                $paymentGateway = 'tripay'; // Default
                
                if (strtoupper($validated['payment_method']) === 'DOKU_JOKUL') {
                    $paymentGateway = 'doku';
                }

                $customerData = [
                    'name'  => $user->nama_lengkap,
                    'email' => $user->email,
                    'phone' => $user->no_wa
                ];

                if ($paymentGateway === 'doku') {
                    // --- PROSES VIA DOKU JOKUL ---
                    Log::info('Memulai Top Up DOKU (Jokul) untuk ' . $invoiceNumber);
                    
                    $DokuJokulService = $dokuJokulService; 
                    $lineItems = [
                        ['name' => 'Top Up Saldo', 'price' => $amount, 'quantity' => 1]
                    ];
                    $successRedirectUrl = route('customer.topup.show', ['topup' => $invoiceNumber]);
                    
                    $paymentUrl = $DokuJokulService->createPayment(
                        $invoiceNumber, 
                        $amount, 
                        $customerData, 
                        $lineItems, 
                        [], // additionalInfo
                        $successRedirectUrl // redirectUrl
                    );
                    
                    $redirectUrl = $paymentUrl; 
                    
                    if (empty($paymentUrl)) {
                        throw new Exception('Gagal membuat transaksi DOKU.');
                    }

                } else {
                    // --- PROSES VIA TRIPAY ---
                    Log::info('Memulai Top Up Tripay untuk ' . $invoiceNumber);
                    
                    $apiKey       = config('tripay.api_key');
                    $privateKey   = config('tripay.private_key');
                    $merchantCode = config('tripay.merchant_code');
                    $mode         = config('tripay.mode', 'sandbox');

                    $payload = [
                        'method'         => $validated['payment_method'],
                        'merchant_ref'   => $invoiceNumber,
                        'amount'         => $amount,
                        'customer_name'  => $customerData['name'],
                        'customer_email' => $customerData['email'],
                        'customer_phone' => $customerData['phone'],
                        'order_items'    => [
                            ['sku' => 'TOPUP', 'name' => 'Top Up Saldo', 'price' => $amount, 'quantity' => 1,],
                        ],
                        'expired_time'   => time() + (1 * 60 * 60), // 1 Jam
                        'signature'      => hash_hmac('sha256', $merchantCode.$invoiceNumber.$amount, $privateKey),
                    ];

                    $baseUrl = $mode === 'production'
                        ? 'https://tripay.co.id/api/transaction/create'
                        : 'https://tripay.co.id/api-sandbox/transaction/create';
                    
                    $response = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])
                                    ->timeout(30)
                                    ->post($baseUrl, $payload);
                    
                    if ($response->successful() && isset($response->json()['success']) && $response->json()['success'] === true) {
                        $tripayData = $response->json()['data'];
                        $redirectUrl = $tripayData['checkout_url'] ?? null;
                        
                        if (empty($redirectUrl)) {
                             $redirectUrl = route('customer.topup.show', ['topup' => $transaction->reference_id]);
                        }
                        
                        $paymentUrl = $tripayData['pay_code']   ?? 
                                      $tripayData['qr_url']     ?? 
                                      $redirectUrl            ?? 
                                      null;

                    } else {
                        Log::error('Gagal membuat transaksi di Tripay', $response->json());
                        throw new Exception('Gagal membuat transaksi di Tripay: ' . ($response->json()['message'] ?? 'Error tidak diketahui'));
                    }
                }

                // 4. SIMPAN URL/KODE BAYAR & COMMIT
                $transaction->payment_url = $paymentUrl;
                $transaction->save();
                
                
                DB::commit();

                // 5. ARAHKAN KE HALAMAN PEMBAYARAN
                if (!empty($redirectUrl)) {
                    return redirect()->away($redirectUrl);
                }

                // Fallback jika tidak ada URL
                return redirect()->route('customer.topup.show', ['topup' => $transaction->reference_id]);
            
            } // Tutup else payment gateway

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal memproses Top Up: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan: ' . $e->getMessage()
                ], 500);
            }
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    } // Tutup store()

    /**
     * Menampilkan detail transaksi (termasuk yang pending untuk dibayar).
     */
    public function show($topup)
    {
        $user = Auth::user();

        $topUp = Transaction::where('reference_id', $topup)
                            ->where('user_id', $user->id_pengguna) // Sesuaikan
                            ->where('type', 'topup')
                            ->firstOrFail();

        return view('customer.topup.show', compact('topUp'));
    }
    
    // ==========================================================
    // === METHOD BARU: Untuk menangani upload bukti dari halaman show ===
    // ==========================================================
    /**
     * Meng-upload bukti bayar untuk transaksi manual yang pending.
     */
    public function uploadProof(Request $request, $reference_id)
    {
        $validated = $request->validate([
            'proof_of_payment' => [
                'required',
                'image',
                'mimes:jpeg,png,jpg',
                'max:2048',
            ],
        ]);

        DB::beginTransaction();
        try {
            $user = Auth::user();
            
            // Cari transaksi yang pending & milik user ini
            $transaction = Transaction::where('reference_id', $reference_id)
                ->where('user_id', $user->id_pengguna)
                ->where('description', 'LIKE', '%Transfer Manual%') // <--- INI PERBAIKANNYA
                ->where('status', 'pending')
                ->firstOrFail();

            // Hapus bukti lama jika ada (Opsional, tapi bagus)
            if ($transaction->payment_proof_path) {
                Storage::disk('public')->delete($transaction->payment_proof_path);
            }

            // Simpan file baru
            $filePath = $request->file('proof_of_payment')->store('proofs_of_payment', 'public');
            $transaction->payment_proof_path = $filePath;
            $transaction->save();

           // ==========================================================
            // 👇 [PERBAIKAN] KIRIM NOTIFIKASI KE ADMIN (Pindah ke sini)
            // ==========================================================
            try {
                $admins = User::where('role', 'admin')->get();
                if ($admins->isNotEmpty()) {
                    $dataNotifAdmin = [
                        'tipe'        => 'TopUp',
                        'judul'       => 'Konfirmasi Top Up Manual',
                        'pesan_utama' => $user->nama_lengkap . ' telah mengunggah bukti bayar Rp ' . number_format($transaction->amount),
                        'url'         => route('admin.saldo.requests.index'),
                        'icon'        => 'fas fa-upload',
                    ];
                    Notification::send($admins, new NotifikasiUmum($dataNotifAdmin));
                }
            } catch (Exception $e) {
                Log::error('Gagal kirim notif admin (uploadProof): ' . $e->getMessage());
            }
            // ==========================================================
            // 👆 AKHIR PERBAIKAN
            // ==========================================================


            DB::commit();

            return back()->with('success', 'Bukti transfer Anda telah terkirim dan sedang menunggu konfirmasi admin.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal upload bukti Top Up: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }
    // ==========================================================

    
    /**
     * =========================================================================
     * HANDLER WEBHOOK DOKU (JOKUL)
     * =========================================================================
     */
    public function handleDokuCallback(array $data)
    {
        $merchantRef = $data['order']['invoice_number'];
        $status = $data['transaction']['status']; // Seharusnya 'SUCCESS'

        Log::info('Processing DOKU Callback (di TopUpController)...', [
            'ref' => $merchantRef, 'status' => $status
        ]);
        
        $internalStatus = ($status === 'SUCCESS') ? 'PAID' : 'FAILED';
        
        return self::processTopUp($merchantRef, $internalStatus, $data['order']['amount']);
    }

    /**
     * =========================================================================
     * HANDLER WEBHOOK TRIPAY
     * =========================================================================
     */
    public static function processTopUpCallback($merchantRef, $status, $amount)
    {
        Log::info('Processing Tripay Callback (di TopUpController)...', [
            'ref' => $merchantRef, 'status' => $status
        ]);
        
        return self::processTopUp($merchantRef, $status, $amount);
    }
    
    /**
     * =========================================================================
     * PROSESOR INTI TOP UP (Dipakai oleh DOKU & TRIPAY)
     * =========================================================================
     */
    private static function processTopUp($merchantRef, $status, $amount)
    {
        DB::beginTransaction();
        try {
            $transaction = Transaction::where('reference_id', $merchantRef)->lockForUpdate()->first();

            if (!$transaction) {
                Log::error('TopUp Callback: Transaksi tidak ditemukan.', ['ref' => $merchantRef]);
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Not Found'], 404);
            }

            if ($transaction->status !== 'pending') {
                Log::info('TopUp Callback: Transaksi sudah diproses.', ['ref' => $merchantRef, 'status' => $transaction->status]);
                DB::rollBack();
                return response()->json(['success' => true, 'message' => 'Already processed']);
            }
            
            if ($transaction->payment_method === 'TRANSFER_MANUAL') {
                 Log::warning('TopUp Callback: Mencoba memproses TRANSFER_MANUAL via webhook.', ['ref' => $merchantRef]);
                 DB::rollBack();
                 return response()->json(['success' => false, 'message' => 'Manual transfer'], 400);
            }

            // Validasi jumlah
            if ($transaction->amount != $amount) {
                 Log::warning('TopUp Callback: Jumlah tidak cocok.', [
                     'db_amount' => $transaction->amount, 
                     'paid_amount' => $amount
                 ]);
                 // Tetap proses, tapi catat.
            }

            if ($status === 'PAID') { // PAID (Tripay) atau SUCCESS (DOKU)
                
                $transaction->status = 'success';
                $transaction->save();
                
                $user = User::find($transaction->user_id);
                if ($user) {
                    $user->increment('saldo', $transaction->amount); // Tambah saldo utama
                    
                    Log::info('TopUp Callback: Saldo user berhasil ditambah.', [
                        'user_id' => $user->id_pengguna, // Sesuaikan
                        'amount' => $transaction->amount
                    ]);
                    
                    // ==========================================================
                    // 👇 [PERBAIKAN] KIRIM EVENT DAN NOTIFIKASI
                    // ==========================================================
                    
                    // 1. Kirim event ke UI Customer
                    try {
                        $message = 'Top up Anda sebesar ' . number_format($transaction->amount) . ' telah berhasil.';
                        event(new SaldoUpdated($user, $transaction->amount, $user->saldo, $message));
                    } catch (Exception $e) {
                        Log::error('Gagal broadcast SaldoUpdated: ' . $e->getMessage());
                    }
                    
                    // 2. Kirim notifikasi DB ke Customer
                    try {
                        $dataNotifCustomer = [
                            'tipe'        => 'TopUp',
                            'judul'       => 'Top Up Berhasil',
                            'pesan_utama' => 'Top up saldo Rp ' . number_format($transaction->amount) . ' telah berhasil.',
                            'url'         => route('customer.topup.index'), // Link ke riwayat topup
                            'icon'        => 'fas fa-check-circle',
                        ];
                        $user->notify(new NotifikasiUmum($dataNotifCustomer));
                    } catch (Exception $e) {
                        Log::error('Gagal kirim notif customer (topup success): ' . $e->getMessage());
                    }
                    
                    // 3. Kirim notifikasi DB ke Admin
                    try {
                        $admins = User::where('role', 'admin')->get();
                        if ($admins->isNotEmpty()) {
                            $dataNotifAdmin = [
                                'tipe'        => 'TopUp',
                                'judul'       => 'Top Up Otomatis Berhasil',
                                'pesan_utama' => $user->nama_lengkap . ' berhasil top up via PG Rp ' . number_format($transaction->amount),
                                'url'         => route('admin.saldo.requests.history'), // Link ke riwayat
                                'icon'        => 'fas fa-check-circle',
                            ];
                            Notification::send($admins, new NotifikasiUmum($dataNotifAdmin));
                        }
                    } catch (Exception $e) {
                        Log::error('Gagal kirim notif admin (topup success): ' . $e->getMessage());
                    }
                    // ==========================================================
                    // 👆 AKHIR PERBAIKAN
                    // ==========================================================
                    
                } else {
                    Log::error('TopUp Callback: User tidak ditemukan!', ['user_id' => $transaction->user_id]);
                }
                
            } else { // FAILED, EXPIRED, dll.
                $transaction->status = 'failed';
                $transaction->save();
                Log::info('TopUp Callback: Transaksi gagal.', ['ref' => $merchantRef, 'status' => $status]);
            }
            
            DB::commit();
            return response()->json(['success' => true]); // Kirim 200 OK ke gateway

        } catch (\Exception $e) {
            DB::rollBack();
            Log::critical('TopUp Callback: CRITICAL ERROR.', [
                'ref' => $merchantRef, 'error' => $e->getMessage()
            ]);
            return response()->json(['success' => false, 'message' => 'Internal Error'], 500);
        }
    }
    
    /**
     * Method BARU untuk mengecek status via API (untuk polling).
     */
    public function checkStatus($reference_id)
    {
        $transaction = Transaction::where('reference_id', $reference_id)
                                ->where('user_id', auth()->id())
                                ->first(['status']); // Hanya ambil kolom status

        if (!$transaction) {
            return response()->json(['status' => 'not_found'], 404);
        }

        return response()->json(['status' => $transaction->status]);
    }
}