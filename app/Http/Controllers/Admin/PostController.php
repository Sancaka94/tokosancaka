<?php



namespace App\Http\Controllers\Admin;



use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use Illuminate\Support\Str;

use Illuminate\Support\Facades\Http;

use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\File;

use Illuminate\Support\Facades\Storage; // <-- PASTIKAN INI ADA



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

        // Memulai query builder

        $query = Post::with('author', 'category')->latest();



        // -- LOGIKA FILTER --

        // 1. Filter berdasarkan kata kunci (judul atau konten)

        if ($request->filled('search')) {

            $searchTerm = $request->input('search');

            $query->where(function ($q) use ($searchTerm) {

                $q->where('title', 'LIKE', "%{$searchTerm}%")

                  ->orWhere('content', 'LIKE', "%{$searchTerm}%");

            });

        }



        // 2. Filter berdasarkan Kategori

        if ($request->filled('category_id')) {

            $query->where('category_id', $request->input('category_id'));

        }



        // 3. Filter berdasarkan Penulis

        if ($request->filled('author_id')) {

            $query->where('user_id', $request->input('author_id'));

        }



        // 4. Filter berdasarkan Tanggal Publikasi

        if ($request->filled('date')) {

            $query->whereDate('created_at', $request->input('date'));

        }



        // Eksekusi query dengan paginasi

        $posts = $query->paginate(10);



        // Mengambil data untuk mengisi dropdown filter di view

        $categories = Category::all();

        $authors = User::all();



        // Kirim semua data yang diperlukan ke view

        return view('admin.posts.index', compact('posts', 'categories', 'authors'));

    }

    /**
 * Menampilkan detail satu postingan.
 */
