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
    /**
     * 1. HALAMAN UTAMA: Daftar Produk untuk Dipilih
     * Mengatasi error "Undefined method index"
     */
    public function index()
    {
        // Ambil produk tipe 'good' (Barang Jadi) atau 'service' (Jasa)
        // Material tidak perlu dihitung karena itu bahan baku
        $products = Product::whereIn('type', ['good', 'service'])
                           ->orderBy('name', 'asc')
                           ->paginate(10);

        return view('hpp.index', compact('products'));
    }

    /**
     * 2. HALAMAN KALKULATOR: Detail Analisa per Produk
     */
    public function analysis($id)
    {
        // Ambil produk beserta resep yang sudah ada (jika ada)
        $product = Product::with('recipeItems')->findOrFail($id);

        // Ambil daftar bahan baku (Material & Barang Jadi lain) untuk dropdown
        $materials = Product::whereIn('type', ['material', 'good'])
                            ->orderBy('name', 'asc')
                            ->get();

        return view('products.analysis', compact('product', 'materials'));
    }

    /**
     * 3. PROSES SIMPAN: Menyimpan Resep HPP ke Database
     */
    public function updateRecipe(Request $request, $productId)
    {
        // Cek apakah user punya tenant_id (jika multi-tenant)
        $user = Auth::user();
        $tenantId = $user->tenant_id ?? 1; // Fallback ke 1 jika null

        DB::transaction(function() use ($request, $productId, $tenantId) {
            // 1. Hapus resep lama agar bersih (Reset)
            ProductRecipe::where('parent_product_id', $productId)->delete();

            // 2. Loop data items dari inputan Javascript/Form
            if ($request->has('items')) {
                foreach ($request->items as $item) {
                    ProductRecipe::create([
                        'tenant_id' => $tenantId,
                        'parent_product_id' => $productId,
                        'child_product_id' => $item['child_product_id'] ?? null, // ID Bahan Baku (Nullable)
                        'custom_item_name' => $item['custom_name'] ?? null, // Nama Biaya Custom (jika tidak pilih bahan)
                        'quantity' => $item['quantity'],
                        'cost_per_unit' => $item['cost'] ?? 0, // Harga manual/override
                    ]);
                }
            }

            // 3. Update base_price (HPP) di tabel produk utama agar laporan laba rugi akurat
            // Hitung ulang total HPP barusan
            $product = Product::find($productId);
            $product->base_price = $product->calculated_hpp; // Menggunakan Accessor di Model

            // Opsional: Update Harga Jual jika dikirim
            if ($request->filled('new_selling_price') && $request->new_selling_price > 0) {
                $product->sell_price = $request->new_selling_price;
            }

            $product->save();
        });

        return response()->json(['status' => 'success', 'message' => 'Resep & HPP Berhasil Diupdate']);
    }

    /**
     * 4. EKSEKUSI PRODUKSI (Opsional: Jika nanti dipakai untuk manufaktur stok)
     */
    public function manufacture(Request $request)
    {
        $request->validate([
            'product_id' => 'required',
            'qty' => 'required|numeric|min:1'
        ]);

        $product = Product::with('recipeItems.childProduct')->find($request->product_id);
        $qtyProduksi = $request->qty;
        $totalBiayaProduksi = 0;

        DB::transaction(function() use ($product, $qtyProduksi, &$totalBiayaProduksi) {

            // Loop Resep
            foreach ($product->recipeItems as $resep) {
                if ($resep->child_product_id) {
                    $qtyButuh = $resep->quantity * $qtyProduksi;

                    // Cek Stok Bahan
                    if ($resep->childProduct->stock < $qtyButuh) {
                        throw new \Exception("Stok bahan {$resep->childProduct->name} kurang! Butuh: $qtyButuh");
                    }

                    // KURANGI STOK BAHAN BAKU
                    $resep->childProduct->decrement('stock', $qtyButuh);

                    // Hitung HPP Real
                    $totalBiayaProduksi += ($resep->childProduct->base_price * $qtyButuh);
                } else {
                    // Biaya Jasa/Overhead
                    $totalBiayaProduksi += ($resep->cost_per_unit * $resep->quantity * $qtyProduksi);
                }
            }

            // TAMBAH STOK BARANG JADI
            $product->increment('stock', $qtyProduksi);

            // Update HPP Barang Jadi
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

        return response()->json(['status' => 'success', 'message' => 'Produksi Selesai!']);
    }
}
