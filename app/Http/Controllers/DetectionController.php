<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Product; // Pastikan Model ada
use App\Models\Pesanan; // Pastikan Model ada

class DetectionController extends Controller
{
    public function process(Request $request)
    {
        $imageName = '';

        try {
            // ============================================================
            // 1. SETUP ENVIRONMENT & PATH (JANGAN DIUBAH)
            // ============================================================
            $userHome = '/home/tokq3391'; // Sesuaikan username hosting Anda
            $pythonPath = $userHome . '/virtualenv/my_ai_backend/3.9/bin/python';
            $scriptPath = base_path('detect.py');

            if (!file_exists($pythonPath)) throw new \Exception("Python env tidak ditemukan.");
            if (!file_exists($scriptPath)) throw new \Exception("Script detect.py tidak ditemukan.");

            // ============================================================
            // 2. PROSES GAMBAR
            // ============================================================
            $request->validate(['image' => 'required|string']);
            $image = $request->input('image');
            
            if (preg_match('/^data:image\/(\w+);base64,/', $image, $type)) {
                $image = substr($image, strpos($image, ',') + 1);
                $image = str_replace(' ', '+', $image);
                $decodedImage = base64_decode($image);
            } else {
                throw new \Exception('Format gambar invalid.');
            }

            $imageName = 'scan_' . uniqid() . '.png';
            if (!Storage::disk('local')->exists('temp')) {
                Storage::disk('local')->makeDirectory('temp');
            }
            Storage::disk('local')->put('temp/' . $imageName, $decodedImage);
            $imagePath = storage_path('app/temp/' . $imageName);

            // ============================================================
            // 3. EKSEKUSI PYTHON (SAFE MODE)
            // ============================================================
            $command = "export HOME={$userHome} && " .
                       "export OMP_NUM_THREADS=1 && " .
                       "{$pythonPath} {$scriptPath} " . escapeshellarg($imagePath) . " 2>&1";
            
            $output = shell_exec($command);
            Storage::disk('local')->delete('temp/' . $imageName); // Hapus gambar

            // ============================================================
            // 4. LOGIKA PINTAR: CEK PRODUK & PESANAN
            // ============================================================
            $result = json_decode($output);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception("Output Python Error: " . $output);
            }

            foreach ($result as $key => $item) {
                // Inisialisasi kosong
                $result[$key]->product_info = null;
                $result[$key]->order_info = null;

                // Hanya proses jika itu Barcode/QR Code
                if ($item->type === 'barcode' && !empty($item->text_content)) {
                    $code = trim($item->text_content);

                    // --- CEK 1: APAKAH INI RESI/PESANAN? ---
                    $pesanan = Pesanan::where('resi', $code)
                                      ->orWhere('nomor_invoice', $code)
                                      ->first();

                    if ($pesanan) {
                        $result[$key]->label = "📦 PAKET: " . strtoupper($pesanan->expedition);
                        $result[$key]->order_info = [
                            'found' => true,
                            'type'  => 'order',
                            'resi'  => $pesanan->resi,
                            'penerima' => $pesanan->receiver_name,
                            'status' => $pesanan->status_pesanan,
                            'ekspedisi' => strtoupper($pesanan->expedition),
                            'alamat' => substr($pesanan->receiver_address, 0, 50) . '...',
                            'tanggal' => date('d/m/Y', strtotime($pesanan->created_at))
                        ];
                        // Jika ketemu pesanan, lanjut ke item berikutnya (prioritas)
                        continue; 
                    }

                    // --- CEK 2: APAKAH INI PRODUK? ---
                    $product = Product::where('sku', $code)
                                      ->orWhere('id', $code)
                                      ->first();

                    if ($product) {
                        $result[$key]->label = substr($product->name, 0, 15) . '...';
                        $result[$key]->product_info = [
                            'found' => true,
                            'type'  => 'product',
                            'code'  => $code,
                            'name'  => $product->name,
                            'price' => number_format($product->price, 0, ',', '.'),
                            'raw_price' => $product->price
                        ];
                    } else {
                        // --- TIDAK KETEMU DI KEDUANYA ---
                        $result[$key]->label = "BARU: " . $code;
                        $result[$key]->product_info = [
                            'found' => false,
                            'type'  => 'new_product',
                            'code'  => $code // Siap untuk input barang baru
                        ];
                    }
                }
            }

            return response()->json([
                'status' => 'success',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            // Cleanup jika error
            if (!empty($imageName) && Storage::disk('local')->exists('temp/' . $imageName)) {
                Storage::disk('local')->delete('temp/' . $imageName);
            }
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}