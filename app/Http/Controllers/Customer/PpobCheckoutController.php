<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Exception;

// --- MODEL ---
use App\Models\User;
use App\Models\PpobTransaction; 

// --- SERVICES ---
use App\Services\DokuJokulService;

class PpobCheckoutController extends Controller
{
    /**
     * 1. PREPARE: Tambah Item ke Keranjang (Session)
     * Dipanggil via AJAX dari halaman Pricelist/Tagihan
     */
    public function prepare(Request $request)
    {
        try {
            $data = $request->validate([
                'sku'         => 'required',
                'name'        => 'required',
                'price'       => 'required|numeric',
                'customer_no' => 'required',
                'ref_id'      => 'nullable',
                'desc'        => 'nullable'
            ]);

            // Buat ID unik sementara (agar bisa dihapus spesifik itemnya nanti)
            $tempId = uniqid('item_');

            $newItem = [
                'id'          => $tempId, 
                'sku'         => $data['sku'],
                'name'        => $data['name'],
                'price'       => (int) $data['price'],
                'customer_no' => $data['customer_no'],
                'ref_id'      => $data['ref_id'] ?? 'PRE-' . time() . rand(100,999), 
                'desc'        => $data['desc'] ?? [], 
                'quantity'    => 1,
                'is_ppob'     => true
            ];

            // Ambil data keranjang lama (jika ada)
            $cart = session()->get('ppob_cart', []);
            
            // Tambahkan item baru ke array (Push)
            $cart[] = $newItem;

            // Simpan kembali ke session
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
        // Ambil Keranjang
        $cart = session()->get('ppob_cart', []);

        // Jika Kosong, tendang ke dashboard/pricelist
        if (empty($cart)) {
            return redirect()->route('customer.dashboard')->with('error', 'Tidak ada transaksi yang diproses.');
        }

        // Hitung Total Harga Semua Item
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

            // Cek cache dulu biar gak nembak API terus (Opsional, tapi bagus buat performa)
            // Disini kita tembak langsung sesuai request Anda
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
            // Silent fail biar halaman gak crash kalau tripay down
            Log::error('Tripay Error: ' . $e->getMessage());
        }

        // --- C. DOKU (MANUAL) ---
        $paymentChannels['doku'] = [
            ['code' => 'DOKU_CC', 'name' => 'Kartu Kredit', 'icon_url' => 'https://cdn-icons-png.flaticon.com/512/6963/6963703.png'],
            ['code' => 'DOKU_VA', 'name' => 'DOMPET SANCAKA', 'icon_url' => 'https://cdn-icons-png.flaticon.com/512/2331/2331922.png'],
        ];

        return view('customer.ppob.checkout', compact('cart', 'totalPrice', 'user', 'paymentChannels'));
    }

    /**
     * 3. REMOVE ITEM: Hapus satu item dari keranjang
     */
    public function removeItem($id)
    {
        $cart = session()->get('ppob_cart', []);

        // Filter: Ambil semua item KECUALI yang ID-nya sama dengan parameter $id
        $newCart = array_filter($cart, function($item) use ($id) {
            return $item['id'] !== $id;
        });

        // Re-index array (biar urutannya bener 0,1,2...)
        session()->put('ppob_cart', array_values($newCart));

        return redirect()->route('ppob.checkout.index')->with('success', 'Item berhasil dihapus.');
    }

    /**
     * 4. CLEAR CART: Batalkan Semua Transaksi
     */
    public function clearCart()
    {
        session()->forget('ppob_cart');
        // Redirect ke dashboard atau pricelist
        return redirect()->route('customer.dashboard')->with('success', 'Transaksi dibatalkan.');
    }

