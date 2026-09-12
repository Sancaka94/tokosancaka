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
        $user = Auth::user() ?? auth('sanctum')->user();
        
        if (!$user) {
            return response()->json([
                'success' => false, 
                'message' => 'Unauthorized - Token tidak valid'
            ], 401);
        }

        // Karena tabel products tidak punya id_pengguna, 
        // kita filter menggunakan kolom seller_name yang dicocokkan dengan nama_lengkap user.
        $sellerName = $user->nama_lengkap;

        $query = Product::where('seller_name', $sellerName)
                        ->where('status', 'active')
                        ->where('stock', '>', 0);
        
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Map data untuk menambahkan URL gambar yang valid
        $products = $query->latest()->get()->map(function ($item) {
            $imagePath = $item->image_url ?? $item->image ?? $item->foto ?? null;
            
            if ($imagePath && !str_starts_with($imagePath, 'http')) {
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
    }

    // 2. Proses transaksi Kasir (Langsung potong stok, tanpa ongkir)
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

            // Buat Data Pesanan Khusus Kasir (Offline)
            $order = Order::create([
                'invoice_number' => $invoiceNumber,
                'user_id'        => $user->id_pengguna ?? $user->id, // ID Kasir yang bertugas
                'subtotal'       => $grandTotal,
                'shipping_cost'  => 0, // POS tidak ada ongkir
                'shipping_method' => 'Di Tempat (POS)',
                'total_amount'   => $grandTotal,
                'payment_method' => $request->payment_method,
                'status'         => 'paid', // Langsung lunas
                'type'           => 'pos'   // Penanda order dari kasir offline
            ]);

            // Simpan Item & Potong Stok
            foreach ($request->items as $item) {
                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $item['id'],
                    'quantity'   => $item['qty'],
                    'price'      => $item['price']
                ]);

                // Langsung potong stok gudang
                Product::where('id', $item['id'])->decrement('stock', $item['qty']);
            }

            DB::commit();

            return response()->json([
                'success' => true, 
                'message' => 'Transaksi berhasil!',
                'data' => ['invoice' => $invoiceNumber, 'total' => $grandTotal]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()], 500);
        }
    }

    // 3. API BARU UNTUK HALAMAN RIWAYAT KASIR
    public function getHistory(Request $request)
    {
        $user = Auth::user() ?? auth('sanctum')->user();
        if (!$user) return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);

        $userId = $user->id_pengguna ?? $user->id;

        // Ambil riwayat pesanan khusus POS milik kasir ini
        $history = Order::where('user_id', $userId)
                        // ->where('type', 'pos') // Hapus komentar ini jika di database ada kolom 'type'
                        ->orderBy('created_at', 'desc')
                        ->get();

        return response()->json([
            'success' => true,
            'data' => $history
        ]);
    }
}