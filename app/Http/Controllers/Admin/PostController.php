<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse; // PENINGKATAN: Menggunakan JsonResponse untuk return type yang jelas
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

use App\Models\Post;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;

class PostController extends Controller
{
    /**
     * Menampilkan daftar semua post dengan fitur pencarian dan filter.
     */
    public function index(Request $request)
    {
        $query = Post::with('author', 'category')->latest();

        $query->when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = $request->input('search');
            $q->where(function ($subQuery) use ($searchTerm) {
                $subQuery->where('title', 'LIKE', "%{$searchTerm}%")
                         ->orWhere('content', 'LIKE', "%{$searchTerm}%");
            });
        });

        $query->when($request->filled('category_id'), function ($q) use ($request) {
            $q->where('category_id', $request->input('category_id'));
        });

        $query->when($request->filled('author_id'), function ($q) use ($request) {
            $q->where('user_id', $request->input('author_id'));
        });

        $query->when($request->filled('date'), function ($q) use ($request) {
            $q->whereDate('created_at', $request->input('date'));
        });

        $posts = $query->paginate(10);
        $categories = Category::all();
        $authors = User::all();

        return view('admin.posts.index', compact('posts', 'categories', 'authors'));
    }

    /**
     * Menampilkan form untuk membuat post baru.
     */
    public function create()
    {
        $categories = Category::all();
        $tags = Tag::all();
        return view('admin.posts.create', compact('categories', 'tags'));
    }
    
    /**
     * Menampilkan form untuk mengedit post.
     */
    public function edit(Post $post)
    {
        $categories = Category::all();
        $tags = Tag::all();
        $post->load('tags');
        
        return view('admin.posts.edit', compact('post', 'categories', 'tags'));
    }

    /**
     * Menampilkan detail satu post.
     */
    public function show(Post $post)
    {
        return view('admin.posts.show', compact('post'));
    }

    /**
     * Menghasilkan konten artikel menggunakan AI (OpenAI atau Gemini).
     */
    public function generateContent(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'model' => 'required|in:openai,gemini,none'
        ]);

        $model = $request->input('model');
        if ($model === 'none') {
            return response()->json(['content' => '']);
        }

        // PENINGKATAN: Mengirim judul mentah (tanpa html escaping) ke API lebih tepat.
        $title = $request->input('title');
        
        // PENINGKATAN: Menggunakan sintaks Heredoc untuk prompt yang lebih bersih dan mudah dibaca.
        $prompt = <<<PROMPT
Anda adalah penulis konten SEO ahli dan copywriter profesional berbahasa Indonesia. Tugas Anda adalah membuat artikel blog yang siap publish, sangat SEO-friendly, dan nyaman dibaca.

**Judul Artikel:** '{$title}'

