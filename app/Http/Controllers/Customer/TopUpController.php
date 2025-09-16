<?php

namespace App\Http\Controllers\Customer;

use App\Events\AdminNotificationEvent;
use App\Http\Controllers\Controller;
use App\Models\TopUp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TopUpController extends Controller
{
    /**
     * Menampilkan riwayat transaksi top up.
     */
    public function index()
    {
        $user = Auth::user();
        // Memastikan relasi 'topUps' di model User sudah benar
        $topUps = $user->topUps()->latest()->paginate(15);

        return view('customer.topup.index', compact('topUps'));
    }

    /**
     * Menampilkan halaman form top up.
     */
    public function create()
    {
        return view('customer.topup.create');
    }

    /**
     * Menyimpan data top up dari transfer bank manual.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'amount'    => 'required|numeric|min:10000',
            'bank_name' => 'required|string|max:255',
        ]);
    
        try {
            DB::beginTransaction();
    
            $user = Auth::user();
    
            $topUp = TopUp::create([
                'customer_id'     => $user->id_pengguna,
                'amount'          => $validated['amount'],
                'status'          => 'pending',
                'payment_method'  => $validated['bank_name'],
                'proof_of_payment'=> null,
                'transaction_id'  => 'TOPUP-' . strtoupper(uniqid()),
            ]);
    
            $message = $user->nama_lengkap . ' meminta top up sebesar Rp ' . number_format($validated['amount']);
            $url = route('admin.saldo.requests.index');
            event(new AdminNotificationEvent('Permintaan Top Up Baru!', $message, $url));
    
            $apiKey       = config('tripay.api_key');
            $privateKey   = config('tripay.private_key');
            $merchantCode = config('tripay.merchant_code');
            $mode         = config('tripay.mode', 'sandbox');
    
            $payload = [
                'method'        => $validated['bank_name'], 
                'merchant_ref'  => $topUp->transaction_id,
                'amount'        => $topUp->amount,
                'customer_name' => $user->nama_lengkap,
                'customer_email'=> $user->email,
                'customer_phone'=> $user->no_wa,
                'order_items'   => [
                    [
                        'sku'      => 'TOPUP-' . $topUp->id,
                        'name'     => 'Top Up Saldo',
                        'price'    => $topUp->amount,
                        'quantity' => 1,
                    ],
                ],
                'expired_time'  => time() + (24 * 60 * 60),
                'signature'     => hash_hmac('sha256', $merchantCode.$topUp->transaction_id.$topUp->amount, $privateKey),
            ];
    
            $baseUrl = $mode === 'production'
                ? 'https://tripay.co.id/api/transaction/create'
                : 'https://tripay.co.id/api-sandbox/transaction/create';
    
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $baseUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($payload),
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $apiKey
                ],
            ]);
    
            $result = curl_exec($ch);
            $response = json_decode($result, true);
    
            if (isset($response['success']) && $response['success'] === true) {
                // Simpan payment_url dari Tripay
                $topUp->payment_url = $response['data']['qr_url'] 
                                     ?? $response['data']['checkout_url'] 
                                     ?? $response['data']['pay_code'] 
                                     ?? null;
                $topUp->save();
    
                DB::commit();
    
                $checkoutUrl = $response['data']['checkout_url'] ?? $topUp->payment_url;
                 return redirect()->route('customer.topup.show', ['topup' => $topUp->transaction_id]);
            } else {
                DB::rollBack();
                throw new \Exception('Gagal membuat transaksi di Tripay');
            }
    
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal memproses Top Up: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat memproses permintaan Anda. Silakan coba lagi.');
        }
    }

    /**
     * Menampilkan detail transaksi.
     */
    public function show($transaction_id)
    {
        $customer = Auth::user();

        // ✅ PERBAIKAN: Menggunakan id_pengguna agar konsisten
        $topUp = TopUp::where('transaction_id', $transaction_id)
                      ->where('customer_id', $customer->id_pengguna)
                      ->firstOrFail();

        return view('customer.topup.show', compact('topUp'));
    }
}
