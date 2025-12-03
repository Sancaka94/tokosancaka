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

    /**
     * FITUR BARU: Update Harga Massal
     */
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'profit_type' => 'required|in:rupiah,percent',
            'profit_value' => 'required|numeric|min:0',
        ]);

        $products = PpobProduct::all();
        $count = 0;

        foreach ($products as $product) {
            $basePrice = $product->price; // Harga beli dari pusat
            $margin = 0;

            if ($request->profit_type == 'rupiah') {
                $margin = $request->profit_value;
            } else {
                // Persentase
                $margin = $basePrice * ($request->profit_value / 100);
            }

            // Set harga jual baru (dibulatkan ke atas agar aman)
            $product->sell_price = ceil($basePrice + $margin);
            $product->save();
            $count++;
        }

        return redirect()->back()->with('success', "Berhasil memperbarui harga jual untuk $count produk.");
    }

    /**
     * FITUR BARU: Export ke Excel (CSV)
     * Tanpa perlu install library tambahan
     */
    public function exportExcel()
    {
        $fileName = 'pricelist_ppob_' . date('Y-m-d') . '.csv';
        $products = PpobProduct::orderBy('category', 'asc')->orderBy('brand', 'asc')->get();

        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $columns = array('Kategori', 'Brand', 'Kode SKU', 'Nama Produk', 'Harga Jual', 'Status');

        $callback = function() use($products, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($products as $product) {
                $row['Kategori']  = $product->category;
                $row['Brand']     = $product->brand;
                $row['Kode SKU']  = $product->buyer_sku_code;
                $row['Nama']      = $product->product_name;
                $row['Harga']     = $product->sell_price;
                $row['Status']    = $product->seller_product_status ? 'Aktif' : 'Nonaktif';

                fputcsv($file, array($row['Kategori'], $row['Brand'], $row['Kode SKU'], $row['Nama'], $row['Harga'], $row['Status']));
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * FITUR BARU: Export ke PDF (Print View Sederhana)
     * Menggunakan tampilan cetak browser agar rapi tanpa library berat
     */
    public function exportPdf()
    {
        $products = PpobProduct::where('seller_product_status', 1)
                    ->orderBy('category', 'asc')
                    ->orderBy('brand', 'asc')
                    ->get();
                    
        return view('admin.ppob.print_pricelist', compact('products'));
    }
}