**Instruksi Utama:**
Hasilkan konten HANYA dalam format HTML mentah (raw HTML), tanpa ```html atau pembungkus markdown lainnya. Konten harus bisa langsung disalin ke editor WYSIWYG.

**Kaidah SEO & Konten:**
1.  **Riset & Kedalaman:** Bahas topik secara mendalam dan berikan informasi yang benar-benar bernilai bagi pembaca.
2.  **Kata Kunci:** Gunakan kata kunci utama `<strong>{$title}</strong>` secara alami 3-5 kali. Sertakan juga variasi kata kunci (LSI) yang relevan dengan topik.
3.  **Tautan Internal:** Secara strategis, sisipkan 2-3 tautan internal ke domain `tokosancaka.com` atau `tokosancaka.biz.id`. Gunakan anchor text yang relevan dengan halaman tujuan. Contoh: `<a href="https://tokosancaka.com/cek-ongkir">cek ongkos kirim</a>`.
4.  **Panjang Artikel:** Total panjang artikel **tidak boleh lebih dari 1000 kata**.

**Kaidah Bahasa & Keterbacaan (Sesuai EYD & KBBI):**
1.  **Gaya Bahasa:** Gunakan bahasa Indonesia yang baik dan benar sesuai kaidah Ejaan Yang Disempurnakan (EYD) dan Kamus Besar Bahasa Indonesia (KBBI). Hindari kalimat yang terlalu rumit.
2.  **Paragraf Pendek:** Buat paragraf yang singkat, idealnya terdiri dari 2-4 kalimat, agar mudah dibaca di perangkat mobile.
3.  **Struktur Jelas:** Gunakan subjudul untuk memecah konten menjadi bagian-bagian yang logis dan mudah diikuti.

**Persyaratan Format HTML:**
* **Paragraf:** Gunakan tag `<p>` untuk semua paragraf.
* **Subjudul:** Gunakan tag `<h2>` untuk semua subjudul.
* **Penekanan:** Gunakan `<strong>` untuk menekankan poin atau kata kunci penting.
* **Daftar:** Jika ada daftar, gunakan `<ul>` dan `<li>`.
* **Informasi Kontak (Wajib di Akhir):** Tambahkan blok ini persis di akhir artikel, setelah paragraf penutup.

<hr>
<p><strong>Sancaka Express – Ekspedisi Cepat, Aman, dan Terpercaya di Indonesia</strong><br>
CV. Sancaka Karya Hutama<br>
Jl. Dr. Wahidin No.18A RT.22/05 Kel. Ketanggi Kec. Ngawi Kab. Ngawi, Jawa Timur 63211<br>
HP/WA: 0857-4580-8809<br>
Website: tokosancaka.biz.id , tokosancaka.com , sancaka.biz.id </p>
<p><em>Pilihan tepat untuk pengiriman barang cepat, aman, dan bergaransi ke seluruh Indonesia!</em></p>
PROMPT;

        if ($model === 'openai') {
            return $this->generateWithOpenAI($prompt);
        } elseif ($model === 'gemini') {
            return $this->generateWithGemini($prompt);
        }

        return response()->json(['error' => 'Model AI tidak valid.'], 400);
    }

    /**
     * Fungsi helper untuk berkomunikasi dengan API OpenAI.
     */
    private function generateWithOpenAI(string $prompt): JsonResponse
    {
        // PENINGKATAN: Mengambil konfigurasi dari config/services.php, bukan langsung dari env().
        $apiKey = config('services.openai.key');
        if (!$apiKey) {
            Log::error('OpenAI API Error: OPENAI_API_KEY is not set.');
            return response()->json(['error' => 'Konfigurasi API Key OpenAI tidak ditemukan.'], 500);
        }
        
       $response = Http::withHeaders([
    'Authorization' => 'Bearer ' . $apiKey,
    'Content-Type' => 'application/json',
])->post(
    'https://api.openai.com/v1/chat/completions', // ✅ bersih, tanpa markdown
    [
        'model' => config('services.openai.model', 'gpt-4o-mini'),
        'messages' => [
            ['role' => 'user', 'content' => $prompt]
        ],
        'temperature' => 0.7,
    ]
);


        if ($response->failed()) {
            Log::error('OpenAI Error:', ['body' => $response->body()]);
            return response()->json(['error' => 'Gagal menghubungi OpenAI. Periksa log untuk detail.'], 500);
        }

        return response()->json([
            'content' => $response->json('choices.0.message.content', '')
        ]);
    }

    /**
     * Fungsi helper untuk berkomunikasi dengan API Gemini.
     */
    private function generateWithGemini(string $prompt): JsonResponse
    {
        // PENINGKATAN: Mengambil konfigurasi dari config/services.php
        $apiKey = config('services.gemini.key');
        if (!$apiKey) {
            Log::error('Gemini API Error: GEMINI_API_KEY is not set.');
            return response()->json(['error' => 'Konfigurasi API Key Gemini tidak ditemukan.'], 500);
        }

        $model = config('services.gemini.model', 'gemini-1.5-flash-latest');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
        $response = Http::post($url, [
            'contents' => [[
                'parts' => [['text' => $prompt]]
            ]],
            'generationConfig' => [
                'temperature' => 0.7,
            ]
        ]);

        if ($response->failed()) {
            Log::error('Gemini API HTTP Error:', ['status' => $response->status(), 'body' => $response->body()]);
            return response()->json(['error' => 'Gagal menghubungi Gemini. Periksa log untuk detail.'], 500);
        }

        $responseData = $response->json();
        
        if (empty($responseData['candidates'])) {
            Log::error('Gemini API Logic Error: Response did not contain candidates.', ['body' => $responseData]);
            $errorMessage = 'Tidak ada konten yang dihasilkan oleh AI.';
            if (isset($responseData['promptFeedback']['blockReason'])) {
                 $errorMessage = 'Permintaan diblokir karena alasan: ' . $responseData['promptFeedback']['blockReason'];
            }
            return response()->json(['error' => $errorMessage], 422);
        }
        
        $content = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? '';

        return response()->json(['content' => $content]);
    }

    /**
     * Menyimpan post baru ke dalam database.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255|unique:posts,title',
            'content' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'tags' => 'nullable|array'
        ]);

        $imagePath = null;
        if ($request->hasFile('featured_image')) {
            $imagePath = $request->file('featured_image')->store('uploads/posts', 'public');
        }

        $post = Post::create([
            'user_id' => Auth::id(),
            'category_id' => $request->category_id,
            'title' => $request->title,
            'slug' => $this->generateUniqueSlug($request->title),
            'content' => $request->content,
            'featured_image' => $imagePath,
            'status' => 'published',
        ]);

        if ($request->has('tags')) {
            $post->tags()->attach($request->tags);
        }

        return redirect()->route('admin.posts.index')->with('success', 'Postingan berhasil ditambahkan.');
    }

    /**
     * Memperbarui post yang sudah ada di database.
     */
    public function update(Request $request, Post $post)
    {
        $request->validate([
            'title' => 'required|string|max:255|unique:posts,title,' . $post->id,
            'content' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'tags' => 'nullable|array'
        ]);

        $imagePath = $post->featured_image;

        if ($request->hasFile('featured_image')) {
            if ($post->featured_image) {
                Storage::disk('public')->delete($post->featured_image);
            }
            $imagePath = $request->file('featured_image')->store('uploads/posts', 'public');
        }

        $post->update([
            'category_id' => $request->category_id,
            'title' => $request->title,
            'slug' => $this->generateUniqueSlug($request->title, $post->id),
            'content' => $request->content,
            'featured_image' => $imagePath,
        ]);

        $post->tags()->sync($request->tags ?? []);

        return redirect()->route('admin.posts.index')->with('success', 'Postingan berhasil diperbarui.');
    }

    /**
     * Menghapus post dari database.
     */
    public function destroy(Post $post)
    {
        if ($post->featured_image) {
            Storage::disk('public')->delete($post->featured_image);
        }

        $post->delete();
        return redirect()->route('admin.posts.index')->with('success', 'Postingan berhasil dihapus.');
    }

    /**
     * Fungsi helper untuk membuat slug yang unik.
     */
    private function generateUniqueSlug(string $title, int $excludeId = null): string
    {
        $slug = Str::slug($title, '-');
        $originalSlug = $slug;
        $counter = 1;

        $query = Post::where('slug', $slug);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        while ($query->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
            $query = Post::where('slug', $slug);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
        }

        return $slug;
    }
}