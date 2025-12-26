<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process; 
use App\Models\Product;
use App\Models\Pesanan;

class DetectionController extends Controller
{
    public function process(Request $request)
    {
        $imageName = '';
        $lockFile = storage_path('app/temp/scan_lock');

        // --- 1. CEK ANTRIAN (THROTTLING) ---
        // Jika file lock ada dan usianya < 3 detik, tolak request ini.
        if (file_exists($lockFile) && (time() - filemtime($lockFile) < 3)) {
            return response()->json(['status' => 'busy', 'message' => 'Server sibuk, antri...'], 429);
        }
        // Buat/Update lock file
        touch($lockFile);

        try {
            // --- 2. CONFIG PATH ---
            $userHome = '/home/tokq3391'; 
            $pythonBin = $userHome . '/virtualenv/my_ai_backend/3.9/bin/python';
            $scriptPath = base_path('detect.py');

            // --- 3. SIMPAN GAMBAR ---
            $request->validate(['image' => 'required|string']);
            $image = $request->input('image');
            
            // Bersihkan header base64
            if (strpos($image, ',') !== false) {
                $image = substr($image, strpos($image, ',') + 1);
            }
            $image = str_replace(' ', '+', $image);
            
            $imageName = 'scan_' . uniqid() . '.jpg';
            // Pastikan folder temp ada
            if (!Storage::disk('local')->exists('temp')) {
                Storage::disk('local')->makeDirectory('temp');
            }
            Storage::disk('local')->put('temp/' . $imageName, base64_decode($image));
            $imagePath = storage_path('app/temp/' . $imageName);

            // --- 4. EKSEKUSI PYTHON (LOW MEMORY MODE) ---
            // Kita tanam ENV variables langsung di command line untuk memaksa 1 Core
            $cmd = "export OMP_NUM_THREADS=1 && ";
            $cmd .= "export OPENBLAS_NUM_THREADS=1 && ";
            $cmd .= "export MKL_NUM_THREADS=1 && ";
            $cmd .= "export HOME={$userHome} && ";
            $cmd .= "{$pythonBin} {$scriptPath} " . escapeshellarg($imagePath);

            $process = Process::fromShellCommandline($cmd);
            $process->setTimeout(30); // Timeout ketat 30 detik
            $process->run();

            // Bersihkan Lock File setelah selesai
            @unlink($lockFile);

            // --- 5. BACA OUTPUT ---
            $output = $process->getOutput();
            $errorOutput = $process->getErrorOutput();

            // Filter Output: Ambil hanya JSON yang valid (abaikan warning library)
            $jsonStart = strpos($output, '[');
            $jsonEnd = strrpos($output, ']');
            
            if ($jsonStart !== false && $jsonEnd !== false) {
                $cleanJson = substr($output, $jsonStart, ($jsonEnd - $jsonStart) + 1);
                $results = json_decode($cleanJson);
            } else {
                // Jika gagal, cek error output
                Log::error("AI Error Output: " . $errorOutput);
                // Return array kosong daripada error 500
                $results = [];
            }

            // Hapus gambar
            Storage::disk('local')->delete('temp/' . $imageName);

            // --- 6. INTELEGENSI DATABASE ---
            // Gabungkan hasil deteksi dengan data di Database (Produk/Resi)
            $finalData = [];
            if (is_array($results)) {
                foreach ($results as $item) {
                    $item->db_info = null;
                    $label = $item->label;

                    // A. Jika Barcode / Resi
                    if ($item->type === 'barcode') {
                        $pesanan = Pesanan::where('resi', $label)->orWhere('nomor_invoice', $label)->first();
                        if ($pesanan) {
                            $item->label = "📦 " . $pesanan->receiver_name;
                            $item->db_info = ['found'=>true, 'detail'=>$pesanan->status_pesanan];
                        } else {
                            // Cek Produk
                            $prod = Product::where('sku', $label)->first();
                            if ($prod) {
                                $item->label = $prod->name;
                                $item->db_info = ['found'=>true, 'detail'=>"Rp ".number_format($prod->price)];
                            } else {
                                $item->label = "BARU: " . $label;
                                $item->db_info = ['found'=>false];
                            }
                        }
                    }
                    // B. Jika Benda/Kendaraan/Hewan (Cek database berdasarkan nama label)
                    // Contoh: AI deteksi "car", user pernah simpan "car" sebagai "Plat AE 1234"
                    else {
                        $prod = Product::where('sku', $label) // Cek raw label (misal: 'car')
                                       ->orWhere('name', 'LIKE', "%{$label}%")
                                       ->first();
                        
                        if ($prod) {
                            $item->label = $prod->name; // Ganti label jadi nama yg disimpan user
                            $item->db_info = ['found'=>true, 'detail'=>"Rp ".number_format($prod->price)];
                        }
                    }

                    $finalData[] = $item;
                }
            }

            return response()->json(['status' => 'success', 'data' => $finalData]);

        } catch (\Exception $e) {
            // Bersihkan file jika error
            @unlink($lockFile);
            if (!empty($imageName)) Storage::disk('local')->delete('temp/' . $imageName);
            
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}