<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\Category;

class ApiBlogController extends Controller
{
    public function getPosts(Request $request)
    {
        try {
            // 1. Ambil Kategori untuk Menu Horizontal di Expo
            // PERBAIKAN: select() dipindah ke atas SEBELUM withCount()
            $categories = Category::select('id', 'name', 'slug')
              ->withCount(['posts' => function($q) {
                  $q->where('status', 'published');
              }])
              ->having('posts_count', '>', 0)
              ->orderBy('posts_count', 'desc')
              ->get();

            // 2. Query Postingan
            $query = Post::where('status', 'published')->with('category');

            // Filter Pencarian
            if ($request->filled('search')) {
                $search = trim($request->search);
                $query->where('title', 'like', "%{$search}%");
            }

            // Filter Kategori
            if ($request->filled('category') && $request->category !== 'all') {
                $categorySlug = $request->category;
                $query->whereHas('category', function($q) use ($categorySlug) {
                    $q->where('slug', $categorySlug);
                });
            }

            $posts = $query->latest()->get();

            // 3. Pisahkan Headline (postingan terbaru) dari List biasa
            $headline = null;
            if ($posts->count() > 0 && !$request->filled('search') && (!$request->filled('category') || $request->category === 'all')) {
                $headlineModel = $posts->shift(); // Ambil yang paling atas
                $headline = [
                    'id' => $headlineModel->id,
                    'title' => $headlineModel->title,
                    'slug' => $headlineModel->slug,
                    'featured_image' => $headlineModel->featured_image,
                ];
            }

            // 4. Format List Postingan
            $formattedPosts = $posts->map(function($post) {
                return [
                    'id' => $post->id,
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'excerpt' => \Illuminate\Support\Str::limit(strip_tags($post->content), 80),
                    'featured_image' => $post->featured_image,
                    'category_name' => $post->category ? $post->category->name : 'Berita',
                    'date' => $post->created_at->format('d M Y'),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'headline' => $headline,
                    'categories' => $categories,
                    'posts' => $formattedPosts
                ]
            ]);

        } catch (\Exception $e) {
            // PERBAIKAN: Menampilkan error asli dari Laravel untuk mempermudah debug
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem.',
                'debug_error' => $e->getMessage(), // Hapus baris ini nanti jika sudah masuk tahap Production
                'line' => $e->getLine()
            ], 500);
        }
    }

    public function getPostDetail($slug)
{
    try {
        // Cari postingan berdasarkan slug
        $post = Post::where('slug', $slug)
            ->where('status', 'published')
            ->with('category:id,name') // Ambil relasi kategori
            ->first();

        // Jika tidak ketemu
        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'Artikel tidak ditemukan.'
            ], 404);
        }

        // Jika ketemu, format datanya agar sesuai dengan React Native
        $formattedPost = [
            'id' => $post->id,
            'title' => $post->title,
            'content' => $post->content, // Ini berisi teks HTML utuh
            'featured_image' => $post->featured_image,
            'category_name' => $post->category ? $post->category->name : 'Berita',
            'date' => $post->created_at->format('d M Y'),
        ];

        return response()->json([
            'success' => true,
            'data' => $formattedPost
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Terjadi kesalahan sistem.'
        ], 500);
    }
}
}