public function show(Post $post)
{
    // Anda perlu membuat file view ini di resources/views/post-detail.blade.php
    return view('admin.posts.post-detail', compact('post'));
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

        $post->load('tags'); // Memuat relasi tags untuk post yang dipilih

        

        return view('admin.posts.edit', compact('post', 'categories', 'tags'));

    }



    /**

     * Menghasilkan konten artikel menggunakan AI (OpenAI atau Gemini).

     */

    public function generateContent(Request $request)

    {

        $request->validate([

            'title' => 'required|string|max:255',

            'model' => 'required|in:openai,gemini,none'

        ]);



        $model = $request->input('model');

        if ($model === 'none') {

            return response()->json(['content' => '']);

        }



        $title = e($request->input('title'));

        // Prompt yang detail untuk menghasilkan artikel yang SEO-friendly

         // --- PROMPT DIPERBARUI DENGAN KAIDAH SEO & PENULISAN LENGKAP ---

        $prompt = "Anda adalah penulis konten SEO ahli dan copywriter profesional berbahasa Indonesia. Tugas Anda adalah membuat artikel blog yang siap publish, sangat SEO-friendly, dan nyaman dibaca.

**Judul Artikel:** '{$title}'

**Instruksi Utama:**

Hasilkan konten HANYA dalam format HTML mentah (raw HTML), tanpa ``` html atau pembungkus markdown lainnya. Konten harus bisa langsung disalin ke editor WYSIWYG, jangan ada lambang atau icon dan jangan ada ** ** *** **** **

**Kaidah SEO & Konten:**

1.  **Riset & Kedalaman:** Bahas topik secara mendalam dan berikan informasi yang benar-benar bernilai bagi pembaca.
2.  **Kata Kunci:** Gunakan kata kunci utama <strong>{$title}</strong> secara alami 3-5 kali. Sertakan juga variasi kata kunci (LSI) yang relevan dengan topik.
3.  **Tautan Internal:** Sisipkan 2-3 tautan internal ke domain tokosancaka.com atau tokosancaka.biz.id. Gunakan anchor text yang relevan. Contoh: <a href=\"https://tokosancaka.com/cek-ongkir\">cek ongkos kirim</a>.
4.  **Panjang Artikel:** Total panjang artikel **tidak boleh lebih dari 1000 kata**.

**Kaidah Bahasa & Keterbacaan (Sesuai EYD & KBBI):**

1.  **Gaya Bahasa:** Gunakan bahasa Indonesia yang baik dan benar sesuai kaidah EYD & KBBI. Hindari kalimat yang terlalu rumit.
2.  **Paragraf Pendek:** Gunakan paragraf singkat 2-4 kalimat agar nyaman dibaca di perangkat mobile.
3.  **Struktur Jelas:** Gunakan subjudul untuk memecah konten menjadi bagian logis dan mudah diikuti.

**Persyaratan Format HTML:**

* **Paragraf:** Gunakan tag <p> untuk semua paragraf.
* **Subjudul:** Gunakan tag <h2> untuk semua subjudul.
* **Penekanan:** Gunakan <strong> untuk menekankan poin atau kata kunci penting.
* **Daftar:** Jika ada daftar, gunakan <ul> dan <li>.
* **Informasi Kontak (Wajib di Akhir):** Tambahkan blok ini persis di akhir artikel, setelah paragraf penutup.
* **Hindari:** Jangan gunakan tag <div>, <span>, atau tag HTML lain yang tidak disebutkan.
* **Tagar & Kalimat Penguat:** Sebelum paragraf penutup, buat satu kalimat tambahan yang menguatkan topik <strong>{$title}</strong>, lalu sisipkan minimal 100 keyword/tagar terkait <strong>{$title}</strong> dalam satu paragraf, dipisahkan dengan koma, agar SEO lebih optimal.

**Blok Informasi Kontak (Wajib Ditambahkan di Akhir Artikel):**

<hr>

<p><strong>Sancaka Express – Ekspedisi Cepat, Aman, dan Terpercaya di Indonesia</strong><br>
CV. Sancaka Karya Hutama<br>
Jl. Dr. Wahidin No.18A RT.22/05 Kel. Ketanggi Kec. Ngawi Kab. Ngawi, Jawa Timur 63211<br>
HP/WA: 0857-4580-8809<br>
Website: tokosancaka.biz.id , tokosancaka.com , sancaka.biz.id </p>

<p><em>Pilihan tepat untuk pengiriman barang cepat, aman, dan bergaransi ke seluruh Indonesia!</em></p>
";
        // --- AKHIR PROMPT DIPERBARUI ---



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

    private function generateWithOpenAI($prompt)

    {

        $apiKey = env('OPENAI_API_KEY');

        

        $response = Http::withHeaders([

            'Authorization' => 'Bearer ' . $apiKey,

            'Content-Type' => 'application/json',

        ])->post('https://api.openai.com/v1/chat/completions', [

            'model' => 'gpt-4o-mini',

            'messages' => [

                ['role' => 'system', 'content' => 'Anda adalah penulis konten profesional.'],

                ['role' => 'user', 'content' => $prompt]

            ],

            'temperature' => 0.7,

        ]);



        if ($response->failed()) {

            Log::error('OpenAI Error:', ['body' => $response->body()]);

            return response()->json(['error' => 'Gagal menghubungi OpenAI'], 500);

        }



        return response()->json([

            'content' => $response->json()['choices'][0]['message']['content'] ?? ''

        ]);

    }



    /**

     * Fungsi helper untuk berkomunikasi dengan API Gemini.

     */

    private function generateWithGemini($prompt)

    {

        $apiKey = env('GEMINI_API_KEY');

        if (!$apiKey) {

            Log::error('Gemini API Error: GEMINI_API_KEY is not set in the .env file.');

            return response()->json(['error' => 'Konfigurasi API Key Gemini tidak ditemukan.'], 500);

        }



        // Updated to a newer, recommended model.

        $model = 'gemini-2.5-flash-preview-05-20';

        $response = Http::retry(3, 2000)->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
            'contents' => [[
                'parts' => [['text' => $prompt]]
            ]],
            'generationConfig' => [
                'temperature' => 0.7,
            ]
        ]);


        if ($response->failed()) {

            // Handles HTTP-level errors (e.g., 400 Bad Request, 403 Forbidden, 500 Server Error)

            Log::error('Gemini API HTTP Error:', ['status' => $response->status(), 'body' => $response->body()]);

            return response()->json(['error' => 'Gagal menghubungi Gemini karena kesalahan server. Periksa log untuk detail.'], 500);

        }



        $responseData = $response->json();



        // Check for API-level errors within a successful (200 OK) response

        // This happens if the prompt is blocked, etc.

        if (empty($responseData['candidates'])) {

            Log::error('Gemini API Logic Error: Response did not contain candidates.', ['body' => $responseData]);

            $errorMessage = 'Tidak ada konten yang dihasilkan oleh AI.';

            if (isset($responseData['promptFeedback']['blockReason'])) {

                 $errorMessage = 'Permintaan diblokir karena alasan: ' . $responseData['promptFeedback']['blockReason'];

            }

            return response()->json(['error' => $errorMessage], 422); // 422 Unprocessable Entity is more appropriate here

        }

        

        // Safely access the content

        $content = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? '';



        return response()->json([

            'content' => $content

        ]);

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

            // PERBAIKAN: Menyimpan file ke storage/app/public/uploads/posts

            $imagePath = $request->file('featured_image')->store('uploads/posts', 'public');

        }



        $post = Post::create([

            'user_id' => Auth::id(),

            'category_id' => $request->category_id,

            'title' => $request->title,

            'slug' => Str::slug($request->title, '-') . '-' . uniqid(),

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

            // PERBAIKAN: Hapus file lama dari storage jika ada

            if ($post->featured_image) {

                Storage::disk('public')->delete($post->featured_image);

            }



            // PERBAIKAN: Simpan file baru ke storage

            $imagePath = $request->file('featured_image')->store('uploads/posts', 'public');

        }



        $post->update([

            'category_id' => $request->category_id,

            'title' => $request->title,

            'slug' => Str::slug($request->title, '-') . '-' . uniqid(),

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

        // PERBAIKAN: Hapus file dari storage jika ada

        if ($post->featured_image) {

            Storage::disk('public')->delete($post->featured_image);

        }



        $post->delete();

        return redirect()->route('admin.posts.index')->with('success', 'Postingan berhasil dihapus.');

    }

}

