<?php

namespace App\Http\Controllers;

use App\Models\PpobProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\DigiflazzService; // PENTING: Import Service
use Exception;

class PpobProductController extends Controller
{

    // 1. Deklarasi Properti (harus ada)
    protected DigiflazzService $ppobService;

    // 2. Konstruktor untuk Dependency Injection (harus ada)
    public function __construct(DigiflazzService $ppobService)
    {
        // 3. Inisialisasi Properti
        $this->ppobService = $ppobService;
    }
    /**
     * Helper function untuk mendapatkan query yang sudah difilter berdasarkan tipe dan pencarian.
     */
    private function getFilteredProductQuery(Request $request)
    {
        $search = $request->input('q');
        $type = $request->input('type', 'prepaid'); // Ambil type, default 'prepaid'

        $query = PpobProduct::query();

       if ($type === 'prepaid') {
    // Tampilkan yang BUKAN Pascabayar (yaitu Pulsa, Data, Games, dll)
    $query->where('category', '!=', 'Pascabayar'); 
} else { // 'postpaid'
    // Tampilkan HANYA yang berkategori Pascabayar (yaitu PLN Tagihan, PDAM, dll)
    $query->where('category', 'Pascabayar'); 
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
        $product->seller_product_status = $request->boolean('status'); 
        $product->save();

        return redirect()->back()->with('success', 'Harga jual berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $product = PpobProduct::findOrFail($id);
        $product->delete();

        return redirect()->back()->with('success', 'Produk berhasil dihapus.');
    }
    
    public function show($id) {
        $product = PpobProduct::findOrFail($id);
        return response()->json($product);
    }

    /**
     * FITUR BARU: Update Harga Massal
     */
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'profit_type' => 'required|in:rupiah,percent',
            'profit_value' => 'required|numeric|min:0',
            'product_type' => 'required|in:prepaid,postpaid',
        ]);

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
            return redirect()->back()->with('error', 'Gagal memperbarui harga massal: ' . $e->getMessage());
        }

        return redirect()->back()->with('success', "Berhasil memperbarui harga jual untuk $count produk.");
    }

    /**
     * FITUR BARU: Export ke Excel (CSV)
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
            "Content-type"          => "text/csv",
            "Content-Disposition"   => "attachment; filename=$fileName",
            "Pragma"                => "no-cache",
            "Cache-Control"         => "must-revalidate, post-check=0, pre-check=0",
            "Expires"               => "0"
        );

        $columns = array('Kategori', 'Brand', 'Kode SKU', 'Nama Produk', 'Harga Modal', 'Harga Jual', 'Status');

        $callback = function() use($products, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($products as $product) {
                // ⭐ KODE DIBERSIHKAN: Baris ini (dan semua baris di dalamnya) sekarang bersih dari karakter aneh.
                $row['Kategori']    = $product->category;
                $row['Brand']       = $product->brand;
                $row['Kode SKU']    = $product->buyer_sku_code;
                $row['Nama']        = $product->product_name;
                $row['Harga Modal'] = $product->price;
                $row['Harga Jual']  = $product->sell_price;
                $row['Status']      = $product->seller_product_status ? 'Aktif' : 'Nonaktif';

                fputcsv($file, array($row['Kategori'], $row['Brand'], $row['Kode SKU'], $row['Nama'], $row['Harga Modal'], $row['Harga Jual'], $row['Status']));
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * FITUR BARU: Export ke PDF (Print View Sederhana)
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

   // --- FITUR SINKRONISASI TERPISAH (FINAL) ---

    /**
     * Menangani permintaan sinkronisasi HANYA untuk produk PRABAYAR.
     */
    public function syncPrepaid(Request $request)
    {
        $success = $this->ppobService->syncPrepaidProducts();

        if ($success) {
            return redirect()->back()->with('success', 'Sinkronisasi produk **Prabayar** berhasil dilakukan! Perubahan harga mungkin membutuhkan beberapa saat.');
        }

        // Ketika sync gagal, cek pesan dari cache/log atau berikan pesan standar.
        return redirect()->back()->with('error', 'Gagal melakukan sinkronisasi Prabayar. Cek log atau coba lagi setelah 6 menit.');
    }

    /**
     * Menangani permintaan sinkronisasi HANYA untuk produk PASCABAYAR.
     */
    public function syncPostpaid(Request $request)
    {
        $success = $this->ppobService->syncPostpaidProducts();

        if ($success) {
            return redirect()->back()->with('success', 'Sinkronisasi produk **Pascabayar** berhasil dilakukan! Perubahan harga mungkin membutuhkan beberapa saat.');
        }
        
        // Ketika sync gagal
        return redirect()->back()->with('error', 'Gagal melakukan sinkronisasi Pascabayar. Cek log atau coba lagi setelah 6 menit.');
    }
}