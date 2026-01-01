<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\OrderDetail;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    // 1. Menampilkan halaman POS
    public function create()
    {
        $products = Product::where('stock_status', 'available')->get();
        return view('orders.create', compact('products'));
    }

    // 2. Menyimpan Pesanan dari Frontend (AJAX/Fetch)
    public function store(Request $request)
    {
        // Validasi input
        $request->validate([
            'customer_name' => 'required|string|max:255',
            'cart' => 'required|array|min:1',
        ]);

        DB::beginTransaction();

        try {
            $subtotal = 0;
            $discount = 0;

            // Hitung Subtotal berdasarkan data DB (keamanan harga)
            foreach ($request->cart as $item) {
                $product = Product::findOrFail($item['id']);
                $subtotal += $product->base_price * $item['qty'];
            }

            // Logika Kupon (Opsional)
            if ($request->coupon_code) {
                $coupon = Coupon::where('code', $request->coupon_code)
                                ->where('is_active', true)
                                ->first();
                if ($coupon) {
                    $discount = ($coupon->type == 'percent') 
                                ? ($subtotal * ($coupon->value / 100)) 
                                : $coupon->value;
                }
            }

            // Simpan Data Order Utama
            $order = Order::create([
                'order_number' => 'INV-' . strtoupper(Str::random(8)),
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'total_price' => $subtotal,
                'discount_amount' => $discount,
                'final_price' => $subtotal - $discount,
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'note' => $request->note,
            ]);

            // Simpan Setiap Item ke OrderDetails
            foreach ($request->cart as $item) {
                $product = Product::findOrFail($item['id']);
                OrderDetail::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'price_at_order' => $product->base_price,
                    'quantity' => $item['qty'],
                    'subtotal' => $product->base_price * $item['qty'],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true, 
                'message' => 'Pesanan berhasil dibuat!',
                'order_id' => $order->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false, 
                'message' => 'Gagal menyimpan pesanan: ' . $e->getMessage()
            ], 500);
        }
    }
}