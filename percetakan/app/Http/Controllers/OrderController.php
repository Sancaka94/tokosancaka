<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// Import Models
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderDetail;
use App\Models\OrderAttachment;
use App\Models\Coupon;

class OrderController extends Controller
{
    /**
     * Menampilkan Halaman Kasir (POS)
     */
    public function create()
    {
        // Ambil produk yang stoknya tersedia
        // Urutkan dari yang terbaru
        $products = Product::where('stock_status', 'available')
                           ->where('stock', '>', 0)
                           ->orderBy('created_at', 'desc')
                           ->get();

        return view('orders.create', compact('products'));
    }

    /**
     * Memproses Checkout (Simpan Order, Detail, dan File)
     */
    public function store(Request $request)
    {
        // 1. Validasi Input
        $request->validate([
            'items' => 'required', // JSON string dari keranjang
            'total' => 'required|numeric',
            // Validasi File: Maksimal 10MB per file, format dokumen/gambar
            'attachments.*' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:10240'
        ]);

        // Decode JSON item keranjang
        $cartItems = json_decode($request->items, true);

        if (!is_array($cartItems) || count($cartItems) < 1) {
            return response()->json(['status' => 'error', 'message' => 'Keranjang belanja kosong.'], 400);
        }

        // Mulai Transaksi Database
        DB::beginTransaction();

        try {
            $subtotal = 0;
            $finalCart = []; 

            // 2. Validasi Stok & Hitung Harga Server-Side (Keamanan)
            foreach ($cartItems as $item) {
                $product = Product::find($item['id']);

                if (!$product) {
                    throw new \Exception("Produk ID {$item['id']} tidak ditemukan.");
                }

                // Cek ketersediaan stok
                if ($product->stock < $item['qty']) {
                    throw new \Exception("Stok '{$product->name}' tidak cukup. Sisa: {$product->stock}");
                }

                // Hitung total pakai harga database (bukan harga kiriman client)
                $lineTotal = $product->sell_price * $item['qty'];
                $subtotal += $lineTotal;

                // Masukkan ke array final untuk diproses nanti
                $finalCart[] = [
                    'product' => $product,
                    'qty' => $item['qty'],
                    'subtotal' => $lineTotal
                ];
            }

            // 3. Cek Kupon (Opsional)
            $discount = 0;
            if ($request->coupon) {
                $couponDB = Coupon::where('code', $request->coupon)->first(); // Tambahkan logika validasi tanggal/aktif jika perlu
                if ($couponDB) {
                    if ($couponDB->type == 'percent') {
                        $discount = $subtotal * ($couponDB->value / 100);
                    } else {
                        $discount = $couponDB->value;
                    }
                }
            }

            // 4. Simpan Order Utama
            $order = Order::create([
                'invoice_number'  => 'INV-' . date('YmdHis') . rand(100,999),
                'customer_name'   => 'Guest', // Bisa diubah jika ada input nama customer
                'total_price'     => $subtotal,
                'discount_amount' => $discount,
                'final_price'     => $subtotal - $discount,
                'status'          => 'pending',
                'payment_status'  => 'unpaid',
            ]);

            // 5. Simpan Detail Item & Potong Stok
            foreach ($finalCart as $data) {
                $prod = $data['product'];
                
                OrderDetail::create([
                    'order_id'     => $order->id,
                    'product_id'   => $prod->id,
                    'product_name' => $prod->name,
                    'price'        => $prod->sell_price,
                    'qty'          => $data['qty'],
                    'subtotal'     => $data['subtotal'],
                ]);

                // Kurangi stok & tambah counter terjual
                $prod->decrement('stock', $data['qty']);
                $prod->increment('sold', $data['qty']);
            }

            // 6. Simpan File Upload (Jika Ada)
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    // Simpan fisik file ke storage/app/public/orders
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

            DB::commit(); // Simpan permanen

            return response()->json([
                'status' => 'success',
                'message' => 'Transaksi Berhasil!',
                'invoice' => $order->invoice_number
            ]);

        } catch (\Exception $e) {
            DB::rollBack(); // Batalkan jika error
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}