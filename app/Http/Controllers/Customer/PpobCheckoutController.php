<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Exception;

// --- MODEL ---
use App\Models\User;
use App\Models\PpobTransaction; 

// --- SERVICES ---
use App\Services\DokuJokulService;

class PpobCheckoutController extends Controller
{
    /**
     * 1. PREPARE SESSION (PINTU GERBANG)
     * Menerima data dari JS (Prabayar/Pascabayar) & Simpan ke Session
     */
    public function prepare(Request $request)
    {
        try {
            // Validasi Data Mentah dari Frontend
            $data = $request->validate([
                'sku'         => 'required',           // Kode Produk (PULSA10 / PLNPOST)
                'name'        => 'required',           // Nama Produk / Nama Tagihan
                'price'       => 'required|numeric',   // Harga Jual ke User
                'customer_no' => 'required',           // No HP / ID Pelanggan
                'ref_id'      => 'nullable',           // ID Inquiry (Wajib utk Pascabayar, Nullable utk Prabayar)
                'desc'        => 'nullable'            // Array Rincian Tagihan (Admin, Denda, Periode, dll)
            ]);

            // Format Data Session
            $ppobItem = [
                'sku'         => $data['sku'],
                'name'        => $data['name'],
                'price'       => (int) $data['price'],
                'customer_no' => $data['customer_no'],
                // Jika ref_id kosong (Prabayar), kita buat dummy biar gak error
                'ref_id'      => $data['ref_id'] ?? 'PRE-' . time() . rand(100,999), 
                'desc'        => $data['desc'] ?? [], // Simpan rincian sebagai array
                'quantity'    => 1,
                'is_ppob'     => true
            ];

            // Masukkan ke Session "ppob_session"
            session()->put('ppob_session', $ppobItem);

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * 2. HALAMAN CHECKOUT (TAMPILAN)
     * Mengambil data dari Session & Menampilkan Metode Pembayaran
     */
    public function index()
    {
        // Ambil Data dari Session
        $item = session()->get('ppob_session');

        // Jika Session Hilang (User kelamaan / Clear Cache) -> Tendang
        if (!$item) {
            return redirect()->route('customer.dashboard')->with('error', 'Sesi transaksi habis. Silakan ulangi pembelian.');
        }

        $user = Auth::user();
        $paymentChannels = [];

        // --- A. CHANNEL SALDO ---
        $paymentChannels['saldo'] = [
            'code'        => 'SALDO', 
            'name'        => 'Saldo Akun', 
            'description' => 'Sisa Saldo: Rp ' . number_format($user->saldo ?? 0),
            'balance'     => $user->saldo ?? 0,
            'active'      => true,
            'icon_url'    => 'https://cdn-icons-png.flaticon.com/512/217/217853.png' // Ganti icon local jika ada
        ];

        // --- B. CHANNEL TRIPAY (API CHECK) ---
        try {
            $apiKey  = config('tripay.api_key');
            $mode    = config('tripay.mode');
            $baseUrl = ($mode === 'production') 
                ? 'https://tripay.co.id/api/merchant/payment-channel' 
                : 'https://tripay.co.id/api-sandbox/merchant/payment-channel';

            $res = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])
                       ->timeout(5)
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
            Log::error('Tripay Channels Error: ' . $e->getMessage());
        }

        // --- C. CHANNEL DOKU (MANUAL ARRAY) ---
        $paymentChannels['doku'] = [
            ['code' => 'DOKU_CC', 'name' => 'Kartu Kredit', 'icon_url' => 'https://cdn-icons-png.flaticon.com/512/6963/6963703.png'],
            ['code' => 'DOKU_VA', 'name' => 'DOKU VA', 'icon_url' => 'https://cdn-icons-png.flaticon.com/512/2331/2331922.png'],
        ];

        return view('customer.ppob.checkout', compact('item', 'user', 'paymentChannels'));
    }

