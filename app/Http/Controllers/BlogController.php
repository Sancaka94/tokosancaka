<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\Category;

class BlogController extends Controller
{
    /**
     * Menampilkan halaman index blog (Halaman /blog).
     * Menangani: Slider Kategori, Pencarian, Headline, Top Articles, dan Paginasi.
     */
    public function blogIndex(Request $request)
    {
        // 1. QUERY DASAR
        // Kita mulai dengan query post yang statusnya published
        // Menggunakan with() untuk Eager Loading (mencegah N+1 query problem)
        $baseQuery = Post::where('status', 'published')
                         ->with(['category', 'author']);

        // 2. FILTER KATEGORI (PENTING untuk Slider)
        // Jika ada parameter ?category=slug di URL
        if ($request->filled('category')) {
            $categorySlug = $request->query('category');
            $baseQuery->whereHas('category', function($q) use ($categorySlug) {
                $q->where('slug', $categorySlug);
            });
        }

        // 3. FILTER PENCARIAN
        // Jika ada parameter ?search=keyword di URL
        if ($request->filled('search')) {
            $search = trim($request->query('search'));
            $baseQuery->where(function($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhere('content', 'like', '%' . $search . '%');
            });
        }

        // --- BAGIAN LAYOUT ---

        // A. HEADLINE (1 Artikel Utama)
        // Gunakan (clone $baseQuery) agar filter di atas tetap terbawa, tapi query aslinya tidak berubah
        $headline = (clone $baseQuery)->latest()->first();
        
        // Siapkan array untuk menampung ID agar tidak muncul ganda
        $excludeIds = [];
        if ($headline) {
            $excludeIds[] = $headline->id;
        }

        // B. TOP ARTICLES (List di samping Headline)
        // Ambil 5 artikel berikutnya, kecuali headline
        $topArticles = (clone $baseQuery)
            ->whereNotIn('id', $excludeIds)
            ->latest()
            ->limit(5)
            ->get();

        // Masukkan ID topArticles ke daftar pengecualian
        $excludeIds = array_merge($excludeIds, $topArticles->pluck('id')->toArray());

        // C. LATEST POSTS (Grid di bawah & Paginasi)
        // Ambil sisanya, kecuali Headline & Top Articles
        $latestPosts = (clone $baseQuery)
            ->whereNotIn('id', $excludeIds)
            ->latest()
            ->paginate(6) // Tampilkan 6 per halaman (kelipatan 3 agar rapi di grid)
            ->withQueryString(); // Agar parameter search/category tidak hilang saat klik page 2

        // --- BAGIAN SIDEBAR & MENU ---

        // Kategori untuk Slider (Urutkan berdasarkan jumlah post terbanyak)
        $categories = Category::withCount(['posts' => function($q) {
            $q->where('status', 'published');
        }])
        ->having('posts_count', '>', 0) // Hanya kategori yang ada isinya
        ->orderBy('posts_count', 'desc')
        ->limit(15)
        ->get();

        // Artikel Populer (Opsional, misalnya random atau berdasarkan views jika ada)
        $popularPosts = Post::where('status', 'published')
            ->inRandomOrder()
            ->limit(5)
            ->get();

        return view('blog.index', compact(
            'headline', 
            'topArticles', 
            'latestPosts', 
            'categories', 
            'popularPosts'
        ));
    }

    /**
     * Menampilkan detail satu postingan.
     */
    public function show($slug)
    {
        // Cari post berdasarkan slug
        $post = Post::with(['category', 'author'])
                    ->where('slug', $slug)
                    ->first();

        // Jika tidak ketemu atau status bukan published -> 404
        if (!$post || $post->status !== 'published') {
            abort(404);
        }

        // Data pendukung untuk Sidebar di halaman detail
        $popularPosts = Post::where('status', 'published')
            ->where('id', '!=', $post->id) // Jangan tampilkan post yang sedang dibaca
            ->inRandomOrder()
            ->limit(5)
            ->get();

        $categories = Category::withCount(['posts' => function($q) {
            $q->where('status', 'published');
        }])
        ->orderBy('posts_count', 'desc')
        ->limit(10)
        ->get();

        return view('blog.show', compact('post', 'popularPosts', 'categories'));
    }

    /**
     * Halaman Homepage Utama (Opsional, jika beda dengan blog index)
     */
    public function index(Request $request)
    {
        // Bisa diarahkan ke blogIndex atau punya tampilan beda
        return $this->blogIndex($request);
    }

    /**
     * Halaman About
     */
    public function about()
    {
        return view('about');
    }

    /**
     * Generate RSS Feed (Opsional)
     */
    public function generateFeed()
    {
        $posts = Post::where('status', 'published')
                     ->latest()
                     ->limit(50)
                     ->get();

        return view('feed', compact('posts'));
    }
}