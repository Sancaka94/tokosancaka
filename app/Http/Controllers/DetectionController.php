<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DetectionController extends Controller
{
    public function process(Request $request)
    {
        $imageName = '';

        try {
            // ============================================================
            // 1. CONFIG PATH & ENVIRONMENT
            // ============================================================
            
            // Username Hosting Anda (PENTING: Sesuaikan jika beda)
            $userHome = '/home/tokq3391'; 
            
            // Path Python Virtual Environment
            $pythonPath = $userHome . '/virtualenv/my_ai_backend/3.9/bin/python'; 
            $scriptPath = base_path('detect.py');

            // Cek File
            if (!file_exists($pythonPath)) throw new \Exception("Python tidak ditemukan.");
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
                throw new \Exception('Format gambar salah.');
            }

            $imageName = 'detect_' . uniqid() . '.png';
            if (!Storage::disk('local')->exists('temp')) {
                Storage::disk('local')->makeDirectory('temp');
            }
            Storage::disk('local')->put('temp/' . $imageName, $decodedImage);
            $imagePath = storage_path('app/temp/' . $imageName);

            // ============================================================
            // 3. EKSEKUSI DENGAN LIMIT RESOURCE (SOLUSI ERROR ANDA)
            // ============================================================

            // KITA SET ENVIRONMENT VARIABLE SECARA MANUAL DI SINI
            // 1. export HOME: Mengatasi KeyError: 'HOME'
            // 2. OPENBLAS_NUM_THREADS=1: Mengatasi Resource temporarily unavailable
            
            $command = "export HOME={$userHome} && " .
                       "export OMP_NUM_THREADS=1 && " .
                       "export OPENBLAS_NUM_THREADS=1 && " .
                       "export MKL_NUM_THREADS=1 && " .
                       "export VECLIB_MAXIMUM_THREADS=1 && " .
                       "export NUMEXPR_NUM_THREADS=1 && " .
                       "{$pythonPath} {$scriptPath} " . escapeshellarg($imagePath) . " 2>&1";
            
            $output = shell_exec($command);

            // ============================================================
            // 4. HASIL
            // ============================================================

            Storage::disk('local')->delete('temp/' . $imageName); // Hapus gambar

            $result = json_decode($output);

            if (json_last_error() !== JSON_ERROR_NONE) {
                // Tampilkan raw output jika error, biar ketahuan kenapa
                throw new \Exception("Error Python: " . $output);
            }

            if (isset($result->error)) {
                throw new \Exception("App Error: " . $result->error);
            }

            return response()->json([
                'status' => 'success',
                'data' => $result
            ]);

        } catch (\Exception $e) {
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