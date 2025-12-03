<?php

namespace App\Http\Controllers;

use App\Models\PpobProduct;
use Illuminate\Http\Request;

class PpobProductController extends Controller
{
    public function index(Request $request)
    {
        // Ambil query pencarian jika ada
        $search = $request->input('q');

        $query = PpobProduct::query();

        if ($search) {
            $query->where('product_name', 'like', "%{$search}%")
                  ->orWhere('buyer_sku_code', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%");
        }

        // Gunakan paginate(10) untuk membatasi 10 data per halaman
        $products = $query->orderBy('category', 'asc')
                          ->orderBy('brand', 'asc')
                          ->paginate(10); 
                          
        // Pastikan parameter pencarian tetap ada saat pindah halaman
        $products->appends(['q' => $search]);

        return view('admin.ppob.index', compact('products'));
    }

    public function updatePrice(Request $request, $id)
    {
        $request->validate([
            'sell_price' => 'required|numeric|min:0',
        ]);

        $product = PpobProduct::findOrFail($id);
        $product->sell_price = $request->sell_price;
        // Opsional: Update status jual jika harga diubah
        $product->seller_product_status = $request->has('status') ? 1 : 0;
        $product->save();

        return redirect()->back()->with('success', 'Harga jual berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $product = PpobProduct::findOrFail($id);
        $product->delete();

        return redirect()->back()->with('success', 'Produk berhasil dihapus.');
    }
    
    // Method show dan edit bisa disesuaikan jika ingin halaman terpisah
    public function show($id) {
        $product = PpobProduct::findOrFail($id);
        return response()->json($product); // Untuk modal detail (Ajax)
    }
}