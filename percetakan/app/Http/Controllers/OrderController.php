<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http; 
use Illuminate\Support\Facades\Hash; // WAJIB: Untuk Cek PIN
use Illuminate\Support\Str;

// Models
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderDetail;
use App\Models\OrderAttachment;
use App\Models\Coupon;
use App\Models\User;
use App\Models\Affiliate;

// Services
use App\Services\TripayService;
use App\Services\DokuJokulService;

class OrderController extends Controller
{
    /**
     * Menampilkan Halaman Kasir (POS)
     */
    public function create(Request $request)
    {
        // 1. Ambil produk yang stoknya tersedia
        $products = Product::where('stock_status', 'available')
                           ->where('stock', '>', 0)
                           ->orderBy('created_at', 'desc')
                           ->get();
        
        // 2. Ambil Data Member DARI TABEL AFFILIATES
        // Karena User hanya untuk Admin, maka kita ambil data dari Affiliate
        $customers = Affiliate::orderBy('name', 'asc')
                         ->get()
                         ->map(function($aff) {
                             // Mapping data agar sesuai dengan frontend
                             $aff->saldo = 0; // Fitur saldo topup dimatikan/0 karena belum ada di affiliate
                             $aff->affiliate_balance = $aff->balance; // Saldo Profit
                             $aff->has_pin = !empty($aff->pin); // Cek apakah sudah set PIN
                             return $aff;
                         });

        // 3. Tangkap Parameter Auto Coupon dari Link
        $autoCoupon = $request->query('coupon');

        return view('orders.create', compact('products', 'customers', 'autoCoupon'));
    }

