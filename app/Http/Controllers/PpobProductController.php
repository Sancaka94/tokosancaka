<?php

namespace App\Http\Controllers;

use App\Models\PpobProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\DigiflazzService;
use Exception;

class PpobProductController extends Controller
{
    // 1. Deklarasi Properti
    protected DigiflazzService $ppobService;

    // 2. Konstruktor Dependency Injection
    public function __construct(DigiflazzService $ppobService)
    {
        $this->ppobService = $ppobService;
    }

    /**
     * Helper function: Filter query berdasarkan tipe dan pencarian.
     */
    private function getFilteredProductQuery(Request $request)
    {
        $search = $request->input('q');
        $type = $request->input('type', 'prepaid'); // Default 'prepaid'

        $query = PpobProduct::query();

        // Filter Type
        if ($type === 'prepaid') {
            // Tampilkan yang BUKAN Pascabayar (Pulsa, Data, dll)
            $query->where('category', '!=', 'Pascabayar');
        } else {
            // Tampilkan HANYA Pascabayar (PLN Tagihan, PDAM, dll)
            $query->where('category', 'Pascabayar');
        }
        
        // Logika Pencarian
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('product_name', 'like', "%{$search}%")
                  ->orWhere('buyer_sku_code', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%");
            });
        }
        
        return $query;
    }

    /**
     * Menampilkan halaman index produk
     */
    public function index(Request $request)
    {
        $products = $this->getFilteredProductQuery($request)
                        ->orderBy('brand', 'asc') // Urutkan Brand dulu biar rapi
                        ->orderBy('category', 'asc')
                        ->orderBy('price', 'asc')
                        ->paginate(20); // Tampilkan 20 per halaman
                          
        $products->appends($request->only(['q', 'type']));

        return view('admin.ppob.index', compact('products'));
    }

    /**
     * Update Harga Satuan (Dari Modal Edit)
     */
    public function updatePrice(Request $request, $id)
    {
        // 1. Validasi Input
        $request->validate([
            'sell_price' => 'required|numeric|min:0',
            'max_buy_price' => 'nullable|numeric|min:0',
        ]);

        // 2. Simpan Data
        $product = PpobProduct::findOrFail($id);
        $product->sell_price = $request->sell_price;
        
        // --- FIX IS HERE ---
        // Change this line:
        // $product->max_buy_price = $request->input('max_buy_price', 0);
        
        // To this (using null coalescing operator):
        $product->max_buy_price = $request->input('max_buy_price') ?? 0;
        
        // Status Produk
        $product->seller_product_status = $request->boolean('status'); 
        
        $product->save();

        return redirect()->back()->with('success', 'Data produk berhasil diperbarui!');
    }

    /**
     * Hapus Produk
     */
    public function destroy($id)
    {
        $product = PpobProduct::findOrFail($id);
        $product->delete();

        return redirect()->back()->with('success', 'Produk berhasil dihapus dari database lokal.');
    }
    
    /**
     * API Show (Untuk keperluan fetch detail jika dibutuhkan)
     */
    public function show($id) {
        $product = PpobProduct::findOrFail($id);
        return response()->json($product);
    }

    /**
     * FITUR: Update Harga Massal
     */
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'profit_type' => 'required|in:rupiah,percent',
            'profit_value' => 'required|numeric|min:0',
            'product_type' => 'required|in:prepaid,postpaid',
        ]);

        // Ambil semua produk sesuai filter saat ini
        $products = $this->getFilteredProductQuery($request)->get();
        $count = 0;

        DB::beginTransaction();
        try {
            foreach ($products as $product) {
                $basePrice = $product->price; 
                $margin = 0;

                if ($request->profit_type == 'rupiah') {
                    $margin = $request->profit_value;
                } else {
                    $margin = $basePrice * ($request->profit_value / 100);
                }

                $product->sell_price = ceil($basePrice + $margin);
                $product->save();
                $count++;
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal update massal: ' . $e->getMessage());
        }

        return redirect()->back()->with('success', "Berhasil memperbarui harga jual untuk $count produk.");
    }

    /**
     * FITUR: Export ke Excel (CSV Stream)
     */
    public function exportExcel(Request $request)
    {
        $type = $request->input('type', 'prepaid');
        $fileName = 'pricelist_ppob_' . $type . '_' . date('Y-m-d') . '.csv';
        
        $products = $this->getFilteredProductQuery($request)
                        ->orderBy('category', 'asc')
                        ->orderBy('brand', 'asc')
                        ->get();

        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $columns = array('Kategori', 'Brand', 'Kode SKU', 'Nama Produk', 'Harga Modal', 'Harga Jual', 'Status', 'Max Price Limit');

        $callback = function() use($products, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($products as $product) {
                $row = [
                    $product->category,
                    $product->brand,
                    $product->buyer_sku_code,
                    $product->product_name,
                    $product->price,
                    $product->sell_price,
                    $product->seller_product_status ? 'Aktif' : 'Nonaktif',
                    $product->max_buy_price // Tambahkan kolom export
                ];

                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * FITUR: Export ke PDF (View Print)
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

    // --- SYNC METHOD ---

    public function syncPrepaid()
    {
        try {
            $success = $this->ppobService->syncPrepaidProducts();
            if ($success) {
                return redirect()->back()->with('success', 'Sinkronisasi Prabayar Berhasil!');
            }
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Error Sync: ' . $e->getMessage());
        }
        return redirect()->back()->with('error', 'Gagal melakukan sinkronisasi Prabayar.');
    }

    public function syncPostpaid()
    {
        try {
            $success = $this->ppobService->syncPostpaidProducts();
            if ($success) {
                return redirect()->back()->with('success', 'Sinkronisasi Pascabayar Berhasil!');
            }
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Error Sync: ' . $e->getMessage());
        }
        return redirect()->back()->with('error', 'Gagal melakukan sinkronisasi Pascabayar.');
    }
}