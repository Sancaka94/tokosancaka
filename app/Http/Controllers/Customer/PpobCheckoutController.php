<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str; // 🔥 Pastikan Str di-import
use Exception;

// --- MODEL ---
use App\Models\User;
use App\Models\PpobTransaction; 

// --- SERVICES ---
// Pastikan kedua service ini ada
use App\Services\DokuJokulService;
use App\Services\DigiflazzService;

class PpobCheckoutController extends Controller
{
    /**
     * 1. PREPARE: Tambah Item ke Keranjang (Session)
     */
    public function prepare(Request $request)
    {
        try {
            $data = $request->validate([
                'sku'         => 'required',
                'name'        => 'required',
                'price'       => 'required|numeric',
                'customer_no' => 'required',
                'ref_id'      => 'nullable', // Ref ID Inquiry (untuk Pasca)
                'desc'        => 'nullable'
            ]);

            $tempId = uniqid('item_');

            $newItem = [
                'id'          => $tempId, 
                'sku'         => $data['sku'],
                'name'        => $data['name'],
                'price'       => (int) $data['price'],
                'customer_no' => $data['customer_no'],
                // Jika Pasca, ref_id dari Inquiry (INQ-...) wajib dibawa
                'ref_id'      => $data['ref_id'] ?? 'PRE-' . time() . rand(100,999), 
                'desc'        => $data['desc'] ?? [], 
                'quantity'    => 1,
                'is_ppob'     => true
            ];

            $cart = session()->get('ppob_cart', []);
            $cart[] = $newItem;
            session()->put('ppob_cart', $cart);

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * 2. INDEX: Tampilkan Halaman Checkout
     */
    public function index()
    {
        $cart = session()->get('ppob_cart', []);

        if (empty($cart)) {
            return redirect()->route('customer.dashboard')->with('error', 'Tidak ada transaksi yang diproses.');
        }

        $totalPrice = array_sum(array_column($cart, 'price'));
        $user = Auth::user();
        $paymentChannels = [];

        // --- A. SALDO AKUN ---
        $paymentChannels['saldo'] = [
            'code'        => 'SALDO', 
            'name'        => 'Saldo Akun', 
            'description' => 'Sisa: Rp ' . number_format($user->saldo ?? 0),
            'balance'     => $user->saldo ?? 0,
            'active'      => true,
            'icon_url'    => 'https://cdn-icons-png.flaticon.com/512/217/217853.png'
        ];

        // --- B. TRIPAY CHANNELS (API) ---
        try {
            $apiKey  = config('tripay.api_key');
            $mode    = config('tripay.mode');
            $baseUrl = ($mode === 'production') 
                ? 'https://tripay.co.id/api/merchant/payment-channel' 
                : 'https://tripay.co.id/api-sandbox/merchant/payment-channel';

            $res = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])
                        ->timeout(3)
                        ->withoutVerifying()
                        ->get($baseUrl);

            if ($res->successful() && isset($res->json()['success']) && $res->json()['success'] === true) {
                foreach($res->json()['data'] as $ch) {
                    if($ch['active'] && in_array($ch['group'], ['QRIS', 'Virtual Account', 'E-Wallet'])) {
                        $paymentChannels['tripay'][] = $ch;
                    }
                }
            }
        } catch (Exception $e) {
            Log::error('Tripay Error: ' . $e->getMessage());
        }

        // --- C. DOKU (MANUAL) ---
        $paymentChannels['doku'] = [
            ['code' => 'DOKU_CC', 'name' => 'Kartu Kredit', 'icon_url' => 'https://cdn-icons-png.flaticon.com/512/6963/6963703.png'],
            ['code' => 'DOKU_VA', 'name' => 'DOMPET SANCAKA', 'icon_url' => 'https://cdn-icons-png.flaticon.com/512/2331/2331922.png'],
        ];

        // 🔥 GENERATE IDEMPOTENCY KEY UNIK
        $idempotencyKey = (string) Str::uuid();

        // 🔥 PASSING KE VIEW
        return view('customer.ppob.checkout', compact('cart', 'totalPrice', 'user', 'paymentChannels', 'idempotencyKey'));
    }

    /**
     * 3. REMOVE ITEM
     */
    public function removeItem($id)
    {
        $cart = session()->get('ppob_cart', []);
        $newCart = array_filter($cart, function($item) use ($id) {
            return $item['id'] !== $id;
        });
        session()->put('ppob_cart', array_values($newCart));
        return redirect()->route('ppob.checkout.index')->with('success', 'Item berhasil dihapus.');
    }

