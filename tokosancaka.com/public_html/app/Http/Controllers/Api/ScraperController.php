<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Post;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ScraperController extends Controller
{
    public function store(Request $request)
    {
        // 1. LOG AWAL (CCTV PINTU MASUK)
        Log::emergency("🔔 [START] Request masuk dari: " . $request->ip());

        // --- INI BAGIAN PENTING: KITA INTIP ISI DATANYA ---
        // Kita catat semua data mentah yang dikirim JS ke dalam Log
        // Log::info("📦 DATA MENTAH DITERIMA:", $request->all()); 
        // --------------------------------------------------

        try {
            // 2. SECURITY CHECK
            $secretKey = 'SancakaRahasia123';
            if ($request->header('X-API-KEY') !== $secretKey) {
                Log::warning("⛔ [AUTH] Kunci Salah!");
                throw new \Exception("Akses Ditolak: API Key Salah");
            }

            // 3. VALIDASI INPUT
            // Gunakan null coalescing operator (??) untuk mencegah error jika key tidak ada
            $judulBersih = trim($request->input('title') ?? '');
            $urlBersih   = trim($request->input('url') ?? '');

            // Debugging Khusus Validasi
            if (empty($judulBersih)) {
                Log::error("❌ Judul Kosong! Data yang terbaca: " . json_encode($request->all()));
            }

            if (empty($judulBersih) || empty($urlBersih) || $judulBersih === 'Gagal' || $judulBersih === 'Error') {
                Log::warning("⚠️ [VALIDASI] Gagal. Judul: '$judulBersih', URL: '$urlBersih'");
                return response()->json(['status' => 'error', 'message' => 'Data Invalid/Kosong'], 400);
            }

            // 4. CEK DUPLIKAT
            $existingPost = Post::where('title', $judulBersih)->first();
            if ($existingPost) {
                Log::info("⏭️ [SKIP] Duplikat: $judulBersih");
                return response()->json(['status' => 'skipped', 'message' => 'Sudah ada.'], 200); 
            }

            // 5. SIMPAN
            $post = new Post();
            $post->user_id      = 4;
            $post->category_id  = 1;
            $post->title        = $judulBersih;
            $post->slug         = Str::slug($judulBersih) . '-' . rand(100, 999);
            $post->content      = $request->input('content') ?? '';
            $post->original_url = $urlBersih;
            $post->status       = 'published';
            $post->save();

            Log::info("✅ [SUCCESS] Tersimpan ID: {$post->id}");

            return response()->json(['status' => 'success', 'message' => 'Berhasil'], 200);

        } catch (\Exception $e) {
            Log::error("❌ [ERROR SYSTEM] " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}