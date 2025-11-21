<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Post;
use App\Models\Category;
use App\Models\User; // Menggunakan App\User sesuai struktur proyek Anda

class ImportController extends Controller
{
    /**
     * Menampilkan form untuk upload file XML.
     */
    public function showForm()
    {
        return view('admin.import.form');
    }

    /**
     * Menangani proses upload dan impor file XML dari WordPress.
     */
    public function handleImport(Request $request)
    {
        $request->validate([
            'wordpress_xml' => 'required|file|mimes:xml',
        ]);

        try {
            $xmlString = file_get_contents($request->file('wordpress_xml'));
            $xml = simplexml_load_string($xmlString, 'SimpleXMLElement', LIBXML_NOCDATA);

            if ($xml === false) {
                throw new \Exception("Gagal mem-parsing file XML. Pastikan formatnya benar.");
            }

            // Namespace yang umum digunakan dalam file ekspor WordPress
            $ns = [
                'content' => 'http://purl.org/rss/1.0/modules/content/',
                'wp' => 'http://wordpress.org/export/1.2/',
                'dc' => 'http://purl.org/dc/elements/1.1/',
            ];

            $importedCount = 0;
            $skippedCount = 0;
            $importerUserId = auth()->id() ?? User::firstOrFail()->id;

            foreach ($xml->channel->item as $item) {
                // Hanya impor item dengan tipe 'post' dan status 'publish'
                if ($item->children($ns['wp'])->post_type == 'post' && $item->children($ns['wp'])->status == 'publish') {
                    
                    $title = (string) $item->title;
                    if (empty($title)) continue;

                    // Lewati jika post dengan judul yang sama sudah ada
                    if (Post::where('title', $title)->exists()) {
                        $skippedCount++;
                        continue;
                    }
                    
                    // Cari atau buat kategori baru secara dinamis
                    $categoryName = "Berita"; // Kategori default jika tidak ditemukan
                    foreach ($item->category as $cat) {
                        if ($cat->attributes()->domain == 'category') {
                            $categoryName = (string) $cat;
                            break;
                        }
                    }
                    $category = Category::firstOrCreate(
                        ['name' => $categoryName],
                        ['slug' => Str::slug($categoryName), 'user_id' => $importerUserId]
                    );

                    // --- PERBAIKAN LOGIKA PENULIS ---
                    $authorId = $importerUserId; // Default ke ID pengguna yang melakukan impor
                    $authorName = (string) $item->children($ns['dc'])->creator;

                    // Coba buat penulis baru hanya jika namanya ada di file XML
                    if (!empty($authorName)) {
                        try {
                            $author = User::firstOrCreate(
                                ['email' => Str::slug($authorName) . '@sancaka.com'], // Buat email unik
                                ['nama_lengkap' => $authorName, 'password' => Hash::make(Str::random(10))]
                            );
                            // Jika user berhasil dibuat atau ditemukan, gunakan ID-nya
                            if ($author && $author->id) {
                                $authorId = $author->id;
                            }
                        } catch (\Exception $userException) {
                            // Jika pembuatan user gagal, catat di log dan tetap gunakan ID pengimpor
                            Log::warning("Gagal membuat user '{$authorName}' saat impor. Fallback ke importer. Error: " . $userException->getMessage());
                        }
                    }
                    
                    Post::create([
                        'title' => $title,
                        'slug' => Str::slug($title) . '-' . uniqid(),
                        'content' => (string) $item->children($ns['content'])->encoded,
                        'status' => 'published',
                        'user_id' => $authorId, // <- Menggunakan ID yang sudah divalidasi
                        'category_id' => $category->id,
                        'created_at' => date('Y-m-d H:i:s', strtotime((string) $item->children($ns['wp'])->post_date)),
                        'updated_at' => date('Y-m-d H:i:s', strtotime((string) $item->children($ns['wp'])->post_date)),
                    ]);

                    $importedCount++;
                }
            }
            
            // Mengembalikan response JSON untuk AJAX
            return response()->json([
                'message' => "Proses impor selesai. Berhasil: {$importedCount} post, Dilewati: {$skippedCount} post (karena judul sudah ada)."
            ]);

        } catch (\Exception $e) {
            Log::error('WordPress Import Failed: ' . $e->getMessage());
            // Mengembalikan response error JSON untuk AJAX
            return response()->json(['message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }
}

