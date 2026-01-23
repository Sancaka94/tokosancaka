<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post; // <--- PENTING: Import Model Post agar tidak error "Class not found"
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    /**
     * Menampilkan Halaman Utama (Landing Page / Home).
     */
    public function index()
    {
        // ==================================================================
        // 1. AMBIL DATA BERITA (BLOG)
        // ==================================================================
        // Kita mengambil 8 artikel terbaru yang statusnya 'published'.
        // Menggunakan 'with' untuk eager loading (optimasi database) kategori & penulis.

        $latestPosts = []; // Default array kosong jaga-jaga jika tabel posts belum ada

        try {
            if (class_exists(Post::class)) {
                $latestPosts = Post::with(['category', 'user']) // Ambil relasi kategori & user
                                    ->where('status', 'published') // Hanya yang sudah terbit
                                    ->latest() // Urutkan dari yang paling baru
                                    ->take(8)  // Ambil 8 item (Supaya pas: 4 kolom x 2 baris)
                                    ->get();
            }
        } catch (\Exception $e) {
            // Jika terjadi error (misal tabel belum dimigrate), biarkan kosong agar web tidak crash
            // Log::error("Gagal memuat berita: " . $e->getMessage());
        }

        // ==================================================================
        // 2. RETURN VIEW
        // ==================================================================
        // Mengirim variabel $latestPosts ke tampilan 'home.blade.php'

        return view('home', compact('latestPosts'));
    }
}
