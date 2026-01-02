<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Import Model
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderDetail;
use App\Models\OrderAttachment; // Pastikan model ini sudah dibuat
use App\Models\Coupon;

class OrderController extends Controller
{
    // 1. Menampilkan Halaman Kasir
    public function create()
    {
        // Ambil produk yang stoknya tersedia (>0) dan status available
        $products = Product::where('stock_status', 'available')
                           ->where('stock', '>', 0)
                           ->orderBy('created_at', 'desc')
                           ->get();

        return view('orders.create', compact('products'));
    }

    // 2. Proses Simpan Pesanan (Checkout)
    public function store(Request $request)
    {
        // A. Validasi Input
        $request->validate([
            'items'       => 'required', // JSON String dari Frontend
            'total'       => 'required|numeric',
            // Validasi File Upload (Gambar/Dokumen max 10MB)
            'attachments.*' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:10240'
        ]);

        // B. Decode JSON dari Frontend (Karena dikirim via FormData sebagai string)
        $cartItems = json_decode($request->items, true);

        if (!is_array($cartItems) || count($cartItems) < 1) {
            return response()->json(['success' => false, 'message' => 'Keranjang belanja kosong.'], 400);
        }

        DB::beginTransaction(); // Mulai Transaksi Database

        try {
            $subtotal = 0;
            $finalCart = []; 
            $couponId = null;

            // --- TAHAP 1: VALIDASI STOK & HITUNG HARGA (Server Side) ---
            foreach ($cartItems as $item) {
                $product = Product::find($item['id']);

                if (!$product) {
                    throw new \Exception("Produk ID {$item['id']} tidak ditemukan.");
                }

                // Cek Stok Realtime
                if ($product->stock < $item['qty']) {
                    throw new \Exception("Stok '{$product->name}' tidak cukup. Sisa: {$product->stock}");
                }

                // Gunakan 'sell_price' (Harga Jual) dari database, JANGAN dari request (biar aman)
                $lineTotal = $product->sell_price * $item['qty'];
                $subtotal += $lineTotal;

                $finalCart[] = [
                    'product' => $product,
                    'qty' => $item['qty'],
                    'subtotal' => $lineTotal
                ];
            }

            // --- TAHAP 2: CEK KUPON ---
            $discount = 0;
            if ($request->coupon) {
                $couponDB = Coupon::where('code', $request->coupon)->first();
                if ($couponDB) {
                    $couponId = $couponDB->id; // Simpan ID untuk relasi database
                    if ($couponDB->type == 'percent') {
                        $discount = $subtotal * ($couponDB->value / 100);
                    } else {
                        $discount = $couponDB->value;
                    }
                }
            }

            // --- TAHAP 3: SIMPAN KE TABEL ORDERS ---
            // Sesuai gambar database image_d0b88a.png
            $order = Order::create([
                'order_number'    => 'INV-' . date('YmdHis') . rand(100, 999),
                'customer_name'   => $request->customer_name ?? 'Guest',
                'customer_phone'  => $request->customer_phone ?? null,
                'coupon_id'       => $couponId,
                'total_price'     => $subtotal,
                'discount_amount' => $discount,
                'final_price'     => $subtotal - $discount,
                'status'          => 'pending',
                'payment_status'  => 'unpaid',
                'note'            => $request->note ?? null,
            ]);

            // --- TAHAP 4: SIMPAN DETAIL & KURANGI STOK ---
            foreach ($finalCart as $data) {
                $prod = $data['product'];
                
                OrderDetail::create([
                    'order_id'     => $order->id,
                    'product_id'   => $prod->id,
                    'product_name' => $prod->name,
                    'price'        => $prod->sell_price, // Harga Jual
                    'qty'          => $data['qty'],
                    'subtotal'     => $data['subtotal'],
                ]);

                // Kurangi Stok & Tambah Terjual
                $prod->decrement('stock', $data['qty']);
                $prod->increment('sold', $data['qty']);
                
                // Jika stok habis, set status unavailable
                if ($prod->stock <= 0) {
                    $prod->update(['stock_status' => 'unavailable']);
                }
            }

            // --- TAHAP 5: UPLOAD BERKAS (Jika Ada) ---
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    // Upload ke storage/app/public/orders
                    $path = $file->store('orders', 'public');

                    OrderAttachment::create([
                        'order_id'  => $order->id,
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'file_type' => $file->getClientMimeType(),
                    ]);
                }
            }

            DB::commit(); // Simpan Permanen

            return response()->json([
                'status' => 'success', // Sesuai pengecekan di JS (result.status === 'success')
                'message' => 'Pesanan berhasil dibuat!',
                'invoice' => $order->order_number
            ]);

        } catch (\Exception $e) {
            DB::rollBack(); // Batalkan semua jika error
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal: ' . $e->getMessage()
            ], 500);
        }
    }
}