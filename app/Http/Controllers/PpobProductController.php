<?php

namespace App\Http\Controllers;

use App\Models\PpobProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Tambahkan DB Facade untuk bulkUpdate

class PpobProductController extends Controller
{
    /**
     * Helper function untuk mendapatkan query yang sudah difilter berdasarkan tipe dan pencarian.
     */
    private function getFilteredProductQuery(Request $request)
    {
        $search = $request->input('q');
        $type = $request->input('type', 'prepaid'); // Ambil type, default 'prepaid'

        $query = PpobProduct::query();

        // 1. Logika Filtering Berdasarkan Tipe Produk
        if ($type === 'prepaid') {
            // Filter: Bukan kategori 'Pascabayar' ATAU punya kolom 'type' terisi.
            $query->where(function ($q) {
                $q->where('category', '!=', 'Pascabayar')
                  ->orWhereNotNull('type');
            });
        } else { // 'postpaid'
            // Filter: Kategori 'Pascabayar' ATAU kolom 'type' kosong.
            $query->where(function ($q) {
                $q->where('category', 'Pascabayar')
                  ->orWhereNull('type');
            });
        }
        
        // 2. Logika Pencarian
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('product_name', 'like', "%{$search}%")
                  ->orWhere('buyer_sku_code', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%");
            });
        }
        
        return $query;
    }

    public function index(Request $request)
    {
        $products = $this->getFilteredProductQuery($request)
                        ->orderBy('category', 'asc')
                        ->orderBy('brand', 'asc')
                        ->paginate(10); 
                         
        // Pastikan parameter pencarian dan tipe tetap ada saat pindah halaman
        $products->appends($request->only(['q', 'type']));

        return view('admin.ppob.index', compact('products'));
    }

    public function updatePrice(Request $request, $id)
    {
        $request->validate([
            'sell_price' => 'required|numeric|min:0',
        ]);

        $product = PpobProduct::findOrFail($id);
        $product->sell_price = $request->sell_price;
        $product->seller_product_status = $request->has('status') ? 1 : 0;
        $product->save();

        // Pertahankan parameter 'type' saat redirect
        return redirect()->back()->with('success', 'Harga jual berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $product = PpobProduct::findOrFail($id);
        $product->delete();

        // Pertahankan parameter 'type' saat redirect
        return redirect()->back()->with('success', 'Produk berhasil dihapus.');
    }
    
    public function show($id) {
        $product = PpobProduct::findOrFail($id);
        return response()->json($product);
    }

    /**
     * FITUR BARU: Update Harga Massal
     * Diperbaiki agar hanya mengupdate produk yang sedang aktif (prepaid/postpaid).
     */
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'profit_type' => 'required|in:rupiah,percent',
            'profit_value' => 'required|numeric|min:0',
            'product_type' => 'required|in:prepaid,postpaid', // Wajib ada dari hidden input di Blade
        ]);

        // Ambil query produk HANYA berdasarkan tipe yang dipilih
        $products = $this->getFilteredProductQuery($request)->get();
        $count = 0;

        DB::beginTransaction(); // Mulai transaksi DB
        try {
            foreach ($products as $product) {
                $basePrice = $product->price; // Harga beli (modal/admin)
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
            DB::commit(); // Commit jika semua berhasil
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback jika ada error
            return redirect()->back()->with('error', 'Gagal memperbarui harga massal: ' . $e->getMessage());
        }

        return redirect()->back()->with('success', "Berhasil memperbarui harga jual untuk $count produk.");
    }

    /**
     * FITUR BARU: Export ke Excel (CSV)
     * Diperbaiki agar hanya mengekspor produk yang sedang aktif (prepaid/postpaid).
     */
    public function exportExcel(Request $request)
    {
        $type = $request->input('type', 'prepaid');
        $fileName = 'pricelist_ppob_' . $type . '_' . date('Y-m-d') . '.csv';
        
        // Ambil data HANYA berdasarkan filter tipe
        $products = $this->getFilteredProductQuery($request)
                        ->orderBy('category', 'asc')
                        ->orderBy('brand', 'asc')
                        ->get();

        $headers = array(
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        );

        $columns = array('Kategori', 'Brand', 'Kode SKU', 'Nama Produk', 'Harga Modal', 'Harga Jual', 'Status');

        $callback = function() use($products, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($products as $product) {
                $row['Kategori']  = $product->category;
                $row['Brand']     = $product->brand;
                $row['Kode SKU']  = $product->buyer_sku_code;
                $row['Nama']      = $product->product_name;
                $row['Harga Modal'] = $product->price; // Harga Beli/Admin
                $row['Harga Jual'] = $product->sell_price;
                $row['Status']    = $product->seller_product_status ? 'Aktif' : 'Nonaktif';

                fputcsv($file, array($row['Kategori'], $row['Brand'], $row['Kode SKU'], $row['Nama'], $row['Harga Modal'], $row['Harga Jual'], $row['Status']));
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * FITUR BARU: Export ke PDF (Print View Sederhana)
     * Diperbaiki agar hanya mengekspor produk yang sedang aktif (prepaid/postpaid).
     */
    public function exportPdf(Request $request)
    {
        $products = $this->getFilteredProductQuery($request)
                        ->where('seller_product_status', 1)
                        ->orderBy('category', 'asc')
                        ->orderBy('brand', 'asc')
                        ->get();
                    
        return view('admin.ppob.print_pricelist', compact('products'));
    }
}