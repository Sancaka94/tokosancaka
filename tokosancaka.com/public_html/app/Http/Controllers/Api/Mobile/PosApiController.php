<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PosApiController extends Controller
{
    public function getProducts(Request $request)
    {
        try {
            $user = Auth::user() ?? auth('sanctum')->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Unauthorized - Token tidak valid'
                ], 401);
            }

            $sellerName = $user->nama_lengkap;

            $query = Product::where('seller_name', $sellerName)
                            ->where('status', 'active')
                            ->where('stock', '>', 0);
            
            if ($request->filled('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            // Ambil data produk
            $products = $query->latest()->get();

            // Format URL Gambar (Menggunakan strpos agar aman di semua versi PHP)
            $products->map(function ($item) {
                $imagePath = $item->image_url ?? $item->image ?? $item->foto ?? null;
                
                if ($imagePath && strpos($imagePath, 'http') !== 0) {
                    $item->full_image_url = asset('storage/' . ltrim($imagePath, '/'));
                } else {
                    $item->full_image_url = $imagePath;
                }
                
                return $item;
            });

            return response()->json([
                'success' => true,
                'data' => $products
            ]);

        } catch (\Throwable $e) { 
            // 🔴 MENGGUNAKAN \Throwable AGAR FATAL ERROR PHP TERTANGKAP KE HP
            return response()->json([
                'success' => false,
                'message' => 'ERROR ASLI: ' . $e->getMessage() . ' | Baris: ' . $e->getLine()
            ], 500);
        }
    }

    public function processTransaction(Request $request)
    {
        $request->validate([
            'payment_method' => 'required|string',
            'amount_paid'    => 'required|numeric',
            'items'          => 'required|array|min:1',
        ]);

        $user = Auth::user() ?? auth('sanctum')->user();
        if (!$user) return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);

        DB::beginTransaction();
        try {
            $invoiceNumber = 'POS-' . date('YmdHis') . rand(10, 99);
            $grandTotal = collect($request->items)->sum(function($item) {
                return $item['price'] * $item['qty'];
            });

            $order = Order::create([
                'invoice_number'   => $invoiceNumber,
                'user_id'          => $user->id_pengguna ?? $user->id, 
                'subtotal'         => $grandTotal,
                'shipping_cost'    => 0, 
                'shipping_method'  => 'Di Tempat (POS)',
                'shipping_address' => 'Pembelian di Toko (POS)',
                'total_amount'     => $grandTotal,
                'payment_method'   => $request->payment_method,
                'status'           => 'paid', 
                'type'             => 'pos'   
            ]);

            foreach ($request->items as $item) {
                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $item['id'],
                    'quantity'   => $item['qty'],
                    'price'      => $item['price']
                ]);

                Product::where('id', $item['id'])->decrement('stock', $item['qty']);
            }

            DB::commit();

            return response()->json([
                'success' => true, 
                'message' => 'Transaksi berhasil!',
                'data' => ['invoice' => $invoiceNumber, 'total' => $grandTotal]
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()], 500);
        }
    }

   public function getHistory(Request $request)
    {
        try {
            $user = Auth::user() ?? auth('sanctum')->user();
            if (!$user) return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);

            $userId = $user->id_pengguna ?? $user->id;

            // Mengambil riwayat beserta detail item dan produknya
            $history = Order::with(['items.product'])
                            ->where('user_id', $userId)
                            ->orderBy('created_at', 'desc')
                            ->get();

            // Memformat URL gambar produk agar bisa dibaca oleh aplikasi
            $history->map(function ($order) {
                if ($order->items) {
                    $order->items->map(function ($item) {
                        if ($item->product) {
                            $imagePath = $item->product->image_url ?? $item->product->image ?? $item->product->foto ?? null;
                            if ($imagePath && strpos($imagePath, 'http') !== 0) {
                                $item->product->full_image_url = asset('storage/' . ltrim($imagePath, '/'));
                            } else {
                                $item->product->full_image_url = $imagePath;
                            }
                        }
                        return $item;
                    });
                }
                return $order;
            });

            return response()->json([
                'success' => true,
                'data' => $history
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }
}