    /**
     * Proses Penyimpanan Transaksi
     */
    public function store(Request $request, TripayService $tripayService)
    {
        // ==========================================
        // 1. VALIDASI INPUT
        // ==========================================
        $request->validate([
            'items'           => 'required', 
            'total'           => 'required|numeric',
            // Tambahkan 'affiliate_balance' ke metode bayar
            'payment_method'  => 'required|in:cash,saldo,affiliate_balance,tripay,doku',
            'cash_amount'     => 'nullable|numeric|required_if:payment_method,cash',
            // Cek customer_id ke tabel affiliates jika bayar pakai saldo profit
            'customer_id'     => 'nullable|exists:affiliates,id|required_if:payment_method,affiliate_balance',
            // Wajib input PIN jika bayar pakai saldo profit
            'affiliate_pin'   => 'nullable|required_if:payment_method,affiliate_balance',
            'attachments.*'   => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:10240'
        ]);

        $cartItems = json_decode($request->items, true);

        if (!is_array($cartItems) || count($cartItems) < 1) {
            return response()->json(['status' => 'error', 'message' => 'Keranjang belanja kosong.'], 400);
        }

        // Mulai Transaksi Database
        DB::beginTransaction();

        try {
            $subtotal = 0;
            $finalCart = []; 

            // ==========================================
            // 2. VALIDASI STOK & HITUNG HARGA
            // ==========================================
            foreach ($cartItems as $item) {
                // Lock baris database (Pessimistic Locking)
                $product = Product::lockForUpdate()->find($item['id']);

                if (!$product) {
                    throw new \Exception("Produk ID {$item['id']} tidak ditemukan.");
                }

                // Cek stok
                if ($product->stock < $item['qty']) {
                    throw new \Exception("Stok '{$product->name}' tidak mencukupi. Sisa: {$product->stock}");
                }

                // Hitung harga dari database (Security)
                $lineTotal = $product->sell_price * $item['qty'];
                $subtotal += $lineTotal;

                $finalCart[] = [
                    'product'  => $product,
                    'qty'      => $item['qty'],
                    'subtotal' => $lineTotal
                ];
            }

            // ==========================================
            // 3. HITUNG DISKON KUPON
            // ==========================================
            $discount = 0;
            $couponId = null;

            if ($request->coupon) {
                $couponDB = Coupon::where('code', $request->coupon)->first();
                
                if ($couponDB && $couponDB->is_active) {
                    // Validasi Syarat Kupon
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

            // Hitung Final Price
            $finalPrice = max(0, $subtotal - $discount);


            // ==========================================
            // 4. LOGIKA PEMBAYARAN & CUSTOMER DATA
            // ==========================================
            $paymentStatus = 'unpaid';
            $paymentUrl    = null;    
            $changeAmount  = 0;     
            $userId        = null; // User ID NULL karena pembeli adalah Affiliate/Guest (Bukan Admin)
            $customerName  = $request->customer_name ?? 'Guest';
            $customerPhone = $request->customer_phone;
            $note          = $request->note;

            // Jika Member Dipilih (ID dari tabel Affiliates)
            if ($request->customer_id) {
                $affiliateMember = Affiliate::find($request->customer_id);
                if ($affiliateMember) {
                    $customerName  = $affiliateMember->name; 
                    $customerPhone = $affiliateMember->whatsapp;
                }
            }

            switch ($request->payment_method) {
                // --- TUNAI ---
                case 'cash':
                    $cashReceived = (int) $request->cash_amount;
                    if ($cashReceived < $finalPrice) {
                        throw new \Exception("Uang tunai kurang! Total: Rp " . number_format($finalPrice,0,',','.') . ", Diterima: Rp " . number_format($cashReceived,0,',','.'));
                    }
                    $changeAmount = $cashReceived - $finalPrice;
                    $paymentStatus = 'paid'; 
                    $note .= "\n[INFO PEMBAYARAN]\nMetode: Tunai\nDiterima: Rp " . number_format($cashReceived,0,',','.') . "\nKembali: Rp " . number_format($changeAmount,0,',','.');
                    break;

                // --- SALDO PROFIT AFILIASI (FITUR BARU) ---
                case 'affiliate_balance':
                    if (!$request->customer_id) {
                        throw new \Exception("Wajib pilih Member Afiliasi untuk metode ini.");
                    }
                    
                    // Ambil Data Affiliate & Lock
                    $affiliatePayor = Affiliate::lockForUpdate()->find($request->customer_id);

                    if (!$affiliatePayor) {
                        throw new \Exception("Data Afiliasi tidak ditemukan.");
                    }

                    // 1. VALIDASI PIN
                    if (!Hash::check($request->affiliate_pin, $affiliatePayor->pin)) {
                        throw new \Exception("PIN Keamanan Salah! Transaksi Ditolak.");
                    }

                    // 2. CEK SALDO CUKUP
                    if ($affiliatePayor->balance < $finalPrice) {
                        throw new \Exception("Saldo Profit Tidak Cukup. Saldo: Rp " . number_format($affiliatePayor->balance,0,',','.'));
                    }

                    // 3. POTONG SALDO
                    $affiliatePayor->decrement('balance', $finalPrice);
                    
                    $paymentStatus = 'paid'; 
                    $note .= "\n[INFO PEMBAYARAN]\nMetode: Potong Profit Afiliasi\nSisa Profit: Rp " . number_format($affiliatePayor->balance,0,',','.');
                    break;

                // --- SALDO TOPUP (Tidak dipakai user admin) ---
                case 'saldo':
                     throw new \Exception("Fitur Saldo Topup User belum tersedia.");
                     break;

                // --- PAYMENT GATEWAY ---
                case 'tripay':
                case 'doku':
                    $paymentStatus = 'unpaid';
                    break;
            }


            // ==========================================
            // 5. SIMPAN ORDER KE DATABASE
            // ==========================================
            $order = Order::create([
                'order_number'    => 'INV-' . date('YmdHis') . rand(100, 999),
                'user_id'         => null, // Set Null
                'customer_name'   => $customerName,
                'customer_phone'  => $customerPhone,
                'coupon_id'       => $couponId,
                'total_price'     => $subtotal,
                'discount_amount' => $discount,
                'final_price'     => $finalPrice,
                'payment_method'  => $request->payment_method,
                'status'          => ($paymentStatus === 'paid') ? 'processing' : 'pending', 
                'payment_status'  => $paymentStatus,
                'note'            => $note,
            ]);


            // ==========================================
            // 6. LANJUTAN PEMBAYARAN ONLINE
            // ==========================================
            if ($request->payment_method === 'tripay') {
                $tripayRes = $tripayService->createTransaction($order, $finalCart, null);

                if (!$tripayRes['success']) {
                    throw new \Exception("Gagal koneksi ke Tripay: " . ($tripayRes['message'] ?? 'Unknown Error'));
                }
                
                $paymentUrl = $tripayRes['data']['checkout_url']; 
                $order->update(['payment_url' => $paymentUrl]); 
            }
            elseif ($request->payment_method === 'doku') {
                $customerData = [
                    'name'  => $order->customer_name,
                    'email' => 'customer@tokosancaka.com', 
                    'phone' => $order->customer_phone ?? '085745808809',
                ];

                $dokuService = app(\App\Services\DokuJokulService::class);
                $paymentUrl = $dokuService->createPayment(
                    $order->order_number, 
                    $order->final_price,
                    $customerData 
                );
                
                if (empty($paymentUrl)) {
                    throw new \Exception("Gagal generate link pembayaran DOKU.");
                }
                $order->update(['payment_url' => $paymentUrl]);
            }


            // ==========================================
            // 7. SIMPAN DETAIL BARANG & UPDATE STOK
            // ==========================================
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

                // Kurangi Stok
                $prod->decrement('stock', $data['qty']);
                $prod->increment('sold', $data['qty']);
                
                if ($prod->stock <= 0) {
                    $prod->update(['stock_status' => 'unavailable']);
                }
            }


            // ==========================================
            // 8. SIMPAN FILE UPLOAD
            // ==========================================
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

            // COMMIT TRANSAKSI
            DB::commit();


            // ==========================================
            // 9. LOGIKA KOMISI / BAGI HASIL (Add Balance)
            // ==========================================
            // Jika ada kupon yang dipakai, pemilik kupon dapat komisi
            if ($request->coupon && $paymentStatus == 'paid') {
                try {
                    $affiliateOwner = Affiliate::where('coupon_code', $request->coupon)->first();
                    
                    if ($affiliateOwner) {
                        // Logic Komisi 10%
                        $komisiRate = 0.10; 
                        $komisiDiterima = $finalPrice * $komisiRate;

                        // Tambah Saldo
                        $affiliateOwner->increment('balance', $komisiDiterima);

                        // Notif WA Komisi Masuk
                        $fonnteToken = env('FONNTE_API_KEY') ?? env('FONNTE_KEY');
                        if($fonnteToken && $affiliateOwner->whatsapp) {
                            $msgKomisi = "💰 *KOMISI MASUK!* 💰\n\n";
                            $msgKomisi .= "Selamat! Kupon Anda *{$request->coupon}* baru saja digunakan.\n\n";
                            $msgKomisi .= "💵 Komisi: Rp " . number_format($komisiDiterima, 0, ',', '.') . "\n";
                            $msgKomisi .= "💳 Total Saldo Profit: Rp " . number_format($affiliateOwner->balance, 0, ',', '.') . "\n\n";
                            $msgKomisi .= "Saldo bisa digunakan belanja di Toko Sancaka.";
                            
                            try {
                                Http::withHeaders(['Authorization' => $fonnteToken])
                                    ->post('https://api.fonnte.com/send', [
                                        'target' => $affiliateOwner->whatsapp,
                                        'message' => $msgKomisi,
                                    ]);
                            } catch (\Exception $e) {}
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("Gagal tambah komisi: " . $e->getMessage());
                }
            }


            // ==========================================
            // 10. NOTIFIKASI WA UMUM (Customer & Admin)
            // ==========================================
            try {
                $fonnteToken = env('FONNTE_API_KEY') ?? env('FONNTE_KEY');

                if ($fonnteToken) {
                    // A. Kirim ke CUSTOMER
                    if ($customerPhone) {
                        $msgCust = "Halo Kak *$customerName*,\n\n";
                        $msgCust .= "Terima kasih, pesananmu berhasil dibuat! ✅\n";
                        $msgCust .= "No Invoice: *$order->order_number*\n";
                        $msgCust .= "Total: Rp " . number_format($finalPrice,0,',','.') . "\n";
                        $msgCust .= "Status Bayar: *$paymentStatus*\n";
                        
                        if ($paymentUrl) {
                            $msgCust .= "Link Pembayaran: $paymentUrl\n";
                        }
                        
                        $msgCust .= "\nTerima kasih telah berbelanja di Sancaka Express.";

                        Http::withHeaders(['Authorization' => $fonnteToken])
                            ->post('https://api.fonnte.com/send', [
                                'target' => $customerPhone,
                                'message' => $msgCust,
                            ]);
                    }

                    // B. Kirim ke ADMIN
                    $adminPhone = '085745808809'; 
                    $msgAdmin = "🔔 *ORDER BARU MASUK*\n\n";
                    $msgAdmin .= "Invoice: *$order->order_number*\n";
                    $msgAdmin .= "Customer: $customerName\n";
                    $msgAdmin .= "Total: Rp " . number_format($finalPrice, 0, ',', '.') . "\n";
                    $msgAdmin .= "Metode: " . strtoupper($request->payment_method) . "\n";
                    $msgAdmin .= "Status: *$paymentStatus*\n";
                    $msgAdmin .= "Waktu: " . date('d-m-Y H:i') . "\n";

                    Http::withHeaders(['Authorization' => $fonnteToken])
                        ->post('https://api.fonnte.com/send', [
                            'target' => $adminPhone,
                            'message' => $msgAdmin,
                        ]);
                }

            } catch (\Exception $waError) {
                Log::error('Fonnte WA Error: ' . $waError->getMessage());
            }

            // ==========================================
            // 11. RETURN RESPONSE
            // ==========================================
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
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * API Cek Kupon (Live Search)
     */
    public function checkCoupon(Request $request)
    {
        $request->validate([
            'coupon_code'   => 'required|string',
            'total_belanja' => 'required|numeric|min:0'
        ]);

        $code = trim($request->coupon_code);
        $total = $request->total_belanja;

        $coupon = Coupon::where('code', 'LIKE', $code)->first();

        if (!$coupon) {
            return response()->json(['status' => 'error', 'message' => 'Kode kupon tidak ditemukan.'], 404);
        }

        if (!$coupon->is_active) {
            return response()->json(['status' => 'error', 'message' => 'Kupon ini sudah dinonaktifkan.'], 400);
        }

        $now = now();
        if ($coupon->start_date && $now->lt($coupon->start_date)) {
            return response()->json(['status' => 'error', 'message' => 'Promo belum dimulai.'], 400);
        }

        if ($coupon->expiry_date && $now->gt($coupon->expiry_date)) {
            return response()->json(['status' => 'error', 'message' => 'Kupon sudah kedaluwarsa.'], 400);
        }

        if ($coupon->usage_limit > 0 && $coupon->used_count >= $coupon->usage_limit) {
            return response()->json(['status' => 'error', 'message' => 'Kuota penggunaan kupon habis.'], 400);
        }

        if ($coupon->min_order_amount > 0 && $total < $coupon->min_order_amount) {
            return response()->json(['status' => 'error', 'message' => 'Min belanja Rp ' . number_format($coupon->min_order_amount, 0, ',', '.') . '.'], 400);
        }

        // Hitung Diskon
        $discountAmount = $coupon->calculateDiscount($total);
        if ($discountAmount > $total) {
            $discountAmount = $total;
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Kupon berhasil diterapkan!',
            'data' => [
                'coupon_id'       => $coupon->id,
                'code'            => $coupon->code,
                'discount_amount' => $discountAmount,
                'final_total'     => max(0, $total - $discountAmount),
                'type'            => $coupon->type
            ]
        ]);
    }
}