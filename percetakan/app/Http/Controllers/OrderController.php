<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http; // <--- Wajib tambah ini biar WA terkirim
use Illuminate\Support\Str;

// Models
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderDetail;
use App\Models\OrderAttachment;
use App\Models\Coupon;
use App\Models\User;
use App\Models\Affiliate;

// Services (Pastikan file Service ini sudah Anda buat)
use App\Services\TripayService;
use App\Services\DokuJokulService;

class OrderController extends Controller
{
    /**
     * Menampilkan Halaman Kasir (POS)
     */
    public function create()
    {
        // 1. Ambil produk yang stoknya tersedia (lebih dari 0)
        $products = Product::where('stock_status', 'available')
                           ->where('stock', '>', 0)
                           ->orderBy('created_at', 'desc')
                           ->get();
        
        // 2. Ambil data Customer (Member) untuk dropdown "Pilih Pelanggan"
        // (Digunakan khusus untuk pembayaran metode Potong Saldo)
        $customers = User::where('role', 'customer')
                         ->orderBy('name', 'asc')
                         ->get();

        return view('orders.create', compact('products', 'customers'));
    }

    public function store(Request $request, TripayService $tripayService)
    {
        // ==========================================
        // 1. VALIDASI INPUT
        // ==========================================
        $request->validate([
            'items'          => 'required', 
            'total'          => 'required|numeric',
            'payment_method' => 'required|in:cash,saldo,tripay,doku',
            'cash_amount'    => 'nullable|numeric|required_if:payment_method,cash',
            'customer_id'    => 'nullable|exists:users,id|required_if:payment_method,saldo',
            'attachments.*'  => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:10240'
        ]);

        $cartItems = json_decode($request->items, true);

        if (!is_array($cartItems) || count($cartItems) < 1) {
            return response()->json(['status' => 'error', 'message' => 'Keranjang belanja kosong.'], 400);
        }

        // Mulai Transaksi Database (Atomic Transaction)
        DB::beginTransaction();

        try {
            $subtotal = 0;
            $finalCart = []; 

            // ==========================================
            // 2. VALIDASI STOK & HITUNG HARGA (Server Side)
            // ==========================================
            foreach ($cartItems as $item) {
                // Lock baris database (Pessimistic Locking)
                $product = Product::lockForUpdate()->find($item['id']);

                if (!$product) {
                    throw new \Exception("Produk ID {$item['id']} tidak ditemukan.");
                }

                // Cek ketersediaan stok
                if ($product->stock < $item['qty']) {
                    throw new \Exception("Stok '{$product->name}' tidak mencukupi. Sisa: {$product->stock}");
                }

                // Hitung harga menggunakan data database (Security)
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
                
                // Gunakan validasi manual atau function model jika ada
                if ($couponDB && $couponDB->is_active) {
                    // Cek validitas standar (tanggal & limit)
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
                        
                        // Tambah counter penggunaan
                        $couponDB->increment('used_count');
                    }
                }
            }

            // Total Akhir yang harus dibayar
            $finalPrice = max(0, $subtotal - $discount);


            // ==========================================
            // 4. LOGIKA PEMBAYARAN (Inti Sistem)
            // ==========================================
            $paymentStatus = 'unpaid';
            $paymentUrl = null;    // Untuk Tripay/Doku
            $changeAmount = 0;     // Kembalian Cash
            $userId = null;        // ID Customer (jika ada)
            $customerName = $request->customer_name ?? 'Guest';
            $customerPhone = $request->customer_phone;
            $note = $request->note;

            // Jika admin memilih member dari dropdown
            if ($request->customer_id) {
                $member = User::find($request->customer_id);
                if ($member) {
                    $userId = $member->id;
                    $customerName = $member->name; 
                    $customerPhone = $member->no_hp ?? $member->phone ?? $customerPhone;
                }
            }

            switch ($request->payment_method) {
                case 'cash':
                    $cashReceived = (int) $request->cash_amount;
                    if ($cashReceived < $finalPrice) {
                        throw new \Exception("Uang tunai kurang! Total: Rp " . number_format($finalPrice,0,',','.') . ", Diterima: Rp " . number_format($cashReceived,0,',','.'));
                    }
                    $changeAmount = $cashReceived - $finalPrice;
                    $paymentStatus = 'paid'; // Lunas
                    $note .= "\n[INFO PEMBAYARAN]\nMetode: Tunai\nDiterima: Rp " . number_format($cashReceived,0,',','.') . "\nKembali: Rp " . number_format($changeAmount,0,',','.');
                    break;

                case 'saldo':
                    if (!$userId) {
                        throw new \Exception("Metode Potong Saldo wajib memilih Member terdaftar.");
                    }
                    // Lock saldo user agar aman
                    $member = User::lockForUpdate()->find($userId);

                    if ($member->saldo < $finalPrice) {
                        throw new \Exception("Saldo member tidak cukup. Sisa Saldo: Rp " . number_format($member->saldo,0,',','.'));
                    }
                    // Eksekusi Potong Saldo
                    $member->decrement('saldo', $finalPrice);
                    $paymentStatus = 'paid'; 
                    $note .= "\n[INFO PEMBAYARAN]\nMetode: Potong Saldo\nMember ID: $userId";
                    break;

                case 'tripay':
                case 'doku':
                    $paymentStatus = 'unpaid';
                    break;
            }


            // ==========================================
            // 5. SIMPAN ORDER UTAMA KE DATABASE
            // ==========================================
            $order = Order::create([
                'order_number'    => 'INV-' . date('YmdHis') . rand(100, 999),
                'user_id'         => $userId,
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
            // 6. LANJUTAN PEMBAYARAN ONLINE (Butuh Order ID)
            // ==========================================
            
            // Integrasi Tripay
            if ($request->payment_method === 'tripay') {
                $tripayRes = $tripayService->createTransaction($order, $finalCart, null);

                if (!$tripayRes['success']) {
                    throw new \Exception("Gagal koneksi ke Tripay: " . ($tripayRes['message'] ?? 'Unknown Error'));
                }
                
                $paymentUrl = $tripayRes['data']['checkout_url']; 
                $order->update(['payment_url' => $paymentUrl]); 
            }
            
            // Integrasi Doku
            elseif ($request->payment_method === 'doku') {
                $customerData = [
                    'name'  => $order->customer_name,
                    'email' => 'customer@tokosancaka.com', 
                    'phone' => $order->customer_phone ?? '085745808809',
                ];

                if ($order->user_id) {
                    $user = User::find($order->user_id);
                    if ($user) $customerData['email'] = $user->email;
                }

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
                    'order_id'          => $order->id,
                    'product_id'        => $prod->id,
                    'product_name'      => $prod->name,     
                    'base_price_at_order' => $prod->base_price, 
                    'price_at_order'    => $prod->sell_price, 
                    'quantity'          => $data['qty'],
                    'subtotal'          => $data['subtotal'],
                ]);

                // Kurangi Stok & Tambah Counter Terjual
                $prod->decrement('stock', $data['qty']);
                $prod->increment('sold', $data['qty']);
                
                if ($prod->stock <= 0) {
                    $prod->update(['stock_status' => 'unavailable']);
                }
            }


            // ==========================================
            // 8. SIMPAN FILE UPLOAD (Jika Ada)
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

            // Simpan semua perubahan ke database
            DB::commit();

            // ==========================================
            // 9. FITUR FONNTE (NOTIFIKASI WHATSAPP)
            // ==========================================
            try {
                // Ambil Token (sesuai settingan env Anda)
                $fonnteToken = env('FONNTE_API_KEY') ?? env('FONNTE_KEY');

                if ($fonnteToken) {
                    
                    // --- A. Kirim ke CUSTOMER (Pembeli) ---
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

                    // --- B. Kirim ke ADMIN TOKO (085745808809) ---
                    $adminPhone = '085745808809'; 
                    
                    $msgAdmin = "🔔 *ORDER BARU MASUK*\n\n";
                    $msgAdmin .= "Invoice: *$order->order_number*\n";
                    $msgAdmin .= "Customer: $customerName\n";
                    $msgAdmin .= "Total: Rp " . number_format($finalPrice, 0, ',', '.') . "\n";
                    $msgAdmin .= "Metode: " . strtoupper($request->payment_method) . "\n";
                    $msgAdmin .= "Status: *$paymentStatus*\n";
                    $msgAdmin .= "Waktu: " . date('d-m-Y H:i') . "\n\n";
                    $msgAdmin .= "Mohon segera cek dashboard.";

                    Http::withHeaders(['Authorization' => $fonnteToken])
                        ->post('https://api.fonnte.com/send', [
                            'target' => $adminPhone,
                            'message' => $msgAdmin,
                        ]);

                    // ==========================================================
// C. KIRIM KE PEMILIK KUPON (FIXED LOGIC)
// ==========================================================
if ($request->coupon) {
    
    $affiliateData = Affiliate::where('coupon_code', $request->coupon)->first();

    if ($affiliateData && !empty($affiliateData->whatsapp)) {
        
        // --- 1. Hitung Gross Profit dari Item (Selisih Harga Jual - Modal) ---
        // Ini profit murni barang sebelum kena potongan diskon kupon
        $grossProfit = DB::table('orders')
            ->join('order_details', 'orders.id', '=', 'order_details.order_id')
            ->where('orders.coupon_id', $couponId)
            ->where('orders.status', '!=', 'cancelled')
            ->sum(DB::raw('(order_details.price_at_order - order_details.base_price_at_order) * order_details.quantity'));

        // --- 2. Hitung Total Diskon yang diberikan ke Customer ---
        // Karena diskon mengurangi keuntungan toko, ini harus jadi pengurang
        $totalDiskonDiberikan = Order::where('coupon_id', $couponId)
            ->where('status', '!=', 'cancelled')
            ->sum('discount_amount');

        // --- 3. Hitung PROFIT BERSIH ---
        // Profit Barang - Diskon yang dikasih ke orang
        $realProfit = $grossProfit - $totalDiskonDiberikan;

        // --- 4. Hitung Omzet (Uang yang masuk/dibayar Customer) ---
        $totalOmzet = Order::where('coupon_id', $couponId)
            ->where('status', '!=', 'cancelled')
            ->sum('final_price'); // Pakai final_price (setelah diskon)

        $totalTrax = Order::where('coupon_id', $couponId)
            ->where('status', '!=', 'cancelled')
            ->count();


        // --- SUSUN PESAN WHATSAPP ---
        $affiliateName = $affiliateData->name ?? 'Partner';
        $bankName      = $affiliateData->bank_name ?? 'BCA/Bank Lain';
        $targetPhone   = $affiliateData->whatsapp;

        $msgAff = "Halo Partner *$affiliateName*, 👋\n\n";
        $msgAff .= "Kabar Gembira! 🎉\n";
        $msgAff .= "Ada order BARU masuk pakai kupon: *{$request->coupon}*\n\n";
        
        $msgAff .= "📄 *DETAIL TRANSAKSI SAAT INI:*\n";
        $msgAff .= "├ Invoice: $order->order_number\n";
        $msgAff .= "├ Nominal Belanja: Rp " . number_format($finalPrice, 0, ',', '.') . "\n"; // Tampilkan yang dibayar customer
        $msgAff .= "└ Status: " . strtoupper($paymentStatus) . "\n\n";

        $msgAff .= "💰 *PERFORMA ANDA (AKUMULASI):*\n";
        $msgAff .= "├ Total Order Masuk: *$totalTrax x*\n";
        $msgAff .= "├ Total Omzet: *Rp " . number_format($totalOmzet, 0, ',', '.') . "*\n";
        $msgAff .= "└ Total Profit Bersih: *Rp " . number_format($realProfit, 0, ',', '.') . "*\n\n"; // <--- INI SUDAH BENAR SEKARANG

        $msgAff .= "Pencairan dana Kakak *$affiliateName* Akan di transfer ke Rekening Bank *$bankName* kakak, Terimakasih \n\n";
        $msgAff .= "Semangat terus promosinya! 🚀";

        Http::withHeaders(['Authorization' => $fonnteToken])
            ->post('https://api.fonnte.com/send', [
                'target' => $targetPhone,
                'message' => $msgAff,
            ]);
            
    }
}
                }

            } catch (\Exception $waError) {
                // Log error WA agar bisa dicek developer, tapi JANGAN gagalkan transaksi utama
                Log::error('Fonnte WA Error: ' . $waError->getMessage());
            }
            // ==========================================
            // END NOTIFIKASI
            // ==========================================


            // ==========================================
            // 10. RETURN RESPONSE JSON KE FRONTEND
            // ==========================================
            return response()->json([
                'status'         => 'success',
                'message'        => 'Transaksi Berhasil!',
                'invoice'        => $order->order_number,
                'order_id'       => $order->id,
                'payment_url'    => $paymentUrl, // Link bayar (Tripay/Doku) atau null
                'change_amount'  => $changeAmount, // Kembalian (khusus Cash)
                'payment_method' => $request->payment_method
            ]);

        } catch (\Exception $e) {
            // Batalkan semua transaksi database jika ada error
            DB::rollBack();
            
            // Log error untuk debugging developer
            Log::error('Order Transaction Failed: ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage() // Tampilkan pesan error ke layar kasir
            ], 500);
        }
    }

    /**
     * API Cek Kupon (Live Search dari Frontend)
     */
    public function checkCoupon(Request $request)
    {
        // 1. Validasi Input
        $request->validate([
            'coupon_code'   => 'required|string',
            'total_belanja' => 'required|numeric|min:0'
        ]);

        $code = trim($request->coupon_code); // Hapus spasi depan/belakang
        $total = $request->total_belanja;

        // 2. Cari Kupon (Case Insensitive)
        // Kita gunakan 'LIKE' tanpa % agar pencarian tetap spesifik tapi tidak sensitif huruf besar/kecil
        $coupon = Coupon::where('code', 'LIKE', $code)->first();

        // --- FILTER 1: Cek Ketersediaan ---
        if (!$coupon) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kode kupon tidak ditemukan.'
            ], 404);
        }

        // --- FILTER 2: Cek Status Aktif ---
        if (!$coupon->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kupon ini sudah dinonaktifkan.'
            ], 400);
        }

        // --- FILTER 3: Cek Tanggal (Mulai & Expired) ---
        $now = now();
        if ($coupon->start_date && $now->lt($coupon->start_date)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Promo belum dimulai. Berlaku mulai: ' . $coupon->start_date->format('d M Y')
            ], 400);
        }

        if ($coupon->expiry_date && $now->gt($coupon->expiry_date)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Yah, kupon ini sudah kedaluwarsa pada ' . $coupon->expiry_date->format('d M Y')
            ], 400);
        }

        // --- FILTER 4: Cek Kuota Pemakaian ---
        if ($coupon->usage_limit > 0 && $coupon->used_count >= $coupon->usage_limit) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kuota penggunaan kupon ini sudah habis.'
            ], 400);
        }

        // --- FILTER 5: Cek Minimal Belanja ---
        // Ini validasi paling penting agar toko tidak rugi
        if ($coupon->min_order_amount > 0 && $total < $coupon->min_order_amount) {
            return response()->json([
                'status' => 'error',
                'message' => 'Minimal belanja Rp ' . number_format($coupon->min_order_amount, 0, ',', '.') . ' untuk memakai kupon ini.'
            ], 400);
        }

        // --- JIKA LOLOS SEMUA ---
        // Hitung Diskon
        $discountAmount = $coupon->calculateDiscount($total);

        // Pastikan diskon tidak melebihi total belanja (biar gak minus)
        if ($discountAmount > $total) {
            $discountAmount = $total;
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Kupon berhasil diterapkan!',
            'data' => [
                'coupon_id'       => $coupon->id, // Kirim ID buat disimpan nanti
                'code'            => $coupon->code,
                'discount_amount' => $discountAmount,
                'final_total'     => max(0, $total - $discountAmount),
                'type'            => $coupon->type // percent/fixed (opsional buat UI)
            ]
        ]);
    }
}