<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process; 
use Symfony\Component\Process\Exception\ProcessFailedException;
use App\Models\Product;
use App\Models\Pesanan;

class DetectionController extends Controller
{
    public function process(Request $request)
    {
        $imageName = '';
        
        try {
            // ============================================================
            // 1. KONFIGURASI PATH (JANGAN DIUBAH KECUALI USERNAME BEDA)
            // ============================================================
            $userHome = '/home/tokq3391'; 
            $pythonBin = $userHome . '/virtualenv/my_ai_backend/3.9/bin/python';
            $scriptPath = base_path('detect.py');

            // ============================================================
            // 2. SIMPAN GAMBAR DARI BASE64
            // ============================================================
            $request->validate(['image' => 'required|string']);
            $image = $request->input('image');

            if (preg_match('/^data:image\/(\w+);base64,/', $image, $type)) {
                $image = substr($image, strpos($image, ',') + 1);
                $image = str_replace(' ', '+', $image);
                $decodedImage = base64_decode($image);
            } else {
                throw new \Exception('Format gambar tidak valid.');
            }

            // Simpan file sementara
            $imageName = 'scan_' . uniqid() . '.jpg';
            if (!Storage::disk('local')->exists('temp')) {
                Storage::disk('local')->makeDirectory('temp');
            }
            Storage::disk('local')->put('temp/' . $imageName, $decodedImage);
            $imagePath = storage_path('app/temp/' . $imageName);

            // ============================================================
            // 3. JALANKAN PYTHON (MENGGUNAKAN SYMFONY PROCESS)
            // ============================================================
            // Ini lebih stabil daripada shell_exec untuk Shared Hosting
            
            $process = new Process([
                $pythonBin, 
                $scriptPath, 
                $imagePath
            ]);

            // Set Environment Variables agar Python tidak bingung
            $process->setEnv([
                'HOME' => $userHome,
                'OMP_NUM_THREADS' => '1', // Batasi CPU agar tidak crash
                'MPLCONFIGDIR' => $userHome . '/tmp',
                'YOLO_CONFIG_DIR' => $userHome . '/tmp'
            ]);

            $process->setTimeout(120); // Batas waktu 2 menit
            $process->run();

            // Cek jika gagal
            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            $output = $process->getOutput();
            
            // Bersihkan output jika ada teks sampah sebelum JSON
            // (Kadang hosting nambahin warning di output)
            $jsonStart = strpos($output, '[');
            $jsonEnd = strrpos($output, ']');
            if ($jsonStart !== false && $jsonEnd !== false) {
                $output = substr($output, $jsonStart, ($jsonEnd - $jsonStart) + 1);
            }

            // Hapus gambar setelah diproses
            Storage::disk('local')->delete('temp/' . $imageName);

            // ============================================================
            // 4. LOGIKA PINTAR (DATABASE INTEGRATION)
            // ============================================================
            $result = json_decode($output);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error("Output Python Rusak: " . $process->getOutput());
                throw new \Exception("Gagal membaca respon AI.");
            }

            foreach ($result as $key => $item) {
                // Default value
                $result[$key]->product_info = null;
                $result[$key]->order_info = null;

                // A. JIKA BARCODE / TEKS TERDETEKSI
                if (($item->type === 'barcode') && !empty($item->label_raw)) {
                    $code = trim($item->label_raw);

                    // 1. Cek Tabel PESANAN (Prioritas Utama)
                    $pesanan = Pesanan::where('resi', $code)
                                      ->orWhere('nomor_invoice', $code)
                                      ->first();

                    if ($pesanan) {
                        $result[$key]->display_label = "📦 " . strtoupper($pesanan->expedition);
                        $result[$key]->is_known = true;
                        $result[$key]->order_info = [
                            'found' => true,
                            'resi' => $pesanan->resi,
                            'penerima' => $pesanan->receiver_name,
                            'ekspedisi' => strtoupper($pesanan->expedition),
                            'status' => $pesanan->status_pesanan,
                            'alamat' => $pesanan->receiver_address
                        ];
                        continue; // Lanjut ke item berikutnya
                    }

                    // 2. Cek Tabel PRODUK (Jika bukan pesanan)
                    $product = Product::where('sku', $code)
                                      ->orWhere('name', 'LIKE', "%{$code}%")
                                      ->first();

                    if ($product) {
                        $result[$key]->display_label = $product->name;
                        $result[$key]->is_known = true;
                        $result[$key]->product_info = [
                            'found' => true,
                            'code' => $code,
                            'name' => $product->name,
                            'price' => number_format($product->price, 0, ',', '.')
                        ];
                    } else {
                        // 3. BARANG BARU (Tidak ada di DB)
                        $result[$key]->display_label = "BARU: " . $code;
                        $result[$key]->is_known = false; // Trigger kotak merah di frontend
                        $result[$key]->product_info = [
                            'found' => false,
                            'code' => $code
                        ];
                    }
                }
                
                // B. JIKA BENDA UMUM (Cek Database berdasarkan nama label)
                // Misal: AI deteksi 'car', cek di DB apakah ada produk bernama 'car'
                elseif ($item->type === 'benda' || $item->type === 'kendaraan') {
                    $search = $item->label_raw;
                    $product = Product::where('name', 'LIKE', "%{$search}%")
                                      ->orWhere('sku', $search)
                                      ->first();
                    
                    if ($product) {
                        $result[$key]->display_label = $product->name;
                        $result[$key]->is_known = true;
                        $result[$key]->product_info = [
                            'found' => true,
                            'name' => $product->name,
                            'price' => number_format($product->price, 0, ',', '.')
                        ];
                    }
                }
            }

            return response()->json([
                'status' => 'success',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            // Log Error Lengkap
            Log::error("AI Error: " . $e->getMessage());
            
            // Hapus gambar jika error
            if (!empty($imageName) && Storage::disk('local')->exists('temp/' . $imageName)) {
                Storage::disk('local')->delete('temp/' . $imageName);
            }

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}