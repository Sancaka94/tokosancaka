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
use Illuminate\Validation\ValidationException;

// Models
use App\Models\OrderMarketplace; 
use App\Models\OrderItemMerketplace; 
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
            $data = $request->validate([
                'sku' => 'required',
                'name' => 'required',
                'price' => 'required|numeric',
                'ref_id' => 'required',
                'customer_no' => 'required',
            ]);

            // Simpan ke session khusus 'ppob_session'
            // Agar tidak tercampur dengan keranjang belanja fisik
            $ppobItem = [
                'sku'         => $data['sku'],
                'name'        => $data['name'],
                'price'       => (int) $data['price'],
                'ref_id'      => $data['ref_id'],
                'customer_no' => $data['customer_no'],
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
            return redirect()->route('customer.dashboard')->with('error', 'Sesi transaksi PPOB berakhir atau kadaluarsa. Silakan ulangi cek tagihan.');
        }

        $user = Auth::user();
        $paymentChannels = [];

        // --- A. SALDO AKUN ---
        $paymentChannels['saldo'] = [
            'code' => 'SALDO', 
            'name' => 'Saldo Akun', 
            'description' => 'Sisa: Rp ' . number_format($user->saldo ?? 0),
            'balance' => $user->saldo ?? 0,
            'active' => true,
            'icon_url' => 'https://cdn-icons-png.flaticon.com/512/217/217853.png'
        ];

        // --- B. TRIPAY CHANNELS ---
        try {
            $apiKey = config('tripay.api_key');
            $mode   = config('tripay.mode');
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
        // Anda bisa mengambil dari config atau statis array
        $paymentChannels['doku'] = [
            ['code' => 'DOKU_CC', 'name' => 'Kartu Kredit (DOKU)', 'icon_url' => 'https://cdn-icons-png.flaticon.com/512/6963/6963703.png'],
            ['code' => 'DOKU_VA', 'name' => 'Virtual Account (DOKU)', 'icon_url' => 'https://cdn-icons-png.flaticon.com/512/2331/2331922.png'],
        ];

        return view('customer.ppob.checkout', compact('item', 'user', 'paymentChannels'));
    }

    /**
     * 3. STORE: Proses Pembayaran PPOB (LENGKAP: SALDO, DOKU, TRIPAY)
     */
    public function store(Request $request)
    {
        $request->validate([
            'payment_method' => 'required'
        ]);
        
        $item = session()->get('ppob_session');
        if (!$item) {
            return redirect()->route('customer.dashboard')->with('error', 'Transaksi kadaluarsa. Silakan ulangi.');
        }

        $user = Auth::user();
        
        // Hitung Angka
        $sellingPrice = (int) $item['price']; 
        $modalPrice = 0; // Nanti diupdate saat sukses
        $profit = $sellingPrice - $modalPrice;

        DB::beginTransaction();
        try {
            // 1. Generate Order ID Unik (TRX-...)
            do {
                $orderId = 'TRX-' . now()->timestamp . rand(100, 999);
            } while (\App\Models\PpobTransaction::where('order_id', $orderId)->exists());

            // 2. Simpan Data Awal ke Database (Status Pending)
            $transaction = new \App\Models\PpobTransaction();
            $transaction->user_id = $user->id_pengguna;
            $transaction->order_id = $orderId;
            $transaction->buyer_sku_code = $item['sku'];
            $transaction->customer_no = $item['customer_no'];
            $transaction->price = $modalPrice;          
            $transaction->selling_price = $sellingPrice;
            $transaction->profit = $profit;
            $transaction->payment_method = $request->payment_method;
            // Default status
            $transaction->status = ($request->payment_method === 'saldo') ? 'Processing' : 'Pending';
            $transaction->message = 'Menunggu Pembayaran';
            $transaction->save();

            // ==========================================================
            // LOGIKA PEMBAYARAN (SWITCH CASE)
            // ==========================================================

            // --- KASUS A: PEMBAYARAN SALDO ---
            if ($request->payment_method === 'saldo') {
                
                if ($user->saldo < $sellingPrice) {
                    throw new Exception('Saldo akun tidak mencukupi.');
                }

                // Potong Saldo User
                $user->decrement('saldo', $sellingPrice);
                
                Log::info("PPOB Paid via Saldo: $orderId");

                // Update Transaksi jadi Sukses (atau Processing jika menunggu provider)
                $transaction->status = 'Processing'; 
                $transaction->message = 'Sedang diproses provider';
                $transaction->save();

                // TODO: Trigger API Digiflazz disini (Background Job / Direct)
                // $this->processDigiflazz($transaction); 
                
                DB::commit();
                session()->forget('ppob_session');
                
                // Redirect ke Dashboard / Riwayat
                return redirect()->route('ppob.invoice', ['invoice' => $orderId]);

            } 
            
            // --- KASUS B: PEMBAYARAN DOKU ---
            elseif (str_contains(strtoupper($request->payment_method), 'DOKU')) {
                
                Log::info("PPOB Request via DOKU: $orderId");
                
                // Panggil Service DOKU
                $dokuService = new \App\Services\DokuJokulService();
                
                // Siapkan Data Customer
                $customerData = [
                    'name'  => $user->nama_lengkap,
                    'email' => $user->email,
                    'phone' => $user->no_wa ?? '08123456789'
                ];

                // Siapkan Item (Format DOKU biasanya butuh array item)
                $dokuItems = [[
                    'name'     => $item['name'],
                    'quantity' => 1,
                    'price'    => $sellingPrice,
                    'sku'      => $item['sku']
                ]];

                // Generate Link Pembayaran
                $paymentUrl = $dokuService->createPayment(
                    $orderId,
                    $sellingPrice,
                    $customerData,
                    $dokuItems
                );

                if (!$paymentUrl) throw new Exception('Gagal generate link DOKU.');

                // Simpan URL Pembayaran & Redirect
                $transaction->payment_url = $paymentUrl; // Pastikan kolom ini ada di DB, atau simpan di 'message'
                $transaction->save();
                
                DB::commit();
                session()->forget('ppob_session');
                
                return redirect($paymentUrl);
            }

            // --- KASUS C: PEMBAYARAN TRIPAY (Default/Else) ---
            else {
                Log::info("PPOB Request via TRIPAY: $orderId");

                $apiKey       = config('tripay.api_key');
                $privateKey   = config('tripay.private_key');
                $merchantCode = config('tripay.merchant_code');
                $isProd       = config('tripay.mode') === 'production';
                $apiUrl       = $isProd 
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
                    'return_url'     => route('customer.dashboard') // Redirect balik setelah bayar
                ];

                $response = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])
                                ->timeout(30)
                                ->withoutVerifying()
                                ->post($apiUrl, $payload);
                
                if ($response->successful() && $response->json()['success']) {
                    $d = $response->json()['data'];
                    $payUrl = $d['checkout_url'] ?? $d['pay_url'] ?? $d['qr_url'] ?? null;
                    
                    $transaction->payment_url = $payUrl;
                    $transaction->save();

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
            return back()->with('error', 'Gagal memproses: ' . $e->getMessage());
        }
    }

    /**
     * 4. INVOICE: Tampilkan Detail Transaksi
     */
    public function invoice($invoice)
    {
        $user = Auth::user();

        // Cari transaksi berdasarkan Order ID dan User yang login
        $transaction = PpobTransaction::where('order_id', $invoice)
                        ->where('user_id', $user->id_pengguna)
                        ->firstOrFail();

        return view('customer.ppob.invoice', compact('transaction'));
    }
}