    /**
     * 4. CLEAR CART
     */
    public function clearCart()
    {
        session()->forget('ppob_cart');
        return redirect()->route('customer.dashboard')->with('success', 'Transaksi dibatalkan.');
    }

    /**
     * 5. STORE: Proses Pembayaran (INTEGRASI DIGIFLAZZ DI SINI)
     */
    public function store(Request $request)
    {

        // 🔥 0. CEK IDEMPOTENCY KEY
        // Mencegah user melakukan refresh halaman setelah submit, atau klik ganda
        $key = $request->input('idempotency_key');
        if ($key && PpobTransaction::where('idempotency_key', $key)->exists()) {
            return redirect()->route('customer.ppob.history')
                ->with('warning', 'Transaksi PPOB ini sudah diproses sebelumnya (Mencegah Double Pembayaran).');
        }

        $request->validate(['payment_method' => 'required']);
        
        $cart = session()->get('ppob_cart', []);
        if (empty($cart)) return redirect()->route('customer.dashboard');

        $user = Auth::user();
        $totalPrice = array_sum(array_column($cart, 'price'));

        DB::beginTransaction();
        try {
            // Validasi Saldo
            if ($request->payment_method === 'saldo' && $user->saldo < $totalPrice) {
                throw new Exception('Saldo tidak mencukupi untuk total transaksi Rp ' . number_format($totalPrice));
            }

            $groupRefId = 'INV-' . time() . rand(100,999);
            $createdTransactions = []; // Array untuk menampung data transaksi yang dibuat

            // --- LOOPING INSERT DATABASE ---
            foreach ($cart as $item) {
                // LOGIKA PENTING: Penentuan Order ID / Ref ID
                // Jika Pascabayar (ref_id diawali INQ), kita HARUS pakai ID itu lagi sebagai Order ID
                // agar Digiflazz bisa memproses 'pay-pasca' dengan ID yang sama.
                $isPasca = isset($item['ref_id']) && str_starts_with($item['ref_id'], 'INQ');
                
                if ($isPasca) {
                    $orderId = $item['ref_id']; // Pakai ID Inquiry yang lama
                } else {
                    $orderId = 'TRX-' . time() . rand(1000, 9999) . rand(10,99); // Buat baru untuk Prabayar
                }

                $trx = new PpobTransaction();
                $trx->user_id        = $user->id_pengguna;
                $trx->order_id       = $orderId;
                $trx->group_order_id = $groupRefId;
                $trx->buyer_sku_code = $item['sku'];
                $trx->customer_no    = $item['customer_no'];
                $trx->selling_price  = $item['price'];
                $trx->price          = 0; // Modal (akan diupdate callback/response)
                $trx->profit         = 0;
                $trx->payment_method = $request->payment_method;
                $trx->desc           = json_encode($item['desc'] ?? []);
                
                // Status Awal
                $trx->status         = ($request->payment_method === 'saldo') ? 'Processing' : 'Pending';
                $trx->message        = 'Menunggu Pembayaran';

                // 🔥 SIMPAN IDEMPOTENCY KEY KE SETIAP ITEM TRANSAKSI
                // Semua item dalam satu kali checkout memiliki key yang sama
                $trx->idempotency_key = $key;

                $trx->save();

                // Simpan info untuk diproses setelah ini
                $createdTransactions[] = [
                    'order_id' => $orderId,
                    'sku'      => $item['sku'],
                    'cust_no'  => $item['customer_no'],
                    'is_pasca' => $isPasca,
                    'price'    => $item['price']
                ];
            }

            // ==========================================
            // LOGIC A: BAYAR PAKAI SALDO (EKSEKUSI LANGSUNG)
            // ==========================================
            if ($request->payment_method === 'saldo') {
                // 1. Potong Saldo User
                $user->decrement('saldo', $totalPrice);
                
                // 2. Eksekusi Ke Digiflazz (Looping per item)
                $digiflazz = new DigiflazzService();
                
                foreach ($createdTransactions as $trxItem) {
                    $response = [];
                    
                    // Cek apakah ini Pascabayar atau Prabayar
                    if ($trxItem['is_pasca']) {
                        // PANGGIL PAY PASCA
                        $response = $digiflazz->payPasca($trxItem['sku'], $trxItem['cust_no'], $trxItem['order_id']);
                    } else {
                        // PANGGIL TRANSAKSI BIASA (PULSA/DATA)
                        $response = $digiflazz->transaction($trxItem['sku'], $trxItem['cust_no'], $trxItem['order_id']);
                    }

                    // Cek jika Gagal Langsung (Saldo habis / Gangguan)
                    if (isset($response['data']['status']) && in_array($response['data']['status'], ['Gagal', 'Failed'])) {
                        // Update Status DB
                        PpobTransaction::where('order_id', $trxItem['order_id'])->update([
                            'status' => 'Failed',
                            'message' => $response['data']['message'] ?? 'Gagal dari Provider'
                        ]);

                        // REFUND SALDO USER (Partial Refund)
                        // Kembalikan saldo senilai harga jual item ini
                        $user->increment('saldo', $trxItem['price']);
                    }
                    // Jika Pending/Sukses, biarkan status 'Processing', nanti Webhook yang update jadi Success
                }
                
                DB::commit();
                session()->forget('ppob_cart');
                
                return redirect()->route('customer.ppob.history')->with('success', 'Transaksi sedang diproses!');
            }

            // ==========================================
            // LOGIC B: BAYAR PAKAI TRIPAY
            // ==========================================
            elseif (isset($request->payment_method) && !str_contains(strtoupper($request->payment_method), 'DOKU')) {
                
                $apiKey       = config('tripay.api_key');
                $privateKey   = config('tripay.private_key');
                $merchantCode = config('tripay.merchant_code');
                $isProd       = config('tripay.mode') === 'production';
                $url          = $isProd 
                                ? 'https://tripay.co.id/api/transaction/create' 
                                : 'https://tripay.co.id/api-sandbox/transaction/create';

                $signature = hash_hmac('sha256', $merchantCode . $groupRefId . $totalPrice, $privateKey);

                $orderItems = [];
                foreach($cart as $c) {
                    $orderItems[] = [
                        'sku' => $c['sku'],
                        'name' => substr($c['name'], 0, 250),
                        'price' => $c['price'],
                        'quantity' => 1
                    ];
                }

                $payload = [
                    'method'         => $request->payment_method,
                    'merchant_ref'   => $groupRefId,
                    'amount'         => $totalPrice,
                    'customer_name'  => $user->nama_lengkap,
                    'customer_email' => $user->email,
                    'customer_phone' => $user->no_wa ?? '0812345678',
                    'order_items'    => $orderItems,
                    'expired_time'   => time() + (60 * 60),
                    'signature'      => $signature,
                    'return_url'     => route('customer.dashboard') 
                ];

                $response = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])
                                ->timeout(30)
                                ->withoutVerifying()
                                ->post($url, $payload);
                
                if ($response->successful() && $response->json()['success']) {
                    $d = $response->json()['data'];
                    $payUrl = $d['checkout_url'] ?? $d['pay_url'] ?? $d['qr_url'];
                    
                    // Update Payment URL
                    PpobTransaction::where('group_order_id', $groupRefId)->update(['payment_url' => $payUrl]);

                    DB::commit();
                    session()->forget('ppob_cart');

                    return redirect($payUrl);
                } else {
                    throw new Exception('Tripay Error: ' . ($response->json()['message'] ?? 'Connection Failed'));
                }
            }

