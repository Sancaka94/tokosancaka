<?php

namespace App\Http\Controllers;

use App\Models\PpobProduct;
use Illuminate\Http\Request;

class PpobProductController extends Controller
{
    public function index()
    {
        // Mengambil semua data, diurutkan terbaru
        $products = PpobProduct::orderBy('category', 'asc')->orderBy('brand', 'asc')->get();
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