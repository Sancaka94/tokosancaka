<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\Paginator;

class HomeController extends Controller
{
    public function index()
{
    // --- MODE DIAGNOSA ---

    // 1. Cek apakah Model Post terbaca
    if (!class_exists(\App\Models\Post::class)) {
        dd("ERROR: Model App\Models\Post tidak ditemukan. Pastikan file model ada.");
    }

    // 2. Cek Total Semua Data (Tanpa Filter)
    $totalSemua = \App\Models\Post::count();

    // 3. Cek Data dengan Status 'published'
    $totalPublished = \App\Models\Post::where('status', 'published')->count();

    // 4. Ambil 1 contoh data untuk dicheck statusnya (jika ada)
    $contohData = \App\Models\Post::first();

    // TAMPILKAN HASIL DIAGNOSA DI LAYAR
    dd([
        'STATUS_KONEKSI' => 'OK (Nyambung ke Database)',
        'TOTAL_ARTIKEL_DI_DB' => $totalSemua . ' artikel',
        'TOTAL_YANG_PUBLISHED' => $totalPublished . ' artikel',
        'CONTOH_DATA_PERTAMA' => $contohData,
        'PESAN_SAYA' => $totalPublished == 0 ? 'Masalahnya di sini! Status artikel di database bukan "published" atau data kosong.' : 'Data ada, mungkin masalah di View.'
    ]);

    }

}
