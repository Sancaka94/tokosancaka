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

// --- IMPORT MODEL (WAJIB ADA) ---
use App\Models\User;
use App\Models\PpobTransaction; 

// --- IMPORT SERVICES ---
use App\Services\DokuJokulService;

class PpobCheckoutController extends Controller
{
    /**
     * 1. PREPARE: Terima data dari JS (Halaman Cek Tagihan), simpan ke Session
     */
    public function prepare(Request $request)
    {
        try {
            // Validasi input dari JavaScript fetch()
            $data = $request->validate([
                'sku'         => 'required',
                'name'        => 'required',
                'price'       => 'required|numeric',
                'ref_id'      => 'required',
                'customer_no' => 'required',
                'desc'        => 'nullable' // <--- MENERIMA ARRAY DETAIL DARI JS
            ]);

            // Simpan ke session khusus 'ppob_session'
            // desc disimpan agar nanti bisa ditampilkan di invoice
            $ppobItem = [
                'sku'         => $data['sku'],
                'name'        => $data['name'],
                'price'       => (int) $data['price'],
                'ref_id'      => $data['ref_id'],
                'customer_no' => $data['customer_no'],
                'desc'        => $data['desc'] ?? [], // Simpan array rincian
                'quantity'    => 1,
                'is_ppob'     => true
            ];

            session()->put('ppob_session', $ppobItem);

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * 2. INDEX: Tampilkan Halaman Checkout Khusus PPOB
     */
    public function index()
    {
        $item = session()->get('ppob_session');

        if (!$item) {
            return redirect()->route('customer.dashboard')->with('error', 'Sesi transaksi berakhir. Silakan ulangi cek tagihan.');
        }

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

        // --- B. TRIPAY CHANNELS ---
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

            if ($res->successful() && $res->json()['success'] === true) {
                foreach($res->json()['data'] as $ch) {
                    if($ch['active'] && in_array($ch['group'], ['QRIS', 'Virtual Account', 'E-Wallet'])) {
                        $paymentChannels['tripay'][] = $ch;
                    }
                }
            }
        } catch (Exception $e) {
            Log::error('Tripay Channels Error (PPOB): ' . $e->getMessage());
        }

        // --- C. DOKU CHANNELS ---
        $paymentChannels['doku'] = [
            ['code' => 'DOKU_CC', 'name' => 'Kartu Kredit', 'icon_url' => 'https://cdn-icons-png.flaticon.com/512/6963/6963703.png'],
            ['code' => 'DOKU_VA', 'name' => 'DOKU VA', 'icon_url' => 'https://cdn-icons-png.flaticon.com/512/2331/2331922.png'],
        ];

        return view('customer.ppob.checkout', compact('item', 'user', 'paymentChannels'));
    }

    /**
     * 3. STORE: Proses Pembayaran & Simpan Transaksi
     */
    public function store(Request $request)
    {
        $request->validate(['payment_method' => 'required']);
        
        $item = session()->get('ppob_session');
        if (!$item) {
            return redirect()->route('customer.dashboard')->with('error', 'Transaksi kadaluarsa.');
        }

        $user = Auth::user();
        $sellingPrice = (int) $item['price'];
        $modalPrice = 0; // Akan diupdate saat callback sukses dari provider
        $profit = $sellingPrice - $modalPrice;

        DB::beginTransaction();
        try {
            // 1. Buat Order ID Unik (TRX-...)
            do {
                $orderId = 'TRX-' . now()->timestamp . rand(100, 999);
            } while (PpobTransaction::where('order_id', $orderId)->exists());

            // 2. Simpan ke Tabel ppob_transactions
            $trx = new PpobTransaction();
            $trx->user_id        = $user->id_pengguna;
            $trx->order_id       = $orderId;
            $trx->buyer_sku_code = $item['sku'];
            $trx->customer_no    = $item['customer_no'];
            $trx->price          = $modalPrice;
            $trx->selling_price  = $sellingPrice;
            $trx->profit         = $profit;
            $trx->payment_method = $request->payment_method;
            
            // Simpan JSON desc agar invoice bisa menampilkan detail array
            // Pastikan kolom 'desc' ada di database, tipe JSON atau TEXT
            $trx->desc           = $item['desc'] ?? null; 
            
            // Status awal
            $trx->status  = ($request->payment_method === 'saldo') ? 'Processing' : 'Pending';
            $trx->message = 'Menunggu Pembayaran';
            $trx->save();

            // =========================================================
            // A. PEMBAYARAN SALDO
            // =========================================================
            if ($request->payment_method === 'saldo') {
                if ($user->saldo < $sellingPrice) {
                    throw new Exception('Saldo akun Anda tidak mencukupi.');
                }
                
                // Potong Saldo User
                $user->decrement('saldo', $sellingPrice);
                
                // Update Status Transaksi
                $trx->status  = 'Processing'; // Processing = Sudah bayar, sedang diproses ke operator
                $trx->message = 'Pembayaran Berhasil via Saldo';
                $trx->save();

                // TODO: TRIGGER API DIGIFLAZZ DISINI (Topup / Bayar Tagihan)
                // $this->processDigiflazz($trx);

                DB::commit();
                session()->forget('ppob_session');
                
                return redirect()->route('ppob.invoice', ['invoice' => $orderId]);
            }

            // =========================================================
            // B. PEMBAYARAN DOKU
            // =========================================================
            elseif (str_contains(strtoupper($request->payment_method), 'DOKU')) {
                
                $dokuService = new DokuJokulService();
                
                $customerData = [
                    'name'  => $user->nama_lengkap,
                    'email' => $user->email,
                    'phone' => $user->no_wa ?? '08123456789'
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

            // =========================================================
            // C. PEMBAYARAN TRIPAY
            // =========================================================
            else {
                $apiKey       = config('tripay.api_key');
                $privateKey   = config('tripay.private_key');
                $merchantCode = config('tripay.merchant_code');
                $isProd       = config('tripay.mode') === 'production';
                $url          = $isProd 
                                ? 'https://tripay.co.id/api/transaction/create' 
                                : 'https://tripay.co.id/api-sandbox/transaction/create';

                $signature = hash_hmac('sha256', $merchantCode . $orderId . $sellingPrice, $privateKey);

                $payload = [
                    'method'         => $request->payment_method,
                    'merchant_ref'   => $orderId,
                    'amount'         => $sellingPrice,
                    'customer_name'  => $user->nama_lengkap,
                    'customer_email' => $user->email,
                    'customer_phone' => $user->no_wa,
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
                    // Ambil URL yang tersedia
                    $payUrl = $d['checkout_url'] ?? $d['pay_url'] ?? $d['qr_url'] ?? null;
                    
                    $trx->payment_url = $payUrl;
                    $trx->save();

                    DB::commit();
                    session()->forget('ppob_session');

                    return redirect($payUrl);
                } else {
                    throw new Exception('Tripay Error: ' . ($response->json()['message'] ?? 'Gagal connect gateway'));
                }
            }

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('PPOB Store Error: ' . $e->getMessage());
            return back()->with('error', 'Gagal memproses transaksi: ' . $e->getMessage());
        }
    }

    /**
     * 4. INVOICE: Tampilkan Detail Transaksi
     */
    public function invoice($invoice)
    {
        $user = Auth::user();
        
        // Ambil data dari tabel ppob_transactions
        $transaction = PpobTransaction::where('order_id', $invoice)
                        ->where('user_id', $user->id_pengguna)
                        ->firstOrFail();

        return view('customer.ppob.invoice', compact('transaction'));
    }
}