    /**
     * 3. PROSES PEMBAYARAN (EKSEKUSI)
     */
    public function store(Request $request)
    {
        $request->validate(['payment_method' => 'required']);
        
        $item = session()->get('ppob_session');
        if (!$item) return redirect()->route('customer.dashboard')->with('error', 'Sesi habis.');

        $user = Auth::user();
        $sellingPrice = (int) $item['price'];
        
        // Harga modal set 0 dulu (Nanti diupdate callback sukses)
        $modalPrice = 0; 
        $profit = $sellingPrice - $modalPrice;

        DB::beginTransaction();
        try {
            // 1. Generate Order ID Unik
            do {
                $orderId = 'TRX-' . now()->timestamp . rand(100, 999);
            } while (PpobTransaction::where('order_id', $orderId)->exists());

            // 2. Simpan Transaksi ke Database
            $trx = new PpobTransaction();
            $trx->user_id        = $user->id_pengguna; // Sesuaikan ID User
            $trx->order_id       = $orderId;
            $trx->buyer_sku_code = $item['sku'];
            $trx->customer_no    = $item['customer_no'];
            $trx->price          = $modalPrice;
            $trx->selling_price  = $sellingPrice;
            $trx->profit         = $profit;
            $trx->payment_method = $request->payment_method;
            
            // Simpan rincian tagihan sebagai JSON agar bisa dibaca di invoice
            $trx->desc           = json_encode($item['desc'] ?? []);
            
            // Status Awal
            $trx->status  = ($request->payment_method === 'saldo') ? 'Processing' : 'Pending';
            $trx->message = 'Menunggu Pembayaran';
            $trx->save();

            // ==========================================
            // LOGIC A: BAYAR PAKAI SALDO
            // ==========================================
            if ($request->payment_method === 'saldo') {
                if ($user->saldo < $sellingPrice) {
                    throw new Exception('Saldo akun Anda tidak mencukupi.');
                }
                
                // Potong Saldo
                /** @var \Illuminate\Database\Eloquent\Model $user */
                $user->decrement('saldo', $sellingPrice);
                
                // Update Status
                $trx->status  = 'Processing'; 
                $trx->message = 'Pembayaran Berhasil via Saldo. Sedang diproses...';
                $trx->save();

                // OPTIONAL: Disini bisa langsung panggil Service Digiflazz kalau mau instant
                // $digiflazz->process($trx);

                DB::commit();
                session()->forget('ppob_session'); // Hapus Session
                
                return redirect()->route('ppob.invoice', ['invoice' => $orderId]);
            }

            // ==========================================
            // LOGIC B: BAYAR PAKAI TRIPAY
            // ==========================================
            elseif (isset($request->payment_method) && !str_contains(strtoupper($request->payment_method), 'DOKU')) {
                // Config Tripay
                $apiKey       = config('tripay.api_key');
                $privateKey   = config('tripay.private_key');
                $merchantCode = config('tripay.merchant_code');
                $isProd       = config('tripay.mode') === 'production';
                $url          = $isProd 
                                ? 'https://tripay.co.id/api/transaction/create' 
                                : 'https://tripay.co.id/api-sandbox/transaction/create';

                // Signature Tripay
                $signature = hash_hmac('sha256', $merchantCode . $orderId . $sellingPrice, $privateKey);

                $payload = [
                    'method'         => $request->payment_method,
                    'merchant_ref'   => $orderId,
                    'amount'         => $sellingPrice,
                    'customer_name'  => $user->nama_lengkap,
                    'customer_email' => $user->email,
                    'customer_phone' => $user->no_wa ?? '0812345678',
                    'order_items'    => [[
                        'sku'      => $item['sku'],
                        'name'     => $item['name'],
                        'price'    => $sellingPrice,
                        'quantity' => 1
                    ]],
                    'expired_time'   => time() + (60 * 60), // 1 Jam
                    'signature'      => $signature,
                    'return_url'     => route('ppob.invoice', ['invoice' => $orderId])
                ];

                $response = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])
                                ->timeout(30)
                                ->withoutVerifying()
                                ->post($url, $payload);
                
                if ($response->successful() && $response->json()['success']) {
                    $d = $response->json()['data'];
                    $payUrl = $d['checkout_url'] ?? $d['pay_url'] ?? $d['qr_url'];
                    
                    $trx->payment_url = $payUrl;
                    $trx->save();

                    DB::commit();
                    session()->forget('ppob_session');

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
                $dokuItem = [[
                    'name'     => $item['name'],
                    'quantity' => 1,
                    'price'    => $sellingPrice,
                    'sku'      => $item['sku']
                ]];

                $paymentUrl = $dokuService->createPayment($orderId, $sellingPrice, $customerData, $dokuItem);
                
                if (!$paymentUrl) throw new Exception('Gagal generate link pembayaran DOKU.');

                $trx->payment_url = $paymentUrl;
                $trx->save();
                
                DB::commit();
                session()->forget('ppob_session');
                
                return redirect($paymentUrl);
            }

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('PPOB Checkout Error: ' . $e->getMessage());
            return back()->with('error', 'Gagal memproses transaksi: ' . $e->getMessage());
        }
    }

    /**
     * 4. HALAMAN INVOICE
     */
    public function invoice($invoice)
    {
        $user = Auth::user();
        
        // Ambil data transaksi milik user tersebut
        $transaction = PpobTransaction::where('order_id', $invoice)
                        ->where('user_id', $user->id_pengguna)
                        ->firstOrFail();

        return view('customer.ppob.invoice', compact('transaction'));
    }
}