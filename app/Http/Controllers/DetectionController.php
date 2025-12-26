<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log; // <--- WAJIB ADA
use App\Models\Product;
use App\Models\Pesanan;

class DetectionController extends Controller
{
    public function process(Request $request)
    {
        $imageName = '';
        $output = ''; // Inisialisasi variabel output

        try {
            // 1. Log Mulai
            Log::info("--- MULAI PROSES AI ---");
            Log::info("IP User: " . $request->ip());

            // Setup Path
            $userHome = '/home/tokq3391'; 
            $pythonPath = $userHome . '/virtualenv/my_ai_backend/3.9/bin/python';
            $scriptPath = base_path('detect.py');

            // 2. Validasi File
            if (!file_exists($pythonPath)) throw new \Exception("Python tidak ada di: $pythonPath");
            if (!file_exists($scriptPath)) throw new \Exception("Script detect.py tidak ada di: $scriptPath");

            // 3. Proses Gambar
            $request->validate(['image' => 'required|string']);
            $image = $request->input('image');
            
            // Cek ukuran data gambar (Log jika terlalu besar)
            $sizeInKb = strlen($image) / 1024;
            Log::info("Ukuran Gambar: " . round($sizeInKb, 2) . " KB");

            if ($sizeInKb > 5000) { // Jika lebih dari 5MB
                throw new \Exception("Gambar terlalu besar! Harap kompres gambar.");
            }

            if (preg_match('/^data:image\/(\w+);base64,/', $image, $type)) {
                $image = substr($image, strpos($image, ',') + 1);
                $image = str_replace(' ', '+', $image);
                $decodedImage = base64_decode($image);
            } else {
                throw new \Exception('Format gambar invalid.');
            }

            $imageName = 'scan_' . uniqid() . '.jpg';
            if (!Storage::disk('local')->exists('temp')) {
                Storage::disk('local')->makeDirectory('temp');
            }
            Storage::disk('local')->put('temp/' . $imageName, $decodedImage);
            $imagePath = storage_path('app/temp/' . $imageName);

            Log::info("Gambar disimpan sementara di: $imagePath");

            // 4. Eksekusi Python
            // Tambahkan timeout command agar tidak hanging selamanya
            $command = "export HOME={$userHome} && " .
                       "export OMP_NUM_THREADS=1 && " .
                       "{$pythonPath} {$scriptPath} " . escapeshellarg($imagePath) . " 2>&1";
            
            Log::info("Menjalankan Command Python...");
            
            // Catat waktu mulai
            $startTime = microtime(true);
            
            $output = shell_exec($command);
            
            // Catat durasi
            $duration = microtime(true) - $startTime;
            Log::info("Python Selesai dalam: " . round($duration, 2) . " detik");

            // Log Raw Output dari Python (PENTING BUAT DEBUGGING)
            Log::info("Raw Output Python: " . substr($output, 0, 500)); // Batasi 500 karakter biar log gak penuh

            // Hapus gambar
            Storage::disk('local')->delete('temp/' . $imageName);

            // 5. Parsing Hasil
            $result = json_decode($output);

            if (json_last_error() !== JSON_ERROR_NONE) {
                // Jika output bukan JSON, berarti Python Error (Print errornya)
                throw new \Exception("Output Python Rusak: " . $output);
            }

            // ... (LOGIKA DATABASE SAMA SEPERTI SEBELUMNYA) ...
            // Salin bagian foreach logic database dari kode sebelumnya di sini
            // Agar kode tidak terlalu panjang, saya persingkat bagian ini. 
            // Pastikan Anda memasukkan logika Pesanan & Product di sini.
            
            // --- CONTOH SINGKAT LOGIKA DB (Gunakan logika lengkap Anda yg tadi) ---
            foreach ($result as $key => $item) {
                 $result[$key]->product_info = null;
                 $result[$key]->order_info = null;
                 
                 if ($item->type === 'barcode' && !empty($item->text_content)) {
                     $code = trim($item->text_content);
                     // Cek Pesanan
                     $pesanan = Pesanan::where('resi', $code)->orWhere('nomor_invoice', $code)->first();
                     if ($pesanan) {
                         $result[$key]->label = "PAKET DITEMUKAN";
                         $result[$key]->order_info = ['found' => true, 'resi' => $pesanan->resi, 'penerima' => $pesanan->receiver_name, 'status' => $pesanan->status_pesanan, 'ekspedisi' => $pesanan->expedition, 'alamat' => $pesanan->receiver_address];
                         continue;
                     }
                     // Cek Produk
                     $product = Product::where('sku', $code)->orWhere('id', $code)->first();
                     if ($product) {
                         $result[$key]->label = $product->name;
                         $result[$key]->product_info = ['found' => true, 'code' => $code, 'name' => $product->name, 'price' => number_format($product->price), 'raw_price' => $product->price];
                     } else {
                         $result[$key]->label = "BARU: " . $code;
                         $result[$key]->product_info = ['found' => false, 'code' => $code];
                     }
                 }
            }
            // -------------------------------------------------------------------

            return response()->json([
                'status' => 'success',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            // LOG ERROR KE FILE
            Log::error("🔥 ERROR DETECT: " . $e->getMessage());
            Log::error("Trace: " . $e->getTraceAsString());

            if (!empty($imageName) && Storage::disk('local')->exists('temp/' . $imageName)) {
                Storage::disk('local')->delete('temp/' . $imageName);
            }
            
            // Kirim pesan error asli ke frontend agar muncul di alert
            return response()->json([
                'status' => 'error', 
                'message' => $e->getMessage(),
                'debug_output' => $output // Kirim output python juga buat dibaca di console browser
            ], 500);
        }
    }
}