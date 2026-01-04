<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http; // Wajib untuk Tripay
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

// Models
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderDetail;
use App\Models\OrderAttachment;
use App\Models\Coupon;
use App\Models\Affiliate;

// Services (Hanya Doku yang masih pakai service, Tripay sudah manual di sini)
use App\Services\DokuJokulService;
use App\Services\KiriminAjaService; // Pastikan ini di-use

class OrderController extends Controller
{
    /**
     * Menampilkan Halaman Kasir (POS)
     */
    public function create(Request $request)
    {
        $products = Product::where('stock_status', 'available')
                           ->where('stock', '>', 0)
                           ->orderBy('created_at', 'desc')
                           ->get();
        
        // Ambil data affiliate sebagai customer
        $customers = Affiliate::orderBy('name', 'asc')
                              ->get()
                              ->map(function($aff) {
                                  $aff->saldo = 0; 
                                  $aff->affiliate_balance = $aff->balance; 
                                  $aff->has_pin = !empty($aff->pin); 
                                  return $aff;
                              });

        $autoCoupon = $request->query('coupon');

        return view('orders.create', compact('products', 'customers', 'autoCoupon'));
    }


    /**
     * API: Pencarian Lokasi (Kecamatan/Kelurahan)
     * Digunakan agar customer bisa input nama desa/kecamatan
     */
    public function searchLocation(Request $request, KiriminAjaService $kiriminAja)
    {
        $keyword = $request->query('query'); // Inputan customer

        if (empty($keyword) || strlen($keyword) < 3) {
            return response()->json(['status' => 'error', 'data' => []]);
        }

        try {
            // Memanggil service searchAddress yang ada di kode Service Anda
            $response = $kiriminAja->searchAddress($keyword);

            if (isset($response['status']) && $response['status'] == true) {
                return response()->json([
                    'status' => 'success',
                    'data'   => $response['data'] // Berisi list alamat beserta ID-nya
                ]);
            }

            return response()->json(['status' => 'error', 'data' => []]);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * API: Cek Ongkir KiriminAja
     * Asal: Ketanggi, Ngawi (Fixed dari ENV)
     * Tujuan: Dinamis (Dari ID hasil search)
     */
    public function checkShippingRates(Request $request, KiriminAjaService $kiriminAja)
    {
        $request->validate([
            'weight' => 'required|numeric',
            // Kita butuh ID kecamatan & kelurahan tujuan untuk akurasi harga
            'destination_district_id' => 'required', 
            'destination_subdistrict_id' => 'nullable', // Opsional tapi disarankan ada
        ]);

        try {
            // 1. SETTING KOTA ASAL (KETANGGI, NGAWI)
            // Masukkan ID Kecamatan & Kelurahan Ketanggi di file .env Anda
            $originDistrict    = env('KIRIMINAJA_ORIGIN_DISTRICT', 574);    // ID Kec. Ngawi (Contoh)
            $originSubDistrict = env('KIRIMINAJA_ORIGIN_SUBDISTRICT', 0);   // ID Kel. Ketanggi (Opsional jika service mengizinkan 0)

            // 2. SETTING KOTA TUJUAN (DARI INPUTAN)
            $destDistrict    = $request->destination_district_id;
            $destSubDistrict = $request->destination_subdistrict_id ?? 0;

            // 3. PARAMETER BARANG
            // KiriminAja v6.1 butuh dimensi
            $length    = 10; 
            $width     = 10;
            $height    = 10;
            $itemValue = 100000; // Estimasi harga barang (untuk asuransi)

            // 4. PANGGIL API (Sesuai Service Anda: getExpressPricing)
            $response = $kiriminAja->getExpressPricing(
                $originDistrict,    // Origin Kecamatan
                $originSubDistrict, // Origin Kelurahan (Ketanggi)
                $destDistrict,      // Destination Kecamatan
                $destSubDistrict,   // Destination Kelurahan
                $request->weight,   // Berat
                $length,            
                $width,             
                $height,            
                $itemValue          
            );

            // 5. FORMAT HASIL
            if (isset($response['status']) && $response['status'] == true) {
                $formattedRates = [];
                $results = $response['results'] ?? [];

                foreach ($results as $rate) {
                    $formattedRates[] = [
                        'code'    => 'kiriminaja',
                        'name'    => $rate['courier'], // JNE, J&T, SiCepat
                        'service' => $rate['service'], // REG, BEST
                        'cost'    => $rate['cost'],
                        'etd'     => $rate['etd'] ?? '-',
                    ];
                }

                return response()->json([
                    'status' => 'success',
                    'data'   => $formattedRates
                ]);
            }

            return response()->json([
                'status' => 'error', 
                'message' => $response['text'] ?? 'Kurir tidak tersedia untuk rute ini.'
            ]);

        } catch (\Exception $e) {
            Log::error('Check Ongkir Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Proses Penyimpanan Transaksi & Pembayaran
     */
    public function store(Request $request, DokuJokulService $dokuService)
    {
        // 1. VALIDASI INPUT
        $request->validate([
            'items'           => 'required', 
            'total'           => 'required|numeric',
            'payment_method'  => 'required', // cash, affiliate_balance, tripay, doku
            'payment_channel' => 'nullable|string', // Wajib jika tripay (misal: BRIVA, QRIS)
            'cash_amount'     => 'nullable|numeric|required_if:payment_method,cash',
            'customer_id'     => 'nullable|exists:affiliates,id|required_if:payment_method,affiliate_balance',
            'affiliate_pin'   => 'nullable|required_if:payment_method,affiliate_balance',
            'attachments.*'   => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:10240',
            'delivery_type'   => 'required|in:pickup,shipping',
            'shipping_cost'   => 'required_if:delivery_type,shipping|numeric',
            'courier_name'    => 'required_if:delivery_type,shipping|string'
        ]);

        $cartItems = json_decode($request->items, true);

        if (!is_array($cartItems) || count($cartItems) < 1) {
            return response()->json(['status' => 'error', 'message' => 'Keranjang belanja kosong.'], 400);
        }

        DB::beginTransaction();

        try {
            $subtotal = 0;
            $finalCart = []; 

            // 2. VALIDASI STOK & HITUNG HARGA (Pessimistic Locking)
            foreach ($cartItems as $item) {
                $product = Product::lockForUpdate()->find($item['id']);

                if (!$product) throw new \Exception("Produk ID {$item['id']} tidak ditemukan.");
                if ($product->stock < $item['qty']) throw new \Exception("Stok '{$product->name}' kurang. Sisa: {$product->stock}");

                $lineTotal = $product->sell_price * $item['qty'];
                $subtotal += $lineTotal;

                $finalCart[] = [
                    'product'  => $product,
                    'qty'      => $item['qty'],
                    'subtotal' => $lineTotal
                ];
            }

            // 3. HITUNG DISKON KUPON
            $discount = 0;
            $couponId = null;

            if ($request->coupon) {
                $couponDB = Coupon::where('code', $request->coupon)->first();
                
                if ($couponDB && $couponDB->is_active) {
                    $now = now();
                    $isValid = true;
                    if ($couponDB->start_date && $now->lt($couponDB->start_date)) $isValid = false;
                    if ($couponDB->expiry_date && $now->gt($couponDB->expiry_date)) $isValid = false;
                    if ($couponDB->usage_limit > 0 && $couponDB->used_count >= $couponDB->usage_limit) $isValid = false;
                    if ($couponDB->min_order_amount > 0 && $subtotal < $couponDB->min_order_amount) $isValid = false;

                    if ($isValid) {
                        $couponId = $couponDB->id;
                        if ($couponDB->type == 'percent') {
                            $discount = $subtotal * ($couponDB->value / 100);
                            if($couponDB->max_discount_amount > 0 && $discount > $couponDB->max_discount_amount) {
                                $discount = $couponDB->max_discount_amount;
                            }
                        } else {
                            $discount = $couponDB->value;
                        }
                        $couponDB->increment('used_count');
                    }
                }
            }

            $finalPrice = max(0, $subtotal - $discount);

            // 4. PREPARE DATA CUSTOMER
            $paymentStatus = 'unpaid';
            $paymentUrl    = null;    
            $changeAmount  = 0;     
            $customerName  = $request->customer_name ?? 'Guest';
            $customerPhone = $request->customer_phone ?? '08123456789';
            $customerEmail = 'customer@tokosancaka.com'; 
            $note          = $request->note;

            if ($request->customer_id) {
                $affiliateMember = Affiliate::find($request->customer_id);
                if ($affiliateMember) {
                    $customerName  = $affiliateMember->name; 
                    $customerPhone = $affiliateMember->whatsapp;
                    if (!empty($affiliateMember->email)) {
                        $customerEmail = $affiliateMember->email;
                    }
                }
            }

            // 5. PROSES PEMBAYARAN (LOGIKA AWAL)
            switch ($request->payment_method) {
                case 'cash':
                    $cashReceived = (int) $request->cash_amount;
                    if ($cashReceived < $finalPrice) {
                        throw new \Exception("Uang tunai kurang!");
                    }
                    $changeAmount = $cashReceived - $finalPrice;
                    $paymentStatus = 'paid'; 
                    $note .= "\n[INFO PEMBAYARAN]\nMetode: Tunai\nDiterima: Rp " . number_format($cashReceived,0,',','.') . "\nKembali: Rp " . number_format($changeAmount,0,',','.');
                    break;

                case 'affiliate_balance':
                    if (!$request->customer_id) throw new \Exception("Wajib pilih Member Afiliasi.");
                    
                    $affiliatePayor = Affiliate::lockForUpdate()->find($request->customer_id);
                    if (!$affiliatePayor) throw new \Exception("Data Afiliasi tidak ditemukan.");
                    if (!Hash::check($request->affiliate_pin, $affiliatePayor->pin)) throw new \Exception("PIN Keamanan Salah!");
                    if ($affiliatePayor->balance < $finalPrice) throw new \Exception("Saldo Profit Tidak Cukup.");

                    $affiliatePayor->decrement('balance', $finalPrice);
                    $paymentStatus = 'paid'; 
                    $note .= "\n[INFO PEMBAYARAN]\nMetode: Potong Profit Afiliasi";
                    break;

                case 'tripay':
                case 'doku':
                    $paymentStatus = 'unpaid';
                    break;
            }

            // 6. SIMPAN ORDER KE DATABASE
            $order = Order::create([
                'order_number'    => 'INV-' . date('YmdHis') . rand(100, 999),
                'user_id'         => null,
                'customer_name'   => $customerName,
                'customer_phone'  => $customerPhone,
                'coupon_id'       => $couponId,
                'total_price'     => $subtotal,
                'discount_amount' => $discount,
                'final_price'     => $finalPrice,
                'payment_method'  => $request->payment_method, // tripay / doku / cash
                'status'          => ($paymentStatus === 'paid') ? 'processing' : 'pending', 
                'payment_status'  => $paymentStatus,
                'note'            => $note,
                'shipping_cost'   => $request->delivery_type === 'shipping' ? $request->shipping_cost : 0,
                'courier_service' => $request->delivery_type === 'shipping' ? $request->courier_name : null,
            ]);

            // ==========================================
// 7. INTEGRASI TRIPAY (MANUAL / TANPA SERVICE)
// ==========================================
if ($request->payment_method === 'tripay') {
    
    // UBAH BAGIAN INI:
    // Jangan default ke QRIS jika user tidak memilih, tapi validasi agar user wajib memilih.
    if (empty($request->payment_channel)) {
        throw new \Exception("Harap pilih Bank/Channel Pembayaran (BRI, Mandiri, dll).");
    }

    $channel = $request->payment_channel; 

                // Siapkan Item Payload Tripay (Optional tapi bagus untuk detail)
                $orderItems = [];
                foreach ($finalCart as $item) {
                    $orderItems[] = [
                        'sku'      => (string) $item['product']->id,
                        'name'     => $item['product']->name,
                        'price'    => (int) $item['product']->sell_price,
                        'quantity' => (int) $item['qty']
                    ];
                }

                // Panggil Private Method Tripay di bawah
                $tripayRes = $this->_createTripayTransaction($order, $channel, (int)$finalPrice, $customerName, $customerEmail, $customerPhone, $orderItems);

                if (!$tripayRes['success']) {
                    throw new \Exception("Tripay Gagal: " . ($tripayRes['message'] ?? 'Unknown Error'));
                }

                $paymentUrl = $tripayRes['data']['checkout_url'];
                $order->update(['payment_url' => $paymentUrl]);
            }
            // ==========================================
            // INTEGRASI DOKU (Masih pakai Service)
            // ==========================================
            elseif ($request->payment_method === 'doku') {
                $dokuCustomerData = [
                    'name'  => $order->customer_name,
                    'email' => $customerEmail, 
                    'phone' => $order->customer_phone,
                ];

                $paymentUrl = $dokuService->createPayment(
                    $order->order_number, 
                    $order->final_price,
                    $dokuCustomerData 
                );
                
                if (empty($paymentUrl)) {
                    throw new \Exception("Gagal generate link pembayaran DOKU.");
                }
                $order->update(['payment_url' => $paymentUrl]);
            }

            // 8. SIMPAN DETAIL BARANG & UPDATE STOK
            foreach ($finalCart as $data) {
                $prod = $data['product'];
                OrderDetail::create([
                    'order_id'            => $order->id,
                    'product_id'          => $prod->id,
                    'product_name'        => $prod->name,    
                    'base_price_at_order' => $prod->base_price, 
                    'price_at_order'      => $prod->sell_price, 
                    'quantity'            => $data['qty'],
                    'subtotal'            => $data['subtotal'],
                ]);

                $prod->decrement('stock', $data['qty']);
                $prod->increment('sold', $data['qty']);
                
                if ($prod->stock <= 0) {
                    $prod->update(['stock_status' => 'unavailable']);
                }
            }

            // 9. SIMPAN FILE UPLOAD (Jika ada)
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('orders', 'public');
                    OrderAttachment::create([
                        'order_id'  => $order->id,
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'file_type' => $file->getClientMimeType(),
                    ]);
                }
            }

            DB::commit();

            // 10. KOMISI AFFILIATE (Jika Lunas)
            if ($request->coupon && $paymentStatus == 'paid') {
                $this->_processAffiliateCommission($request->coupon, $finalPrice);
            }

            // 11. NOTIFIKASI WA (Fonnte)
            $this->_sendWaNotification($order, $finalPrice, $paymentUrl, $paymentStatus);

            return response()->json([
                'status'         => 'success',
                'message'        => 'Transaksi Berhasil!',
                'invoice'        => $order->order_number,
                'order_id'       => $order->id,
                'payment_url'    => $paymentUrl,
                'change_amount'  => $changeAmount,
                'payment_method' => $request->payment_method
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order Transaction Failed: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

   /**
     * PRIVATE: Logic Request ke API Tripay (Dengan Auto-Fix Item Calculation)
     */
    private function _createTripayTransaction($order, $methodChannel, $amount, $custName, $custEmail, $custPhone, $items)
    {
        // 1. Ambil Config
        $apiKey       = config('tripay.api_key');
        $privateKey   = config('tripay.private_key');
        $merchantCode = config('tripay.merchant_code');
        $mode         = config('tripay.mode');

        // Validasi Config
        if (empty($apiKey) || empty($privateKey) || empty($merchantCode)) {
            Log::error('TRIPAY CONFIG MISSING');
            return ['success' => false, 'message' => 'Konfigurasi Tripay belum lengkap.'];
        }

        // ==========================================
        // FIX: VALIDASI MATEMATIKA (Order Items vs Amount)
        // ==========================================
        // Hitung manual total harga dari list item
        $calculatedTotalItems = 0;
        foreach ($items as $item) {
            $calculatedTotalItems += ($item['price'] * $item['quantity']);
        }

        // Pastikan Amount adalah Integer murni
        $amount = (int) $amount;

        // Jika Total Item TIDAK SAMA dengan Total Tagihan (Misal karena ada Diskon Kupon),
        // Kita ganti list item dengan 1 item "Summary" agar diterima Tripay.
        if ($calculatedTotalItems !== $amount) {
            $items = [
                [
                    'sku'      => 'INV-' . $order->order_number,
                    'name'     => 'Pembayaran Invoice #' . $order->order_number,
                    'price'    => $amount, // Paksa harga item sama dengan total tagihan
                    'quantity' => 1
                ]
            ];
        }
        // ==========================================

        $baseUrl = ($mode === 'production') 
            ? 'https://tripay.co.id/api/transaction/create' 
            : 'https://tripay.co.id/api-sandbox/transaction/create';

        $signature = hash_hmac('sha256', $merchantCode . $order->order_number . $amount, $privateKey);

        $payload = [
            'method'         => $methodChannel,
            'merchant_ref'   => $order->order_number,
            'amount'         => $amount,
            'customer_name'  => $custName,
            'customer_email' => $custEmail,
            'customer_phone' => $custPhone,
            'order_items'    => $items, // List item yang sudah diperbaiki
            'return_url'     => url('/'), 
            'expired_time'   => (time() + (24 * 60 * 60)), 
            'signature'      => $signature
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey
            ])->timeout(30)->post($baseUrl, $payload);

            $body = $response->json();

            if (!$response->successful()) {
                Log::error('Tripay API Error:', ['status' => $response->status(), 'body' => $body]);
            }

            if ($response->successful() && ($body['success'] ?? false) === true) {
                return [
                    'success' => true,
                    'data'    => $body['data']
                ];
            }

            return [
                'success' => false,
                'message' => $body['message'] ?? 'Gagal membuat transaksi Tripay.'
            ];

        } catch (\Exception $e) {
            Log::error("Tripay Connection Exception: " . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal terhubung ke server pembayaran.'];
        }
    }

    /**
     * API Cek Kupon (Fixed)
     */
    public function checkCoupon(Request $request)
    {
        $request->validate([
            'coupon_code'   => 'required|string',
            'total_belanja' => 'required|numeric|min:0'
        ]);

        $code = trim($request->coupon_code);
        $total = $request->total_belanja;

        $coupon = Coupon::where('code', $code)->first();

        // 1. Validasi Keberadaan
        if (!$coupon) {
            return response()->json(['status' => 'error', 'message' => 'Kode kupon tidak ditemukan.'], 404);
        }

        // 2. Validasi Status Aktif
        if (!$coupon->is_active) {
            return response()->json(['status' => 'error', 'message' => 'Kupon tidak aktif.'], 400);
        }

        $now = now();

        // 3. Validasi Tanggal
        if ($coupon->start_date && $now->lt($coupon->start_date)) {
            return response()->json(['status' => 'error', 'message' => 'Promo belum dimulai.'], 400);
        }
        if ($coupon->expiry_date && $now->gt($coupon->expiry_date)) {
            return response()->json(['status' => 'error', 'message' => 'Kupon sudah kedaluwarsa.'], 400);
        }

        // 4. Validasi Limit Pemakaian
        if ($coupon->usage_limit > 0 && $coupon->used_count >= $coupon->usage_limit) {
            return response()->json(['status' => 'error', 'message' => 'Kuota kupon habis.'], 400);
        }

        // 5. Validasi Minimal Belanja
        if ($coupon->min_order_amount > 0 && $total < $coupon->min_order_amount) {
            return response()->json(['status' => 'error', 'message' => 'Min. belanja Rp ' . number_format($coupon->min_order_amount, 0, ',', '.') . '.'], 400);
        }

        // 6. Hitung Diskon
        $discountAmount = 0;
        if ($coupon->type == 'percent') {
            $discountAmount = $total * ($coupon->value / 100);
            // Cek Max Discount (jika ada field ini di DB)
            if (isset($coupon->max_discount_amount) && $coupon->max_discount_amount > 0) {
                if ($discountAmount > $coupon->max_discount_amount) {
                    $discountAmount = $coupon->max_discount_amount;
                }
            }
        } else {
            $discountAmount = $coupon->value;
        }

        // Cegah diskon minus
        if ($discountAmount > $total) {
            $discountAmount = $total;
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Kupon diterapkan!',
            'data' => [
                'coupon_id'       => $coupon->id,
                'code'            => $coupon->code,
                'discount_amount' => $discountAmount,
                'final_total'     => $total - $discountAmount,
                'type'            => $coupon->type
            ]
        ]);
    }

    // --- HELPER PRIVAT LAINNYA ---

    private function _processAffiliateCommission($couponCode, $finalPrice)
    {
        try {
            $affiliateOwner = Affiliate::where('coupon_code', $couponCode)->first();
            if ($affiliateOwner) {
                $komisiRate = 0.10; // 10%
                $komisiDiterima = $finalPrice * $komisiRate;

                $affiliateOwner->increment('balance', $komisiDiterima);

                // Notif WA ke Affiliate
                $this->_sendFonnteMessage($affiliateOwner->whatsapp, 
                    "💰 *KOMISI MASUK!* 💰\n\nSelamat! Kupon *{$couponCode}* digunakan.\nKomisi: Rp " . number_format($komisiDiterima, 0, ',', '.')
                );
            }
        } catch (\Exception $e) {
            Log::error("Gagal tambah komisi: " . $e->getMessage());
        }
    }

    private function _sendWaNotification($order, $finalPrice, $paymentUrl, $paymentStatus)
    {
        try {
            // Ke Customer
            if ($order->customer_phone) {
                $msg = "Halo *{$order->customer_name}*, Order *{$order->order_number}* Berhasil!\nTotal: Rp " . number_format($finalPrice,0,',','.') . "\nStatus: $paymentStatus";
                if($paymentUrl) $msg .= "\nLink Bayar: $paymentUrl";
                
                $this->_sendFonnteMessage($order->customer_phone, $msg);
            }

            // Ke Admin
            $adminPhone = '085745808809'; 
            $msgAdmin = "🔔 *ORDER BARU*\nInv: {$order->order_number}\nTotal: Rp " . number_format($finalPrice, 0, ',', '.') . "\nMetode: {$order->payment_method}";
            
            $this->_sendFonnteMessage($adminPhone, $msgAdmin);

        } catch (\Exception $e) {}
    }

    private function _sendFonnteMessage($target, $message)
    {
        $token = env('FONNTE_API_KEY') ?? env('FONNTE_KEY');
        if (!$token) return;

        Http::withHeaders(['Authorization' => $token])
            ->post('https://api.fonnte.com/send', [
                'target' => $target,
                'message' => $message,
            ]);
    }

    public function getPaymentChannels()
    {
        // 1. Matikan Cache sementara untuk Debugging
        // $channels = \Illuminate\Support\Facades\Cache::remember('tripay_channels', 60 * 24, function () {
            
            $apiKey = config('tripay.api_key');
            $mode   = config('tripay.mode');
            
            // Log Konfigurasi (Cek apakah terbaca)
            Log::info('TRIPAY DEBUG: Config Check', [
                'mode' => $mode,
                'has_api_key' => !empty($apiKey)
            ]);

            $baseUrl = ($mode === 'production') 
                ? 'https://tripay.co.id/api/merchant/payment-channel' 
                : 'https://tripay.co.id/api-sandbox/merchant/payment-channel';

            try {
                $response = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])->get($baseUrl);
                
                // Log Response Mentah dari Tripay
                Log::info('TRIPAY DEBUG: API Response', [
                    'status' => $response->status(),
                    'body' => $response->json()
                ]);

                if ($response->successful()) {
                    return response()->json([
                        'status' => 'success',
                        'data' => $response->json()['data'] ?? []
                    ]);
                }
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal koneksi ke Tripay: ' . $response->status(),
                    'debug' => $response->json()
                ], 500);

            } catch (\Exception $e) {
                Log::error("TRIPAY DEBUG: Exception", ['msg' => $e->getMessage()]);
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
            }
            
        // }); // Tutup Cache sementara
    }

}