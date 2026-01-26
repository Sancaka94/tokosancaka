<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Str;
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

        // --- Ambil Kategori UNTUK SEMUA VIEW (Pencarian maupun Normal) ---
    $categories = Category::withCount(['posts' => function($q) {
        $q->where('status', 'published');
    }])->having('posts_count', '>', 0)->orderBy('posts_count', 'desc')->limit(15)->get();

    if ($request->filled('search')) {
        $search = trim($request->query('search'));
        $baseQuery->where(function($q) use ($search) {
            $q->where('title', 'like', '%' . $search . '%')
              ->orWhere('content', 'like', '%' . $search . '%');
        });

        $latestPosts = $baseQuery->latest()->paginate(12)->withQueryString();

        // Kirim $categories juga di sini agar partial tidak error
        return view('blog.search', compact('latestPosts', 'categories'));
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
            ->limit(20)
            ->get();

        // Masukkan ID topArticles ke daftar pengecualian
        $excludeIds = array_merge($excludeIds, $topArticles->pluck('id')->toArray());

        // C. LATEST POSTS (Grid di bawah & Paginasi)
        // Ambil sisanya, kecuali Headline & Top Articles
        $latestPosts = (clone $baseQuery)
            ->whereNotIn('id', $excludeIds)
            ->latest()
            ->paginate(17) // Tampilkan 6 per halaman (kelipatan 3 agar rapi di grid)
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

    public function index()
{
    // Jangan panggil $this->blogIndex($request);

    // Tampilkan view landing page khusus (Anda harus buat file view-nya dulu)
    return view('home'); // Pastikan ada file resources/views/welcome.blade.php
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
                     //->limit(50)
                     //->get();
                     ->paginate(10);

        return view('feed', compact('posts'));
    }

    public function showCategory($slug, Request $request) {
    // 1. Ambil Kategori
    $category = Category::where('slug', $slug)->firstOrFail();

    // 2. Ambil Postingan (10 per halaman: 4 Grid + 6 List)
    $posts = $category->posts()->latest()->paginate(10);

    // 3. LOGIKA AJAX (PENTING!)
    // Jika request datang dari klik pagination, hanya kirim file partial (Langkah 1)
    if ($request->ajax()) {
        return view('blog.partials.content_grid', compact('posts', 'category'))->render();
    }

    // 4. Jika load biasa, kirim halaman utama (Langkah 3)
    return view('blog.category', compact('category', 'posts'));
}

/**
     * Menampilkan daftar semua kategori.
     * Mengatasi error: Call to undefined method ...::categories()
     */
    public function categories()
    {
        $categories = Category::withCount(['posts' => function($q) {
            $q->where('status', 'published');
        }])
        ->having('posts_count', '>', 0)
        ->orderBy('posts_count', 'desc')
        ->get();

        return view('blog.categories_list', compact('categories'));
    }

}
