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

// Services
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
     * 3. STORE: Proses Pembayaran & Pembuatan Order
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
        $grandTotal = (int) $item['price']; // Tambah biaya admin aplikasi disini jika ada

        DB::beginTransaction();
        try {
            // 1. Generate Invoice Unik
            do {
                $invoiceNumber = 'PPOB-' . strtoupper(Str::random(10));
            } while (OrderMarketplace::where('invoice_number', $invoiceNumber)->exists());

            // 2. Tentukan Status Awal
            // Jika bayar pakai SALDO, status langsung 'processing' (artinya Paid)
            // Jika bayar pakai Tripay/Doku, status 'pending' (menunggu callback)
            $initialStatus = ($request->payment_method === 'saldo') ? 'processing' : 'pending';

            // 3. Buat Data Order (Master)
            $order = new OrderMarketplace([
                'user_id'          => $user->id_pengguna,
                'store_id'         => null, // PPOB tidak punya toko fisik
                'invoice_number'   => $invoiceNumber,
                'subtotal'         => $item['price'],
                'shipping_cost'    => 0,
                'insurance_cost'   => 0,
                'total_amount'     => $grandTotal,
                'shipping_method'  => 'Digital Delivery',
                'payment_method'   => $request->payment_method,
                'status'           => $initialStatus,
                'shipping_address' => 'Digital Product (No. Pelanggan: '.$item['customer_no'].')',
                'is_digital'       => 1 // Penanda Produk Digital
            ]);
            $order->save();

            // 4. Buat Order Item
            // Kita simpan ref_id inquiry di kolom 'notes' atau json field agar bisa diproses ke Digiflazz nanti
            OrderItemMerketplace::create([
                'order_id'   => $order->id,
                'product_id' => 0, // ID 0 menandakan PPOB (Non-Fisik)
                'quantity'   => 1,
                'price'      => $item['price'],
                'name'       => $item['name'],
                'sku'        => $item['sku'],
                'notes'      => json_encode([
                    'ref_id_inquiry' => $item['ref_id'], 
                    'customer_no'    => $item['customer_no']
                ])
            ]);

            // ==========================================================
            // LOGIKA PEMBAYARAN
            // ==========================================================

            // A. PEMBAYARAN SALDO
            if ($request->payment_method === 'saldo') {
                
                // Cek kecukupan saldo
                if ($user->saldo < $grandTotal) {
                    throw new Exception('Saldo akun Anda tidak mencukupi (Rp ' . number_format($user->saldo) . ').');
                }

                // Potong Saldo
                $user->decrement('saldo', $grandTotal);
                
                Log::info("PPOB Paid via Saldo: $invoiceNumber. User: {$user->id_pengguna}");

                // TODO: TRIGGER API DIGIFLAZZ (BAYAR TAGIHAN)
                // Disini Anda bisa memanggil Service/Job untuk mengeksekusi pembayaran ke Digiflazz
                // Contoh: DigiflazzService::pay($invoiceNumber);

            } 
            
            // B. PEMBAYARAN DOKU
            elseif (str_starts_with($request->payment_method, 'DOKU')) {
                
                Log::info("PPOB Request via DOKU: $invoiceNumber");
                
                $dokuService = new DokuJokulService();
                
                $customerData = [
                    'name'  => $user->nama_lengkap,
                    'email' => $user->email,
                    'phone' => $user->no_wa
                ];

                // Item untuk DOKU
                $dokuItem = [[
                    'name'     => $item['name'],
                    'quantity' => 1,
                    'price'    => $item['price'],
                    'sku'      => $item['sku']
                ]];
                
                $paymentUrl = $dokuService->createPayment(
                    $invoiceNumber,
                    $grandTotal,
                    $customerData,
                    $dokuItem
                );
                
                if (!$paymentUrl) throw new Exception('Gagal membuat link pembayaran DOKU.');
                $order->payment_url = $paymentUrl;

            } 
            
            // C. PEMBAYARAN TRIPAY (QRIS, VA, E-Wallet)
            else {
                
                Log::info("PPOB Request via TRIPAY: $invoiceNumber Method: {$request->payment_method}");

                $apiKey       = config('tripay.api_key');
                $privateKey   = config('tripay.private_key');
                $merchantCode = config('tripay.merchant_code');
                $isProd       = config('tripay.mode') === 'production';
                $apiUrl       = $isProd 
                                ? 'https://tripay.co.id/api/transaction/create' 
                                : 'https://tripay.co.id/api-sandbox/transaction/create';

                $signature = hash_hmac('sha256', $merchantCode . $invoiceNumber . $grandTotal, $privateKey);

                $payload = [
                    'method'         => $request->payment_method,
                    'merchant_ref'   => $invoiceNumber,
                    'amount'         => $grandTotal,
                    'customer_name'  => $user->nama_lengkap,
                    'customer_email' => $user->email,
                    'customer_phone' => $user->no_wa,
                    'order_items'    => [[
                        'sku'      => $item['sku'],
                        'name'     => $item['name'],
                        'price'    => (int) $item['price'],
                        'quantity' => 1
                    ]],
                    'expired_time'   => time() + (30 * 60), // Expired 30 menit
                    'signature'      => $signature,
                    'return_url'     => route('customer.checkout.invoice', ['invoice' => $invoiceNumber])
                ];

                $response = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])
                                ->timeout(30)
                                ->withoutVerifying()
                                ->post($apiUrl, $payload);
                
                if ($response->successful() && $response->json()['success']) {
                    $d = $response->json()['data'];
                    // Ambil URL pembayaran yang tersedia
                    $payUrl = $d['checkout_url'] ?? $d['pay_url'] ?? $d['qr_url'] ?? null;
                    $order->payment_url = $payUrl;
                } else {
                    $errMsg = $response->json()['message'] ?? 'Gagal menghubungi Tripay.';
                    Log::error('Tripay Error:', ['body' => $response->body()]);
                    throw new Exception('Tripay Error: ' . $errMsg);
                }
            }

            // Simpan perubahan order (payment_url / status)
            $order->save();
            
            DB::commit();
            
            // Hapus session PPOB agar tidak bisa back
            session()->forget('ppob_session');

            // Redirect ke halaman Invoice
            return redirect()->route('customer.checkout.invoice', ['invoice' => $invoiceNumber]);

        } catch (ValidationException $e) {
            DB::rollBack();
            return back()->withErrors($e->errors());
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('PPOB Checkout Error: ' . $e->getMessage());
            return back()->with('error', 'Gagal memproses transaksi: ' . $e->getMessage());
        }
    }
}