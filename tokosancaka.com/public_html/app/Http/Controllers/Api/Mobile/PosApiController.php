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
            $userId = $user->id_pengguna ?? $user->id; // Mengambil ID untuk mencocokkan Admin

            // 🔥 PERBAIKAN: Filter Kombinasi (Cari seller_name ATAU store_id)
            $query = Product::where(function($q) use ($sellerName, $userId) {
                                $q->where('seller_name', $sellerName)
                                  ->orWhere('store_id', $userId);
                            })
                            ->where('status', 'active');
            
            if ($request->filled('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            // Ambil data produk
            $products = $query->latest()->get();

            // Format URL Gambar (Aman di semua versi PHP)
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

    // ==============================================
    // CRUD STOK PRODUK (TAMBAH, EDIT, HAPUS)
    // ==============================================

    // 1. Tambah Produk Baru
    public function storeProduct(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'price' => 'required|numeric',
            'stock' => 'required|numeric',
            'sku'   => 'nullable|string|max:100'
        ]);

        $user = Auth::user() ?? auth('sanctum')->user();
        if (!$user) return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);

        try {
            // Generate slug otomatis dari nama
            $slug = \Illuminate\Support\Str::slug($request->name) . '-' . uniqid();

            $product = Product::create([
                'store_id'    => $user->id_pengguna ?? $user->id,
                'seller_name' => $user->nama_lengkap,
                'name'        => $request->name,
                'price'       => $request->price,
                'stock'       => $request->stock,
                'sku'         => $request->sku,
                'status'      => 'active', // default status
                'slug'        => $slug
            ]);

            return response()->json(['success' => true, 'message' => 'Produk berhasil ditambahkan', 'data' => $product]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()], 500);
        }
    }

    // 2. Edit Produk
    public function updateProduct(Request $request, $id)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'price' => 'required|numeric',
            'stock' => 'required|numeric',
            'sku'   => 'nullable|string|max:100'
        ]);

        $user = Auth::user() ?? auth('sanctum')->user();
        if (!$user) return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);

        try {
            $product = Product::find($id);
            if (!$product) {
                return response()->json(['success' => false, 'message' => 'Produk tidak ditemukan'], 404);
            }

            $product->update([
                'name'  => $request->name,
                'price' => $request->price,
                'stock' => $request->stock,
                'sku'   => $request->sku
            ]);

            return response()->json(['success' => true, 'message' => 'Produk berhasil diperbarui', 'data' => $product]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()], 500);
        }
    }

    // 3. Hapus Produk (Satuan)
    public function destroyProduct($id)
    {
        $user = Auth::user() ?? auth('sanctum')->user();
        if (!$user) return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);

        try {
            $product = Product::find($id);
            if (!$product) {
                return response()->json(['success' => false, 'message' => 'Produk tidak ditemukan'], 404);
            }

            $product->delete();

            return response()->json(['success' => true, 'message' => 'Produk berhasil dihapus']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()], 500);
        }
    }

    // 4. Hapus Produk (Massal)
    public function bulkDestroyProducts(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'integer'
        ]);

        $user = Auth::user() ?? auth('sanctum')->user();
        if (!$user) return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);

        try {
            Product::whereIn('id', $request->ids)->delete();

            return response()->json(['success' => true, 'message' => count($request->ids) . ' produk berhasil dihapus']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()], 500);
        }
    }
}