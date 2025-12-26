<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class DetectionController extends Controller
{
    public function process(Request $request)
    {
        // Nama file sementara
        $imageName = '';

        try {
            // ============================================================
            // 1. KONFIGURASI PATH (SESUAIKAN INI DENGAN SERVER ANDA)
            // ============================================================
            
            // Path Python Virtual Environment (Dari hasil 'which python' Anda)
            $pythonPath = '/home/tokq3391/virtualenv/my_ai_backend/3.9/bin/python'; 

            // Path Script Python (detect.py ada di root folder project/public_html)
            $scriptPath = base_path('detect.py');

            // Cek apakah mesin Python valid
            if (!file_exists($pythonPath)) {
                throw new \Exception("Path Python salah atau tidak ditemukan di: " . $pythonPath);
            }

            // Cek apakah script detect.py ada
            if (!file_exists($scriptPath)) {
                throw new \Exception("File detect.py tidak ditemukan di: " . $scriptPath);
            }

            // ============================================================
            // 2. PROSES GAMBAR (BASE64 KE FILE)
            // ============================================================
            
            $request->validate([
                'image' => 'required|string',
            ]);

            $image = $request->input('image');
            
            // Bersihkan header data URI (data:image/png;base64,...)
            if (preg_match('/^data:image\/(\w+);base64,/', $image, $type)) {
                $image = substr($image, strpos($image, ',') + 1);
                $type = strtolower($type[1]); // jpg, png, gif

                if (!in_array($type, ['jpg', 'jpeg', 'gif', 'png'])) {
                    throw new \Exception('Tipe gambar tidak valid. Hanya JPG, PNG, GIF.');
                }

                $image = str_replace(' ', '+', $image);
                $decodedImage = base64_decode($image);

                if ($decodedImage === false) {
                    throw new \Exception('Gagal mendekode gambar base64.');
                }
            } else {
                throw new \Exception('Format data gambar tidak valid (Bukan Base64).');
            }

            // Buat nama file unik
            $imageName = 'detect_' . uniqid() . '.png';
            
            // Pastikan folder temp ada di storage/app/temp
            if (!Storage::disk('local')->exists('temp')) {
                Storage::disk('local')->makeDirectory('temp');
            }
            
            // Simpan gambar
            Storage::disk('local')->put('temp/' . $imageName, $decodedImage);
            
            // Ambil Absolute Path untuk dikirim ke Python
            $imagePath = storage_path('app/temp/' . $imageName);

            // ============================================================
            // 3. EKSEKUSI PYTHON
            // ============================================================

            // Susun Command: [Python] [Script] [Gambar]
            // escapeshellarg() wajib ada untuk keamanan (mencegah hack via nama file)
            // 2>&1 berguna untuk menangkap pesan error Python ke variabel $output
            $command = "{$pythonPath} {$scriptPath} " . escapeshellarg($imagePath) . " 2>&1";
            
            // Jalankan
            $output = shell_exec($command);

            // ============================================================
            // 4. PARSING HASIL
            // ============================================================

            // Decode JSON dari output Python
            $result = json_decode($output);

            // Cek apakah output valid JSON
            if (json_last_error() !== JSON_ERROR_NONE) {
                // Jika error, kembalikan pesan raw output untuk debugging
                // Ini sangat membantu jika Python error (misal: ModuleNotFoundError)
                throw new \Exception("Output Python bukan JSON valid. Raw Output: " . $output);
            }

            // Cek jika Script Python mengirimkan sinyal error (key "error" di JSON)
            if (isset($result->error)) {
                throw new \Exception("Python Error: " . $result->error);
            }

            // HAPUS GAMBAR SEMENTARA (CLEANUP)
            if (Storage::disk('local')->exists('temp/' . $imageName)) {
                Storage::disk('local')->delete('temp/' . $imageName);
            }
            

            // SUKSES
            return response()->json([
                'status' => 'success',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            // BERSIHKAN GAMBAR JIKA TERJADI ERROR
            if (!empty($imageName) && Storage::disk('local')->exists('temp/' . $imageName)) {
                Storage::disk('local')->delete('temp/' . $imageName);
            }

            // KEMBALIKAN PESAN ERROR KE FRONTEND
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}