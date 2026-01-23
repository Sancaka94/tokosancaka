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
        $latestPosts = [];

        try {
            if (class_exists(Post::class)) {
                // Gunakan paginate() bukan take()->get()
                $latestPosts = Post::with(['category', 'user'])
                                    ->where('status', 'published') // Pastikan data di DB statusnya 'published'
                                    ->latest()
                                    ->paginate(8); // Otomatis membagi 8 artikel per halaman
            }
        } catch (\Exception $e) {
            // Log::error($e->getMessage());
        }

        return view('home', compact('latestPosts'));
    }
}
