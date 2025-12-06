<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\Category;

class BlogController extends Controller
{
    /**
     * Menampilkan halaman utama (homepage) dengan artikel.
     */
    public function index(Request $request)
    {
        // Gunakan scope dan withRelationships untuk kode yang lebih ringkas
        $baseQuery = Post::published()->withRelationships();

        // 1. Ambil postingan utama sebagai headline
        $headline = $baseQuery->latest()->first();
        $headlineId = $headline?->id;

        // Siapkan array untuk menampung ID post yang sudah ditampilkan
        $excludeIds = $headlineId ? [$headlineId] : [];

        // 2. Ambil 8 artikel untuk bagian di samping headline (eksklusif headline)
        $topArticles = $baseQuery
            ->when($headlineId, fn($q) => $q->where('id', '!=', $headlineId))
            ->latest()
            ->limit(8)
            ->get();
        
        // Tambahkan ID dari top articles ke dalam array pengecualian
        $excludeIds = array_merge($excludeIds, $topArticles->pluck('id')->toArray());

        // 3. Mengambil postingan terbaru dengan PAGINASI dan mengecualikan post yang sudah ada
        $latestPosts = $baseQuery
            ->whereNotIn('id', $excludeIds)
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.trim($request->query('search')).'%';
                $q->where('title', 'like', $term);
            })
            ->latest()
            ->paginate(5)
            ->withQueryString();

        // 4. Mengambil data untuk sidebar dan navigasi
        $categories = Category::withCount('posts')->orderBy('posts_count', 'desc')->limit(4)->get();
        $popularPosts = Post::published()->inRandomOrder()->limit(10)->get();

        return view('home', compact('headline', 'topArticles', 'latestPosts', 'categories', 'popularPosts'));
    }

    /**
     * Menampilkan halaman blog index khusus dengan paginasi.
     */
    public function blogIndex(Request $request)
    {
        $baseQuery = Post::published()->withRelationships();

        // 1. Mengambil postingan utama (headline)
        $headline = $baseQuery->latest()->first();
        $headlineId = $headline?->id;

        // 2. Data untuk 4 artikel kecil di samping headline (limit 9 karena di view mungkin dibagi 2 baris)
        $topArticles = $baseQuery
            ->when($headlineId, fn($query) => $query->where('id', '!=', $headlineId))
            ->latest()
            ->limit(9)
            ->get();
        
        // Buat daftar ID yang dikecualikan (Headline + Top Articles)
        $excludeIds = array_merge($topArticles->pluck('id')->toArray(), $headlineId ? [$headlineId] : []);

        // 3. Mengambil daftar postingan terbaru dengan paginasi (eksklusif artikel di atas)
        $latestPosts = $baseQuery
            ->whereNotIn('id', $excludeIds)
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.trim($request->query('search')).'%';
                $q->where('title', 'like', $term);
            })
            ->latest()
            ->paginate(5)
            ->withQueryString();

        // 4. Mengambil data untuk sidebar
        $popularPosts = Post::published()->inRandomOrder()->limit(10)->get();
        $categories = Category::withCount('posts')->orderBy('posts_count', 'desc')->limit(10)->get();

        return view('blog.index', compact('headline', 'latestPosts', 'topArticles', 'popularPosts', 'categories'));
    }

    /**
     * Menampilkan detail satu postingan menggunakan Route Model Binding.
     * @param Post $post (Otomatis mencari berdasarkan slug jika Post model disiapkan)
     */
    public function show(Post $post)
    {
        // Pastikan post berstatus 'published'
        if ($post->status !== 'published') {
            abort(404);
        }
        
        // Lazy load relationships jika belum terload (tergantung setup route binding)
        $post->load('category', 'author');

        // Ambil data untuk sidebar (Populer & Kategori)
        $popularPosts = Post::published()
            ->where('id', '!=', $post->id) // Jangan tampilkan post yang sedang dibaca
            ->inRandomOrder()
            ->limit(5)
            ->get();

        $categories = Category::withCount('posts')->orderBy('posts_count', 'desc')->limit(7)->get();

        return view('blog.show', compact('post', 'popularPosts', 'categories'));
    }

    /**
     * Menghasilkan halaman arsip blog/feed.
     */
    public function generateFeed()
    {
        // Batasi jumlah post untuk efisiensi
        $posts = Post::published()->withRelationships()->latest()->limit(500)->get();

        return view('feed', compact('posts'));
    }

    /**
     * METODE BARU: Menangani error "Call to undefined method BlogController::about()"
     * Pastikan metode ini dipanggil dari route yang benar.
     */
    public function about()
    {
        // Contoh sederhana: Mengembalikan view halaman about
        return view('about');
        
        
    }
}