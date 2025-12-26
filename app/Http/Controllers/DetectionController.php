<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Product;
use App\Models\Pesanan;

class DetectionController extends Controller
{
    public function process(Request $request)
    {
        try {
            // 1. Setup Path (Sesuaikan User Hosting Anda)
            $userHome = '/home/tokq3391'; 
            $pythonPath = $userHome . '/virtualenv/my_ai_backend/3.9/bin/python';
            $scriptPath = base_path('detect.py');

            // 2. Simpan Gambar
            $request->validate(['image' => 'required']);
            $image = $request->input('image');
            $image = str_replace('data:image/jpeg;base64,', '', $image);
            $image = str_replace(' ', '+', $image);
            $imageName = 'scan_' . uniqid() . '.jpg';
            Storage::disk('local')->put('temp/' . $imageName, base64_decode($image));
            $imagePath = storage_path('app/temp/' . $imageName);

            // 3. Eksekusi Python
            $command = "export HOME={$userHome} && {$pythonPath} {$scriptPath} " . escapeshellarg($imagePath) . " 2>&1";
            $output = shell_exec($command);
            Storage::disk('local')->delete('temp/' . $imageName); // Hapus

            $results = json_decode($output);
            if (json_last_error() !== JSON_ERROR_NONE) throw new \Exception("AI Error");

            // 4. LOGIKA INTEGRASI DATABASE & DATA BIOLOGIS
            foreach ($results as $key => $obj) {
                // Default Values
                $results[$key]->info_db = null;
                $results[$key]->bio_data = null;
                $results[$key]->display_label = $obj->label_raw;
                $results[$key]->is_known = false;

                // --- A. JIKA MANUSIA/WAJAH ---
                if ($obj->type == 'manusia' || $obj->type == 'wajah') {
                    // Simulasi Suhu Badan (Random Logis 36.1 - 37.5)
                    $temp = mt_rand(361, 375) / 10;
                    
                    // Estimasi Bayi/Dewasa berdasarkan ukuran kotak (Simplifikasi)
                    $boxWidth = $obj->box[2] - $obj->box[0];
                    $ageGroup = ($boxWidth < 150) ? "Bayi/Anak" : "Dewasa";
                    $estAge = ($ageGroup == "Bayi/Anak") ? mt_rand(1, 5) . " Th" : mt_rand(18, 50) . " Th";

                    $results[$key]->bio_data = [
                        'suhu' => $temp . "°C",
                        'usia' => $estAge,
                        'gender' => (mt_rand(0,1) ? 'L' : 'P') // Simulasi 50:50
                    ];
                    $results[$key]->display_label = "Manusia (" . $results[$key]->conf . "%)";
                }

                // --- B. CEK DATABASE (UNTUK SEMUA BENDA/BARCODE/PLAT) ---
                // Kita gunakan label_raw sebagai kunci pencarian awal
                // Jika user pernah menyimpan "cup" sebagai "Gelas Kopi", maka akan muncul "Gelas Kopi"
                
                $searchKey = $obj->label_raw; // Bisa berisi 'cup', 'car', atau kode barcode '12345'
                
                // Cek Tabel Pesanan (Resi)
                $pesanan = Pesanan::where('resi', $searchKey)->first();
                if ($pesanan) {
                    $results[$key]->display_label = "PAKET: " . $pesanan->receiver_name;
                    $results[$key]->is_known = true;
                    $results[$key]->info_db = ['tipe' => 'resi', 'data' => $pesanan];
                    continue;
                }

                // Cek Tabel Produk (Barang/Plat Nomor Manual)
                // Kita cari di kolom SKU atau Name
                $product = Product::where('sku', $searchKey)
                                  ->orWhere('name', 'LIKE', "%{$searchKey}%")
                                  ->first();

                if ($product) {
                    $results[$key]->display_label = $product->name;
                    $results[$key]->is_known = true;
                    $results[$key]->info_db = ['tipe' => 'produk', 'data' => $product];
                } else {
                    // JIKA TIDAK DIKENAL
                    $results[$key]->display_label = $obj->label_raw . " (?)";
                    $results[$key]->is_known = false; 
                }
            }

            return response()->json(['status' => 'success', 'data' => $results]);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}