    /**
     * 5. STORE: Proses Pembayaran (Looping Transaction)
     */
    public function store(Request $request)
    {
        $request->validate(['payment_method' => 'required']);
        
        $cart = session()->get('ppob_cart', []);
        if (empty($cart)) return redirect()->route('customer.dashboard');

        $user = Auth::user();
        
        // Hitung ulang total di backend (aman dari manipulasi html)
        $totalPrice = array_sum(array_column($cart, 'price'));

        DB::beginTransaction();
        try {
            // --- Validasi Saldo Global ---
            if ($request->payment_method === 'saldo' && $user->saldo < $totalPrice) {
                throw new Exception('Saldo tidak mencukupi untuk total transaksi Rp ' . number_format($totalPrice));
            }

            // Group Transaction ID (Jika bayar gateway, butuh 1 ID Referensi Utama)
            $groupRefId = 'INV-' . time() . rand(100,999);
            
            // Tampung Order ID yang dibuat
            $createdOrderIds = [];

            // --- LOOPING INSERT DATABASE ---
            foreach ($cart as $item) {
                $orderId = 'TRX-' . time() . rand(1000, 9999) . rand(10,99);
                $createdOrderIds[] = $orderId;

                $trx = new PpobTransaction();
                $trx->user_id        = $user->id_pengguna; // Sesuaikan kolom user ID Anda
                $trx->order_id       = $orderId;
                $trx->group_order_id = $groupRefId; // Kolom baru (opsional) buat grouping invoice
                $trx->buyer_sku_code = $item['sku'];
                $trx->customer_no    = $item['customer_no'];
                $trx->selling_price  = $item['price'];
                $trx->price          = 0; // Modal (update nanti via callback provider)
                $trx->profit         = 0;
                $trx->payment_method = $request->payment_method;
                $trx->desc           = json_encode($item['desc'] ?? []);
                
                // Jika Saldo -> Processing, Jika Gateway -> Pending
                $trx->status         = ($request->payment_method === 'saldo') ? 'Processing' : 'Pending';
                $trx->message        = 'Menunggu Pembayaran';
                $trx->save();
            }

            // ==========================================
            // LOGIC A: BAYAR PAKAI SALDO (INSTANT)
            // ==========================================
            if ($request->payment_method === 'saldo') {
                // Potong Saldo Sekaligus
                $user->decrement('saldo', $totalPrice);
                
                DB::commit();
                session()->forget('ppob_cart');
                
                return redirect()->route('customer.dashboard')->with('success', 'Semua transaksi berhasil diproses!');
            }

            // ==========================================
            // LOGIC B: BAYAR PAKAI TRIPAY (SINGLE LINK FOR ALL)
            // ==========================================
            elseif (isset($request->payment_method) && !str_contains(strtoupper($request->payment_method), 'DOKU')) {
                
                $apiKey       = config('tripay.api_key');
                $privateKey   = config('tripay.private_key');
                $merchantCode = config('tripay.merchant_code');
                $isProd       = config('tripay.mode') === 'production';
                $url          = $isProd 
                                ? 'https://tripay.co.id/api/transaction/create' 
                                : 'https://tripay.co.id/api-sandbox/transaction/create';

                // Gunakan Group ID sebagai Referensi ke Tripay
                $signature = hash_hmac('sha256', $merchantCode . $groupRefId . $totalPrice, $privateKey);

                // Buat item list untuk payload Tripay
                $orderItems = [];
                foreach($cart as $c) {
                    $orderItems[] = [
                        'sku' => $c['sku'],
                        'name' => substr($c['name'], 0, 250), // Batasi panjang nama
                        'price' => $c['price'],
                        'quantity' => 1
                    ];
                }

                $payload = [
                    'method'         => $request->payment_method,
                    'merchant_ref'   => $groupRefId, // Kirim Group Ref ID
                    'amount'         => $totalPrice,
                    'customer_name'  => $user->nama_lengkap,
                    'customer_email' => $user->email,
                    'customer_phone' => $user->no_wa ?? '0812345678',
                    'order_items'    => $orderItems,
                    'expired_time'   => time() + (60 * 60), // 1 Jam
                    'signature'      => $signature,
                    // Redirect ke dashboard atau history transaksi setelah bayar
                    'return_url'     => route('customer.dashboard') 
                ];

                $response = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])
                                ->timeout(30)
                                ->withoutVerifying()
                                ->post($url, $payload);
                
                if ($response->successful() && $response->json()['success']) {
                    $d = $response->json()['data'];
                    $payUrl = $d['checkout_url'] ?? $d['pay_url'] ?? $d['qr_url'];
                    
                    // Update Payment URL ke SEMUA transaksi di batch ini
                    PpobTransaction::whereIn('order_id', $createdOrderIds)->update(['payment_url' => $payUrl]);

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
                
                // Doku butuh line items
                $dokuItems = [];
                foreach($cart as $c) {
                    $dokuItems[] = [
                        'name' => $c['name'],
                        'quantity' => 1,
                        'price' => $c['price'],
                        'sku' => $c['sku']
                    ];
                }

                // Gunakan GroupRefId
                $paymentUrl = $dokuService->createPayment($groupRefId, $totalPrice, $customerData, $dokuItems);
                
                if (!$paymentUrl) throw new Exception('Gagal generate link pembayaran DOKU.');

                // Update URL ke database
                PpobTransaction::whereIn('order_id', $createdOrderIds)->update(['payment_url' => $paymentUrl]);
                
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

    /**
     * 6. INVOICE (Opsional, untuk satuan)
     */
    public function invoice($invoice)
    {
        $user = Auth::user();
        $transaction = PpobTransaction::where('order_id', $invoice)
                        ->where('user_id', $user->id_pengguna)
                        ->firstOrFail();

        return view('customer.ppob.invoice', compact('transaction'));
    }
}