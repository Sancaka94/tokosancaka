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
        // 1. VALIDASI DATA MASUK
        if (!$request->url || !$request->title) {
            return response()->json(['status' => 'error', 'message' => 'Data incomplete'], 400);
        }

        // 2. CEK DUPLIKAT BERDASARKAN JUDUL (FITUR BARU)
        // Kita cek apakah judul persis sama sudah ada di database?
        $existingPost = Post::where('title', $request->title)->first();

        if ($existingPost) {
            // JIKA ADA: Catat di log dan kembalikan respon 'skipped'
            Log::warning("⛔ [Scraper] DITOLAK: Judul Duplikat - '{$request->title}' sudah ada (ID: {$existingPost->id})");
            
            return response()->json([
                'status' => 'skipped', 
                'message' => 'Artikel ditolak karena judul sudah ada di database.',
                'data' => $request->title
            ], 200); // Tetap return 200 OK agar Scraper di browser tidak menganggap ini error koneksi
        }

        // 3. JIKA JUDUL AMAN (BELUM ADA), LANJUT SIMPAN
        try {
            Log::info('📥 [Scraper] Memproses Artikel Baru: ' . $request->title);

            // Data Default
            $defaultUserId = 4; 
            $defaultCategoryId = 1; 

            // Simpan Data Baru
            // Kita gunakan updateOrCreate berdasarkan URL untuk jaga-jaga
            // Tapi karena judul sudah dicek di atas, ini kemungkinan besar akan create baru
            $post = Post::updateOrCreate(
                [
                    'original_url' => $request->url 
                ],
                [
                    'user_id'      => $defaultUserId,
                    'category_id'  => $defaultCategoryId,
                    'title'        => $request->title,
                    'slug'         => Str::slug($request->title) . '-' . rand(1000, 9999),
                    'content'      => $request->content,
                    'status'       => 'published',
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]
            );

            Log::info("✅ [Scraper] BERHASIL DISIMPAN: ID {$post->id} - {$post->title}");

            return response()->json([
                'status' => 'success',
                'message' => 'Artikel berhasil masuk database!',
                'data' => $post->title
            ]);

        } catch (\Exception $e) {
            Log::error("❌ [Scraper] ERROR SYSTEM: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}