            // ==========================================
            // LOGIC C: BAYAR PAKAI DOKU
            // ==========================================
            else {
                $dokuService = new DokuJokulService();
                $customerData = [
                    'name'  => $user->nama_lengkap,
                    'email' => $user->email,
                    'phone' => $user->no_wa
                ];
                
                $dokuItems = [];
                foreach($cart as $c) {
                    $dokuItems[] = [
                        'name' => $c['name'],
                        'quantity' => 1,
                        'price' => $c['price'],
                        'sku' => $c['sku']
                    ];
                }

                $paymentUrl = $dokuService->createPayment($groupRefId, $totalPrice, $customerData, $dokuItems);
                
                if (!$paymentUrl) throw new Exception('Gagal generate link pembayaran DOKU.');

                PpobTransaction::where('group_order_id', $groupRefId)->update(['payment_url' => $paymentUrl]);
                
                DB::commit();
                session()->forget('ppob_cart');
                
                return redirect($paymentUrl);
            }

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('PPOB Checkout Error: ' . $e->getMessage());
            return back()->with('error', 'Gagal memproses transaksi: ' . $e->getMessage());
        }
    }

    public function invoice($invoice)
    {
        $user = Auth::user();
        $transaction = PpobTransaction::where('order_id', $invoice)
                        ->where('user_id', $user->id_pengguna)
                        ->firstOrFail();

        return view('customer.ppob.invoice', compact('transaction'));
    }
}