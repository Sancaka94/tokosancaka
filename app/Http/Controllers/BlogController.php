<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Str;
use App\Models\Post;
use App\Models\Category;

class BlogController extends Controller
{
    public function blogIndex(Request $request)
{
    // 1. QUERY DASAR
    $baseQuery = Post::where('status', 'published')->with(['category', 'author']);

    // 2. FILTER KATEGORI (Jika ada)
    if ($request->filled('category')) {
        $categorySlug = $request->query('category');
        $baseQuery->whereHas('category', function($q) use ($categorySlug) {
            $q->where('slug', $categorySlug);
        });
    }

    // 3. AMBIL KATEGORI SIDEBAR (Pindahkan ke atas agar bisa dipakai Search & Normal)
    // Ini dipindah ke sini agar tidak perlu ditulis ulang di dalam blok if($search)
    $categories = Category::withCount(['posts' => function($q) {
        $q->where('status', 'published');
    }])->having('posts_count', '>', 0)->orderBy('posts_count', 'desc')->limit(15)->get();

    // 4. LOGIKA PENCARIAN
    if ($request->filled('search')) {
        $search = trim($request->query('search'));
        $baseQuery->where(function($q) use ($search) {
            $q->where('title', 'like', '%' . $search . '%')
              ->orWhere('content', 'like', '%' . $search . '%');
        });

        $latestPosts = $baseQuery->latest()->paginate(12)->withQueryString();

        // Return view khusus search
        return view('blog.search', compact('latestPosts', 'categories'));
    }

    // 5. LOGIKA TAMPILAN NORMAL (Headline, Top, Latest)

    // A. Headline
    $headline = (clone $baseQuery)->latest()->first();
    $excludeIds = $headline ? [$headline->id] : [];

    // B. Top Articles (5 artikel)
    $topArticles = (clone $baseQuery)->whereNotIn('id', $excludeIds)->latest()->limit(5)->get(); // Saya ubah limit jadi 5 (biasanya Top tidak sampai 20)
    $excludeIds = array_merge($excludeIds, $topArticles->pluck('id')->toArray());

    // C. Latest Posts (Sisanya)
    $latestPosts = (clone $baseQuery)
        ->whereNotIn('id', $excludeIds)
        ->latest()
        ->paginate(17)
        ->withQueryString();

    // D. Popular Posts (Opsional)
    $popularPosts = Post::where('status', 'published')->inRandomOrder()->limit(5)->get();

    return view('blog.index', compact(
        'headline', 'topArticles', 'latestPosts', 'categories', 'popularPosts'
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
