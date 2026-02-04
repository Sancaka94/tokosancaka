<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Events\BarcodeScanned;
use App\Models\Product;         // Import Model Produk
use App\Models\ProductVariant;  // Import Model Varian
use Illuminate\Support\Facades\Log;

class ScannerController extends Controller
{
    /**
     * Menampilkan halaman scanner di HP.
     */
    public function index()
    {
        return view('mobile-scanner');
    }

    /**
     * Menangani data scan dari HP dan mem-broadcast ke Laptop.
     */
    public function handleScan(Request $request)
    {
        // 1. LOG START
        Log::info("LOG SERVER: [BROADCAST REQUEST] Menerima data final dari HP.");
        Log::info("LOG SERVER: Data: ", $request->all());

        // 2. Validasi Input
        $request->validate([
            'barcode' => 'required|string',
            'qty'     => 'required|numeric|min:0.01' // Support desimal
        ]);

        $barcode = $request->barcode;
        $qty     = $request->qty;
        $imageUrl = null;

        // 3. LOGIKA CARI GAMBAR (Agar Laptop tidak 404)
        try {
            // Cek di Produk Utama
            $product = Product::where('barcode', $barcode)
                        ->orWhere('sku', $barcode)
                        ->first();

            if ($product && $product->image) {
                $imageUrl = asset('storage/' . $product->image);
            }
            else {
                // Jika tidak ketemu, Cek di Varian
                $variant = ProductVariant::with('product')
                            ->where('barcode', $barcode)
                            ->orWhere('sku', $barcode)
                            ->first();

                // Ambil gambar dari parent product jika varian ketemu
                if ($variant && $variant->product && $variant->product->image) {
                    $imageUrl = asset('storage/' . $variant->product->image);
                }
            }

            Log::info("LOG SERVER: Image URL resolved: " . ($imageUrl ?? 'NULL'));

        } catch (\Exception $e) {
            Log::error("LOG SERVER: Gagal resolve gambar: " . $e->getMessage());
            // Lanjut saja meski gambar gagal, jangan stop proses
        }

        // 4. KIRIM BROADCAST (PUSHER)
        try {
            // Parameter: (Barcode, Qty, ImageUrl)
            // Pastikan file App/Events/BarcodeScanned.php konstruktornya menerima 3 parameter ini!
            broadcast(new \App\Events\BarcodeScanned($barcode, $qty, $imageUrl))->toOthers();

            Log::info("LOG SERVER: [SUCCESS] Sinyal Broadcast Terkirim ke Laptop!");

            return response()->json([
                'status'  => 'success',
                'message' => 'Berhasil masuk ke Laptop!'
            ]);

        } catch (\Exception $e) {
            Log::error("LOG SERVER: [ERROR] Gagal Broadcast: " . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal koneksi realtime: ' . $e->getMessage()
            ], 500);
        }
    }
}
