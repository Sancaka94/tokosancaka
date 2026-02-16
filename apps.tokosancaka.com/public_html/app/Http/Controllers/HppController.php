<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductRecipe;
use App\Models\ProductionHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class HppController extends Controller
{
    // 1. Simpan Resep HPP (Untuk Jasa / Manufaktur)
    // Contoh: Input Resep "Cuci Komplit" = Deterjen 50ml + Parfum 10ml + Tenaga 2000
    public function updateRecipe(Request $request, $productId)
    {
        $tenantId = Auth::user()->tenant_id;

        DB::transaction(function() use ($request, $productId, $tenantId) {
            // Hapus resep lama (Reset)
            ProductRecipe::where('parent_product_id', $productId)->delete();

            // Loop input dari form (Vue/Blade/Livewire)
            // Format input: items[0][child_id], items[0][qty], items[0][cost]
            foreach ($request->items as $item) {
                ProductRecipe::create([
                    'tenant_id' => $tenantId,
                    'parent_product_id' => $productId,
                    'child_product_id' => $item['child_product_id'] ?? null, // ID Bahan Baku (Nullable)
                    'custom_item_name' => $item['custom_name'] ?? null, // Nama Biaya (jika null child_id)
                    'quantity' => $item['quantity'],
                    'cost_per_unit' => $item['cost'] ?? 0, // Harga manual jika bukan ambil dari stok
                ]);
            }

            // Update base_price produk utama dengan HPP terbaru
            $product = Product::find($productId);
            $product->base_price = $product->calculated_hpp;
            $product->save();
        });

        return response()->json(['status' => 'success', 'message' => 'HPP Berhasil Diupdate']);
    }

    // 2. Eksekusi Produksi (Khusus Manufaktur)
    // Contoh: Ubah stok 'Kain' & 'Benang' menjadi stok 'Baju'
    public function manufacture(Request $request)
    {
        $request->validate(['product_id' => 'required', 'qty' => 'required|numeric|min:1']);

        $product = Product::with('recipeItems.childProduct')->find($request->product_id);
        $qtyProduksi = $request->qty;
        $totalBiayaProduksi = 0;

        DB::transaction(function() use ($product, $qtyProduksi, &$totalBiayaProduksi) {

            // Loop Resep untuk kurangi bahan baku
            foreach ($product->recipeItems as $resep) {
                if ($resep->child_product_id) {
                    $qtyButuh = $resep->quantity * $qtyProduksi;

                    // Cek Stok Bahan
                    if ($resep->childProduct->stock < $qtyButuh) {
                        throw new \Exception("Stok bahan {$resep->childProduct->name} kurang! Butuh: $qtyButuh");
                    }

                    // KURANGI STOK BAHAN BAKU
                    $resep->childProduct->decrement('stock', $qtyButuh);

                    // Hitung HPP Real (Berdasarkan harga bahan saat ini)
                    $totalBiayaProduksi += ($resep->childProduct->base_price * $qtyButuh);
                } else {
                    // Biaya Jasa/Overhead (Uang keluar/beban)
                    $totalBiayaProduksi += ($resep->cost_per_unit * $resep->quantity * $qtyProduksi);
                }
            }

            // TAMBAH STOK BARANG JADI
            $product->increment('stock', $qtyProduksi);

            // Update HPP Barang Jadi (Moving Average sederhana)
            $unitCost = $totalBiayaProduksi / $qtyProduksi;
            $product->update(['base_price' => $unitCost]);

            // Catat Log
            ProductionHistory::create([
                'tenant_id' => $product->tenant_id,
                'product_id' => $product->id,
                'qty_produced' => $qtyProduksi,
                'total_cost' => $totalBiayaProduksi,
                'unit_cost' => $unitCost,
                'created_by' => Auth::id()
            ]);
        });

        return response()->json(['status' => 'success', 'message' => 'Produksi Selesai! Stok Bertambah.']);
    }

    public function analysis($id)
{
    $product = Product::with('recipeItems')->findOrFail($id);
    // Ambil semua produk tipe 'material' atau 'good' untuk dijadikan bahan baku
    $materials = Product::whereIn('type', ['material', 'good'])->get();

    return view('products.analysis', compact('product', 'materials'));
}

}
