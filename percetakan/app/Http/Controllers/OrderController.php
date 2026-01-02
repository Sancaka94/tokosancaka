<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

// Models
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderDetail;
use App\Models\OrderAttachment;
use App\Models\Coupon;
use App\Models\User;

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

    /**
     * Proses Simpan Pesanan (Checkout)
     */
    public function store(Request $request, TripayService $tripayService)
    {
        // ==========================================
        // 1. VALIDASI INPUT
        // ==========================================
        $request->validate([
            'items'          => 'required', // Dikirim sebagai JSON string dari FormData
            'total'          => 'required|numeric',
            'payment_method' => 'required|in:cash,saldo,tripay,doku',
            
            // Validasi Kondisional: Cash butuh jumlah uang, Saldo butuh ID Customer
            'cash_amount'    => 'nullable|numeric|required_if:payment_method,cash',
            'customer_id'    => 'nullable|exists:users,id|required_if:payment_method,saldo',
            
            // Validasi File Upload (Gambar/Dokumen max 10MB)
            'attachments.*'  => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:10240'
        ]);

        // Decode JSON Keranjang menjadi Array PHP
        $cartItems = json_decode($request->items, true);

        if (!is_array($cartItems) || count($cartItems) < 1) {
            return response()->json(['status' => 'error', 'message' => 'Keranjang belanja kosong.'], 400);
        }

        // Mulai Transaksi Database (Atomic Transaction)
        DB::beginTransaction();

        try {
            $subtotal = 0;
            $finalCart = []; // Menampung item yang sudah divalidasi

            // ==========================================
            // 2. VALIDASI STOK & HITUNG HARGA (Server Side)
            // ==========================================
            foreach ($cartItems as $item) {
                // Lock baris database (Pessimistic Locking) agar stok tidak diambil user lain saat proses ini
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
                // Opsional: Tambahkan cek tanggal expired kupon disini
                
                if ($couponDB) {
                    $couponId = $couponDB->id;
                    if ($couponDB->type == 'percent') {
                        $discount = $subtotal * ($couponDB->value / 100);
                    } else {
                        $discount = $couponDB->value;
                    }
                }
            }

            // Total Akhir yang harus dibayar
            $finalPrice = max(0, $subtotal - $discount);


            // ==========================================
            // 4. LOGIKA PEMBAYARAN (Inti Sistem)
            // ==========================================
            $paymentStatus = 'unpaid';
            $paymentUrl = null;     // Untuk Tripay/Doku
            $changeAmount = 0;      // Kembalian Cash
            $userId = null;         // ID Customer (jika ada)
            $customerName = $request->customer_name ?? 'Guest';
            $customerPhone = $request->customer_phone;
            $note = $request->note;

            // Jika admin memilih member dari dropdown (berlaku utk semua metode bayar jika mau)
            if ($request->customer_id) {
                $member = User::find($request->customer_id);
                if ($member) {
                    $userId = $member->id;
                    $customerName = $member->name; // Pakai nama asli dari DB
                    $customerPhone = $member->no_hp ?? $member->phone ?? $customerPhone;
                }
            }

            switch ($request->payment_method) {
                // KASUS A: BAYAR TUNAI
                case 'cash':
                    $cashReceived = (int) $request->cash_amount;
                    
                    // Validasi: Uang tidak boleh kurang
                    if ($cashReceived < $finalPrice) {
                        throw new \Exception("Uang tunai kurang! Total Tagihan: Rp " . number_format($finalPrice,0,',','.') . ", Diterima: Rp " . number_format($cashReceived,0,',','.'));
                    }

                    $changeAmount = $cashReceived - $finalPrice;
                    $paymentStatus = 'paid'; // Lunas
                    
                    $note .= "\n[INFO PEMBAYARAN]\nMetode: Tunai\nDiterima: Rp " . number_format($cashReceived,0,',','.') . "\nKembali: Rp " . number_format($changeAmount,0,',','.');
                    break;

                // KASUS B: POTONG SALDO MEMBER
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
                    
                    $paymentStatus = 'paid'; // Lunas
                    $note .= "\n[INFO PEMBAYARAN]\nMetode: Potong Saldo\nMember ID: $userId";
                    break;

                // KASUS C: TRIPAY (Online)
                case 'tripay':
                    // Proses generate link dilakukan SETELAH order ID terbentuk (di bawah)
                    $paymentStatus = 'unpaid';
                    break;

                // KASUS D: DOKU (Online)
                case 'doku':
                    // Proses generate link dilakukan SETELAH order ID terbentuk (di bawah)
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
                'status'          => ($paymentStatus === 'paid') ? 'processing' : 'pending', // Kalau lunas langsung diproses
                'payment_status'  => $paymentStatus,
                'note'            => $note,
            ]);


            // ==========================================
            // 6. LANJUTAN PEMBAYARAN ONLINE (Butuh Order ID)
            // ==========================================
            
            // Integrasi Tripay
            if ($request->payment_method === 'tripay') {
                // Panggil Service Tripay (createTransaction)
                // Parameter ke-3 null = User bisa pilih metode (QRIS/VA) di halaman Tripay
                $tripayRes = $tripayService->createTransaction($order, $finalCart, null);

                if (!$tripayRes['success']) {
                    throw new \Exception("Gagal koneksi ke Tripay: " . ($tripayRes['message'] ?? 'Unknown Error'));
                }
                
                $paymentUrl = $tripayRes['data']['checkout_url']; // Link bayar
                $order->update(['payment_url' => $paymentUrl]); // Simpan link ke DB
            }
            
            // Integrasi Doku
            elseif ($request->payment_method === 'doku') {
                // Asumsi: Anda sudah punya service DokuJokulService yang valid
                $dokuService = new DokuJokulService();
                $paymentUrl = $dokuService->createPayment($order->order_number, $order->final_price);
                
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
                
                // Simpan ke tabel order_details
                OrderDetail::create([
                    'order_id'       => $order->id,
                    'product_id'     => $prod->id,
                    'product_name'   => $prod->name,     // Simpan nama saat ini (snapshot)
                    'price_at_order' => $prod->sell_price, 
                    'quantity'       => $data['qty'],
                    'subtotal'       => $data['subtotal'],
                ]);

                // Kurangi Stok & Tambah Counter Terjual
                $prod->decrement('stock', $data['qty']);
                $prod->increment('sold', $data['qty']);
                
                // Update status produk jika habis
                if ($prod->stock <= 0) {
                    $prod->update(['stock_status' => 'unavailable']);
                }
            }


            // ==========================================
            // 8. SIMPAN FILE UPLOAD (Jika Ada)
            // ==========================================
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    // Upload fisik file ke folder storage/app/public/orders
                    $path = $file->store('orders', 'public');

                    // Simpan info ke database
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
            // 9. RETURN RESPONSE JSON KE FRONTEND
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
}