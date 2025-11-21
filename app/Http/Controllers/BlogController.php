<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\Category;

class BlogController extends Controller
{
    /**
     * Menampilkan halaman utama (homepage) dengan beberapa artikel dan paginasi.
     */
    public function index(Request $request)
    {
        // Ambil postingan utama sebagai headline
        $headline = Post::with('category', 'author')
                        ->where('status', 'published')
                        ->latest()
                        ->first();

        // Siapkan array untuk menampung ID post yang sudah ditampilkan (headline & top articles)
        // agar tidak muncul lagi di bagian "Lainnya dari Blog Kami"
        $excludeIds = [];
        if ($headline) {
            $excludeIds[] = $headline->id;
        }

        // Ambil 4 artikel untuk bagian di samping headline
        $topArticles = Post::where('status', 'published')
                            ->whereNotIn('id', $excludeIds)
                            ->latest()
                            ->limit(8)
                            ->get();

        // Tambahkan ID dari top articles ke dalam array pengecualian
        $excludeIds = array_merge($excludeIds, $topArticles->pluck('id')->toArray());

        // --- PERBAIKAN UTAMA DI SINI ---
        // Mengambil postingan terbaru dengan PAGINASI dan mengecualikan post yang sudah ada
        $latestPosts = Post::with('category', 'author')
                            ->where('status', 'published')
                       ->whereNotIn('id', $excludeIds)
                       ->when($request->filled('search'), function ($q) use ($request) {
                           $q->where(function ($query) use ($request) {
                               $query->where('title', 'like', '%'.$request->search.'%');
                           });
                       })
                       ->latest()
                       ->paginate(5)
                       ->withQueryString(); // Anda bisa mengubah angka 5 sesuai jumlah post per halaman

        // Mengambil data untuk sidebar dan navigasi
        $categories = Category::withCount('posts')->orderBy('posts_count', 'desc')->limit(4)->get();
        $popularPosts = Post::where('status', 'published')->inRandomOrder()->limit(10)->get();

        // Mengirim semua data ke view 'home' (yang merender welcome.blade.php)
        // Variabel $latestPosts sekarang adalah objek Paginator, sehingga ->links() akan berfungsi.
        return view('home', compact('headline', 'topArticles', 'latestPosts', 'categories', 'popularPosts'));
    }

    /**
     * Menampilkan halaman khusus blog dengan paginasi.
     */
    public function blogIndex(Request $request)
    {
        // Mengambil postingan utama (headline)
        $headline = Post::with('category', 'author')
                            ->where('status', 'published')
                            ->latest()
                            ->first();
        
        // Mengambil ID headline jika ada, untuk dikecualikan dari query lain
        $headlineId = $headline?->id;

        // Data untuk 4 artikel kecil di samping headline
        $topArticles = Post::where('status', 'published')
                            ->when($headlineId, fn($query) => $query->where('id', '!=', $headlineId))
                            ->latest()
                            ->limit(9)
                            ->get();

        // Mengambil daftar postingan terbaru dengan paginasi
        $latestPosts = Post::with('category', 'author')
                           ->where('status','published')
        ->when($headlineId, fn ($q) => $q->where('id','!=',$headlineId))
        ->when($request->filled('search'), function ($q) use ($request) {
            $term = '%'.trim($request->query('search')).'%';
            $q->where(function ($qq) use ($term) {
                $qq->where('title','like',$term);
            });
        })
        ->latest()
        ->paginate(5)
        ->withQueryString();

        // Mengambil data untuk sidebar
        $popularPosts = Post::where('status', 'published')->inRandomOrder()->limit(10)->get();
        $categories = Category::withCount('posts')->orderBy('posts_count', 'desc')->limit(10)->get();

        // Mengarahkan ke view 'blog.index' dengan semua data yang diperlukan
        return view('blog.index', compact('headline', 'latestPosts', 'topArticles', 'popularPosts', 'categories'));
    }

    /**
     * Menampilkan detail satu postingan dan mengirimkan data untuk sidebar.
     */
    public function show($slug)
    {
        // 1. Ambil postingan yang sedang dibuka berdasarkan slug
        $post = Post::with('category', 'author')->where('slug', $slug)->where('status', 'published')->firstOrFail();
        
        // ======================================================================
        // == PERBAIKAN: Menambahkan data untuk sidebar di halaman detail ==
        // ======================================================================
        // 2. Ambil data untuk sidebar (Populer & Kategori)
        $popularPosts = Post::where('status', 'published')
                            ->where('id', '!=', $post->id) // Jangan tampilkan post yang sedang dibaca
                            ->inRandomOrder()
                            ->limit(5)
                            ->get();

        $categories = Category::withCount('posts')->orderBy('posts_count', 'desc')->limit(7)->get();

        // 3. Kirim semua data yang dibutuhkan ke view 'blog.show'
        return view('blog.show', compact('post', 'popularPosts', 'categories'));
    }
    
    /**
     * Menghasilkan halaman arsip blog dalam bentuk tabel.
     */
    public function generateFeed()
    {
        // Ambil 50 postingan terbaru untuk ditampilkan di halaman arsip
        $posts = Post::with('author', 'category')->latest()->limit(100000)->get();

        // Kembalikan view 'feed' sebagai halaman HTML biasa
        return view('feed', compact('posts'));
    }
}
